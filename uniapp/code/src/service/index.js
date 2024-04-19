import Vue from 'vue'
import needCatchList from './modules'
import http from './request.js'

let that_ = Vue.prototype;

/**
 * @description 请求前统一拦截
 * @param {String} name 请求地址值key，具体看配置文件@/api/config.js
 * @param {Object} query 请求参数列表
 * @param {String} modifyObj 修改请求配置
 * @param {String} type 请求类型：get | post,默认post
 * @return {Promise} 返回是一个promise
 */
export default function requestBefore(name, query = {}, modifyObj = {}, type) {
	let catchObj = that_.$deepClone(needCatchList[name] || {});
	if (modifyObj) {
		for (var i in modifyObj) {
			if (typeof modifyObj[i] == 'object' && typeof catchObj[i] == 'object') {
				Object.assign(catchObj[i], modifyObj[i])
			} else {
				catchObj[i] = modifyObj[i];
			}
		}
	}
	if (!catchObj.url) { //请求地址不能为空
		return new Promise((resolve, reject) => {
			that_.$toast('请求地址不能为空');
			console.error('[fatal error] 请求地址不能为空，『' + name + '』不存在请求表中');
			reject({
				code: -1,
				msg: '请求地址不能为空'
			});
		})
	}
	if (catchObj.catchName && !catchObj.update && catchObj.abort) { //说明需要存缓存，先去去缓存，强制更新他人的  必须请求
		var storage = catchObj.persistence ? uni.getStorageSync : that_.$session.get;
		var catchName = getCatchName(catchObj.catchName, query)
		var catchStorage = storage(catchName);
		if (catchStorage) return new Promise((resolve, reject) => {
			let storageRes = {
				code: 1,
				msg: '读取缓存成功',
				data: catchStorage
			}
			responseSuccess(storageRes, catchObj, query);
			resolve(storageRes);
		})
	}
	type = (type || catchObj.type || 'POST').toLocaleLowerCase();
	return new Promise((resolve, reject) => {
		catchObj.loading && that_.$loading(catchObj.loading);
		return ;
		catchObj.url += (that_.$typeOf(query) !== 'Object' ? ('/' + query) : '');
		http[type](catchObj.url, query).then(async res => {
			responseSuccess(res, catchObj, query);
			resolve(res);
		}).catch(err => {
			catchObj.loading && that_.$hideLoading();
			uni.getNetworkType({
				success: (res) => {
					if (res.networkType === 'none' || res.networkType === 'unknown') {
						that_.$toast('您的网络不佳，请稍候再试');
					} else {
						that_.$toast('系统繁忙，请稍候再试');
					}
				}
			})
			reject(err);
		})
	})
}

async function responseSuccess(res, catchObj, query) {
	catchObj.loading && hideLoading();
	if (res.code === 1) {
		res.data = catchObj.catchBefore ? await catchObj.catchBefore(res.data) : res.data;
		res.msg = res.toast || res.msg;
		if (catchObj.catchName || catchObj.update) { //表示要进行缓存或者强制更新
			catchHandle(catchObj, res, query);
		}
		if (catchObj.removeName) {
			var removeObj = needCatchList[catchObj.removeName];
			var removeName = getCatchName(removeObj.name);
			if (removeObj.persistence) {
				uni.removeStorageSync(removeName)
			} else {
				that_.$session.remove(removeName)
			}
		}
		catchObj.toast && that_.$toast(that_.$typeOf(catchObj.toast) === 'Boolean' ? res.msg : catchObj.toast, 1);
	} else{
		if (res.code === 501) { //未登录或者登录过期
			let userInfo = getUserInfo();
			that_.$modal({
				title: '温馨提示',
				content: '您还没有登录，请登录后操作',
				confirmText: '去登录',
				cancelText: catchObj.modalBack ? '返回' : '我在想想',
				success: (res) => {
					if (res.confirm) {
						Router.push({
							name: 'login'
						})
					} else {
						catchObj.modalBack && Vue.prototype.$back();
					}
				}
			})
		} else{
			catchObj.showErr && that_.$toast(res.msg);
		}
	}
}

/**
 * @description 缓存统一设置或删除管理
 * @param {Object} catchObj 缓存对象
 * @param {type} queryObj 请求参数
 */

function catchHandle(catchObj = {}, resObj = {}, queryObj = {}) {
	let catchName = getCatchName(catchObj.catchName, queryObj);
	catchObj.catchName && setStorageSync(catchName, catchObj, resObj.data);
}

/**
 * @description 获取缓存名称
 * @param {Object} catchObj 缓存项
 * @return {String}
 */
const getCatchName = (nameObj, queryObj = {}) => {
	let catchName = '';
	if (that_.$typeOf(nameObj) === 'Object') {
		catchName = nameObj.value;
		if (nameObj.position) {
			let nameStorage, extraName = '';
			if (that_.$typeOf(nameObj.storage) === 'Function') {
				(async () => {
					nameObj.storage = await nameObj.storage();
				})();
			}
			nameStorage = nameObj.storage ? nameObj.storage : false;
			if (nameStorage) {
				extraName = nameStorage[extraName] || '';
			}else if(that_.$typeOf(queryObj) === 'Object'){
				let _key = nameObj.key || '';
				extraName = _key && queryObj.hasOwnProperty(_key) ? queryObj[_key] : _key;
			}else{
				extraName = queryObj;
			}
			catchName = nameObj.position === 'after' ? (catchName + extraName) : (extraName + catchName);
		}
	} else {
		catchName = nameObj;
	}
	return catchName;
}
// 设置缓存
function setStorageSync(name, catchObj, data) {
	if (that_.$typeOf(data) === 'Object' && JSON.stringify(data) === '{}') return;
	if (that_.$typeOf(data) === 'Array' && data.length === 0) return;
	let {
		persistence
	} = catchObj;
	if (persistence) {
		uni.setStorageSync(name, data);
	} else {
		that_.$session.set(name, data);
	}
}
/**
 * @param {Array} list 需要清空缓存的数组
 * @param {String} list 键值
 * @return {Void} 无返回值
 */
function removeStorageSync(list) {
	list.forEach(o => {
		var catchName;
		var storage = o.persistence ? {
			get: uni.getStorageSync,
			remove: uni.removeStorageSync
		} : {
			get: that_.$session.get,
			remove: that_.$session.remove
		};
		if (that_.$typeOf(o.name) === 'Object') {
			catchName = o.name.value;
		} else {
			catchName = o.name;
		}
		var _position = o.name.position;
		if (_position) { //这种情况都是做持久化的，获取名字匹配的直接删除
			var allCatch = uni.getStorageInfoSync().keys || [];
			allCatch.forEach(o1 => {
				if (_position === 'before') { //加载前缀
					var item = o1.split('').reverse().join('');
					if (item.indexOf(catchName.split('').reverse().join('')) === 0) {
						console.warn('模糊删除前缀为：' + catchName + '的缓存');
						storage.remove(o1);
					}
				} else {
					if (o1.indexOf(catchName) === 0) {
						console.warn('模糊删除后缀为：' + catchName + '的缓存');
						storage.remove(o1);
					}
				}
			})
		} else {
			console.warn('删除缓存：' + catchName)
			storage.remove(catchName);
		}
	})
}
