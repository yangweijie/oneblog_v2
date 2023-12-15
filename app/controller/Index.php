<?php

namespace app\controller;

use app\BaseController;

class Index extends BaseController
{
    public function index()
    {
        return view('form');
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
