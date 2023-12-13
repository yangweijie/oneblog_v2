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

use util\Http;
use Symfony\Component\DomCrawler\Crawler;
use app\admin\model\Attachment as AttachmentModel;
use plugins\Lol\model\Job;
use plugins\Lol\model\Race;
use plugins\Lol\model\Heros;
use plugins\Lol\model\Weapons;
use think\App;
use think\Image;

/**
 * 插件控制器
 * @package app\index\controller
 */
class Plugin extends Home
{

    public function __construct(App $app = null){
        $this->app     = $app ?: \think\Container::get('app');
        $this->request = $this->app['request'];
        $this->view    = $this->app['view'];
        if(!defined('DS')){
            define('DS', DIRECTORY_SEPARATOR);
        }
    }
    /**
     * 执行插件内部方法
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function execute()
    {
        $plugin     = input('param._plugin');
        $controller = input('param._controller');
        $action     = input('param._action');
        $params     = $this->request->except(['_plugin', '_controller', '_action'], 'param');

        if (empty($plugin) || empty($controller) || empty($action)) {
            $this->error('没有指定插件名称、控制器名称或操作名称');
        }

        if (!plugin_action_exists($plugin, $controller, $action)) {
            $this->error("找不到方法：{$plugin}/{$controller}/{$action}");
        }
        return plugin_action($plugin, $controller, $action, $params);
    }

    public function lol_scraw(){
    	set_time_limit(0);
        $jobs     = $this->scraw_job();
        $races    = $this->scraw_race();
        $races[0] = '';
        $this->scraw_weapons();
        $this->scraw_hero($jobs, $races);
    }

    public function scraw_hero($jobs, $races){
        $lol_path   = '../plugins/Lol';
        $heros_file = $lol_path.'/data/hero.json';
        $heros_data = file_get_contents($heros_file);
        $heross     = json_decode($heros_data, 1);

        foreach ($heross as $key => $heros) {
            if(!$exist = Heros::where('hero_id', $heros['heroId'])->find()){
                $remote_path = Heros::get_pic_remote($heros['heroId']);
                $tmp_dir     = './uploads/temp/';
                $temp        = $tmp_dir.time().'.png';
                file_put_contents($temp, file_get_contents($remote_path));
                $pic = self::remote_upload($temp);
                if($pic['code']){
                    halt($pic['error']);
                }else{
                    foreach ($heros['level'] as $key => $level) {
                        Heros::create([
                            'hero_id'         => $heros['heroId'],
                            'name'            => $heros['hero_name'],
                            'title'           => $heros['hero_tittle'],
                            'pic'             => $pic['id'],
                            'health'          => $level['health'],
                            'armor'           => $level['armor'],
                            'magic_res'       => $level['magic_res'],
                            'speed'           => $level['speed'],
                            'attack_distance' => $level['range'],
                            'dps'             => $level['dps'],
                            'mana'            => $level['Mana'],
                            'damage'          => $level['damage'],
                            'StartingMana'    => $level['StartingMana'],
                            'CR'              => $level['CR'],
                            'skill'           => [
                                'introduce' => $heros['skill_introduce'],
                                'name'      => $heros['skill_name'],
                                'type'      => $heros['skill_type']
                            ],
                            'price'     => $heros['price'],
                            'level'     => $level['name'],
                            'job'       => $jobs[$heros['job']],
                            'race'      => isset($heros['otherrace']) && $heros['otherrace']? "{$races[$heros['race']]},{$races[$heros['otherrace']]}":$races[$heros['race']],
                            'equipment' => $heros['equipment'],
                        ]);
                    }
                }
            }
        }
    }

    public function scraw_weapons(){
        $lol_path = '../plugins/Lol';
        $weapons_file = $lol_path.'/data/equipment.json';
        $weapons_data = file_get_contents($weapons_file);
        $weaponss = json_decode($weapons_data, 1);
        foreach ($weaponss as $key => $weapons) {
            if(!$exist = Weapons::get($weapons['equipmentId'])){
                $remote_path = Weapons::get_pic_remote($weapons['equipmentId']);
                $tmp_dir     = './uploads/temp/';
                $temp        = $tmp_dir.time().'.png';
                file_put_contents($temp, file_get_contents($remote_path));
                $pic = self::remote_upload($temp);
                if($pic['code']){
                    halt($pic['error']);
                }else{
                    Weapons::create([
                        'id'       => $weapons['equipmentId'],
                        'name'     => $weapons['eq_name'],
                        'pic'      => $pic['id'],
                        'effect'   => $weapons['eq_effect'],
                        'keywords' => $weapons['eq_keywords'],
                        'formula'  => $weapons['eq_formula'],
                    ]);
                }
            }
        }
    }

    public function scraw_race(){
        $lol_path  = '../plugins/Lol';
        $race_file = $lol_path.'/data/race.json';
        $race_data = file_get_contents($race_file);
        $races     = json_decode($race_data, 1);
        // $keys      = [];

        foreach ($races as $key => $race) {
            // $keys[] = $key+1;
            if(!$exist = Race::get($race['traitID'])){
                $remote_path = Race::get_pic_remote($race['race_name']);
                $tmp_dir = './uploads/temp/';
                $temp = $tmp_dir.time().'.png';
                file_put_contents($temp, file_get_contents($remote_path));
                $pic = self::remote_upload($temp);
                if($pic['code']){
                    halt($pic['error']);
                }else{
                    Race::create([
                        'id'        => $race['traitID'],
                        'name'      => $race['race_name'],
                        'introduce' => $race['introduce'],
                        'pic'       => $pic['id'],
                        'level'     => $race['level'],
                    ]);
                }
            }
        }
        return array_combine(array_keys($races), array_column($races, 'traitID'));
    }

    public function scraw_job(){
        $lol_path = '../plugins/Lol';
        $job_file = $lol_path.'/data/job.json';
        $job_data = file_get_contents($job_file);
        $jobs     = json_decode($job_data, 1);
        // $keys     = [];

        foreach ($jobs as $key => $job) {
            // $keys[] = $key+1;
            if(!$exist = Job::get($job['traitID'])){
                $remote_path = Job::get_pic_remote($job['job_name']);
                $tmp_dir     = './uploads/temp/';
                $temp        = $tmp_dir.time().'.png';
                file_put_contents($temp, file_get_contents($remote_path));
                $pic = self::remote_upload($temp);
                if($pic['code']){
                    halt($pic['error']);
                }else{
                    Job::create([
                        'id'        => $job['traitID'],
                        'name'      => $job['job_name'],
                        'introduce' => $job['introduce'],
                        'pic'       => $pic['id'],
                        'level'     => $job['level'],
                    ]);
                }
            }
        }
        return array_combine(array_keys($jobs), array_column($jobs, 'traitID'));
    }

    // 上传文件
    public function upload($dir = 'file', $from = 'web', $module = 'same_rhythm'){
                // 附件大小限制
        switch ($dir) {
            case 'images':
                $size_limit = 2048 * 1024;
                break;
            case 'file':
                $size_limit = 2048*1024*10;
                break;
            default:
                $size_limit = config('upload_file_size');
                break;
        }

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
        if(is_null($file)){
            return json([
                'code'  => 1,
                'class' => 'danger',
                'error'  => '上传文件为空',
            ]);
        }
        trace(strtolower($this->request->action()));
        // ptrace($file);

        // 判断附件是否已存在
        if ($file_exists = AttachmentModel::get(['md5' => $file->hash('md5')])) {
            if ($file_exists['driver'] == 'local') {
                $file_path = PUBLIC_PATH. $file_exists['path'];
            } else {
                $file_path = $file_exists['path'];
            }
            return json([
                'code'   => 0,
                'info'   => '上传成功',
                'class'  => 'success',
                'id'     => $file_exists['id'],
                'path'   => $file_path
            ]);
        }

        // 判断附件大小是否超过限制
        if ($size_limit > 0 && ($file->getInfo('size') > $size_limit)) {
            return json([
                'code'   => 44,
                'class'  => 'danger',
                'error'   => '附件过大'
            ]);
        }

        // 判断附件格式是否符合
        $file_name = $file->getInfo('name');
        $file_ext  = strtolower(substr($file_name, strrpos($file_name, '.')+1));
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
                'code'   => 44,
                'class'  => 'danger',
                'error'   => $error_msg
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
        if($info){
            // 缩略图路径
            $thumb_path_name = '';
            // 图片宽度
            $img_width = '';
            // 图片高度
            $img_height = '';
            if ($dir == 'images') {
                $img = Image::open($info);
                $img_width  = $img->width();
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
                $file_path = PUBLIC_PATH. $file_info['path'];
                return json([
                    'code'   => 0,
                    'info'   => '上传成功',
                    'class'  => 'success',
                    'id'     => $file_add['id'],
                    'path'   => $file_path,
                ]);
            } else {
                return json(['code' => 44, 'class' => 'danger', 'error' => '上传失败']);
            }
        }else{
            return json(['code' => 44, 'class' => 'danger', 'info' => $file->getError()]);
        }
    }

    // 远程上传
    public static function remote_upload($temp, $dir = 'images', $header = []){
        $formname = 'file';
        $param = [
            'dir'    => $dir,
        ];
        if(\stripos($temp, 'http') !== false){
            $tmp = tempnam(sys_get_temp_dir(), 'http_');
            file_put_contents($tmp, file_get_contents($temp));
            $md5 = \md5_file($tmp);
            $ext = pathinfo($temp, PATHINFO_EXTENSION);
            if(\stripos($ext, '?')!== false){
                $ext = strstr($ext, '?', true);
            }
            $local = config('upload_path') . DS . 'temp'.DS."{$md5}.{$ext}";
            \file_put_contents($local, \file_get_contents($temp));
            $temp = $local;
        }
        $url = home_url('index/plugin/upload', $param, false, true);
        $url = str_ireplace('https://', 'http://', $url);
        // dump($url);
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
        if ($header) {
            trace($header);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        $result = curl_exec($ch);
        trace($result);
        // dump($result);
        // ptrace($result);
        $ret_arr = json_decode($result, true);
        if(false === $ret_arr || null == $ret_arr){
            trace($url);
            trace($temp);
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



}
