import router from './index'
import { Route } from 'uni-simple-router'

//全局路由前置守卫
router.beforeEach((to:Route, from:Route, next:Function) => {
    console.log(to)
    next()
})
// 全局路由后置守卫
router.afterEach((to:Route, from:Route) => {
})


const a =  (a:number, b:number):number => {
    return a + b;
}