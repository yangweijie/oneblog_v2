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

namespace app\index\controller;

use think\db\Where;
use think\facade\Db;
use think\facade\Hook;
use think\facade\View;
use util\Http;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use \app\cms\model\Document;
use app\queue\service\QueueService;

/**
 * 前台首页控制器
 * @package app\index\controller
 */
class Index extends Home
{
	public function index($page = 1)
	{
		// queue('app\index\job\TestJob');
		$this->lists($page);
		return View::fetch();
	}

	public function job(){
		$ret = \app\queue\service\QueueService::add('测试', 'app\index\job\TestJob', 0, ['a'=>1], 0);
		return json($ret);
	}

	//详情
	public function detail($id)
	{
		$map       = new Where;
		$map['id'] = ['in', '1,2'];
		$list      = Document::where($map)->select();
		// halt($list);
		/* 标识正确性检测 */
		if (!($id && is_numeric($id))) {
			return $this->error('文档ID错误！');
		}

		/* 获取详细信息 */
		$info = Document::getOne($id);
		if (!$info) {
			return $this->error('未获取到详情信息');
		}
		$info['cate_title'] = get_cate_name($info['cid']);

		$tmpl = 'index/detail';

		/* 模板赋值并渲染模板 */
		View::assign('info', $info);
		return View::fetch($tmpl);
	}

	//单页
	public function single($name)
	{
		$article = Db::name('cms_page')->getByTitle($name);
		if (!$article) {
			return $this->error('单页不存在');
		} else {
			if (!empty($article['content'])) {
				View::assign($article);
				return View::fetch();
			} else {
				$params = [
					'name'  => $article['description'],
					'title' => $article['title'],
				];
				// dump(method_exists("plugins\\{$article['description']}\\{$article['description']}", 'single'));
				return hook('single', $params);
			}
		}
	}

	//分类
	public function category($name)
	{
		$cate = Db::name('cms_column')->getByName($name);
		if (!$cate) {
			$this->error('错误的分类');
		}
		View::assign('cate', $cate);
		$this->lists(input('get.page', 1), $cate['id']);
		return View::fetch('index/cate');
	}

	//归档
	public function archive($year, $month)
	{
		$_GET['month'] = $month;
		$_GET['year']  = $year;
		View::assign('year', $year);
		View::assign('month', $month);
		$this->lists(input('get.page', 1));
		return View::fetch('index/archive');
	}

	//搜索
	public function search($kw ='')
	{
		if (!$kw) {
			return $this->error('请输入关键字');
		}

		View::assign('kw', $kw);
		$this->lists(input('get.page', 1));
		return View::fetch('index/search');
	}

	public function static_tags()
	{

	}

	public function phpinfo(){
		phpinfo();
	}

	public function upload(){
		$this->view->engine->layout(false);
		return View::fetch();
	}

	public function doc_to_pdf(){
		$ret = \Pdftk::word_to_pdf('t1.doc');
		dump($ret);
	}

	// 测试同步 并发请求
	public function multi_post($type = 'sync', $num = 10){
		if($this->request->isPost()){
			return json(['i'=>$num+1]);
		}else{
			ini_set('memory_limit', '1024M');
			$now_date = datetime();
			if($type == 'sync'){
				$change_url = url('', ['type'=>'async'], false, true);
				echo <<<HTML
<div id="center">测试同步请求,开始时间：{$now_date}  <a href="javascript:;" onclick="window.stop();">终止</a>  <a href='{$change_url}'>点此切换至异步请求</a></div>
<div id="notify"></div>
<div id="notify2"></div>
HTML;
				$buff = new \ScriptProgress(10000, 0);
				$buff->next();
				$buff->set('');
				debug('begin');
				$url = url('', ['type'=>$type], false, true);
				for ($i = 0; $i < $num; $i++) {
					$ret = Http::post($url, ['num'=>$i]);
					$now = datetime();
					$buff->notify("正在进行第{$i}个请求，结束时间{$now}");
					$buff->next();
				}
				$msg = sprintf('%d个post请求结束，共耗时%s秒', $num, debug('begin', 'end'));
				$buff->notify($msg);
				$buff->next();
			}else{
				echo <<<HTML
<div id="center">测试异步请求,开始时间：{$now_date}  <a href="javascript:;" onclick="window.stop();">终止</a></div>
<div id="notify"></div>
<div id="notify2"></div>
HTML;
				$buff = new \ScriptProgress(10000, 0);
				$buff->next();
				$buff->set('');
				debug('begin');
				$url = url('', ['type'=>$type], false, true);
				$client = new Client(['base_uri' => $url]);
				$promises = [];
				for ($i = 0; $i < $num; $i++) {
					$promises[] = $client->postAsync($url, ['form_params'=>['num'=>$i]]);
				}

				// Wait on all of the requests to complete.
				$results = Promise\unwrap($promises);
				// foreach ($results as $key=>$value) {
				// 	$buff->notify(($key+1).'个请求的结果是:'. $value->getBody());
				// 	$buff->next();
				// }
				$msg = sprintf('%d个post请求结束，共耗时%s秒', $num, debug('begin', 'end'));
				$buff->notify($msg);
				$buff->next();
			}
            session_write_close();
			exit;
		}
	}

	public function index2(){
		View::config(['layout_on' =>false]);
		return View::fetch('index/index2');
	}
}
