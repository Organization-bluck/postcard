<?php
/**
 * Created by PhpStorm.
 * User: xuheng
 * Date: 2018/4/22
 * Time: 下午2:40
 */

namespace app\wap\controller;

use app\manager\controller\Vip;
use think\Db;
use think\Exception;
use think\Request;

class User extends Base
{
    public function appoint()
    {
        if(Request::instance()->isPost()) {
            try{
                $params = $this->request->param();
                $rules = [
                    'appoint_user'    => 'require',
                    'appoint_phone'   => ['require', 'regex:/^1[345678]\d{9}$/'],
                    'appoint_time'    => 'require',
                    'appoint_requite' => 'require',
                ];
                $vali_msg = [
                    'appoint_user.require'   => '姓名不能为空',
                    'appoint_phone.require'  => '手机号不能为空',
                    'appoint_phone.regex'    => '手机号不合法',
                    'appoint_time.require'   => '预约时间不能为空',
                    'appoint_requite.require'=> '内容不能为空',
                ];
                $result_val = $this->validate($params, $rules, $vali_msg);
                if($result_val !== true) {
                    throw new Exception($result_val);
                }
                if(strtotime($params['appoint_time']) < time()) {
                    throw new Exception('预约时间不能小于当前时间');
                }

                $params['openid'] = $this->openid;
                if(!Db::table('manager_appoint')->insert($params)) {
                    throw new Exception('添加预约信息失败');
                }

                $this->success('预约成功');
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
        }
        $this->assign('seo_title', '专家预约');
        return view();
    }

    public function order()
    {
        $vip_order = Db::table('manager_vip_order mvo')
            ->field('mv.title, mv.*,mvo.id, mvo.order_status, mvo.valid_time, mvo.times, mvo.use_times')
            ->join('manager_vip mv', 'mv.id = mvo.vip_id')
            ->where(['mvo.order_status' => 1, 'mvo.valid_time' => ['gt', date('Y-m-d H:i:s')],  'mvo.openid' => $this->openid])
            ->select();
        $this->assign('order_list', $vip_order);
        $this->assign('time_type', Vip::$time_type);
        return view();
    }

    public function info()
    {
        $this->assign('seo_title', '个人信息');
        $this->assign('user_info', Db::table('wechat_fans')->where(['openid' => $this->openid])->find());
        return view();
    }

    public function message()
    {
        if(Request::instance()->isPost()) {
           try{
               $params = $this->request->param();
               $rules = [
                   'message_name'    => 'require',
                   'message_phone'   => ['require', 'regex:/^1[345678]\d{9}$/'],
                   'message_email'   => 'require|email',
                   'message_content' => 'require',
               ];
               $vali_msg = [
                   'message_name.require'   => '姓名不能为空',
                   'message_phone.require'  => '手机号不能为空',
                   'message_phone.regex'    => '手机号不合法',
                   'message_email.require'  => '邮箱地址不能为空',
                   'message_email.email'    => '邮箱地址不合法',
                   'message_content.require'=> '留言内容不能为空',
               ];
               $result_val = $this->validate($params, $rules, $vali_msg);
               if($result_val !== true) {
                   throw new Exception($result_val);
               }
               $params['openid'] = $this->openid;
               if(!Db::table('manager_message')->insert($params)) {
                    throw new Exception('添加留言失败');
               }

               $this->success('留言成功');
           } catch (Exception $e) {
               $this->error($e->getMessage());
           }
        }
        $this->assign('seo_title', '留言板');
        return view();
    }

    /**
     * 上传图片
     */
    public function ajaxUploadImg()
    {
        try{
            $file = $_FILES;
            if(!isset($file['file'])) {
                throw new Exception('请上传文件');
            }
            if(empty($file['file']['name'])) {
                throw new Exception('上传文件不能为空');
            }
            $upload_list = [];
            if(is_array($file['file']['name'])) {
                foreach ((array)$file['file']['name'] as $k => $v) {
                    if(empty($file['file']['tmp_name'][$k])) {
                        throw new Exception($v.'文件上传失败');
                    }
                    if(empty($file['file']['size'][$k])) {
                        throw new Exception($v.'文件大小错误');
                    }
                    $upload_data = [
                        'size' => $file['file']['size'][$k],
                        'tmp_name' => $file['file']['tmp_name'][$k],
                        'name' => $v
                    ];
                    $upload_list[$k] = $this->_uploadImg($upload_data);
                    if($upload_list[$k] === false) {
                        throw new Exception($v.$this->error);
                    }
                }
            } else {
                if(empty($file['file']['tmp_name'])) {
                    throw new Exception($file['file']['tmp_name'].'文件上传失败');
                }
                if(empty($file['file']['size'])) {
                    throw new Exception($file['file']['tmp_name'].'文件大小错误');
                }
                $upload_data = [
                    'size' => $file['file']['size'],
                    'tmp_name' => $file['file']['tmp_name'],
                    'name' => $file['file']['tmp_name']
                ];
                $upload_list = $this->_uploadImg($upload_data);
                if($upload_list === false) {
                    throw new Exception($file['file']['tmp_name'].$this->error);
                }
            }

            echo json_encode(['code' => 0, 'msg' => '操作成功', 'date' => $upload_list]);
        } catch (Exception $e) {
            echo json_encode(['code' => -1, 'msg' => $e->getMessage()]);
        }
        exit;
    }

}