<?php

namespace app\wap\controller;

use service\FileService;
use think\Db;
use think\Exception;


class Index extends Base
{
    public function index()
    {
        $postcard_list = Db::table('post_media')->field('id, img_path')->where(['openid' => $this->openid, 'is_del' => 0])->order('create_at desc')->select();

        $this->assign('postcard_list', json_encode($postcard_list));

        return view();
    }

    public function choose()
    {
        $this->assign('is_share', Db::table('post_media')->where(['openid' => $this->openid, 'is_share' => 1])->count());

        return view();
    }

    public function recode()
    {
        $id = input('get.id/d');
        if(!$id) {
            $this->redirect(url('index/index'));
        }
        $img_path = Db::table('post_media')->where(['is_del' => 0, 'id' => $id])->value('img_path');

        if(!$img_path) {
            $this->redirect(url('index/index'));
        }

        return view('', ['id' => $id, 'img_path' => $img_path]);
    }

    public function write()
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

    public function uploadImg()
    {
        try{
            $img_info = $_FILES['file'];
            if($img_info) {
                $img_type = 'jpg';

                $source_info = base64_decode($img_info); //二进制数据流
            } else {
                $image = input('post.img_path');

                if(!$image) {
                    throw new Exception('请重新选择图片');
                }
                $image = ROOT_PATH.'public'.$image;
                if(!file_exists($image)) {
                    throw new Exception('图片文件不存在');
                }

                $img_type = pathinfo($image,PATHINFO_EXTENSION);

                $source_info = file_get_contents($image);
            }

            $file_info = FileService::qiniu($this->openid.'/image/'.uniqid().'.'.$img_type, $source_info);
            if(empty($file_info['url'])) {
                throw new Exception('图片上传失败');
            }
            $id = input('post.id/d');
            if($id) {
                if(!Db::table('post_media')->where([
                    'openid' => $this->openid,
                    'is_del' => 0,
                    'id'     => $id
                ])->update([
                    'update_at' => date('Y-m-d H:i:s'),
                    'img_path'  => $file_info['url']
                ])) {
                    throw new Exception('图片修改失败');
                }
            } else {
                $id = Db::table('post_media')->insert([
                    'openid'    => $this->openid,
                    'img_path'  => $file_info['url']
                ], '', true);
                if(!$id) {
                    throw new Exception('明信片创建失败');
                }
            }

            $this->success('操作成功', '', $id);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function uploadRecode()
    {
        try{
            $id = input('post.id/d');
            if(!$id) {
                throw new Exception('信息不存在');
            }
            $serviceId = input('post.serverId/s');
            if(!$serviceId) {
                throw new Exception('信息不存在');
            }
            $source_info = load_wechat('media')->getMedia($serviceId);
            $file_info = FileService::qiniu($this->openid.'/recode/'.uniqid().'.mp3', $source_info);
            if(!isset($file_info['url'])) {
                throw new Exception('上传录音失败');
            }

            if(!Db::table('post_media')->where([
                'openid' => $this->openid,
                'is_del' => 0,
                'id'     => $id
            ])->count()) {
                $this->redirect(url('index/index'));
            }

            if(!Db::table('post_media')->where([
                'openid' => $this->openid,
                'is_del' => 0,
                'id'     => $id
            ])->update([
                'update_at' => date('Y-m-d H:i:s'),
                'file_url'  => $file_info['url']
            ])) {
                throw new Exception('录音添加失败');
            }
            $this->success('操作成功', '', $file_info['url']);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function uploadContent()
    {
        try{
            $id = input('post.id/d');
            if(!$id) {
                throw new Exception('信息不存在');
            }
            $content = input('post.content');
            if(!$content) {
                throw new Exception('请输入或选择内容');
            }

            if(!Db::table('post_media')->where([
                'openid' => $this->openid,
                'is_del' => 0,
                'id'     => $id
            ])->count()) {
                $this->redirect(url('index/index'));
            }

            if(!Db::table('post_media')->where([
                'openid' => $this->openid,
                'is_del' => 0,
                'id'     => $id
            ])->update([
                'update_at' => date('Y-m-d H:i:s'),
                'is_save'   => 0,
                'content'   => $content
            ])) {
                throw new Exception('录音信息更新失败');
            }
            $this->success('更新成功');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function preview()
    {
        try{
            $this->openid = 'oYuInv1TBP_LiWP8ObF7b_G6wm10';
            $id = input('get.id/d');
            if(!$id) {
                throw new Exception('信息不存在');
            }
            //需要加入信卡中的文字
            $info = Db::table('post_media')->field('id, img_path, file_url, content, is_save, card_path')->where(['openid' => $this->openid, 'is_del' => 0, 'id' => $id])->find();
            if(!$info) {
                throw new Exception('明信片信息不存在');
            }
            if(($info['is_save'] == 0) || empty($info['card_path'])) { //未生成过
                //将生成的图片转换二进制流
                $image = $this->_savePicture($info['content']);
                if(!$image) {
                    throw new Exception('图片不存在');
                }

                $source_info = file_get_contents($image);

                $file_info = FileService::qiniu($this->openid.'/postcard/'.uniqid().'.png', $source_info);
                if(empty($file_info['url'])) {
                    throw new Exception('图片上传失败');
                }
                unlink ($image);
                if(!Db::table('post_media')->where([
                    'openid' => $this->openid,
                    'is_del' => 0,
                    'id'     => $id
                ])->update([
                    'update_at' => date('Y-m-d H:i:s'),
                    'is_save'   => 1,
                    'card_path' => $file_info['url']
                ])) {
                    throw new Exception('明星片生成失败');
                }
            }

            return view('', ['info' => $info]);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    private function _savePicture($content)
    {
        $picture = ROOT_PATH.'public/static/wap/images/postcard.png';
        if(!file_exists($picture)) {
            throw new Exception('信卡不存在');
        }

        $im = @imagecreatefrompng($picture);    //从图片建立文件，此处以jpg文件格式为例
        $white = imagecolorallocate($im, 0, 0, 0);
        $font = ROOT_PATH.'public/static/wap/font/wuming.ttf';  //写的文字用到的字体。

        $width = imagesx($im);
        $height = imagesy($im);

        //邮政编码
        imagettftext($im, 45, 0, 60, 130, $white, $font, rand(1,9));
        imagettftext($im, 45, 0, 125, 130, $white, $font, rand(1,9));
        imagettftext($im, 45, 0, 195, 130, $white, $font, rand(1,9));
        imagettftext($im, 45, 0, 260, 130, $white, $font, rand(1,9));
        imagettftext($im, 45, 0, 330, 130, $white, $font, rand(1,9));
        imagettftext($im, 45, 0, 395, 130, $white, $font, rand(1,9));

        $font_arr = autoLineSplit($content, $font, 15, 'utf8', 250);
        foreach ((array)$font_arr as $k => $v) {
            imagettftext($im, 30, 0, 40, 250+$k*40, $white, $font, $v);
        }

        //默认地址
        $address = '第五百二十一个路口遇到你';

        imagettftext($im, 30, 0, 700, 320, $white, $font, $this->fansinfo['nickname']);

        $font_arr = autoLineSplit($address, $font, 18, 'utf8', 400);
        foreach ((array)$font_arr as $ks => $vs) {
            imagettftext($im, 18, 0, 600, 450+$ks*60, $white, $font, $vs);
        }
        if($width < $height) { //横图
            $im = imagerotate($im, 180, 0);
        }

        $path = ROOT_PATH.'public/upload/'.uniqid().'.png';
        imagepng($im, $path);
        imagedestroy($im);
        return $path;
    }
}
