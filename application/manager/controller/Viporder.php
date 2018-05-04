<?php
/**
 * Created by PhpStorm.
 * User: xuheng
 * Date: 2018/4/21
 * Time: 下午3:12
 */
namespace app\manager\controller;

use controller\BasicAdmin;
use service\DataService;
use service\LogService;
use think\Db;
use think\Exception;

class Viporder extends BasicAdmin
{
    private $order_status = [0 => '待支付', 1 => '已支付', 2 => '已取消']; //0待支付 1已支付 2已取消
    private $third_platform_type = [0 => '', 1 => '微信', 2 => '支付宝', 10 => '后台添加']; //1微信 2支付宝

    /**
     * 默认数据表
     * @var string
     */
    public $table = 'manager_vip_order';

    public $rowPage = 20; //一页显示个数

    /**
     * 列表
     */
    public function index()
    {
        $db = Db::name($this->table . ' mvo')
            ->field('mvo.id, mvo.order_sn, mvo.nickname, mvo.phone, mvo.order_status, mvo.valid_time, mvo.times, mvo.use_times, mvo.order_amount, mvo.pay_amount, mvo.pay_time, mvo.third_platform_type, mvo.third_platform_order_sn, mvo.create_at,mv.title')
            ->join('manager_vip mv', 'mv.id=mvo.vip_id', 'LEFT')
            ->order('mvo.id desc');
        // 搜索条件
        $get = $this->request->get();
        $db->where(['mvo.is_del' => 0]);
        foreach (['order_sn, nickname', 'phone'] as $key) {
            (isset($get[$key]) && $get[$key] !== '') && $db->whereLike('mvo.'.$key, "%{$get[$key]}%");
        }
        if (isset($get['pay_time']) && $get['pay_time'] !== '') {
            list($start, $end) = explode('-', str_replace(' ', '', $get['pay_time']));
            $db->whereBetween('mvo.pay_time', ["{$start} 00:00:00", "{$end} 23:59:59"]);
        }
        if (isset($get['create_at']) && $get['create_at'] !== '') {
            list($start, $end) = explode('-', str_replace(' ', '', $get['date']));
            $db->whereBetween('mvo.create_at', ["{$start} 00:00:00", "{$end} 23:59:59"]);
        }
        $result = array();
        $page = $db->paginate($this->rowPage, false);
        $result['list'] = $page->all();
        $result['page'] = preg_replace(['|href="(.*?)"|', '|pagination|'], ['data-open="$1" href="javascript:void(0);"', 'pagination pull-right'], $page->render());

        $this->assign('title', 'vip订单列表');
        $this->assign('order_status', $this->order_status);
        $this->assign('third_platform_type', $this->third_platform_type);
        return view('', $result);
    }

    /**
     * 添加会员订单
     */
    public function add()
    {
        exit;
        return $this->_form($this->table, 'form');
    }

    /**
     * 编辑会员订单
     */
    public function edit()
    {
        return $this->_form($this->table, 'form');
    }

    /**
     * 支付订单
     */
    public function pay_order()
    {
        try{
            if(!($order_id = input('post.id/d'))) {
                throw new Exception('信息不存在');
            }
            $vip_order_info = Db::table($this->table)->where(['id' => $order_id])->find();
            if(!$vip_order_info) {
                throw new Exception('订单信息不存在');
            }

            $vip_info = Db::table('manager_vip')->where(['id' => $vip_order_info['vip_id']])->find();
            if(!$vip_info) {
                throw new Exception('vip卡信息不存在');
            }
            switch ($vip_info['time_type']) {
                case 1: //天
                    $valid_time = strtotime($vip_info['time_long'].'day', strtotime(date('Y-m-d')))+24*3600;
                    break;
                case 2: //周
                    $valid_time = strtotime((7*$vip_info['time_long']).'day', strtotime(date('Y-m-d')))+24*3600;
                    break;
                case 3: //月
                    $valid_time = strtotime($vip_info['time_long'].'month', strtotime(date('Y-m-d')))+24*3600;
                    break;
                case 4: //季
                    $valid_time = strtotime((3*$vip_info['time_long']).'day', strtotime(date('Y-m-d')))+24*3600;
                    break;
                case 5: //年
                    $valid_time = strtotime($vip_info['time_long'].'year', strtotime(date('Y-m-d')))+24*3600;
                    break;
                default:
                    throw new Exception('类型不存在');
                    break;
            }
            $order_valid_time = date('Y-m-d', $valid_time);


            if (Db::table($this->table)->where(['id' => $order_id])->update([
                'valid_time'    => $order_valid_time,
                'pay_time'      => date('Y-m-d H:i:s'),
                'update_at'     => date('Y-m-d H:i:s'),
                'order_status'  => 1
            ])) {
                LogService::write('Vip订单管理', '确认付款'.$order_id);
                $this->success("确认付款成功！", '');
            } else {
                throw new Exception('支付失败');
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 取消订单
     */
    public function cancel_order()
    {
        try{
            if(!($order_id = input('post.id/d'))) {
                throw new Exception('信息不存在');
            }
            $vip_order_info = Db::table($this->table)->where(['id' => $order_id])->find();
            if(!$vip_order_info) {
                throw new Exception('订单信息不存在');
            }

            if (Db::table($this->table)->where(['id' => $order_id])->update([
                'update_at'     => date('Y-m-d H:i:s'),
                'order_status'  => 2
            ])) {
                LogService::write('Vip订单管理', '取消订单'.$order_id);
                $this->success("取消订单成功！", '');
            } else {
                throw new Exception('取消订单失败');
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 删除
     */
    public function del() {
        if (DataService::update($this->table)) {
            $this->success("删除成功！", '');
        }
        $this->error("删除失败，请稍候再试！");
    }

    /**
     * 表单处理
     * @param $data
     */
    protected function _form_filter($data)
    {
        if ($this->request->isPost() && isset($data['order_sn'])) {
            $db = Db::name($this->table)->where('order_sn', $data['order_sn']);
            !empty($data['id']) && $db->where('id', 'neq', $data['id']);
            $db->count() > 0 && $this->error('此订单号已存在！');
        } else {
            $this->assign('vip_list', Db::table('manager_vip')->where(['status' => 0, 'is_del' => 0])->select());
            $this->assign('time_type', Vip::$time_type);
            $this->assign('order_status', $this->order_status);
            $this->assign('third_platform_type', $this->third_platform_type);
        }
    }
}