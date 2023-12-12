<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]
namespace think;

require __DIR__ . '/../vendor/autoload.php';
define('IS_VERCEL', $_ENV['VERCEL']??0);
// 定义后台入口文件
define('ADMIN_FILE', 'admin.php');
// 执行HTTP应用并响应
$app = (new App());
if(IS_VERCEL){
	$app->setRuntimePath(sys_get_temp_dir().DIRECTORY_SEPARATOR);
}
$http = $app->setEnvName(IS_VERCEL?'vercel':'')->http;

$response = $http->run();

$response->send();

$http->end($response);
