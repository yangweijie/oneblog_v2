<?php

require_once __DIR__.'/../cms/common.php';

use think\facade\Cache;
use think\Db;
use util\File;
use util\Tree;

if (!function_exists('widget')) {
    /**
     * 渲染输出Widget
     * @param string    $name Widget名称
     * @param array     $data 传入的参数
     * @return mixed
     */
    function widget($name, $data = [])
    {
    	static $objects;
    	$objects = [];
    	list($class, $method) = explode('/', $name);
    	$app_name = app('http')->getName();
    	$name = "\app\\{$app_name}\\widget\\{$class}";
    	if(isset($objects[$name])){
    		$object = $objects[$name];
    	}else{
        	$object = new $name(app());
    	}
        $result = '';
        if (is_object($object)) {
        	$objects[$name] = $object;
            $result = $object->$method($data);
            halt($result);
        }
        return $result;
    }
}

if (!function_exists('get_front_cache')) {
    function get_front_cache() {
        // if (false === Cache::get('front_cache')) {
	        $single_list = \app\cms\model\Page::where('status', 1)->order('update_time DESC')->select();
	        $new_article = \app\cms\model\Document::view('cms_document', true)
	                ->view("cms_column", ['name' => 'column_name'], 'cms_column.id=cms_document.cid', 'left')
	                ->view("admin_user", 'username', 'admin_user.id=cms_document.uid', 'left')
	                ->where(['cms_document.status' => 1])
	                ->order('update_time DESC')
	                ->limit(3)->select();

	        $category = \app\cms\model\Column::where('status = 1')->order('sort asc')->select();
	        $count = \app\cms\model\Document::group('cid')->where('status=1')->column('cid', 'count(*) as num');
	        $count = array_combine(array_values($count), array_keys($count));
	        foreach ($category as $key => $value) {
	            $category[$key]['article_num'] = isset($count[$value['id']]) ? $count[$value['id']] : 0;
	        }

	        // $cate = \app\cms\model\Column::where('status=1')->column(true);
	        $cate = Tree::config(['title' => 'name'])->toList($category);
	        $list = \app\cms\model\Document::getList([], 'create_time DESC, id DESC');
	        $date = $time = [];
	        foreach ($list as $key => $value) {
	            if ($value['create_time']) {
	                $time[] = date('F Y', strtotime($value['create_time']));
	            }
	        }
	        $time = array_unique($time);
	        foreach ($time as $key => $value) {
	            $date[] = array(
	                'text' => $value,
	                'link' => sprintf('/index/index/archive/year/%d/month/%s',
	                	date('Y', strtotime($value)),
	                	date('m', strtotime($value))
	                )
	            );
	        }

	        $cache = array(
	            'single_list' => $single_list,
	            'new_article' => $new_article,
	            'cate' => $cate,
	            'date' => $date,
	        );

	        Cache::set('front_cache', $cache);
        // }
        return $cache;
        // return Cache::get('front_cache');
    }

}
