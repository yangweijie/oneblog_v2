<?php

namespace app\controller;

use app\BaseController;
use think\facade\Env;

class Index extends BaseController
{
    public function index()
    {
        return view('index');
    }

    public function json()
    {
        trace(file_get_contents('php://input'));
        return json(['code'=>1]);
    }

    public function hello($name = 'ThinkPHP8')
    {
        return 'hello,' . $name;
    }
}
