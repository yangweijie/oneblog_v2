<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2017 河源市卓锐科技有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

namespace app\index\home;

use app\cms\model\Document as DocumentModel;
use app\common\controller\Common;
use think\Config;
use think\facade\Db;
use think\facade\View;

/**
 * 前台公共控制器
 * @package app\index\controller
 */
class Home extends Common
{

    private $cache = [];

    /**
     * 初始化方法
     * @author 蔡伟明 <314013107@qq.com>
     */
    protected function initialize()
    {
        require_once __DIR__.'/../common.php';

    	$this->cache = get_front_cache();
    	// halt($this->cache);
        // dump('config');
        // dump(config());
        // 系统开关
        if (!config('app.web_site_status')) {
            $this->error('站点已经关闭，请稍后访问~');
        }
        if ($this->request->isPjax()) {
            Config::set('default_ajax_return', 'html');
            View::config(['layout_on' =>true, 'layout_name'=>'home/layout_pjax']);
        } else {
        	View::config(['layout_on' =>true, 'layout_name'=>'home/layout']);
        }
        View::assign('new_article', $this->cache['new_article']);
        View::assign('category', $this->cache['cate']);
        View::assign('tags', ['测试']);
        View::assign('single', $this->cache['single_list']);
        View::assign('archive', $this->cache['date']);
    }

    /* 空操作，用于输出404页面 */
    public function _empty()
    {
    	dump(1);
        switch (strtolower($this->request->controller())) {
            case 'public':
                dump($this->request);
                die;
                break;
            case 'archive':
                $Index = new Index();
                return $Index->detail($this->request->action());
                break;
            case 'category':
                $Index = new Index();
                return $Index->category($this->request->action());
                break;
            case 'search':
                $_GET['kw'] = $this->request->action();
                $Index      = new Index();
                return $Index->search($this->request->action());
                break;
            case 'feed':
                $type = input('get.type');
                return $this->feed($type);
                break;
            case 'submit':
            	if(isset($_REQUEST['package_control_version'])){
            		return json(['result'=>'success']);
            	}else{
            		return json(['result'=>'error']);
            	}
            	break;
            default:
                $Index = new Index();
                if (is_numeric($this->request->controller()) && is_numeric($this->request->action())) {
                    return $Index->archive($this->request->controller(), $this->request->action());
                }
                break;
        }
    }

    /* 文档模型列表页 */
    public function lists($page = 1, $cid = 0)
    {
        $tag = input('tag', '');
        $map = [];
        if ($cid) {
            $map['cms_document.cid'] = $cid;
        }
        if ($kw = trim(request()->action())) {
            $map['cms_document.title'] = array('like', "%{$kw}%");
            $like_id                   = Db::name('cms_document_article')->where("content like '%{$kw}%'")->column('aid');
            if ($like_id) {
                $map['cms_document.id'] = $like_id;
            }
        }
        $year  = input('get.year', 0);
        $month = input('get.month', 0);
        if ($year && $month) {
            $begin_time                      = strtotime($year . $month . "01");
            $end_time                        = strtotime("+1 month", $begin_time);
            $map['cms_document.create_time'] = array('between time', [$begin_time, $end_time]);
        }
        if ($tag) {
            $ids = Db::name('cms_document_article')->where("FIND_IN_SET('{$tag}', tags)")->column('aid');
            if ($ids) {
                $map['cms_document.id'] = $ids;
            } else {
                $map['cms_document.id'] = 0;
            }
        }
        $map['cms_document.trash'] = 0;
        // 排序
        $order = 'update_time desc';
        // 数据列表
        $data_list = DocumentModel::getList($map, $order);
        // dd($data_list);
        // die;
        View::assign('list', $data_list);
        return $data_list;
    }

    //RSS
    public function feed($type = 'RSS2')
    {
        $this->_currentFeedUrl = '';

        /** 判断聚合类型 */
        switch ($type) {
            case 'rss':
                /** 如果是RSS1标准 */
                $this->_feedType        = Feed::RSS1;
                $this->_currentFeedUrl  = $this->options->feedRssUrl;
                $this->_feedContentType = 'application/rdf+xml';
                break;
            case 'atom':
                /** 如果是ATOM标准 */
                $this->_feedType        = Feed::ATOM1;
                $this->_currentFeedUrl  = $this->options->feedAtomUrl;
                $this->_feedContentType = 'application/atom+xml';
                break;
            default:
                $this->_feedType        = Feed::RSS2;
                $this->_currentFeedUrl  = $this->options->feedUrl;
                $this->_feedContentType = 'application/rss+xml';
                break;
        }

        $feedQuery = 'article';

        $this->_feed = new Feed(ONETHINK_VERSION, $this->_feedType, 'UTF-8', 'zh-CN');

        $list_rows = 10;

        $this->_feed->setSubTitle(C('WEB_SITE_DESCRIPTION'));
        $this->_feed->setFeedUrl($this->_currentFeedUrl);

        $this->_feed->setBaseUrl(U('/', '', false, true));

        $this->_feed->setTitle(C('WEB_SITE_TITLE') . '-文章');
        $Document = D('Article');
        $list     = $this->lists(1);

        foreach ($list as $key => $value) {
            $feedUrl = '';
            if (Feed::RSS2 == $this->_feedType) {
                $feedUrl = U('/feed', '', false, true);
            } else if (Feed::RSS1 == $this->_feedType) {
                $feedUrl = U('/feed/rss', '', false, true);
            } else if (Feed::ATOM1 == $this->_feedType) {
                $feedUrl = U('/feed/atom', '', false, true);
            }

            $this->_feed->addItem(array(
                'title'   => $value['title'],
                'content' => C('FEEDFULLTEXT') ? $value['content'] : ($value['description'] ? $value['description'] : msubstr($value['content'], 0, 200, "utf-8", false)),
                'date'    => $value['create_time'],
                'link'    => U('archive/' . $value['id'] . '.html', '', false, true),
                'author'  => get_username($value['uid']),
                'excerpt' => $value['description'],
                'suffix'  => null,
            ));
        }

        header('Content-Type: ' . $this->_feedContentType . '; charset=utf-8', true);
        echo $this->_feed->__toString();
        exit;

    }
}
