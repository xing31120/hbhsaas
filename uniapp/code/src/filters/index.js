import { ossImgUrl } from '@/config'

function getFullOssImg(url) {
	if(!url) return '';
	if (/(http:\/\/|https:\/\/)((\w|=|\?|\.|\/|&|-)+)/g.test(url)) {
		return url
	} else {
		return ossImgUrl + url
	}
}

function reservedDecimal(num, fixed = 1) {
	if (!num) return Number(0).toFixed(fixed);
	num = String(num);
	let splitNum = num.split('.')
	let firstNum = splitNum[0];
	let lastNum = splitNum[1] ? String(splitNum[1]) : (new Array(fixed + 1)).join('0');
	let toFixedNum = (lastNum && lastNum.length) || 0;
	toFixedNum = toFixedNum > fixed ? fixed : toFixedNum;
	//toFixed会进行四舍五入 所以我们用裁剪
	// return .toFixed(toFixedNum);
	return firstNum + (toFixedNum > 0 ? '.' : '') + lastNum.substr(0, toFixedNum)
}

export default {
	ossImg: getFullOssImg,
	price: reservedDecimal
}