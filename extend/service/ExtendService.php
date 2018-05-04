<?php

// +----------------------------------------------------------------------
// | Think.Admin
// +----------------------------------------------------------------------
// | 版权所有 2014~2017 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.ctolog.com
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zoujingli/Think.Admin
// +----------------------------------------------------------------------

namespace service;

require_once VENDOR_PATH.'/aliyunsms/vendor/autoload.php';

use Aliyun\Api\Sms\Request\V20170525\SendSmsRequest;
use Aliyun\Core\DefaultAcsClient;
use Aliyun\Core\Profile\DefaultProfile;
use think\Db;
use think\Request;

use Aliyun\Core\Config;

Config::load();
/**
 * 扩展服务
 * Class ExtendService
 * @package service
 */
class ExtendService
{
    static $acsClient = null;

    /**
     * 取得AcsClient
     *
     * @return DefaultAcsClient
     */
    public static function getAcsClient() {
        //产品名称:云通信流量服务API产品,开发者无需替换
        $product = "Dysmsapi";

        //产品域名,开发者无需替换
        $domain = "dysmsapi.aliyuncs.com";

        // TODO 此处需要替换成开发者自己的AK (https://ak-console.aliyun.com/)
        $accessKeyId = "LTAI4GvaZX5eWLom"; // AccessKeyId

        $accessKeySecret = "VSiVsC0v2d5naB0X5Q8PnR2ulXEDCZ"; // AccessKeySecret

        // 暂时不支持多Region
        $region = "cn-hangzhou";

        // 服务结点
        $endPointName = "cn-hangzhou";


        if(static::$acsClient == null) {

            //初始化acsClient,暂不支持region化
            $profile = DefaultProfile::getProfile($region, $accessKeyId, $accessKeySecret);
            // 增加服务结点
            DefaultProfile::addEndpoint($endPointName, $region, $product, $domain);
            // 初始化AcsClient用于发起请求
            static::$acsClient = new DefaultAcsClient($profile);
        }
        return static::$acsClient;
    }
    /**
     * 发送短信验证码
     * @param string $phone 手机号
     * @param string $content 短信内容
     * @param string $productid 短信通道ID
     * @return bool
     */
    public static function sendSms($phone, $content, $type = 1)
    {
        set_time_limit(0);
        header('Content-Type: text/plain; charset=utf-8');
        // 初始化SendSmsRequest实例用于设置发送短信的参数
        $request = new SendSmsRequest();
        // 必填，设置短信接收号码
        $request->setPhoneNumbers($phone);
        // 必填，设置签名名称，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
        $request->setSignName("兑讯科技");
        // 必填，设置模板CODE，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
        $request->setTemplateCode("SMS_70355227");
        // 可选，设置模板参数, 假如模板中存在变量需要替换则为必填项
        $request->setTemplateParam(json_encode(Array(  // 短信模板中字段的值
            "code" => $content,
        )));

        // 发起访问请求
        $result = static::getAcsClient()->getAcsResponse($request);
        if($result->Code == 'OK') {
            $insert = ['phone' => $phone, 'content' => $content, 'type' => $type, 'ip' => getRealIp()];
            Db::name('manager_sms_log')->insert($insert);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 查询短信余额
     * @return array
     */
    public static function querySmsBalance()
    {
        $tkey = date("YmdHis");
        $data = [
            'username' => sysconf('sms_username'), 'tkey' => $tkey,
            'password' => md5(md5(sysconf('sms_password')) . $tkey),
        ];
        $result = HttpService::post('http://www.ztsms.cn/balanceN.do', $data);
        if ($result > -1) {
            return ['code' => 1, 'num' => $result, 'msg' => '获取短信剩余条数成功！'];
        } elseif ($result > -2) {
            return ['code' => 0, 'num' => '0', 'msg' => '用户名或者密码不正确！'];
        } elseif ($result > -3) {
            return ['code' => 0, 'num' => '0', 'msg' => 'tkey不正确！'];
        } elseif ($result > -4) {
            return ['code' => 0, 'num' => '0', 'msg' => '用户不存在或用户停用！'];
        }
    }

    /**
     * 通用物流单查询
     * @param string $code 快递物流编号
     * @return array
     */
    public static function expressByAuto($code)
    {
        list($result, $client_ip) = [[], Request::instance()->ip()];
        $header = ['Host' => 'www.kuaidi100.com', 'CLIENT-IP' => $client_ip, 'X-FORWARDED-FOR' => $client_ip];
        $autoResult = HttpService::get("http://www.kuaidi100.com/autonumber/autoComNum?text={$code}", [], 30, $header);
        foreach (json_decode($autoResult)->auto as $vo) {
            $result[$vo->comCode] = self::express($vo->comCode, $code);
        }
        return $result;
    }

    /**
     * 查询物流信息
     * @param string $express_code 快递公司编辑
     * @param string $express_no 快递物流编号
     * @return array
     */
    public static function express($express_code, $express_no)
    {
        list($microtime, $client_ip) = [microtime(true), Request::instance()->ip()];
        $header = ['Host' => 'www.kuaidi100.com', 'CLIENT-IP' => $client_ip, 'X-FORWARDED-FOR' => $client_ip];
        $location = "http://www.kuaidi100.com/query?type={$express_code}&postid={$express_no}&id=1&valicode=&temp={$microtime}";
        return json_decode(HttpService::get($location, [], 30, $header), true);
    }

}