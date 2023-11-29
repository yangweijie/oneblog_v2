<?php
declare (strict_types = 1);

namespace app;

use think\Service;

/**
 * 应用服务类
 */
class AppService extends Service
{
    public function register()
    {
        // 服务注册
    }

    public function boot()
    {
        // 服务启动
        if(IS_VERCEL){
            app()->setRuntimePath(sys_get_temp_dir().DIRECTORY_SEPARATOR);
        }
    }
}
