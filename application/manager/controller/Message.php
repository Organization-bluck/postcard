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

class Message extends BasicAdmin
{
    /**
     * 默认数据表
     * @var string
     */
    public $table = 'manager_message';

    /**
     * 列表
     */
    public function index()
    {
        $this->title = '留言信息列表';
        $get = $this->request->get();
        $db = Db::name($this->table)->where(['is_del' => 0])->order('id desc');
        // 搜索条件
        foreach (['message_name', 'message_phone', 'message_email'] as $key) {
            (isset($get[$key]) && $get[$key] !== '') && $db->whereLike($key, "%{$get[$key]}%");
        }
        if (isset($get['date']) && $get['date'] !== '') {
            list($start, $end) = explode('-', str_replace(' ', '', $get['date']));
            $db->whereBetween('create_at', ["{$start} 00:00:00", "{$end} 23:59:59"]);
        }

        return parent::_list($db);
    }

    /**
     * 查看留言信息
     * @return array|string
     */
    public function info()
    {
        return $this->_form($this->table, 'form');
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

    public function _form_filter(&$vo)
    {
        if($vo['is_read'] == 1) {
            Db::table($this->table)->where(['id' => $vo['id']])->update(['is_read' => 2, 'update_at'=>date('Y-m-d H:i:s')]);
        }
    }
}