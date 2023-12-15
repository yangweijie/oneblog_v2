<?php
require_once __DIR__ . '/vendor/autoload.php';

use Adapterman\Adapterman;
use Workerman\Worker;

//$disable_functions = 'header,header_remove,headers_sent,http_response_code,setcookie,session_create_id,session_id,session_name,session_save_path,session_status,session_start,session_write_close,session_regenerate_id,set_time_limit';
//ini_alter('disable_functions', $disable_functions);
Adapterman::init();

$http_worker                = new Worker('http://0.0.0.0:8080');
$http_worker->count         = 8;
$http_worker->name          = 'AdapterMan';

$http_worker->onWorkerStart = static function () {
    //init();
    require __DIR__.'/start.php';
};

$http_worker->onMessage = static function ($connection, $request) {
    $connection->send(run());
};

Worker::runAll();
