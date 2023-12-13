<?php
namespace app\index\job;

use app\common\model\SukaRecord;
use app\common\model\SukaShareimg;
use think\queue\Job;

class SukaJob extends BaseJob
{

	public function fire(Job $job, $data){
		$in = sprintf('%s 正在处理任务', datetime());
		extract($data);
		$img = file_get_contents($pic_url);
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
		$this->log($in);
		$job->delete();
		$this->log('处理结束');
	}

	public function test(Job $job, $data){
		echo 'in job';
		ptrace($data);
		$job->delete();
	}
}
