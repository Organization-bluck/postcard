<?php
/**
 * Created by PhpStorm.
 * User: xuheng
 * Date: 2018/5/6
 * Time: 下午5:52
 */

namespace app\manager\controller;


use controller\BasicAdmin;
use think\Db;

class Postcardlist extends BasicAdmin
{
    /**
     * 默认数据表
     * @var string
     */
    public $table = 'manager_luckdraw_log';

    /**
     * 列表
     */
    public function index()
    {
        $this->title = '中奖列表';
        $db = Db::name($this->table)->order('id desc');

        return parent::_list($db);
    }
}