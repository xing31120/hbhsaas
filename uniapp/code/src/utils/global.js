import store from '@/store'
import sendHttp from '@/service'
import config from '@/config'
import * as utilFn from '@/utils'
import filters from '@/filters'
import globalMixins from '@/mixins/globalMixins'

const globalFun = {
	install(Vue) {
		// 批量挂载在this上
		Vue.prototype.$http = sendHttp
		Vue.prototype.$store = store
		Vue.prototype.$config = config
		Vue.prototype.$utilFn = utilFn
		Vue.prototype.$toast = showToast
		Vue.prototype.$loading = showLoading
		Vue.prototype.$hideLoading = hideLoading
		Vue.prototype.$modal = showModal
		Vue.prototype.$deepClone = deepClone
		Vue.prototype.$typeOf = getVariableType
		Vue.prototype.$session = sessionCatch
		
		// 批量注入filter
		Object.keys(filters).forEach(fKey => {
			Vue.filter(fKey, filters[fKey])
		})
		
		// 导入全局mixin
		Vue.mixin(globalMixins)
	}
}

/* 
	消息提示框
	title String 是 提示的内容，长度与 icon 取值有关。 
	icon String 否 图标，有效值 "success", "loading", "none" 
	duration Number 否 提示的延迟时间，单位毫秒，默认：1500 
	image String 否 自定义图标的本地路径 
	mask Boolean 否 是否显示透明蒙层，防止触摸穿透，默认：false 
*/
function showToast(msg = '系统繁忙，请稍后再试！', icon = 0, duration = 1500, mask = true, image = false) {
	const icons = ['none', 'success', 'loading']
	const _icon = icons[icon];
	let params = {
		icon: _icon,
		title: msg,
		duration: duration,
		mask: mask
	}
	image && (params.image = image);
	uni.showToast(params);
}


// loading框
function showLoading(msg = '加载中', mask = true){
	let params = {
		mask: mask,
		title: msg
	}
	uni.showLoading(params);
}

// 隐藏loading框
function hideLoading() {
	uni.hideLoading();
}

/*
	显示对话框
	title	String	是	提示的标题	
	content	String	是	提示的内容
	showCancel	Boolean	否	是否显示取消按钮，默认为 true	
	cancelText	String	否	取消按钮的文字，默认为"取消"，最多 4 个字符	
	cancelColor	HexColor	否	取消按钮的文字颜色，默认为"#000000"	H5、微信小程序、百度小程序
	confirmText	String	否	确定按钮的文字，默认为"确定"，最多 4 个字符	
	confirmColor	HexColor	否	确定按钮的文字颜色，H5平台默认为"#007aff"，微信小程序平台默认为"#3CC51F"，百度小程序平台默认为"#3c76ff"	H5、微信小程序、百度小程序
	success	Function	否	接口调用成功的回调函数	
	fail	Function	否	接口调用失败的回调函数	
	complete	Function	否	接口调用结束的回调函数（调用成功、失败都会执行）
*/
function showModal({
		title = '', content = '这是一个模态框', showCancel = true, cancelText = '取消', cancelColor = '#666', confirmText = '确定',
			confirmColor = '#0260fe', success, fail, complete
	}) {
	uni.showModal({
		title: title,
		content: content,
		showCancel: showCancel,
		cancelText: cancelText,
		cancelColor: cancelColor,
		confirmText: confirmText,
		confirmColor: confirmColor,
		success: (res) => {
			success && success(res);
		},
		fail: (err) => {
			fail && fail(err);
		},
		complete: (res) => {
			complete && complete(res);
		}
	});
}


// 深拷贝
const deepClone = obj => {
	if (typeof obj !== 'object') return ;//排除null、undefined、string、number
	const newObj = obj instanceof Array ? [] : {}
	for (const key in obj) {
		if (obj.hasOwnProperty(key)) {
			newObj[key] = getVariableType(obj[key]) === 'Object' ? deepClone(obj[key]) : obj[key]
		}
	}
	return newObj
}


/**
 *@description 判断数据类型
 * @param {*} anything 任意数据类型 any
 * @return {string} 返回数据类型有Array,Number,Object,Boolean,String,Undefined,Function,Null
 */
function getVariableType(anything) {
	return Object.prototype.toString.call(anything).slice(8, -1)
}


const sessionCatch = {
	// #ifdef H5
	set(key, val) {
		let sessionKey = key + 'Session';
		let type = typeof val;
		sessionStorage.setItem(sessionKey, JSON.stringify({
			type: type,
			data: val
		}));
	},
	get(key) {
		let sessionKey = key + 'Session';
		let data = JSON.parse(sessionStorage.getItem(sessionKey) || '{}');
		return data.data;
	},
	remove(key) {
		let sessionKey = key + 'Session';
		sessionStorage.removeItem(sessionKey)
	},
	clear() {
		sessionStorage.clear();
	},
	// #endif
	// #ifndef H5
	set(key, val) {
		let sessionKey = key + 'Session';
		uni.setStorageSync(sessionKey, val);
	},
	get(key) {
		let sessionKey = key + 'Session';
		return uni.getStorageSync(sessionKey);
	},
	remove(key) {
		let sessionKey = key + 'Session';
		uni.removeStorageSync(sessionKey);
	},
	clear() {
		let allSession = uni.getStorageInfoSync('uni-storage-keys').keys;
		allSession.forEach(key => {
			if (key.indexOf('Session') !== -1) {
				uni.removeStorageSync(key);
			}
		});
	}
	// #endif
}

export default globalFun