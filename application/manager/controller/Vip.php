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
use think\Db;

class Vip extends BasicAdmin
{
    public static $time_type = [1 => '天',2 => '周',3 => '个月',4 => '季',5 => '年'];//时间类型 1天，2周，3月，4季，5年

    /**
     * 默认数据表
     * @var string
     */
    public $table = 'manager_vip';

    /**
     * 列表
     */
    public function index()
    {
        $this->title = 'vip卡列表';
        $get = $this->request->get();
        $db = Db::name($this->table)->where(['is_del' => 0])->order('id desc');
        // 搜索条件
        foreach (['title'] as $key) {
            (isset($get[$key]) && $get[$key] !== '') && $db->whereLike($key, "%{$get[$key]}%");
        }

        $this->assign('time_type', self::$time_type);
        return parent::_list($db);
    }

    /**
     * 添加会员卡
     */
    public function add()
    {
        return $this->_form($this->table, 'form');
    }

    /**
     * 编辑会员卡
     */
    public function edit()
    {
        return $this->_form($this->table, 'form');
    }

    /**
     * 禁用
     */
    public function forbid()
    {
        if (DataService::update($this->table)) {
            $this->success("信息禁用成功！", '');
        }
        $this->error("信息禁用失败，请稍候再试！");
    }

    /**
     * 禁用
     */
    public function resume()
    {
        if (DataService::update($this->table)) {
            $this->success("信息启用成功！", '');
        }
        $this->error("信息启用失败，请稍候再试！");
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
        if ($this->request->isPost() && isset($data['title'])) {
            $db = Db::name($this->table)->where('title', $data['title']);
            !empty($data['id']) && $db->where('id', 'neq', $data['id']);
            $db->count() > 0 && $this->error('此名称已存在！');
        } else {
            $this->assign('time_type', self::$time_type);
        }
    }
}