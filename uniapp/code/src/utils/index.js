import {
	hostUrl
} from '@/config'

/** *
 * 获取小程序地址完整路径
 */
export function getMiniAppUrl(data = {}, isPath = false) {
	let getCurPages = getCurrentPages()
	if (getCurPages.length == 0) return false
	let getCurPagesObj = getCurPages[getCurPages.length - 1]
	let getRoute = getCurPagesObj.route + parse(data)
	getRoute = isPath ? `${hostUrl}/#/${getRoute}` : getRoute

	return getRoute
}

/** *
 * 生成从minNum到maxNum的随机数
 * @parmas minNum 最小数
 * @parmas maxNum 最大数
 */
export function getRandomNum(minNum, maxNum) {
	switch (arguments.length) {
		case 1:
			return parseInt(Math.random() * minNum + 1, 10)
			// eslint-disable-next-line no-unreachable
			break
		case 2:
			return parseInt(Math.random() * (maxNum - minNum + 1) + minNum, 10)
			break
		default:
			return 0
			break
	}
}

/** *
 * 防抖
 * @parmas fn 回调函数
 * @parmas time 规定时间
 */
export const debounce = (function() {
	let timer = {};
	return function(func, wait) {
		let context = this; // 注意 this 指向
		let args = arguments; // arguments中存着e
		let name = arguments[0].name || 'arrow'; //箭头函数
		if (timer[name]) clearTimeout(timer[name]);
		timer[name] = setTimeout(() => {
			func.apply(this, args)
		}, wait)
	}
})();

/** *
 * 节流(规定的时间才触发)
 * @parmas fn 结束完运行的回调
 * @parmas delay 规定时间
 */
export const throttle = (function() {
	let timeout = null;
	return function(func, wait) {
		let context = this;
		let args = arguments;
		if (!timeout) {
			timeout = setTimeout(() => {
				timeout = null;
				func.apply(context, args)
			}, wait)
		}
	}
})();

/** *
 * 复制到粘贴板
 * @parmas str 拷贝的字符
 * @parmas success 成功回调
 * @parmas error 失败回调
 */
export function copyText({
	content,
	success,
	error
}) {
	if (!content) return error('复制的内容不能为空 !')
	content = typeof content === 'string' ? content : content.toString() // 复制内容，必须字符串，数字需要转换为字符串
	/**
	 * 小程序端 和 app端的复制逻辑
	 */
	//#ifndef H5
	uni.setClipboardData({
		data: content,
		success: function() {
			success("复制成功~")
			console.log('success');
		},
		fail: function() {
			success("复制失败~")
		}
	});
	//#endif

	/**
	 * H5端的复制逻辑
	 */
	// #ifdef H5
	if (!document.queryCommandSupported('copy')) { //为了兼容有些浏览器 queryCommandSupported 的判断
		// 不支持
		error('浏览器不支持')
	}
	let textarea = document.createElement("textarea")
	textarea.value = content
	textarea.readOnly = "readOnly"
	document.body.appendChild(textarea)
	textarea.select() // 选择对象
	textarea.setSelectionRange(0, content.length) //核心
	let result = document.execCommand("copy") // 执行浏览器复制命令
	if (result) {
		success("复制成功~")
	} else {
		error("复制失败，请检查h5中调用该方法的方式，是不是用户点击的方式调用的，如果不是请改为用户点击的方式触发该方法，因为h5中安全性，不能js直接调用！")
	}
	textarea.remove()
	// #endif
}

/** *
 * 对象参数转为url参数
 * @parmas query 拼接得参数对象
 */
export const parse = (query) => {
	return Object.keys(query)
		.filter(key => !isEmpty(query[key]))
		.reduce((result, key) => {
			const value = query[key]
			// in查询特殊处理
			if (Array.isArray(value) && !isEmpty(value)) {
				return `${result}&${value.reduce((val, cVal) => `${val ? `${val}&` : val}${key}=${cVal}`, '')}`
			}

			// between查询做特殊处理
			if (typeof value === 'object' && !isEmpty(value)) {
				const [start, end] = value
				return `${result}&${key}[]=${start}&${key}[]=${end}`
			}

			return `${result}&${key}=${value}`
		}, '')
		.replace(/^&/, '?')
}

export function checkPhone(phone) {
	if (!(/^1[3456789]\d{9}$/.test(phone))) {
		return false;
	}
	return true;
}

export function checkIdCard(idCard) {
	//15位和18位身份证号码的正则表达式
	var regIdCard =
		/^(^[1-9]\d{7}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}$)|(^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])((\d{4})|\d{3}[Xx])$)$/;
	//如果通过该验证，说明身份证格式正确，但准确性还需计算
	if (regIdCard.test(idCard)) {
		if (idCard.length == 18) {
			var idCardWi = new Array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2); //将前17位加权因子保存在数组里
			var idCardY = new Array(1, 0, 10, 9, 8, 7, 6, 5, 4, 3, 2); //这是除以11后，可能产生的11位余数、验证码，也保存成数组
			var idCardWiSum = 0; //用来保存前17位各自乖以加权因子后的总和
			for (var i = 0; i < 17; i++) {
				idCardWiSum += idCard.substring(i, i + 1) * idCardWi[i];
			}
			var idCardMod = idCardWiSum % 11; //计算出校验码所在数组的位置
			var idCardLast = idCard.substring(17); //得到最后一位身份证号码
			//如果等于2，则说明校验码是10，身份证号码最后一位应该是X
			if (idCardMod == 2) {
				if (idCardLast == "X" || idCardLast == "x") {
					return true;
				} else {
					return false;
				}
			} else {
				//用计算出的验证码与最后一位身份证号码匹配，如果一致，说明通过，否则是无效的身份证号码
				if (idCardLast == idCardY[idCardMod]) {
					return true;
				} else {
					return false;
				}
			}
		}
		return true;
	} else {
		return false;
	}
}

// 判断对象是否相等
export const diffByObj = (obj1, obj2) => {
	var o1 = obj1 instanceof Object;
	var o2 = obj2 instanceof Object;
	// 判断是不是对象
	if (!o1 || !o2) {
		return obj1 === obj2;
	}

	//Object.keys() 返回一个由对象的自身可枚举属性(key值)组成的数组,
	//例如：数组返回下表：let arr = ["a", "b", "c"];console.log(Object.keys(arr))->0,1,2;
	if (Object.keys(obj1).length !== Object.keys(obj2).length) {
		return false;
	}
	var isDif = true;
	for (var o in obj1) {
		var t1 = obj1[o] instanceof Object;
		var t2 = obj2[o] instanceof Object;
		if (t1 && t2) {
			isDif = diffByObj(obj1[o], obj2[o]);
		} else if (obj1[o] !== obj2[o]) {
			isDif = false;
			break;
		}
	}
	return isDif;
}

//判断是否微信登陆
export const isWeiXin = (() => {
	// #ifdef H5
	var ua = window.navigator.userAgent.toLowerCase();
	if (ua.match(/MicroMessenger/i) == 'micromessenger') {
		return true;
	} else {
		return false;
	}
	// #endif
	return false;
})();

export function getElSize(id, self) { //得到元素的size
	return new Promise((res, rej) => {
		self && self.$nextTick(() => {
			uni.createSelectorQuery().in(self).select('#' + id).fields({
				size: true,
				scrollOffset: true
			}, (data) => {
				res(data);
			}).exec();
		});
	})
}
