
/** *
 * 获取位置距离
 * data 经纬度数据信息
 */
export function calcDistance(data) {
	let userRegions = uni.getStorageSync('userRegion')
	if (!userRegions || !data) {
		return
	}
	userRegions = JSON.parse(userRegions)
	if (userRegions && data.lat && data.lng) {
		// eslint-disable-next-line one-var
		const lat1 = data.lat * 1,
			lng1 = data.lng * 1,
			lat2 = parseFloat(userRegions.postion.lat),
			lng2 = parseFloat(userRegions.postion.lng)

		return parseInt(calcDistanceFn(lat1, lng1, lat2, lng2))
	}
}

// 计算距离 公用方法
export function calcDistanceFn(lat1, lng1, lat2, lng2) {
	const f = (((lat1 + lat2) / 2) * Math.PI) / 180.0
	const g = (((lat1 - lat2) / 2) * Math.PI) / 180.0
	const l = (((lng1 - lng2) / 2) * Math.PI) / 180.0

	let sg = Math.sin(g)
	let sl = Math.sin(l)
	let sf = Math.sin(f)

	let s, c, w, r, d, h1, h2
	const a = 6378.137 // 取WGS84标准参考椭球中的地球长半径 单位M
	const fl = 1 / 298.257

	sg = sg * sg
	sl = sl * sl
	sf = sf * sf

	s = sg * (1 - sl) + (1 - sf) * sl
	c = (1 - sg) * (1 - sl) + sf * sl

	w = Math.atan(Math.sqrt(s / c))
	r = Math.sqrt(s * c) / w
	d = 2 * w * a
	h1 = (3 * r - 1) / 2 / c
	h2 = (3 * r + 1) / 2 / s

	return d * (1 + fl * (h1 * sf * (1 - sg) - h2 * (1 - sf) * sg))
}