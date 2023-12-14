<?php
namespace app\index\home;

use app\common\controller\Common;
use think\facade\Request;
class Error extends Common
{

    /* 空操作，用于输出404页面 */
    public function __call($method, $args)
    {
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
                if (is_numeric(input('year', '')) && is_numeric(input('month', ''))) {
                    return $Index->archive(input('year', ''), input('month', ''));
                }
                $action = strtolower($this->request->controller());
                return $Index->$action();
                break;
        }
    }
}
