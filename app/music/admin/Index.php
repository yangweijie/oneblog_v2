<?php
declare (strict_types = 1);

namespace app\music\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\music\model\Playlist;
use think\admin\service\QueueService;
use think\exception\HttpResponseException;

class Index extends Admin
{
    public function index()
    {
		cookie('__forward__', $_SERVER['REQUEST_URI']);

		// 获取查询条件
		$map = $this->getMap();

		// 数据列表
		$data_list = Playlist::where($map)->append([
//			'cid_link',
//			'title_link',
		])->order('id desc')->paginate();

		// 分页数据
		$page = $data_list->render();

		$btn_download = [
			'title' => '下载歌单',
			'icon'  => 'fa fa-times-circle-o',
			'class' => 'btn btn-primary ajax-get',
			'href'  => url('download', ['id'=>'__id__']),
		];

		$btn_mv = [
			'title' => '生成mv',
			'icon'  => 'fa fa-times-circle-o',
			'class' => 'btn btn-primary ajax-get',
			'href'  => url('mv', ['id'=>'__id__']),
		];

		// 使用ZBuilder快速创建数据表格
		return ZBuilder::make('table')
			->setPageTitle('歌单列表')// 设置页面标题
			->setTableName('Playlist')// 设置数据表名
			->setSearch(['title' => '标题', 'type'=>'来源','url'=>'链接'])// 设置搜索参数
			->addColumns([ // 批量添加列
				['id', 'ID'],
				['type', '类型'],
				['title', '名称'],
				['url', '链接'],
				['create_time', '执行时间', 'datetime', '', 'Y-m-d H:i:s'],
				['update_time', '更新时间', 'datetime', '', 'Y-m-d H:i:s'],
				['right_button', '操作', 'btn']
			])
			->addTopButton('delete')// 批量添加顶部按钮
//			->addTopButton('clear', $btn_clear) // 添加清空按钮
			->addRightButtons([
				'edit' => ['title' => '浏览',
					'href'=>url('music/music/index', [
					'_select_field'=>'playlist_id',
					'_select_value'=>'__id__'
				]),
			],
			])// 批量添加右侧按钮
			->addRightButton('download', $btn_download)
			->addRightButton('mv', $btn_mv)
			->addRightButton('delete')
			->setRowList($data_list)// 设置表格数据
			->setPages($page)// 设置分页数据
			->fetch(); // 渲染页面
    }

	public function download($id){
		try {
			QueueService::instance()->register('下载歌单', '\app\music\command\DownloadPlayList', 3, ['id'=>$id], 1, 0);
//            QueueService::instance()->register('测试任务', 'test', 3, ['total'=>2000], 1, 0);
			$this->success('添加测试任务成功');
		} catch (HttpResponseException $exception) {
			throw $exception;
		} catch (\Exception $exception) {
			$this->error($exception->getMessage());
		}
	}

	public function mv($id){
		try {
			QueueService::instance()->register('生成mv', '\app\music\command\MvBuild', 3, ['id'=>$id], 1, 0);
//            QueueService::instance()->register('测试任务', 'test', 3, ['total'=>2000], 1, 0);
			$this->success('添加测试任务成功');
		} catch (HttpResponseException $exception) {
			throw $exception;
		} catch (\Exception $exception) {
			$this->error($exception->getMessage());
		}
	}
}
