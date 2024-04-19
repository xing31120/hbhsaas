import store from '@/store'
import { hostUrl, version } from '@/config/index.js'

// import Request from '@/js_sdk/luch-request/index.js'
import Request from 'luch-request' // 下载的插件

/**
 * 修改全局配置示例
 **/
const http = new Request({
    // #ifdef H5
    baseURL: '/api',
    // #endif
    // #ifdef MP-WEIXIN
    baseURL: hostUrl,
    // #endif
    header: {
        'Content-Type': 'application/json',
        'app-version': version
    },
    validateStatus: (statusCode) => { // statusCode 必存在。此处示例为全局默认配置
        return statusCode >= 200 && statusCode < 300
    }
})

/* 请求之前拦截器。可以使用async await 做异步操作 */
http.interceptors.request.use((config) => {
    config.header = {
        ...config.header,
        'Authorization': 'Bearer ' + getTokenStorage(),
        'app-from': store.state.config.pagePath
    }
    /*
   if (!token) { // 如果token不存在，return Promise.reject(config) 会取消本次请求
     return Promise.reject(config)
   }
   */
    return config
}, (config) => {
    return Promise.reject(config)
})

/* 请求之后拦截器。可以使用async await 做异步操作  */
http.interceptors.response.use(async (response) => {
	let { data, statusCode } = response;
    if (response.statusCode !== 200) { // 服务端返回的状态码不等于200，则reject()
      data.code = 99;
    }else{
		let message = data.message;
		if(data.status === 'failed'){
			switch(data.errors.code){
				case 12:
					message = "用户未登录";
				break
				case 404:
					message = "暂无记录";
				break
				case 506:
					message = "存在违禁词";
				break
				case 102:
					message = "用户数据错误，请重新登录";
					break
				default:
			}
		}
		data.code = data.status === "success" ? 1 : (data.errors && data.errors.code) || 0;
		data.message = message;
	}
    return data
}, (response) => {
    // 请求错误做点什么。可以使用async await 做异步操作
    console.log(response)
    return Promise.reject(response)
})

const getTokenStorage = () => {
    return uni.getStorageSync('token') || ''
}

export default http
// export {
//     http
// }
