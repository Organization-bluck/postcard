<?php
/**
 * Created by PhpStorm.
 * User: xuheng
 * Date: 2018/1/23
 * Time: 上午11:50
 */

namespace app\manager\controller;


use controller\BasicAdmin;
use service\DataService;
use think\Db;

class Banner extends BasicAdmin
{
    /**
     * 默认数据表
     * @var string
     */
    public $table = 'manager_banner';

    /**
     * 列表
     */
    public function index()
    {
        $this->title = '轮播图列表';
        $db = Db::name($this->table)->order('sort asc, id desc');

        return parent::_list($db);
    }

    /**
     * 添加轮播图
     */
    public function add()
    {
        $this->title = '添加轮播图';
        return $this->_form($this->table, 'form');
    }

    /**
     * 编辑轮播图
     * @return string
     */
    public function edit()
    {
        $this->title = '编辑轮播图';
        return $this->_form($this->table, 'form');
    }

    /**
     * 轮播图禁用
     */
    public function forbid()
    {
        if (DataService::update($this->table)) {
            $this->success("信息禁用成功！", '');
        }
        $this->error("信息禁用失败，请稍候再试！");
    }

    /**
     * 轮播图启用
     */
    public function resume()
    {
        if (DataService::update($this->table)) {
            $this->success("信息启用成功！", '');
        }
        $this->error("信息启用失败，请稍候再试！");
    }
}