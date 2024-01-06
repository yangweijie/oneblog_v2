<?php
declare (strict_types = 1);

namespace app\music\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\music\model\Musics;
use app\music\model\Playlist;

class Music extends Admin
{
	public function index()
	{
		cookie('__forward__', $_SERVER['REQUEST_URI']);

		// 获取查询条件
		$map = $this->getMap();

		// 数据列表
		$data_list = Musics::where($map)->append([
			'playlist_cn',
			'info'
		])->order('id desc')->paginate();

		// 分页数据
		$page = $data_list->render();

		$btn_clear = [
			'title' => '清空日志',
			'icon'  => 'fa fa-times-circle-o',
			'class' => 'btn btn-primary ajax-get confirm',
			'data-title' => '真的要清除吗？',
			'href'  => url('clear')
		];
		$playlists = Playlist::order('id desc')->column('title', 'id');

		// 使用ZBuilder快速创建数据表格
		return ZBuilder::make('table')
			->setPageTitle('歌曲列表')// 设置页面标题
			->setTableName('Musics')// 设置数据表名
			->setSearch(['name' => '标题', 'type'=>'来源','url'=>'链接'])// 设置搜索参数
			->addColumns([ // 批量添加列
				['id', 'ID'],
				['type', '来源'],
				['playlist_cn', '歌单'],
				['info', '歌曲信息'],
//				['pic', '链接', 'img_url'],
				['create_time', '执行时间'],
				['update_time', '更新时间'],
				['right_button', '操作', 'btn']
			])
			->addTopButton('delete')// 批量添加顶部按钮
			->addTopSelect('playlist_id', '歌单', $playlists)
//			->addTopButton('clear', $btn_clear) // 添加清空按钮
			->addRightButtons([ 'delete'])// 批量添加右侧按钮
			->setRowList($data_list)// 设置表格数据
			->setPages($page)// 设置分页数据
			->fetch(); // 渲染页面
	}
}
