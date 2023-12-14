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

use EasyWeChat\Factory;
use EasyWeChat\Message\Article;
use think\facade\Session;
use app\common\model\Wxuser;
use app\common\model\SukaShareimg;
use GuzzleHttp\Client;

/**
 * 插件控制器
 * @package app\index\controller
 */
class Wechat extends Home
{

	public $app2 = null;

	public function __construct()
	{
		config('SHOW_PAGE_TRACE', false);

		// $course_config = C('course_config');
		$options = [
			'debug'  => true,
			// 'app_id' => config('wechat_appid'),
			// 'secret' => config('wechat_appsecret'),
			// 'token'  => config('wechat_apptoken'),

			'app_id' => 'wxfb51aaac6400361a',
			'secret' => '6d0d7acde7a440bf88396f2f7e232b1a',
			'token'  => 'freelog',

			'log'    => [
				'level' => 'debug',
				'file' => __DIR__ . '/../../../wechat.log',
			],
		];

		$this->wxpay_config =
			array_merge($options,
			[
				'payment' => [
					'merchant_id' => config('wechat_merchant_id'),
					'key'         => config('wechat_key'),
				],
			]
		);
		$this->options = $options;
		$this->app2    = Factory::officialAccount($options);
	}

	public function run()
	{
		$app = $this->app2;
		set_time_limit(0);
		$app->server->push(function ($message) use($app){
			if($message != [0=>'']){
				$openid = $message['FromUserName'];
				trace($message);
				Session::init([
					'id'           => md5($openid),
					'cache_expire' => 3600,
				]);
				// $message['FromUserName'] // 用户的 openid
				// $message['MsgType'] // 消息类型：event, text....
				switch ($message['MsgType']) {
					case 'event':
						return '收到事件消息'.PHP_EOL.json_encode($message, JSON_UNESCAPED_UNICODE);
						break;
					case 'text':
						if($message['Content'] == '酥咔报表'){
							if(!$uid = Wxuser::get_uid($openid)){
								$wxuser = Wxuser::create([
									'openid'=>$openid,
								]);
								$uid = $wxuser->id;
							}
							session('uid', $uid);
							return "您的内部uid 为{$uid}, 请开始上传图片";
						}
						return '收到文字消息';
						break;
					case 'image':
						if(session('?uid')){
							try {
								$mediaId   = $message['MediaId'];
								$picUrl    = $message['PicUrl'];
								$md5       = md5_file($picUrl);
								$url = url('suka/index', ['uid'=>session('uid')], false, true);
								if($exist = SukaShareimg::where('md5', $md5)->find()){
									trace('exist. id'.$exist->id);
									$url = url('suka/index', ['uid'=>session('uid')], false, true);
									return '上传成功，请稍后访问'.$url.'来查看报表';
								}
								$temppath  = realpath(config('upload_path').DS.'temp');
								$temp_name = tempnam($temppath, 'wxa_');
								// ptrace(ltrim($temp_name, $temppath));
								$temp = $temppath.DS.ltrim($temp_name, $temppath).'.jpg';
								trace($temp);
								file_put_contents($temp, file_get_contents($picUrl));
								$ret = \app\index\home\Wechat::remote_upload($temp);
								trace($ret);
								if($ret['code'] == 0){
									return '上传失败';
								}
								try {
									\app\index\home\Suka::rec_report($ret['id'], $temp, session('uid'));
								} catch (\Exception $e) {
									trace($e->getMessage().PHP_EOL.$e->getTraceAsString());
								}
								done:
								$url = url('suka/index', ['uid'=>session('uid')], false, true);
								return '上传成功，请稍后访问'.$url.'来查看报表';
							} catch (\Exception $e) {
								trace($e->getMessage().PHP_EOL.$e->getTraceAsString());
								return '错误';
							}
						}else{
							return '收到图片消息, 如果是想上传酥咔对比图，请先回复“酥咔报表”';
						}
						break;
					case 'voice':
						return '收到语音消息';
						break;
					case 'video':
						return '收到视频消息';
						break;
					case 'location':
						return '收到坐标消息';
						break;
					case 'link':
						return '收到链接消息';
						break;
					case 'file':
						return '收到文件消息';
					// ... 其它消息
					default:
						return '收到其它消息';
						break;
				}
				return "您好！欢迎使用 EasyWeChat";
			}
		});

		$response = $app->server->serve();
		// 将响应输出
		$response->send();
		exit;
	}


	public static function remote_upload($temp, $dir='images'){
		$formname = 'file';
		$url = home_url('suka/upload', ['dir'=>$dir], false, true).'?api_token=100kthwww';
		$url = str_ireplace('https://', 'http://', $url);
		// ptrace($url);
		// curl
		if (version_compare(phpversion(), '5.5.0') >= 0 && class_exists('CURLFile')) {
			$post_data = array(
				$formname => new \CURLFile(realpath($temp)),
			);
		} else {
			$post_data = array(
				$formname => '@' . realpath($temp),
			);
		}
		// ptrace($post_data);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSLVERSION, 3);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($ch);
		// ptrace($result);
		$ret_arr = json_decode($result, true);
		if(false === $ret_arr || null == $ret_arr){
			trace($url);
			trace($result);
			exception('服务器出错');
		}

		// 'code'  => 1 ,
		// 'info'  => '上传成功' ,
		// 'class' => 'success' ,
		// 'id'    => $file_add['id'],
		// 'path'  => $file_path
		// alert2(curl_errno($ch));
		// alert2(curl_error($ch));
		// alert2($result);
		curl_close($ch);
		return $ret_arr;
	}
	// 测试群发
	public function group_send()
	{
		config('default_return_type', 'json');
		$broadcast = $this->app->broadcast;
		$openId = 'oPXlfs2igTAzbjM7mgZhvKAll8rI';
		// try {
		// 永久素材
		$material = $this->app->material;
		$pic = './public/uploads/images/58858d335ff90.jpg';
		$result = $this->postMediaImg($pic);
		trace($result);
		$content = $this->content_fix($this->description());
		trace($content);
		$article = new Article([
			'author' => 'jay',
			'title' => '测试图文',
			'content' => $content,
			'digest' => '简介',
			'source_url' => 'http://baidu.com',
			'show_cover' => 1,
			'thumb_media_id' => $result['media_id'],
			'',
		]);
		$ret = $material->uploadArticle($article);
		trace($ret);
		//    "thumb_media_id":"qI6_Ze_6PtV7svjolgs-rN6stStuHIjs9_DidOHaj0Q-mwvBelOXCFZiq2OsIU-p",
		//     "author":"xxx",
		//      "title":"Happy Day",
		//      "content_source_url":"www.qq.com",
		//      "content":"content",
		//      "digest":"digest",
		//     "show_cover_pic":0

		$ret = $broadcast->previewNews($ret['media_id'], $openId);
		// return $news;
		// $ret = $broadcast->previewNews($news, $openId);
		return $ret;
		// } catch (\Exception $e) {
		//     return $e->getMessage();
		// }
	}

	public function content_fix($content)
	{
		$img_tags = array();
		preg_match_all('/<img\s.*?>/', $content, $img_tags);
		$original_src_list = []; //原图链接
		foreach ($img_tags[0] as $k => $v) {
			//单引号转双引号
			$v = str_replace("'", '"', $v);
			$tmp = explode(' src="', $v);
			$tmp = explode('"', $tmp[1]);
			if (!in_array($tmp[0], $original_src_list)) {
				array_push($original_src_list, $tmp[0]);
			}
		}

		$new_src_list = array();
		foreach ($original_src_list as $k => $v) {
			if (substr($v, 0, 7) == "http://") {
				//其他来源的图片下载 并上传
				$filepath = $this->downloadimg($v);
				$ret = $this->postMediaImg($filepath);
				if (!empty($ret['url'])) {
					$new_src_list[$k] = $ret['url'];
				} else {
					$new_src_list[$k] = '';
				}
			}
		}
		//原文内容替换
		foreach ($new_src_list as $k => $v) {
			if (!empty($v)) {
				$content = str_replace($original_src_list[$k], $v, $content);
			}
		}
		// alert($content);
		return $content;
	}

	public function postMediaImg($pic)
	{
		$material = $this->app->material;
		$result = $material->uploadImage($pic);
		$result = json_decode($result, 1);
		return $result;
	}

	/**
	 * 下载文件并保存到本地  同时返回本地路径 相对根目录 如: /Public/uploads/download/dd.jpg
	 * Enter description here ...
	 * @param $url
	 */
	public function downloadimg($url)
	{
		//文件后缀名
		$arr = explode('.', $url);
		$type = end($arr);
		$file_name = date("YmdHis") . mt_rand(1000, 9999) . '.' . $type;
		//创建下载目录
		if (!file_exists('./public/uploads/downloadimg')) {
			mkdir('./public/uploads/downloadimg', 0777, true);
		}
		$file_path = 'public/uploads/downloadimg/' . $file_name;
		//$img = file_get_contents($url);
		$img = file_get_contents($url);
		file_put_contents($file_path, $img);
		return $file_path;
	}

	public function description()
	{
		return <<<HTML
*{
	padding: 0;
	margin: 0;
}
header{
	border: 1px solid;
	border-radius: 50px;
	margin: 20px;
	font-weight: 900;
}
nav{
	background-color: #000000;
	color: white;
}
nav a{
	color: white;
}
.box{
	overflow: hidden;
}
#box1{
	float: left;
}
#box2{
	float: right;
}
<div>
	<link rel="stylesheet" href="https://www.v2ex.com/static/css/style.css?v=416609f46253c81f1226585249e3d16f">
	<header style="color: red; font-size: 18px; text-align: center;">图文消息</header>
	<section>
		<div>
			<sapn style="background: url('http://www.weiwoju.com/Public/www/index_v20161209/image/wwjlogo.png') no-repeat; display: block; height: 60px; position: absolute; right: 0; width: 120px;"></sapn><img src="http://www.weiwoju.com/Public/www/index_v20161209/image/wwjlogo.png" alt=""></div>
		<nav><a href="/html/" style="font-style: normal; text-decoration: none;">HTML</a> | <a href="/css/" style="font-style: normal; text-decoration: none;">CSS</a>			| <a href="/js/" style="font-style: normal; text-decoration: none;">JavaScript</a> | <a href="/jquery/" style="font-style: normal; text-decoration: none;">jQuery</a>			</nav>
		<div>
			<div id="box1">left</div>
			<div id="box2">right</div>        </div>        <div>            <ul><li style=" line-height: 35px; list-style:
			 none; "><i style="background-color: #ff8c02; border-radius: 50%; color: white; display: block; float: left; font-style:
			 normal; height: 25px; line-height: 25px; text-align: center; width: 25px;
			 ">1</i>.测试微信支持标签</li>                <li style="line-height: 35px; list-style: none; "><i style="background-color: #ff8c02; border-radius: 50%; color: white; display:
			 block; float: left; font-style: normal; height: 25px; line-height: 25px; text-align: center; width: 25px;
			 ">2</i>.测试微信支持样式</li>                <li style="line-height: 35px; list-style: none; "><i style="background-color: #ff8c02; border-radius: 50%; color: white; display:
			 block; float: left; font-style: normal; height: 25px; line-height: 25px; text-align: center; width: 25px;
			 ">3</i>.测试微信支持内容</li>                <li style="line-height: 35px; list-style: none; "><i style="background-color: #ff8c02; border-radius: 50%; color: white; display:
			 block; float: left; font-style: normal; height: 25px; line-height: 25px; text-align: center; width: 25px;
			 ">4</i>.测试微信支持图片</li>                <li style="line-height: 35px; list-style: none; "><i style="background-color: #ff8c02; border-radius: 50%; color: white; display:
			 block; float: left; font-style: normal; height: 25px; line-height: 25px; text-align: center; width: 25px;
			 ">5</i>.测试微信支持事件</li>                <li style="line-height: 35px; list-style: none; "><i style="background-color: #ff8c02; border-radius: 50%; color: white; display:
			 block; float: left; font-style: normal; height: 25px; line-height: 25px; text-align: center; width: 25px;
			 ">6</i>.测试微信支持媒体</li>            </ul><ol start="01
			 "><li>呵呵</li>                <li>哈哈</li>                <li>嘿嘿</li>            </ol></div>    </section><table border="1
			 "><caption>测试1</caption>        <tbody><tr><th>方法1</th>            <th>方法2</th>        </tr><tr><td>css测试</td>            <td>标签测试</td>        </tr></tbody></table><article><h3>表单填写</h3>        <form>            <input type="text "><input type="radio "><label>爱好</label>           <br><input type="checkbox " name="vehicle " value="Bike
			 "><label>游戏</label>            <br><input type="checkbox " name="vehicle " value="Car "><label>睡觉</label>            <select><option value="volvo
			 ">Volvo</option><option value="saab ">Saab</option><option value="opel ">Opel</option><option value="audi
			 ">Audi</option></select><select><option value="volvo ">Volvo</option><option value="saab ">Saab</option><option value="opel ">Opel</option><option value="audi
			 ">Audi</option></select><textarea rows="10 " cols="30 ">           留言            </textarea><button>留言</button>            <input type="button " value="取消
			 "><input type="submit "></form>        <a href="http://baidu.com " style="font-style: normal; text-decoration: none;
			 ">阅读手册</a>    </article><footer style="color: #ccc; ">微信图文消息<br><hr></footer>
</div>
HTML;
	}

	public function get_client_qrcode($client_id){
		$app    = $this->app2;
		$result = $app->qrcode->forever("client_{$client_id}");// 或者 $app->qrcode->forever("foo");
        // ptrace($result);
        $url = $app->qrcode->url($result['ticket']);
        return $url;
	}
}
