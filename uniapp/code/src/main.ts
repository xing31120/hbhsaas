import Vue from 'vue'
import App from './App.vue'

// 路由插件
import { RouterMount } from 'uni-simple-router'
import './router/router-ctrl'

// 全局变量
import global from '@/utils/global'
Vue.use(global)

// 生产环境中控制台不打印普通信息，以免造成内存泄漏
if (process.env.NODE_ENV == 'production') {
	if (console) {
		console.log = () => { }
	}
}

Vue.config.productionTip = false

const app= new App();

//v1.3.5起 H5端 你应该去除原有的app.$mount();使用路由自带的渲染方式
// #ifdef H5
	RouterMount(app,'#app');
// #endif
console.time('star')
// #ifndef H5
	app.$mount(); //为了兼容小程序及app端必须这样写才有效果
// #endif