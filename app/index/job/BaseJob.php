<?php
namespace app\index\job;

class BaseJob
{
    public $config;

    public function __construct()
    {
        config('default_return_type', 'json');
        $this->config = config('mainten');
    }

    // 报警
    public function alert($msg, $type = 'all')
    {
        if ($msg == '') {
            return ['code' => 0, 'msg' => '信息为空'];
        }
        switch ($type) {
            case 'tel':
                // TODO
                break;
            case 'email':
                // TODO
                break;
            case 'ptrace':
                error($msg, 'queue_fail');
                return ptrace($msg);
                break;
            default:
                $types = ['ptrace'];
                foreach ($types as $key => $type) {
                    $ret = $this->alert($msg, $type);
                }
                return '';
                break;
        }
    }

    /**
     * @param $jobObject   \think\queue\Job   //任务对象，保存了该任务的执行情况和业务数据
     * @return bool     true                  //是否需要删除任务并触发其failed() 方法
     */
    public function logAllFailedQueues(&$jobObject)
    {
        // 这个只有在设置了 队列尝试次数，然后 执行尝试次数+1次后失败会进来
        $rawBody      = $jobObject->getRawBody();
        $rawBodyArr   = json_decode($rawBody, 1);
        $failedJobLog = [
            'jobHandlerClassName' => $jobObject->getName(), // 'application\index\job\Hello'
            'queueName'           => $jobObject->getQueue(), // 'helloJobQueue'
            'jobData'             => $rawBodyArr['data'], // '{'a': 1 }'
            'attempts'            => $jobObject->attempts(), // 3
        ];
        $log_msg = sprintf("在队列 %s 里 执行任务 %s 失败了%d次 ，数据%s ,最后执行时间 %s",
            $failedJobLog['queueName'],
            $failedJobLog['jobHandlerClassName'],
            $failedJobLog['attempts'],
            json_encode($failedJobLog['jobData'], JSON_UNESCAPED_UNICODE),
            datetime()
        );
        trace($log_msg);
        switch ($failedJobLog['jobHandlerClassName']) {
            case 'test':
                # code...
                break;
            default:
                $this->alert($log_msg);
                return true;
                break;
        }

        //$jobObject->release();     //重发任务
        //$jobObject->delete();         //删除任务
        //$jobObject->failed();      //通知消费者类任务执行失败

        return true;
    }

    // 格式化输出json
    public function pretty_json($data){
    	if(is_string($data)){
    		return $data;
    	}else{
    		return json_encode($data, JSON_PRETTY_PRINT);
    	}
    }

    public function log($content)
    {
    	// dlog($content);
        return file_put_contents(APP_PATH . '../queue.log', $content . PHP_EOL, FILE_APPEND);
    }

    /**
     * 添加队列任务
     *
     * @param string $job_name 队列执行的类路径 不带走类fire方法 带@方法 走类@的方法
     * @param array $data 传入数据
     * @param mixed $queue_name 队列名 null 或字符串
     * @param integer $delay  延迟执行的时间  单位秒
     * @return void
     */
    public function push_job($job_name, $data, $queue_name = null, $delay = 0)
    {
        // trace('queue_name:'.$queue_name);
        config('default_return_type', 'json');
        $class_name = \strstr($job_name, '@', true);
        // if (class_exists($class_name)) {
            if ($delay > 0) {
                $ret = \think\Queue::later($delay, $job_name, $data, $queue_name);
            } else {
                trace($job_name);
                $ret = \think\Queue::push($job_name, $data, $queue_name);
            }
            trace(sprintf("加入任务%s, 时间%s", $job_name, datetime()));
            return $ret;
        // }
        // return $this->alert('job类 ' . $job_name . '不存在');
    }

    public function post($url, $data = null, $type = 'array')
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //协议头 https，curl 默认开启证书验证，所以应关闭
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 获取数据返回
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); //强制ipv4解析
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true); // 在启用 CURLOPT_RETURNTRANSFER 时候将获取数据返回
        if ($type == 'array') {
            if (!$data) {
                $data = [];
            }
            $data = http_build_query($data);
        } else {
            if ($data) {
                $data = json_encode($data);
            } else {
                $data = '';
            }
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $ret = curl_exec($ch);
        if (curl_errno($ch)) {
            $err_code = curl_errno($ch);
            $err_msg  = curl_error($ch);
            curl_close($ch);
            throw new \Exception("curl错误:" . $err_code . ',' . $err_msg);
        }
        curl_close($ch);
        return $ret;
    }
}
