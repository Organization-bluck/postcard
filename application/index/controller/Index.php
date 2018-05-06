<?php

// +----------------------------------------------------------------------
// | Think.Admin
// +----------------------------------------------------------------------
// | 版权所有 2014~2017 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.ctolog.com
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zoujingli/Think.Admin
// +----------------------------------------------------------------------

namespace app\index\controller;

use Endroid\QrCode\QrCode;
use service\FileService;
use think\Controller;
use think\Response;

/**
 * 网站入口控制器
 * Class Index
 * @package app\index\controller
 * @author Anyon <zoujingli@qq.com>
 * @date 2017/04/05 10:38
 */
class Index extends Controller
{

    /**
     * 网站入口
     */
    public function index()
    {
        $this->redirect('@admin');
    }

    public function qrc()
    {
        $wechat = load_wechat('Extends');
        for ($i = 10; $i < 11; $i++) {
            $qrc = $wechat->getQRCode($i, 1);
            print_r($qrc);
        }

    }

    public function getCode()
    {
        $qrCode = new QrCode();
        $qrCode
            ->setText('http://baidu.com  ')
            ->setExtension('png')
            ->setSize(300)
            ->setPadding(10)
            ->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0])
            ->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0])
            ->setErrorCorrection(QrCode::LEVEL_MEDIUM);
//        header('Content-Type: '.$qrCode->getContentType());
        $result = FileService::qiniu('/qrcode/'.uniqid(), $qrCode->get());

        var_dump($result['url']);exit;
//        $response = new Response($qrCode->get(), 200, ['Content-Type' => $qrCode->getContentType()]);
//        var_dump($response->getData());exit;
    }

}
