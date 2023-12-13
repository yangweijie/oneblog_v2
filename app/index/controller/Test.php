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

use app\common\builder\ZBuilder;

use think\{Cache,Db,File,Hook,Loader};
use think\helper\Hash;
use GuzzleHttp\Client;
use app\journal\home\Cron;

use util\Http;

/**
 * 前台首页控制器
 * @package app\index\controller
 */
class Test extends Home
{
	public function index($max = 100)
	{
		$data_list = [];
		for ($i = 0; $i < $max; $i++) {
			$index = $i+1;
			$data_list[] = [
				'name'=>"名称_{$index}",
				'title'=>'标题',
			];
		}
		// 后台公共模板
		$this->assign('_admin_base_layout', config('admin_base_layout'));
		// 当前配色方案
		$this->assign('system_color', config('system_color'));
		$this->assign('_message', 0);

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('配置管理') // 设置页面标题
            ->addColumns([ // 批量添加数据列
                ['name', '名称'],
                ['title', '标题'],
                ['title', '标题'],
                ['title', '标题'],
                ['title', '标题'],
                ['title', '标题'],
                ['title', '标题'],
                ['title', '标题'],
                ['title', '标题'],
            ])
            ->setRowList($data_list) // 设置表格数据
            ->fetch(); // 渲染模板
	}

	public function journal(){
		$cron = new Cron;
		$cron->execute();
	}

	public function pizhi_wexin_server(){
		$url = 'http://fj.pizhigu.com/index.php/pizhi/weixin/server?signature=fc97ea4858c3cfa907b851d51f34e9eb55f26ee1×tamp=1616486215&nonce=44697503&openid=oCIz-0jIHUwpEDrzOaf9-GcNCH10&encrypt_type=aes&msg_signature=6aa8842feeab8e81df3b54ad8f202015b62aacc6';
		$post = <<<XML
<xml>
    <ToUserName><![CDATA[gh_783a0057c826]]></ToUserName>
    <FromUserName><![CDATA[oCIz-0jIHUwpEDrzOaf9-GcNCH10]]></FromUserName>
    <CreateTime>1616486215</CreateTime>
    <MsgType><![CDATA[event]]></MsgType>
    <Event><![CDATA[SCAN]]></Event>
    <EventKey><![CDATA[plat_user_1]]></EventKey>
    <Ticket><![CDATA[gQFK8DwAAAAAAAAAAS5odHRwOi8vd2VpeGluLnFxLmNvbS9xLzAyRmVnS3daOGNmLWkxMDAwMHcwN18AAgTrSFlgAwQAAAAA]]></Ticket>
</xml>
XML;
		$option['headers'] = ['Content-Type'=>'text/xml'];
		$ret = Http::post($url, $post, $option);
		halt($ret);
	}

	public function upload_pic(){
		// $repositories = $client->api('user')->repositories('ornicar');
		// halt($repositories);

		// Create a blob
		$file       = 'static/home/img/no-cover.png';
		// halt(sha1_file($file));
		$img        = file_get_contents($file);
		$img_base64 = base64_encode($img);
		// halt($img_base64);
		$client     = new \Github\Client();
		// $repos = $client->api('user')->repositories('yangweijie');
		// // halt($repos);
		// return json($repos);
		// yangweijie/photo_album
		// 5870b6be26b3cfcba350d624265ba9e79c945641
		$blob = $client->api('gitData')->blobs()->create('yangweijie', 'blog1', ['content' => $img_base64, 'encoding' => 'base64']);
		return json($blob);
		// $blob = $client->api('gitData')->blobs()->show('yangweijie', 'php-photo_album-api', '5870b6be26b3cfcba350d624265ba9e79c945641');
	}
}