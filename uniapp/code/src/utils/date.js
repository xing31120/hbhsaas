
/** *
 * 根据时间戳获取日期时间
 * fmt格式 例：yyyy-MM-dd hh:mm:ss
 * status 为 true 时是13位时间
 */
export function getFormatDate(time, fmt, status) {
	try {
		time = status ? time : time * 1000
		const now = new Date(time)
		return now.Format(fmt)
	} catch (e) {
		if (window.console) {
			console.log(e)
		}
	}
}

/** *
 * 时间格式化
 */
// eslint-disable-next-line no-extend-native
Date.prototype.Format = function(fmt = 'yyyy-MM-dd hh:mm:ss') {
	const o = {
		'M+': this.getMonth() + 1, // 月份
		'd+': this.getDate(), // 日
		'h+': this.getHours(), // 小时
		'm+': this.getMinutes(), // 分
		's+': this.getSeconds(), // 秒
		'q+': Math.floor((this.getMonth() + 3) / 3), // 季度
		S: this.getMilliseconds() // 毫秒
	}
	if (/(y+)/.test(fmt)) {
		fmt = fmt.replace(
			RegExp.$1,
			(this.getFullYear() + '').substr(4 - RegExp.$1.length)
		)
	}
	for (const k in o) {
		if (new RegExp('(' + k + ')').test(fmt)) {
			fmt = fmt.replace(RegExp.$1, RegExp.$1.length == 1 ? o[k] : ('00' + o[k]).substr(('' + o[k]).length))
		}
	}
	return fmt
}

/** *
 * 根据日期获取时间戳
 * @parmas yyyy-MM-dd hh:mm:ss
 */
// 获取时间戳方法
export function getDataTimestamp(time) {
	let etime = time
	etime = new Date(etime)
	etime = etime.getTime()
	return etime
}