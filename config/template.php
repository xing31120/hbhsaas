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

// +----------------------------------------------------------------------
// | 模板设置
// +----------------------------------------------------------------------

return [
    // 模板引擎类型 支持 php think 支持扩展
    'type'         => 'Think',
    // 默认模板渲染规则 1 解析为小写+下划线 2 全部转换小写 3 保持操作方法
    'auto_rule'    => 1,
    // 模板路径
    'view_path'    => '',
    // 模板后缀
    'view_suffix'  => 'html',
    // 模板文件名分隔符
    'view_depr'    => DIRECTORY_SEPARATOR,
    // 模板引擎普通标签开始标记
    'tpl_begin'    => '{',
    // 模板引擎普通标签结束标记
    'tpl_end'      => '}',
    // 标签库标签开始标记
    'taglib_begin' => '{',
    // 标签库标签结束标记
    'taglib_end'   => '}',
    //模版替换变量
    'tpl_replace_string'=>[
        '__YUN_PUBLIC_DIR__' => '', //CSS,JS,图片的云空间域名配置
    ],
    //模版缓存配置
    'html_cache_on' => false,//是否开启静态缓存
    'html_cache_time' => 43200,//静态缓存时间
    'html_file_suffix' => '.shtml',//静态缓存文件后缀
    'html_cache_compile_type' => 'file',//缓存存储驱动
    'html_cache_rules' => [//静态缓存规则
//        '*'=>array('{$_SERVER.SERVER_NAME}'.'{$_SERVER.REQUEST_URI}',60,'md5'),//根据当前的URL进行缓存，0为永久有效
//        '*'=>array('{$_SERVER.HTTP_HOST}/{$_SERVER.REQUEST_URI}'),
//        'index:index' => array('{$_SERVER.HTTP_HOST}/Index/index.html'),
//        'product:detail' => array('{$_SERVER.HTTP_HOST}/{$_SERVER.REQUEST_URI}', 259200),
    ],
];
