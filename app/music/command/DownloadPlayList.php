<?php
declare (strict_types = 1);

namespace app\music\command;

use app\music\model\Musics;
use app\music\model\Playlist;
use think\console\input\Option;

class DownloadPlayList extends \think\admin\service\QueueService
{
    protected function configure()
    {
        // 指令配置
        $this->setName('playlist:down')
            ->setDescription('下载最新歌单歌曲')
            ->addOption('id', 'i', Option::VALUE_REQUIRED, '歌单id');
    }

    public function execute(array $data = [])
    {
        if(!defined('DS')){
            define('DS', DIRECTORY_SEPARATOR);
        }
        debug('begin');
        $id = $data['id']?:0;
        $playlist = Playlist::find($id);
        $musicAll = json_decode(file_get_contents($playlist['url']), true);
        $new = Musics::new($musicAll, $id);
        list($count, $total) = [0, count($new)];
        $dir = public_path().'uploads'.DS.'music'.DS.$id;
        if(!is_dir($dir)){
            mkdir($dir,0777, true);
        }
        foreach($new as $item){
            $count++;
            $name = "{$item['name']}-{$item['artist']}";
            $name = safe_name($name);
            $file = "{$name}.mp3";
            $cover = "{$name}.jpg";
            $lrc = "{$name}.lrc";
            file_put_contents($dir.DS.$file, file_get_contents($item['url']));
            file_put_contents($dir.DS.$cover, file_get_contents($item['pic']));
            file_put_contents($dir.DS.$lrc, file_get_contents($item['lrc']));
            $insert = Musics::create([
                'playlist_id'=>$id,
                'type'=>$playlist['type'],
                'name'=>$item['name'],
                'artist'=>$item['artist'],
                'url'=>$item['url'],
                'path'=>str_ireplace('\\', '/', $dir.DS.$file),
                'lrc'=>str_ireplace('\\', '/', $dir.DS.$lrc),
                'pic'=>str_ireplace('\\', '/', $dir.DS.$cover),
                'mp4'=>'',
            ]);
            $state = is_file($dir.DS.$file)?'成功':'失败';
            $this->progress(2, "下载歌单 {$id} {$item['name']} {$item['artist']} 歌曲{$state}");
            $this->message($total, $count, "下载歌单 {$id} {$item['name']} {$item['artist']} 歌曲{$state}");
        }
        debug('end');
        $this->progress(3, "下载 {$count} 个 {$id} 歌单的新歌！");
        // 指令输出
        $this->success('下载完成，共耗时'. debug('begin', 'end', 6). '秒');
    }
}
