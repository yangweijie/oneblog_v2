<?php
namespace app\index\job;

use think\queue\Job;
use app\queue\BaseJob;

class TestJob extends BaseJob
{
	public function execute(){
		$this->output = new \think\console\Output();
		$this->output->writeln('in job');
		$this->output->newLine();
		// echo 'in job'.PHP_EOL;
		trace($this->data);
		return true;
	}
}
