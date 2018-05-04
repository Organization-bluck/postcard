<?php

namespace app\wap\controller;

use app\manager\controller\Vip;
use service\DataService;
use service\ExtendService;
use service\PayService;
use think\Db;
use think\Exception;


class Index extends Base
{

    public function index()
    {
        //推荐文章和轮播图
        $banner_list = Db::table('manager_banner')->where(['status' => 0])->order('sort asc')->select();
        $comment_list = Db::table('manager_article')->field('id, title, img_path, short')->order('is_commen desc, create_at desc')->limit(5)->select();
        $this->assign('data', ['banner_list' => $banner_list, 'comment_list' => $comment_list]);
        return view();
    }

    public function service()
    {
        $this->assign('seo_title', '会员服务');
        $this->assign('card_list', Db::table('manager_vip')->where(['status' => 0, 'is_del' => 0])->select());
        return view();
    }

    public function card()
    {
        try{
            $id = input('get.id/d');
            if(!$id) {
                throw new Exception('信息不存在');
            }
            if(!($info = Db::table('manager_vip')->where(['status' => 0, 'is_del' => 0, 'id' => $id])->find())) {
                throw new Exception('会员卡信息不存在');
            }
            $info['time_type'] = Vip::$time_type[$info['time_type']];

            $this->assign('card_info', $info);
            $this->assign('seo_title', '会员卡详情');
            return view();
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function payOrder()
    {
        try{
            $params = $this->request->param();
            $rules = [
                'phone' => ['require', 'regex:/^1[345678]\d{9}$/'],
                'code'  => ['require', 'regex:/^\d{6}$/'],
                'id'    => ['require'],
            ];
            $vali_msg = [
                'phone.require' => '手机号不能为空',
                'phone.regex'   => '手机号不合法',
                'code.require'  => '验证码不能为空',
                'code.regex'    => '验证码不合法',
                'id.require'    => '购买好不能为空',
            ];
            $result_val = $this->validate($params, $rules, $vali_msg);
            if($result_val !== true) {
                throw new Exception($result_val);
            }
            //验证码判断
            if(!($sms_info = Db::table('manager_sms_log')->where(['is_valid' => 0, 'type' => 1, 'content' => $params['code'], 'phone' => $params['phone']])->value('create_at'))) {
                throw new Exception('验证码不存在');
            }
            if(strtotime($sms_info) < (time() - $this->sms_order_over_time)) {
                throw new Exception('验证码已超时, 请点击获取验证码');
            }

            //判断是否购买过次类型卡
            $vip_order = Db::table('manager_vip_order')->field('id, order_status, valid_time, times, use_times')->where(['vip_id' => $params['id'], 'openid' => $this->openid])->find();
            if($vip_order) { //判断用户是否在有效期内
                if(($vip_order['order_status'] == 1) && (strtotime($vip_order['valid_time']) > time()) && (($vip_order['times'] > 0) && ($vip_order['times'] > $vip_order['use_times']))) {
                    throw new Exception('对不起，您此卡还在有效期内, 到期时间:'.$vip_order['valid_time']);
                }
            }
            //判断是否存在未支付订单，存在，则修改信息，不存在，则添加信息
            //判断vip卡信息
            if(!($vip_info = Db::table('manager_vip')->field('title, price, discount')->where(['status' => 0, 'is_del' => 0,'id' => $params['id']])->find())) {
                throw new Exception('会员卡信息不存在');
            }
            //整理数组
            $data = [
                'openid'        => $this->openid,
                'vip_id'        => $params['id'],
                'order_sn'      => DataService::createSequence(10, 'card-vip'),
                'nickname'      => $this->fansinfo['nickname'],
                'phone'         => $params['phone'],
                'order_amount'  => $vip_info['price'] * $vip_info['discount'],
            ];
            try{
                Db::startTrans();
                //更新验证码状态
                if(!Db::table('manager_sms_log')->where(['type' => 1, 'content' => $params['code'], 'phone' => $params['phone']])->update(['is_valid' => 1])) {
                    throw new Exception('更新验证码失败');
                }

                //创建或更新订单
                if($vip_order) {
                    $data['update_at'] = date('Y-m-d H:i:s');
                    if(!Db::table('manager_vip_order')->where(['id' => $params['id']])->update($data)) {
                        throw new Exception('添加订单失败');
                    }
                } else {
                    if(!Db::table('manager_vip_order')->insert($data)) {
                        throw new Exception('添加订单失败');
                    }
                }
                //微信支付下单
                $pay = load_wechat('pay');
                $options = PayService::createWechatPayJsPicker($pay, $this->openid, $data['order_sn'], $data['order_amount'], $vip_info['title']);
                if ($options === false) {
                    throw new Exception("创建支付失败，{$pay->errMsg}[$pay->errCode]");
                }
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                throw new Exception($e->getMessage());
            }

            echo json_encode(['code' => 1, 'msg' => '下单成功', 'result' => $options]);
        } catch (Exception $e) {
            echo json_encode(['code' => -1, 'msg' => $e->getMessage()]);
        }
        exit;
    }

    public function message()
    {
        $this->assign('seo_title', '留言板');
        return view();
    }

    public function getAjaxMessage()
    {
        try{
            $list = Db::table('manager_message mm')
                ->field('wf.nickname, wf.headimgurl, mm.create_at, mm.message_content')
                ->join('wechat_fans wf', 'wf.openid = mm.openid', 'left')
                ->where(['mm.is_del' => 0])
                ->order('mm.create_at desc')
                ->limit($this->_getStartCount(), $this->page_size)
                ->select();
            array_walk($list, function (&$v) {
                $v['create_at'] = get_last_time(strtotime($v['create_at']));
                $v['message_content'] = htmlspecialchars_decode($v['message_content']);
            });

            echo json_encode(['code' => 0, 'data' => $list]);
        } catch (Exception $e) {
            echo json_encode(['code' => -1, 'data' => $e->getMessage()]);
        }
        exit;
    }

    public function knowledge()
    {
//        $comment_list = Db::table('manager_article')->field('id, title, img_path, short')->order('is_commen desc, create_at desc')->limit(10)->select();
//        $this->assign('comment_list', $comment_list);
        $this->assign('seo_title', '相关知识');
        return view();
    }

    public function getAjaxArticle()
    {
        try{
            $act_list = Db::table('manager_article')
                ->field('id, title, img_path, short')
                ->order('is_commen desc, create_at desc')
                ->limit($this->_getStartCount(), $this->page_size)
                ->select();
            echo json_encode(['code' => 0, 'data' => $act_list]);
        } catch (Exception $e) {
            echo json_encode(['code' => -1, 'msg' => $e->getMessage()]);
        }
        exit;
    }

    public function detail()
    {
        $id = input('id');

        if($info = Db::table('manager_article')->where(['id' => $id, 'is_del' => 0, 'status' => 0])->find()) {
            $info['create_at'] = date('Y-m-d', strtotime($info['create_at']));
            $this->assign('info', $info);
        }

        $this->assign('seo_title', '知识详情');
        return view();
    }

    public function about()
    {
        $this->assign('seo_title', '关于我们');
        return view();
    }

    public function getCode()
    {
        try{
            $params = $this->request->param();
            $rules = [
                'phone'   => ['require', 'regex:/^1[345678]\d{9}$/'],
            ];
            $vali_msg = [
                'phone.require'  => '手机号不能为空',
                'phone.regex'    => '手机号不合法',
            ];
            $result_val = $this->validate($params, $rules, $vali_msg);
            if($result_val !== true) {
                throw new Exception($result_val);
            }
            $sms_log = Db::table('manager_sms_log')->where(['create_at' => ['gt', date('Y-m-d H:i:s', (time()-120))],'is_valid' => 0, 'phone' => $params['phone']])->find();
            if(!$sms_log) {
                if(!ExtendService::sendSms($params['phone'], mt_rand(100000,999999), 1)) {
                    throw new Exception('短信发送失败');
                }
            } else {
                $this->sms_order_over_time = strtotime($sms_log['create_at'])+$this->sms_order_over_time - time();
            }

            echo json_encode(['code' => 1, 'msg' => '发送成功', 'result' => $this->sms_order_over_time]);
        } catch (Exception $e) {
            echo json_encode(['code' => -1, 'msg' => $e->getMessage()]);
        }
    }

}
