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
use think\facade\Route;

Route::get('think', function () {
    return 'hello,ThinkPHP6!';
});

Route::get('hello/:name', 'index/hello');

// 后台绑定admin前缀处理
Route::any('admin', function(){
    $url = request()->server('REQUEST_URI');
    $path = str_ireplace('/admin/', '', $url);
    $path_array = explode('/', $path);
    $action = $path_array[0];
    $method = $path_array[1];
    // 控制器分层
    Route::bind('\app\admin');
    config(['view_dir_name'=>'view'.DIRECTORY_SEPARATOR.'admin'], 'view');
    if(!defined('MODULE')||empty(MODULE)){
        define('MODULE', $action);
    }
    return app("\app\admin\\$action")->$method();
});
