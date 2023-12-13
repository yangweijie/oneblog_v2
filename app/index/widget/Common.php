<?php
namespace app\index\widget;

use think\Facade\View;

class Common
{

	protected $cache;

    public function initialize()
    {
        // $this->view->engine->layout(false);
        $this->cache = get_front_cache();
    }

    /**
     * 获取单页的列表
     */
    public function single()
    {
        View::assign('single', $this->cache['single_list']);
        return View::fetch('widget/single');
    }

    /**
     * 最新文章
     */
    public function new_article()
    {
        View::assign('new_article', $this->cache['new_article']);
        return View::fetch('widget/new_article');
    }

    /* 显示指定分类的同级分类或子分类列表 */
    public function cates()
    {
        View::assign('category', $this->cache['cate']);
        return View::fetch('widget/lists');
    }

    /**
     * 归档显示
     */
    public function archive()
    {
        View::assign('archive', $this->cache['date']);
        return View::fetch('widget/archive');
    }

    public function tags()
    {
        // $tags = model('Tags')->getField('title', true);
        $tags = ['测试'];
        View::assign('tags', $tags);
        return View::fetch('widget/tags');
    }
}
