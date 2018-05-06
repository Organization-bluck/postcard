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

    public function luckdraw()
    {
        //奖项
        $luckdraw_list = Db::table('manager_luckdraw')->field('id, name, image, rank, percent')->select();
        $is_end = max(array_column($luckdraw_list, 'percent'));

        //判断用户今天是否抽过奖
        $this->assign('is_over', Db::table('manager_luckdraw_log')->where(['openid' => $this->openid, 'create_at' => ['egt', date('Y-m-d')]])->count());

        $this->assign('is_end', $is_end>100?0:1);//如果人数低于100,则结束抽奖活动
        $this->assign('lucklist', json_encode($luckdraw_list));
        return view();
    }

    public function isOkLuck()
    {
       try{
           $id = input('get.id/d');
           if(!$id) {
               throw new Exception('信息不存在');
           }
           if(Db::table('manager_luckdraw_log')->where(['openid' => $this->openid, 'create_at' => ['egt', date('Y-m-d')]])->count()) {
               throw new Exception('对不起，您已经抽过奖了');
           }
           //判断奖项是否存在名额
           if(Db::table('manager_luckdraw')->where(['id' => $id])->value('percent') <= 0) {
                $this->success('切换奖项', '', 1);
           }
           $this->success('成功');
       } catch (Exception $e) {
            $this->error($e->getMessage());
       }
    }

    public function ajaxLuckInfo()
    {
        try{
            $id = input('post.id/d');
            if(!$id) {
                throw new Exception('信息不存在');
            }
            if(Db::table('manager_luckdraw_log')->where(['openid' => $this->openid, 'create_at' => ['egt', date('Y-m-d')]])->count()) {
                throw new Exception('对不起，您已经抽过奖了');
            }
            if(!($luck_info = Db::table('manager_luckdraw')->where(['id' => $id])->find())) {
                throw new Exception('奖品信息不存在');
            }

            try{
                Db::startTrans();
                if(!Db::table('manager_luckdraw_log')->insert([
                    'openid'    => $this->openid,
                    'nickname'  => $this->fansinfo['nickname'],
                    'luck_id'   => $luck_info['id'],
                    'name'      => $luck_info['name'],
                ])) {
                    throw new Exception('添加奖品记录失败');
                }
                if(!Db::table('manager_luckdraw')->where([
                    'id' => $id
                ])->update([
                    'percent'   => ['exp', 'percent - 1'],
                    'update_at' => date('Y-m-d H:i:s')
                ])) {
                    throw new Exception('更新失败');
                }
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                throw new Exception($e->getMessage());
            }
            $this->success('恭喜您获得 '.$luck_info['name']);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function uploadRecode()
    {
        try{
            $serviceId = input('post.serverId/s');
            if(!$serviceId) {
                throw new Exception('信息不存在');
            }
            $source_info = load_wechat('media')->getMedia($serviceId);
            $file_info = FileService::qiniu($this->fansinfo['nickname'].uniqid().'.m4a', $source_info);
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
