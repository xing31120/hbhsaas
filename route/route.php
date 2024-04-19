<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Env;


Route::domain(Env::get('route.domain_admin','admin'),function(){
    Route::rule('login','Common/login');
})->bind('admin');
Route::domain(Env::get('route.domain_api','api'),function(){
})->bind('api');
Route::domain(Env::get('route.domain_cron','api'),function(){
})->bind('cron');
Route::domain(Env::get('route.domain_callback','callback'),function(){
})->bind('callback');
Route::domain(Env::get('route.domain_shop','shop'),function(){
    Route::rule('login','Common/login');
})->bind('shop');
Route::domain(Env::get('route.domain_push','push'),function(){
})->bind('push');

Route::domain(Env::get('route.domain_pc','pc'),function(){
    Route::rule('about','Index/about');
    Route::rule('contact','Index/contact');
    Route::rule('index_dance_school','Index/index_dance_school');
    Route::rule('index-dance-school','Index/index_dance_school');
    Route::rule('index-ballet-studio','Index/index_ballet_studio');
    Route::rule('index-onepage','Index/index_onepage');
    Route::rule('index-2-onepage','Index/index_onepage_2');
    Route::rule('index-3-onepage','Index/index_onepage_3');
    Route::rule('dance-classes','Classes/dance_classes');
    Route::rule('single-classes','Classes/single_classes');
    Route::rule('class-schedule','Classes/schedule_classes');


    Route::rule('account','Auth/login');
    Route::rule('reg','Auth/reg');
    Route::rule('forgot','Auth/forgot');
})->bind('pc');

//Route::domain(Env::get('route.domain_admin','admin'),function(){
//    Route::rule('login','Common/login');
//})->bind('admin');
//Route::domain(Env::get('route.domain_api','api'),function(){
//})->bind('api');
//Route::domain(Env::get('route.domain_app','app-saas'),function(){
//})->bind('app');
//Route::domain(Env::get('route.domain_cron','cron-saas'),function(){
//})->bind('cron');
//Route::domain(Env::get('route.domain_home','*.zzsp'),function(){
//})->bind('home');
//Route::domain(Env::get('route.domain_shop','sj'),function(){
//})->bind('shop');
//Route::domain(Env::get('route.domain_supplier','gys'),function(){
//})->bind('supplier');
//Route::domain(Env::get('route.domain_web','web'),function(){
//})->bind('web');

return [

];
