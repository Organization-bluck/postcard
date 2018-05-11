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
    protected $max_upload_size;
    protected $page_size = 10;

    protected $sms_order_over_time = 120;

    public function _initialize()
    {
        $this->max_upload_size = 10240*500; //上传最大500k

        $this->openid = 'fdasfdas';
        $this->fansinfo['nickname'] = 'fdsa';

        $this->checkAuth = false;
        parent::_initialize();
    }

    //计算分页开始条数
    protected function _getStartCount()
    {
        return max(0, (input('request.page/d', 0)-1)*$this->page_size);
    }

}