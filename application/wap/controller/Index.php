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
        return view();
    }

    public function uploadRecode()
    {
        var_dump(input('post.'));
        exit;
    }
}
