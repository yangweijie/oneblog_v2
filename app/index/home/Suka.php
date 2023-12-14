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

use app\admin\model\Attachment as AttachmentModel;
use app\common\builder\ZBuilder;
use app\common\model\SukaRecord;
use app\common\model\SukaShareimg;
use app\index\job\BaseJob;
use think\File;
use think\Hook;
use think\Image;
use think\Cache;
use think\Db;
use think\Loader;
use GuzzleHttp\Client;
use think\helper\Hash;

/**
 * 前台首页控制器
 * @package app\index\controller
 */
class Suka extends Home
{
	public function _initialize(\think\Request $request = null)
	{
		// config('dispatch_success_tmpl',APP_PATH . 'oxygen/view/mobile_jump.tpl');
		// config('dispatch_error_tmpl', APP_PATH . 'oxygen/view/mobile_jump.tpl');
		// if(!defined('DOMAIN')){
		//     define('DOMAIN', config('web_site_domain'));
		// }
		// if(!defined('UID')){
		//     define('UID', 1);
		// }
		// parent::_initialize();
		// debug('api_begin');
		// $this->ip = get_client_ip(0,1);
		// $this->cookie_expire = time() + 86400 * 7;
		// $this->openid = cookie('openid') ?: '';
		// // dlog($this->openid);
		// // debug
		// $this->wxuid       = Wxuser::get_uid($this->openid)?:0;
		// // $this->wxuid       = 4;
		// $this->user        = Wxuser::get($this->wxuid);
		// $this->current_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		// // trace($this->config);
		// $this->assign('openid', $this->openid);
		// $this->assign('uid', $this->wxuid);
		// $this->assign('config', $this->config);
		// $this->assign('user', $this->user);
		// $this->assign('is_weixin', is_weixin() ? '1' : '0');
		// $this->assign('back_url', 'javascript:history.back(-1);');
		// $this->assign('cookie_prefix', config('cookie.prefix'));
		// $this->assign('_home_base_layout', APP_PATH . 'oxygen/view/user/layout.html');
		// // dlog(is_weixin());
		// if (is_weixin()) {
		//     // dlog(strtolower(request()->controller()));
		//     if ('weixin' !== strtolower(request()->controller())) {
		//         $this->is_auth(); // 微信授权登录
		//     }
		// }
	}

	/**
	 * 用户登录检测
	 * @author jry <598821125@qq.com>
	 */
	protected function is_auth()
	{
		if (empty($this->openid) || empty($this->wxuid)) {
			cookie('openid', null);
			$this->wechat();
		} else {
			return $this->openid;
		}
	}

	public function wechat()
	{
		$cookie_time = $this->cookie_expire;
		if (stripos($this->current_url, 'auth_back') === false && $this->request->action() != 'weixin') {
			cookie('target_url', $this->current_url, $cookie_time);
		}
		$back_url = home_url('index/weixin/auth_back', null, false, DOMAIN);
		$weixin = new Weixin(self::$user_appid);
		return $weixin->auth($back_url, 'snsapi_userinfo');
	}

	public function index($uid = 0)
	{
		if (!$uid) {
			$this->error('未知用户访问');
		}
		// 后台公共模板
		$this->assign('_admin_base_layout', config('admin_base_layout'));
		// 当前配色方案
		$this->assign('system_color', config('system_color'));
		$data = SukaRecord::where('wxuid', $uid)->field([
			'id',
			'DATE_FORMAT(create_time, "%Y-%m-%d")' => 'date',
			'tizhong'                 ,
			'tizhilv'                 ,
			'zhifangliang'            ,
			'neizangzhifangzhishu'    ,
			'shuifenzhong'            ,
			'jirouliang'              ,
			'danbaizhi'               ,
			'guzhi'                   ,
			'shentizhuangtaizhishu'   ,
		])
			->group('DATE_FORMAT(create_time, "%Y-%m-%d")')
			->order('create_time ASC')->select();
		return ZBuilder::make('table')
			->setPageTitle('体重称重历史报表') // 设置页面标题
			// ->setPrimaryKey('id')
			->hideCheckbox()
			->setTableName('suka_record')
			->addColumns([ // 批量添加数据列
				['id',     '序号'],
				['date',   '日期'],
				['tizhong',     '体重' ,'text.edit'],
				['tizhilv',    '体脂率','text.edit'],
				['zhifangliang',     '脂肪', 'text.edit'],
				['neizangzhifangzhishu',   '内脏脂肪', 'text.edit'],
				['shuifenzhong',     '水分', 'text.edit'],
				['jirouliang',     '肌肉', 'text.edit'],
				['danbaizhi',    '蛋白质', 'text.edit'],
				['guzhi',     '骨质', 'text.edit'],
				['shentizhuangtaizhishu', '身体状态指数', 'text.edit'],
			])
			->setRowList($data) // 设置表格数据
			->fetch();
		// $img = file_get_contents('http://mmbiz.qpic.cn/mmbiz_jpg/pHU2pkfhF3UpGkglh6m9fP62B8hiaeOibicXLHhGuPDhUQkgU3xuJMs2b9bLNnvmuI0mLVMy4icIFoIuABSI7DzYrg/0');
		// $ret = plugin_action('BaiduAi', 'Ocr', 'custom', [$img, 'cda4584d2772948331e46956da9f6ee9',]);
		//     trace($ret);
		// return json($ret);
	}

	public function upload($dir = 'images', $from = 'weapp', $module = 'suka')
	{
		config('default_return_type', 'json');
		define('O2APIERROR', 'o2apierror');
		define('UID', 1);
		define('SUCCESS_MSG', '');

		// 附件大小限制
		$size_limit = $dir == 'images' ? config('upload_image_size') : config('upload_file_size');
		$size_limit = $size_limit * 1024;
		// 附件类型限制
		$ext_limit = $dir == 'images' ? config('upload_image_ext') : config('upload_file_ext');
		$ext_limit = $ext_limit != '' ? parse_attr($ext_limit) : '';
		// 缩略图参数
		$thumb = $this->request->post('thumb', '');
		// 水印参数
		$watermark = $this->request->post('watermark', '');

		// 获取附件数据
		$callback = '';

		$file_input_name = 'file';
		$file = $this->request->file($file_input_name);
		// 判断附件是否已存在
		if ($file_exists = AttachmentModel::get(['md5' => $file->hash('md5')])) {
			if ($file_exists['driver'] == 'local') {
				$file_path = PUBLIC_PATH . $file_exists['path'];
			} else {
				$file_path = $file_exists['path'];
			}

			return json([
				'code' => 1,
				'info' => '上传成功',
				'class' => 'success',
				'id' => $file_exists['id'],
				'path' => $file_path,
			]);
		}

		// 判断附件大小是否超过限制
		if ($size_limit > 0 && ($file->getInfo('size') > $size_limit)) {
			return json([
				'code' => 0,
				'class' => 'danger',
				'info' => '附件过大',
			]);
		}

		// 判断附件格式是否符合
		$file_name = $file->getInfo('name');
		$file_ext = strtolower(substr($file_name, strrpos($file_name, '.') + 1));
		$error_msg = '';
		if ($ext_limit == '') {
			$error_msg = '获取文件信息失败！';
		}
		if ($file->getMime() == 'text/x-php' || $file->getMime() == 'text/html') {
			$error_msg = '禁止上传非法文件！';
		}
		if (preg_grep("/php/i", $ext_limit)) {
			$error_msg = '禁止上传非法文件！';
		}
		if (!preg_grep("/$file_ext/i", $ext_limit)) {
			$error_msg = '附件类型不正确！';
		}

		if ($error_msg != '') {
			return json([
				'code' => 0,
				'class' => 'danger',
				'info' => $error_msg,
			]);
		}

		// 附件上传钩子，用于第三方文件上传扩展
		if (config('upload_driver') != 'local') {
			$hook_result = Hook::listen('upload_attachment', $file, ['from' => $from, 'module' => $module], true);
			if (false !== $hook_result) {
				return $hook_result;
			}
		}

		// 移动到框架应用根目录/uploads/ 目录下
		$info = $file->move(config('upload_path') . DS . $dir);
		if ($info) {
			// 缩略图路径
			$thumb_path_name = '';
			// 图片宽度
			$img_width = '';
			// 图片高度
			$img_height = '';
			if ($dir == 'images') {
				$img = Image::open($info);
				$img_width = $img->width();
				$img_height = $img->height();
				// 水印功能
				if ($watermark == '') {
					if (config('upload_thumb_water') == 1 && config('upload_thumb_water_pic') > 0) {
						$this->create_water($info->getRealPath(), config('upload_thumb_water_pic'));
					}
				} else {
					if (strtolower($watermark) != 'close') {
						list($watermark_img, $watermark_pos, $watermark_alpha) = explode('|', $watermark);
						$this->create_water($info->getRealPath(), $watermark_img, $watermark_pos, $watermark_alpha);
					}
				}

				// 生成缩略图
				if ($thumb == '') {
					if (config('upload_image_thumb') != '') {
						$thumb_path_name = $this->create_thumb($info, $info->getPathInfo()->getfileName(), $info->getFilename());
					}
				} else {
					if (strtolower($thumb) != 'close') {
						list($thumb_size, $thumb_type) = explode('|', $thumb);
						$thumb_path_name = $this->create_thumb($info, $info->getPathInfo()->getfileName(), $info->getFilename(), $thumb_size, $thumb_type);
					}
				}
			}

			// 获取附件信息
			$file_info = [
				'uid'    => 1,
				'name'   => $file->getInfo('name'),
				'mime'   => $file->getInfo('type'),
				'path'   => 'uploads/' . $dir . '/' . str_replace('\\', '/', $info->getSaveName()),
				'ext'    => $info->getExtension(),
				'size'   => $info->getSize(),
				'md5'    => $info->hash('md5'),
				'sha1'   => $info->hash('sha1'),
				'thumb'  => $thumb_path_name,
				'module' => $module,
				'width'  => $img_width,
				'height' => $img_height,
			];

			// 写入数据库
			if ($file_add = AttachmentModel::create($file_info)) {
				$file_path = PUBLIC_PATH . $file_info['path'];
				return json([
					'code'  => 1,
					'info'  => '上传成功',
					'class' => 'success',
					'id'    => $file_add['id'],
					'path'  => $file_path,
				]);
			} else {
				return json(['code' => 0, 'class' => 'danger', 'info' => '上传失败']);
			}
		} else {
			return json(['code' => 0, 'class' => 'danger', 'info' => $file->getError()]);
		}
	}

	// 异步图片识别
	public function rec_report_async($attachment_id, $pic_url, $wxuid){
		set_time_limit(0);
		$img = file_get_contents($pic_url);
		config('app_debug', 0);
		config('app_trace', 0);
		// config('default_return_type', 'json');
		echo 'ok';
		fastcgi_finish_request();
		trace([$attachment_id, $pic_url, $wxuid]);
		trace($img);
		$rec = SukaShareimg::where('attachment_id', $attachment_id)->value('result') ?: '';
		if (!$rec) {
			$ret = plugin_action('BaiduAi', 'Ocr', 'custom', [$img, 'cda4584d2772948331e46956da9f6ee9']);
			trace($ret);
			if ($ret['error_code'] == 0) {
				SukaRecord::insert_from_words($ret['data']['ret'], $attachment_id, $wxuid);
			}
		}
		return 'ok';
	}

	// 识别图片内容
	public static function rec_report($attachment_id, $pic_url, $wxuid)
	{
		$url = url('suka/rec_report_async', [], false, true);
		trace($url);
		trace(func_get_args());
		$ret = curl_post_async($url, ['attachment_id'=>$attachment_id, 'pic_url'=>$pic_url, 'wxuid'=>$wxuid]);
		// trace($ret);
		return $ret;
	}

	public function mock()
	{
		try {
			$client = new Client([
				// 'base_uri' => 'http://httpbin.org',
				'timeout'  => 30,
			]);
			$url = url('suka/rec_report_async', [], false, true);
			trace($url);
			$promise = $promise = $client->postAsync($url, [
				'form_params' => [
					'attachment_id' => 37,
					'pic_url'       => '/uploads/images/20181003/5de53fe22848c28dd0faff4d828dc614.jpg',
					'wxuid'         => 1,
				]
			]);
			$promise->then(
			    function (ResponseInterface $res) {
			        echo $res->getStatusCode() . "\n";
			    },
			    function (RequestException $e) {
			        echo $e->getMessage() . "\n";
			        echo $e->getRequest()->getMethod();
			    }
			);
			$promise->wait();
			// trace($ret);
		} catch (\Exception $e) {
			trace($e->getMessage().PHP_EOL.$e->getTraceAsString());
		}
		return 'done';
  //   	$baseJob                    = new BaseJob();
		// try {
		// 	$ret = $baseJob->push_job('app\index\job\TestJob@test', [
		// 		'id'      => 1,
		// 	],
		// 	'default');
		// 	$error = '';
		// } catch (\Exception $e) {
		// 	trace("加入任务失败".PHP_EOL.$e->getMessage().PHP_EOL.$e->getTraceAsString());
		// 	$error = '失败';
		// }
		// if($error){
		// 	$this->error('加入队列失败，请联系开发');
		// }
		// return $ret;
		// $ret = \app\index\controller\Suka::rec_report(29, config('upload_path').DS.'temp/xa_np7Vi9.jpg', 1);
		// halt($ret);
		// $img = file_get_contents('http://mmbiz.qpic.cn/mmbiz_jpg/pHU2pkfhF3UpGkglh6m9fP62B8hiaeOibicXLHhGuPDhUQkgU3xuJMs2b9bLNnvmuI0mLVMy4icIFoIuABSI7DzYrg/0');
		// $data = plugin_action('BaiduAi', 'Ocr', 'custom', [$img, 'cda4584d2772948331e46956da9f6ee9',]);
		// trace($data);
		// session('uid', 1);
		// SukaRecord::insert_from_words($data['data']['ret'], 27);
	}

	 /**
     * 快速编辑
     * @param array $record 行为日志内容
     * @author 蔡伟明 <314013107@qq.com>
     */
    public function quickEdit($record = [])
    {
        $field           = input('post.name', '');
        $value           = input('post.value', '');
        $type            = input('post.type', '');
        $id              = input('post.pk', '');
        $validate        = input('post.validate', '');
        $validate_fields = input('post.validate_fields', '');

        $field == '' && $this->error('缺少字段名');
        $id    == '' && $this->error('缺少主键值');

        $Model = $this->getCurrModel();
        $protect_table = [
            '__ADMIN_USER__',
            '__ADMIN_ROLE__',
            config('database.prefix').'admin_user',
            config('database.prefix').'admin_role',
        ];

        // 验证是否操作管理员
        if (in_array($Model->getTable(), $protect_table) && $id == 1) {
            $this->error('禁止操作超级管理员');
        }

        // 验证器
        if ($validate != '') {
            $validate_fields = array_flip(explode(',', $validate_fields));
            if (isset($validate_fields[$field])) {
                $result = $this->validate([$field => $value], $validate.'.'.$field);
                if (true !== $result) $this->error($result);
            }
        }

        switch ($type) {
            // 日期时间需要转为时间戳
            case 'combodate':
                $value = strtotime($value);
                break;
            // 开关
            case 'switch':
                $value = $value == 'true' ? 1 : 0;
                break;
            // 开关
            case 'password':
                $value = Hash::make((string)$value);
                break;
        }

        // 主键名
        $pk     = $Model->getPk();
        $result = $Model->where($pk, $id)->setField($field, $value);

        cache('hook_plugins', null);
        cache('system_config', null);
        cache('access_menus', null);
        if (false !== $result) {
            // 记录行为日志
            if (!empty($record)) {
                call_user_func_array('action_log', $record);
            }
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

        /**
     * 获取当前操作模型
     * @author 蔡伟明 <314013107@qq.com>
     * @return object|\think\db\Query
     */
    final protected function getCurrModel()
    {
        $table_token = input('param._t', '');
        $module      = $this->request->module();
        $controller  = parse_name($this->request->controller());

        $table_token == '' && $this->error('缺少参数');
        !session('?'.$table_token) && $this->error('参数错误');

        $table_data = session($table_token);
        $table      = $table_data['table'];

        $Model = null;
        if ($table_data['prefix'] == 2) {
            // 使用模型
            try {
                $Model = Loader::model($table);
            } catch (\Exception $e) {
                $this->error('找不到模型：'.$table);
            }
        } else {
            // 使用DB类
            $table == '' && $this->error('缺少表名');
            if ($table_data['module'] != $module || $table_data['controller'] != $controller) {
                $this->error('非法操作');
            }

            $Model = $table_data['prefix'] == 0 ? Db::table($table) : Db::name($table);
        }

        return $Model;
    }
}
