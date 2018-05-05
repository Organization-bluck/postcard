<?php

namespace app\wap\controller;

use service\FileService;
use think\Db;
use think\Exception;


class Index extends Base
{

    public function index()
    {
        return view();
    }

    public function uploadRecode()
    {
        try{
            $serviceId = input('post.serverId/s');
            if(!$serviceId) {
                throw new Exception('信息不存在');
            }
            $source_info = load_wechat('media')->getMedia($serviceId);
            $file_info = FileService::qiniu($this->fansinfo['nickname'].uniqid().'.amr', $source_info);
            if(isset($file_info['url'])) {
                if(!Db::table('post_media')->insert([
                    'openid'    => $this->openid,
                    'file_url'  => $file_info['url']
                ])) {
                    throw new Exception('录音添加失败');
                }
            }
            $this->success('操作成功', '', $file_info['url']);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
