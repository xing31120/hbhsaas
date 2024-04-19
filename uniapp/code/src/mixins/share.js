// #ifdef H5
import jweixin from 'jweixin-module'
import { wxShare } from '@/api/common'
// #endif

export default {
	data() {
		return {
			shareData: null
		}
	},
	// // #ifdef MP-WEIXIN
	onShareAppMessage(res) {
		console.log(this.shareData)
		return this.shareData
	},
	// // #endif
	methods: {
		async jweixinFn(data) {
			let that = this
			function success() {
				// that.shareStatiscFN(data.linkurl, data.owner_user_id, data.puser_id)
			}
			// #ifdef H5
			await that.getWeiXinOptin(data) // 需在用户可能点击分享按钮前就先调用
			jweixin.ready(function() {
				let option = {
					title: data.title, // 分享标题
					desc: data.desc, // 分享描述
					link: data.pagePath, // 分享链接，该链接域名或路径必须与当前页面对应的公众号JS安全域名一致
					imgUrl: data.imgUrl, // 分享图标
					success
				}
				// 分享给朋友”及“分享到QQ
				// jweixin.updateAppMessageShareData(option)
				// 分享到朋友圈”及“分享到QQ空间
				// jweixin.updateTimelineShareData(option)
				// “分享到腾讯微博”
				// jweixin.onMenuShareWeibo(option)
				//分享给朋友接口
				jweixin.onMenuShareAppMessage(option)
				//分享到朋友圈接口
				jweixin.onMenuShareTimeline(option);  
			})
			// #endif

			// #ifdef MP-WEIXIN
			that.shareData = {
				title: data.title, // 分享标题
				path: data.pagePath,
				imageUrl: data.imgUrl,
				success
			}
			// #endif
		},

		// ==分享统计回调==
		// shareStatiscFN(res, owner_user_id, puser_id) {
		// 	const userid = uni.getStorageSync('user_id') || null
		// 	const params = {}
		// 	if (userid == owner_user_id) {
		// 		params.share_user_id = userid
		// 		params.owner_user_id = owner_user_id
		// 	} else {
		// 		params.owner_user_id = owner_user_id
		// 		params.share_user_id = userid
		// 		params.p_share_user_id = puser_id
		// 	}
		// 	// shareStatisc(params)
		// },
		/**
		 * jsSdk入权限验证配置
		 * @parmas apiList 为需要获取的微信jsdk的接口列表
		 */
		getWeiXinOptin(data, apiList = ['checkJsApi', 'onMenuShareTimeline', 'onMenuShareAppMessage']) {
			return new Promise((resolve, reject) => {
				const params = {
					// url: location.origin
					url: data.pagePath
				}
				const jsApiList = apiList
				wxShare(params).then(res => {
					let resdata = res.data
					jweixin.config({
						debug: false, // 开启调试模式,调用的所有api的返回值会在客户端alert出来，若要查看传入的参数，可以在pc端打开，参数信息会通过log打出，仅在pc端时才会打印。
						appId: resdata.appId, // 必填，公众号的唯一标识
						timestamp: resdata.timestamp, // 必填，生成签名的时间戳
						nonceStr: resdata.nonceStr, // 必填，生成签名的随机串
						signature: resdata.signature, // 必填，签名，见附录1
						jsApiList// 必填，需要使用的JS接口列表
					})

					resolve()
				})
			})
		}
	}

}
