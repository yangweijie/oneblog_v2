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
use think\helper\Str;

Route::any('admin', function (Route $route, \think\Request $request){
    $url = $request->pathinfo();
    if($url){
        $path_array = explode('/', $url);
        $action = $path_array[1];
        $method = $path_array[2];
    }else{
        $action = 'index';
        $method = 'index';
    }
    $method = Str::contains($method, '?')?  strstr($method, '?', true) : $method;
    if($view_suffix = config('view.view_suffix')){
        $method = str_replace(".{$view_suffix}", '', $method);
    }
    $request->setController(ucfirst($action));
    $request->setAction($method);
    $class = sprintf('\app\%s\controller\%s', MODULE, ucfirst($action));
    define('APP_PATH', root_path().DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR);
    // 定义应用目录
    return app($class)->$method();
});
