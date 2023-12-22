<?php
declare (strict_types = 1);

namespace app\service;

class IgnitionService extends \think\Service
{
    /**
     * 注册服务
     *
     * @return mixed
     */
    public function register()
    {
    	//
    }

    /**
     * 执行服务
     *
     * @return mixed
     */
    public function boot()
    {

        $this->app->bind('ignition', \Spatie\Ignition\Ignition::make()
            ->applicationPath($this->app->getBasePath())
            ->register());
    }
}
