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


use service\DataService;
use service\FileService;
use service\NodeService;
use service\SoapService;
use think\Db;
use Wechat\Loader;

/**
 * 打印输出数据到文件
 * @param mixed $data
 * @param bool $replace
 * @param string|null $pathname
 */
function p($data, $replace = false, $pathname = null)
{
    is_null($pathname) && $pathname = RUNTIME_PATH . date('Ymd') . '.txt';
    $str = (is_string($data) ? $data : (is_array($data) || is_object($data)) ? print_r($data, true) : var_export($data, true)) . "\n";
    $replace ? file_put_contents($pathname, $str) : file_put_contents($pathname, $str, FILE_APPEND);
}

/**
 * 获取mongoDB连接
 * @param string $col 数据库集合
 * @param bool $force 是否强制连接
 * @return \think\db\Query|\think\mongo\Query
 */
function mongo($col, $force = false)
{
    return Db::connect(config('mongo'), $force)->name($col);
}

/**
 * 获取微信操作对象
 * @param string $type
 * @return \Wechat\WechatMedia|\Wechat\WechatMenu|\Wechat\WechatOauth|\Wechat\WechatPay|\Wechat\WechatReceive|\Wechat\WechatScript|\Wechat\WechatUser|\Wechat\WechatExtends|\Wechat\WechatMessage
 * @throws Exception
 */
function & load_wechat($type = '')
{
    static $wechat = [];
    $index = md5(strtolower($type));
    if (!isset($wechat[$index])) {
        $config = [
            'token'          => sysconf('wechat_token'),
            'appid'          => sysconf('wechat_appid'),
            'appsecret'      => sysconf('wechat_appsecret'),
            'encodingaeskey' => sysconf('wechat_encodingaeskey'),
            'mch_id'         => sysconf('wechat_mch_id'),
            'partnerkey'     => sysconf('wechat_partnerkey'),
            'ssl_cer'        => sysconf('wechat_cert_cert'),
            'ssl_key'        => sysconf('wechat_cert_key'),
            'cachepath'      => CACHE_PATH . 'wxpay' . DS,
        ];
        $wechat[$index] = Loader::get($type, $config);
    }
    return $wechat[$index];
}

/**
 * UTF8字符串加密
 * @param string $string
 * @return string
 */
function encode($string)
{
    list($chars, $length) = ['', strlen($string = iconv('utf-8', 'gbk', $string))];
    for ($i = 0; $i < $length; $i++) {
        $chars .= str_pad(base_convert(ord($string[$i]), 10, 36), 2, 0, 0);
    }
    return $chars;
}

/**
 * UTF8字符串解密
 * @param string $string
 * @return string
 */
function decode($string)
{
    $chars = '';
    foreach (str_split($string, 2) as $char) {
        $chars .= chr(intval(base_convert($char, 36, 10)));
    }
    return iconv('gbk', 'utf-8', $chars);
}

/**
 * 网络图片本地化
 * @param string $url
 * @return string
 */
function local_image($url)
{
    if (is_array(($result = FileService::download($url)))) {
        return $result['url'];
    }
    return $url;
}

/**
 * 日期格式化
 * @param string $date 标准日期格式
 * @param string $format 输出格式化date
 * @return false|string
 */
function format_datetime($date, $format = 'Y年m月d日 H:i:s')
{
    return empty($date) ? '' : date($format, strtotime($date));
}

/**
 * 设备或配置系统参数
 * @param string $name 参数名称
 * @param bool $value 默认是null为获取值，否则为更新
 * @return string|bool
 */
function sysconf($name, $value = null)
{
    static $config = [];
    if ($value !== null) {
        list($config, $data) = [[], ['name' => $name, 'value' => $value]];
        return DataService::save('SystemConfig', $data, 'name');
    }
    if (empty($config)) {
        $config = Db::name('SystemConfig')->column('name,value');
    }
    return isset($config[$name]) ? $config[$name] : '';
}

/**
 * RBAC节点权限验证
 * @param string $node
 * @return bool
 */
function auth($node)
{
    return NodeService::checkAuthNode($node);
}

/**
 * 生成订单号
 */
function get_order_sn()
{
    $yCode = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
    return $yCode[intval(date('Y')) - 2011] . strtoupper(dechex(date('m'))) . date('d') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
}


/**
 * 获取已经过了多久
 * PHP时间转换
 * 刚刚、几分钟前、几小时前
 * 今天昨天前天几天前
 * @param  string $targetTime 时间戳
 * @return string
 */
function get_last_time($targetTime)
{
    // 今天最大时间
    $todayLast   = strtotime(date('Y-m-d 23:59:59'));
    $agoTimeTrue = time() - $targetTime;
    $agoTime     = $todayLast - $targetTime;
    $agoDay      = floor($agoTime / 86400);

    if ($agoTimeTrue < 60) {
        $result = '刚刚';
    } elseif ($agoTimeTrue < 3600) {
        $result = (ceil($agoTimeTrue / 60)) . '分钟前';
    } elseif ($agoTimeTrue < 3600 * 12) {
        $result = (ceil($agoTimeTrue / 3600)) . '小时前';
    } elseif ($agoDay == 0) {
        $result = '今天 ' . date('H:i', $targetTime);
    } elseif ($agoDay == 1) {
        $result = '昨天 ' . date('H:i', $targetTime);
    } elseif ($agoDay == 2) {
        $result = '前天 ' . date('H:i', $targetTime);
    } elseif ($agoDay > 2 && $agoDay < 16) {
        $result = $agoDay . '天前 ' . date('H:i', $targetTime);
    } else {
        $format = date('Y') != date('Y', $targetTime) ? "Y-m-d H:i" : "m-d H:i";
        $result = date($format, $targetTime);
    }
    return $result;
}

function getRealIp()
{
    $ip=false;
    if(!empty($_SERVER["HTTP_CLIENT_IP"])){
        $ip = $_SERVER["HTTP_CLIENT_IP"];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
        if ($ip) { array_unshift($ips, $ip); $ip = FALSE; }
        for ($i = 0; $i < count($ips); $i++) {
            if (!eregi ("^(10│172.16│192.168).", $ips[$i])) {
                $ip = $ips[$i];
                break;
            }
        }
    }
    return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
}

/**
 * 画布 文字转行
 * @param $str          string  字符创
 * @param $fontFamily   string  字体文件
 * @param $fontSize     int     字号大小
 * @param $charset      string  文字格式
 * @param $width        int     文字宽度
 * @return array
 */
function autoLineSplit ($str, $fontFamily, $fontSize, $charset, $width) {
    $result = [];

    $len = (strlen($str) + mb_strlen($str, $charset)) / 2;

    // 计算总占宽
    $dimensions = imagettfbbox($fontSize, 0, $fontFamily, $str);
    $textWidth = abs($dimensions[4] - $dimensions[0]);

    // 计算每个字符的长度
    $singleW = $textWidth / $len;
    // 计算每行最多容纳多少个字符
    $maxCount = floor($width / $singleW);

    while ($len > $maxCount) {
        // 成功取得一行
        $result[] = mb_strimwidth($str, 0, $maxCount, '', $charset);
        // 移除上一行的字符
        $str = str_replace($result[count($result) - 1], '', $str);
        // 重新计算长度
        $len = (strlen($str) + mb_strlen($str, $charset)) / 2;
    }
    // 最后一行在循环结束时执行
    $result[] = $str;

    return $result;
}


/**
 * array_column 函数兼容
 */
if (!function_exists("array_column")) {

    function array_column(array &$rows, $column_key, $index_key = null)
    {
        $data = [];
        foreach ($rows as $row) {
            if (empty($index_key)) {
                $data[] = $row[$column_key];
            } else {
                $data[$row[$index_key]] = $row[$column_key];
            }
        }
        return $data;
    }

}