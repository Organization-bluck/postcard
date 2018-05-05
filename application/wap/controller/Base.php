<?php
/**
 * Created by PhpStorm.
 * User: xuheng
 * Date: 2018/4/23
 * Time: 上午8:20
 */

namespace app\wap\controller;


use controller\BasicWechat;
use think\Db;
use think\Exception;

class Base extends BasicWechat
{
    private $max_upload_size;
    protected $page_size = 10;

    protected $sms_order_over_time = 120;

    public function _initialize()
    {
        $this->max_upload_size = 10240*500; //上传最大500k
//        $this->openid = 'oYuInv1TBP_LiWP8ObF7b_G6wm10';
        $this->checkAuth = false;
        parent::_initialize();
        $this->assign('is_vip', Db::table('manager_vip_order')->where(['valid_time' => ['gt', date('Y-m-d H:i:s')], 'is_del' => 0])->count());
        $this->assign('user_info', $this->fansinfo);
    }

    //curl模拟表单提交文件
    protected function _uploadImg($file)
    {
        try{
            if(!isset($file['tmp_name']) || !isset($file['name']) || !isset($file['size'])) {
                throw new Exception('参数错误');
            }
            if($file['size'] > $this->max_upload_size) {
                throw new Exception('上传文件不能超过500k');
            }
            $tmp_name=dirname($file['tmp_name']).'/'.$file['name'];//加上文件后缀
            rename($file['tmp_name'],$tmp_name);
            if(version_compare(phpversion(),'5.5.0') >= 0 && class_exists('CURLFile')){
                $fields['file'] = new \CURLFile(realpath($tmp_name));
            }else{
                $fields['file'] = '@'.$tmp_name;//加@符号curl就会把它当成是文件上传处理
            }
            $fields['md5'] = md5($file['name']);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, config('environment')[config('current_environment')]['upload']);
            curl_setopt($ch, CURLOPT_POST,true);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1); //连接超时
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            $data=curl_exec ($ch);
//            $info=curl_getinfo($ch);
            curl_close($ch);
            $result = json_decode($data, true);
            if(!isset($result['code']) || ($result['code'] != 'SUCCESS')) {
                throw new Exception('上传失败');
            }
            return $result['data']['site_url'];
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    //计算分页开始条数
    protected function _getStartCount()
    {
        return max(0, (input('request.page/d', 0)-1)*$this->page_size);
    }

}