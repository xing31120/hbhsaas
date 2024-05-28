<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Request;
use think\exception\HttpResponseException;
// 应用公共文件
function sqlTime($start_time, $end_time, $key, &$where, $strtotime = true) {
    if (empty($key)) {
        return $where;
    }
    //按时间查询
    $start_time = trim($start_time);
    $end_time = trim($end_time);
    if ($start_time && $end_time) {
        if ($strtotime == true) {
            $start_time = strtotime($start_time);
            $end_time = strtotime($end_time . ' 23:59:59');
        } else {
            $end_time = $end_time + 86399;
        }
        $where[$key] = array('BETWEEN', array($start_time, $end_time));
    } else {
        if ($start_time) {
            if ($strtotime == true) {
                $start_time = strtotime($start_time);
            }
            $where[$key] = array('EGT', $start_time);
        }
        if ($end_time) {
            if ($strtotime == true) {
                $end_time = strtotime($end_time . ' 23:59:59');
            } else {
                $end_time = $end_time + 86399;
            }
            $where[$key] = array('ELT', $end_time);
        }
    }
    return $where;
}

if (!function_exists('redis')) {
    /**
     * 获取容器对象实例
     * @return Redis
     */
    function redis()
    {
        return \app\common\tools\Redis::redis();
    }
}

/**
 * url转二维码
 * @param type $url
 * @return string
 */
function qrcode($url) {
    $file = RUNTIME_PATH . 'Cache/' . md5($url) . '.png';
    $cache = file_get_contents($file);
    if (empty($cache)) {
        $qrcode = new \Org\Qrcode\Qrcode();
        $qrcode::png($url, $file, QR_ECLEVEL_L, 3, 1);
        $cache = file_get_contents($file);
    }
    $file_content = base64_encode($cache); //base64编码的一个大字符串。。。
    $img = 'data:' . $type['mime'] . ';base64,' . $file_content; //合成图片的base64编码
    return $img;
}

/**
 * 获取时间范围
 * @param type $type
 * @return array
 */
function getTimeRange($type) {
    $time = time();
    switch ($type) {
        case 1://本月
            $timestamp = $time;
            $mdays = date('t', $timestamp);
            $ret['sdate'] = date('Y-m-1 00:00:00', $timestamp);
            $ret['edate'] = date('Y-m-' . $mdays . ' 23:59:59', $timestamp);
            break;
        case 2://本季度
            $season = ceil((date('n'))/3);//当月是第几季度
            $ret['sdate'] = date('Y-m-d H:i:s', mktime(0, 0, 0,$season*3-3+1,1,date('Y')));//本季度第一天
            $ret['edate'] = date('Y-m-d H:i:s', mktime(23,59,59,$season*3,date('t',mktime(0, 0 , 0,$season*3,1,date("Y"))),date('Y')));//本季度最后一天
            break;
        case 3://本年
            $ret['sdate'] = date('Y-m-d H:i:s', strtotime(date('Y') . '-1-1 0:0:0'));
            $ret['edate'] = date('Y-m-d H:i:s', strtotime((date('Y') + 1) . '-1-1 0:0:0') - 1);
            break;
        case 4://上个月
            $timestamp = strtotime('last month');
            $mdays = date('t', $timestamp);
            $ret['sdate'] = date('Y-m-1 00:00:00', $timestamp);
            $ret['edate'] = date('Y-m-' . $mdays . ' 23:59:59', $timestamp);
            break;
        case 5://上个季度
            $season = ceil((date('n'))/3)-1;//上季度是第几季度
            $ret['sdate'] = date('Y-m-d H:i:s', mktime(0, 0, 0,$season*3-3+1,1,date('Y')));//上季度第一天
            $ret['edate'] = date('Y-m-d H:i:s', mktime(23,59,59,$season*3,date('t',mktime(0, 0 , 0,$season*3,1,date("Y"))),date('Y')));//上季度最后一天
            break;
        case 6://上一年
            $ret['sdate'] = date('Y-m-d H:i:s', strtotime((date('Y') - 1) . '-1-1 0:0:0'));
            $ret['edate'] = date('Y-m-d H:i:s', strtotime(date('Y') . '-1-1 0:0:0') - 1);
            break;
        case 7://昨天
            $timestamp = $time - 86400;
            $mdays = date('Y-m-d', $timestamp);
            $ret['sdate'] = date('Y-m-d', $timestamp);
            $ret['edate'] = date('Y-m-d', $timestamp - 86400);
            break;
        case 8://前4周
            $now_w = date("w");
            if ($now_w ==1){
                $ret['sdate'] = date('Y-m-d',strtotime('-3 Monday'));
            }else{
                $ret['sdate'] = date('Y-m-d',strtotime('-4 Monday'));
            }
            $ret['edate'] = date('Y-m-d 23:59:59', time());
            break;
        case 9://本周
            $timestamp = $time;
            $w = date('w', $timestamp);
            $w = ($w + 6) % 7;
            $ret['sdate'] = date('Y-m-d', strtotime('-' . $w . ' day'));
            $ret['edate'] = date('Y-m-d 23:59:59', strtotime('-' . ($w-6) . ' day'));
            break;
        default://默认今天
            $timestamp = $time;
            $mdays = date('Y-m-d', $timestamp);
            $ret['sdate'] = date('Y-m-d', $timestamp);
            $ret['edate'] = date('Y-m-d', $timestamp + 86400);
            break;
    }
    $rs['s_time'] = strtotime($ret['sdate']);
    $rs['e_time'] = strtotime($ret['edate']);
    return $rs;
}

/**
 * 根据内容截取摘要
 * @param type $content 需截取内容
 * @param type $limit 截取摘要的字数
 * @return null
 */
function getDigestByContent($content, $limit = 100) {
    if ($content) {
        $digest = strip_tags(htmlspecialchars_decode($content, ENT_QUOTES));
        $digest = str_replace('&nbsp;', '', $digest);
        $limit = $limit > 0 ? $limit : 100;
        return mb_substr(trim($digest), 0, $limit, 'utf-8');
    }
    return '';
}

function array_sort($arr, $keys, $type = 'asc') {
    $keysvalue = $new_array = array();
    foreach ($arr as $k => $v) {
        $keysvalue[$k] = $v[$keys];
    }
    if ($type == 'asc') {
        //对数组进行排序并保持索引关系
        asort($keysvalue);
    } else {
        //对数组进行逆向排序并保持索引关系
        arsort($keysvalue);
    }
    foreach ($keysvalue as $k => $v) {
        $new_array[] = $arr[$k];
    }
    return $new_array;
}

function getRandomCode($length = 10){
    $length = intval($length);
    $length = $length > 0 ? $length : 10;
    return substr(str_shuffle("0123456789"), 0, $length);
}

function getRandomString($length = 10){
    $length = intval($length);
    $length = $length > 0 ? $length : 10;
    return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
}

/*根据月份数字获取中文名称*/
function getMonthByNum($month){
    switch($month){
        case '01':
            $rs = '一';
            break;
        case '02':
            $rs = '二';
            break;
        case '03':
            $rs = '三';
            break;
        case '04':
            $rs = '四';
            break;
        case '05':
            $rs = '五';
            break;
        case '06':
            $rs = '六';
            break;
        case '07':
            $rs = '七';
            break;
        case '08':
            $rs = '八';
            break;
        case '09':
            $rs = '九';
            break;
        case '10':
            $rs = '十';
            break;
        case '11':
            $rs = '十一';
            break;
        case '12':
            $rs = '十二';
            break;
        default:
            $rs = '';
            break;
    }
    return $rs;
}

/*根据年月获取对应月份的时间范围*/
function getRangeByMonth($year, $month, $tostr = 0){
    if($year <= 0 || $month <= 0){
        return false;
    }
    $start_time = mktime(0,0,0,$month,1,$year);
    $end_time = mktime(23,59,59,($month+1),0,$year);
    if($tostr == 1){
        $rs = ['s_time' => date('Y-m-d H:i:s',$start_time),'e_time' => date('Y-m-d H:i:s',$end_time)];
    }elseif($tostr == 2){
        $rs = ['s_time' => date('Y-m-d',$start_time),'e_time' => date('Y-m-d',$end_time)];
    }else{
        $rs = ['s_time' => $start_time,'e_time' => $end_time];
    }
    return $rs;
}

/**
 * 检验是否是手机号码
 * @param type $mobile
 * @return boolean
 */
function is_mobile($mobile) {
    if (preg_match("/^1[3-9][0-9]{9}$/", $mobile)) {
        return true;
    } else {
        return false;
    }
}

/**
 * 文件下载方法
 * @param type $file
 * @param type $file_name
 * @return boolean
 */
function downloadFile($file, $file_name = '') {
    //初始化curl连接
    $curl = curl_init($file);
    curl_setopt($curl, CURLOPT_NOBODY, true);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
    $result = curl_exec($curl);
    $bool = false;
    // 如果请求没有发送失败并且http响应码为200
    if ($result !== false && curl_getinfo($curl, CURLINFO_HTTP_CODE) == 200) {
        $bool = true;
    }
    //关闭curl连接
    curl_close($curl);
    //检验文件是否存在
    if ($bool === true) {
        //设置文件名称
        if ($file_name == '') {
            $file_name = pathinfo($file, PATHINFO_BASENAME);
        } else {
            $file_name = strpos($file_name, '.') ? $file_name : ($file_name . '.' . pathinfo($file, PATHINFO_EXTENSION)); //补充文件后缀
        }
        //通过文件头信息获取文件大小
        $header_array = get_headers($file, true);
        //设置header信息让浏览器识别并下载文件
        header('Content-Description: File Transfer');
        if(pathinfo($file, PATHINFO_EXTENSION) == 'mp3'){
            header('Content-Type: audio/mpeg');
        }else{
            header('Content-Type: application/octet-stream');
        }
        header('Content-Disposition: attachment; filename=' . $file_name);
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Accept-Ranges: bytes');
        header('Content-Length: ' . $header_array['Content-Length']);
        header('Content-Range: bytes 0-'.($header_array['Content-Length'] - 1).'/'.$header_array['Content-Length']);
        ob_clean();
        flush();
        readfile($file);
        exit;
    } else {
        return false;
    }
    /**
 * 随机验证码
 * @param type $length
 * @return string
 */
function rand_code($length = 8) {
        // 密码字符集
        $chars = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '9', '8', '7', '6', '5', '4', '3', '2', '1', '0');
        // 在 $chars 中随机取 $length 个数组元素键名
        $keys = array_rand($chars, $length);
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            // 将 $length 个数组元素连接成字符串
            $password .= $chars[$keys[$i]];
        }
        return $password;
    }

}

/**
 * 生成layui子孙树
 * @param $data
 * @return array
 */
function makeTree($data) {

    $res = [];
    $tree = [];

    // 整理数组
    foreach ($data as $key => $vo) {
        $res[$vo['id']] = $vo;
        $res[$vo['id']]['children'] = [];
    }
    unset($data);

    // 查询子孙
    foreach ($res as $key => $vo) {
        if($vo['pid'] != 0){
            $res[$vo['pid']]['children'][] = &$res[$key];
        }
    }

    // 去除杂质
    foreach ($res as $key => $vo) {
        if($vo['pid'] == 0){
            $tree[] = $vo;
        }
    }
    unset($res);

    return $tree;
}

function adminOut($res)
{
    return json(successReturn($res));
}
function adminOutError($res)
{
    return json(errorReturn($res));
}

/**
 * 将结果集对外输出
 * @author sx
 * @param $resultData
 * @return null
 */
function apiOut($resultData){
    $returnData = [
        'result' => true,
        'msg' => '',
        'code' => 0,
        'data'=>[]
    ];

    //header("Access-Control-Allow-Origin: *"); // 允许任意域名发起的跨域请求
    $returnData = array_merge($returnData, $resultData);
    if (Request::isPost() || Request::isAjax()){//ajax或post才输出json编码的值
        return json($returnData);
    }else{
        var_dump($returnData);exit;
    }
}

function apiOutError($msg = 'error', $code = \app\common\tools\SysEnums::AffairError, array $header = []){
    $result = [
        'result' => false,
        'msg' => $msg,
        'code' => $code,
        'data'=>[]
    ];
    $response = Response::create($result, 'json')->header($header);
    throw new HttpResponseException($response);
}

/**
 * 内部调用的 错误结果集
 * @param string $msg
 * @param int $code
 * @param array $data
 * @return array
 */
function errorReturn($obj = 'error', $code = \app\common\tools\SysEnums::AffairError, $resultData = [])
{
    $returnData = [
        'result' => false,
        'msg' => 'error',
        'code' => \app\common\tools\SysEnums::AffairError,
        'data' => []
    ];
    if (is_array($obj)) {
        $returnData = array_merge($returnData, $obj);
        $returnData['result'] = false;
        return $returnData;
    }
    if (is_string($obj)) {
        $returnData['msg'] = $obj;
    }
    $returnData['code'] = $code;
    $returnData = array_merge($returnData, $resultData);

    return $returnData;
}

/**
 * Notes: 内部调用的 成功结果集
 * @param array|string $obj
 * @param int $code
 * @param array $resultData
 * @return array
 * User: SongX DateTime: 2021/7/8 10:30
 */
function successReturn($obj, $code = 0, $resultData = [])
{
    $returnData = [
        'result' => true,
        'msg' => 'success',
        'code' => 0,
        'data' => []
    ];
    if (is_array($obj)) {
        $returnData = array_merge($returnData, $obj);
        return $returnData;
    }
    if (is_string($obj)) {
        $returnData['msg'] = $obj;
    }
    $returnData['code'] = $code;
    $returnData = array_merge($returnData, $resultData);

    return $returnData;
}

/*
 * uuid 唯一序列码
 */

function bizUserId($appCode, $uid){
    return $appCode . $uid;
}

function uuid($prefix = '')
{
    $chars = md5(uniqid(mt_rand(), true));
    $uuid  = substr($chars,0,8) . '-';
    $uuid .= substr($chars,8,4) . '-';
    $uuid .= substr($chars,12,4) . '-';
    $uuid .= substr($chars,16,4) . '-';
    $uuid .= substr($chars,20,12);
    return $prefix . $uuid;
}

/**用户密码加密方法
 * @param $str              待加密的字符串
 * @param string $auth_key  加密盐
 * @return string           加密后长度为32的字符串
 * User: 宋星 DateTime: 2020/11/5 16:57
 */
function user_md5($str, $auth_key = '4retg845sdfgdg6ret354'){
    return '' === $str ? '' : md5(md5($str) . $auth_key);
}

function getBizUid($bizUserId){
    $res = [
        'appUid' => substr($bizUserId,0,4),
        'bizUid' => substr($bizUserId,4)
    ];
    return $res;
}

function xml2arr($xml)
{
    $entity = libxml_disable_entity_loader(true);
    $data = (array)simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
    libxml_disable_entity_loader($entity);
    return json_decode(json_encode($data), true);
}

function arr2xml($data){
    return "<xml>" . _arr2xml($data) . "</xml>";
}

/**
 * XML内容生成
 * @param array $data 数据
 * @param string $content
 * @return string
 */
function _arr2xml($data, $content = ''){
    foreach ($data as $key => $val) {
        is_numeric($key) && $key = 'item';
        $content .= "<{$key}>";
        if (is_array($val) || is_object($val)) {
            $content .= _arr2xml($val);
        } elseif (is_string($val)) {
            $content .= '<![CDATA[' . preg_replace("/[\\x00-\\x08\\x0b-\\x0c\\x0e-\\x1f]/", '', $val) . ']]>';
        } else {
            $content .= $val;
        }
        $content .= "</{$key}>";
    }
    return $content;
}

/**
 * 价格转换 元转分
 * @param [type] $price
 * @return void
 * @author LX
 * @date 2021-01-07
 */
function rmbToPenny($price){
    return floatval($price) * 100;
}
/**
 * 价格转化 分转元
 * @param [type] $money
 * @return void
 * @author LX
 * @date 2021-01-07
 */
function pennyToRmb($penny){
    return sprintf("%.2f", $penny / 100);
}

if (!function_exists('setOrderSn')) {
    /**
     * Description:生成唯一订单号
     * User: Vijay <1937832819@qq.com>
     * Date: 2020/12/8
     * Time: 9:57
     * @param string $prefix 前缀
     * @param int $shop_uid 商家id
     * @param int $user_id 用户id
     * @param bool $repeat 是否重复
     * @return bool|string
     */
    function setOrderSn($prefix = 'MA', $shop_uid = 0, $repeat = false)
    {
        $orderSn = $prefix . date('YmdHis') . uniqueRandNumber();
//        if (isOrdersNumberRepeat($shop_uid, $orderSn) === false) {
//            //重置
//            $orderSn = setOrderSn($shop_uid, true);
//        }
        return $orderSn;
    }
}
if (!function_exists('uniqueRandNumber')) {
    /**
     * Description:
     * User: Vijay <1937832819@qq.com>
     * Date: 2020/12/18
     * Time: 20:39
     * @param int $len
     * @return int
     */
    function uniqueRandNumber($len = 6)
    {
        $min = pow(10, $len - 1);
        $max = pow(10, $len) - 1;
        return mt_rand($min, $max);
    }
}

if (!function_exists('pj')) {
    /**
     * Notes:
     * @param $data
     * @param int $isExit
     * User: SongX DateTime: 2021/9/23 11:49
     */
    function pj($data, $isExit = 1){
        echo "<pre>";
        echo json_encode($data);
        echo "</pre>";
        if ($isExit) exit;
    }
}
if (!function_exists('pr')) {
    /**
     * Description:打印数据
     * User: Vijay <1937832819@qq.com>
     * Date: 2020/11/14
     * Time: 15:47
     * @param $data
     * @param int $choice
     */
    function pr($data, $choice = 1)
    {
        if ($choice == 1) {
            echo "<pre>";
            print_r($data);
            echo "</pre>";
        } elseif ($choice == 2) {
            dump($data, true, null);
        } else {
            echo "<pre>";
            var_export($data);
            echo "</pre>";
        }
        exit;
    }
}
// 写日志
function write_logs($log,$dir)
{
    //日志全部都放在根目录下的log目录,如果指定目录不存在则尝试创建
    $log_path = \think\facade\Env::get('root_path').'runtime/log/'.$dir;
    if (!is_dir($log_path)) {
        mkdir($log_path, 0755, true);
    }
    $log_path .= '/'.date('Y-m-d',time()).'.txt';
    file_put_contents($log_path, "[log time]".date('Y-m-d H:i:s',time())."：[log info]\r\n".$log."\r\n", FILE_APPEND);
}
if (!function_exists('characet')) {
    /**
     * 判断字符串是否中文，如果是转为utf8
     * @param $data
     * @return false|string|string[]|null
     * User: cwh  DateTime:2021/9/23 21:12
     */
    function charAcet($data)
    {

        if (!empty($data)) {
            $fileType = mb_detect_encoding($data, array('UTF-8', 'GBK', 'LATIN1', 'BIG5'));

            if ($fileType != 'UTF-8') {

                $data = mb_convert_encoding($data, 'utf-8', $fileType);
            }
        }
        return $data;
    }
}

if (!function_exists('getSmsKey')) {
    function getSmsKey($mobile, $type = 0)
    {
        switch ($type) {
            case 1:     //注册
                $key = 'official_website_shop_user_mobile_register_' . $mobile;
                break;
            case 2:     //登录
                $key = 'official_website_shop_user_mobile_login_' . $mobile;
                break;
            case 3:     // 上课通知
                $key = 'notice_class_' . $mobile;
                break;
            case 4:     // 忘记密码
                $key = 'user_store_forget_pass_check' . $mobile;
                break;
            default:    //其他
                $key = 'official_website_shop_user_mobile_other_' . $mobile;
        }
        return $key;
    }
}

// 发送验证码
if (!function_exists('SendSmsCode')) {
    function SendSmsCode($mobile, $type = 0)
    {
        $key = getSmsKey($mobile, $type);
        if (empty($mobile)) return errorReturn(['msg' => 'empty phone!']);
        $code = mt_rand(1000, 9999);
        $sms_param = [
//            'RegionId' => "cn-hangzhou",
            'phoneNumbers' => $mobile,
            'signName' => env('sms.alisms_sign_name'),
            'templateCode' => 'SMS_465970872',
            'templateParam' => json_encode(['code' => $code]),
//            'shop_id' => $shop_uid,
        ];
//pj([$key, $sms_param]);
        $sms_service = new \app\common\service\AliyunSmsService();
        $return_msg = $sms_service->sendSms($sms_param);
//pj($return_msg);
        if ($return_msg['Code'] == 'OK') {
            \think\facade\Cache::set($key, $code, 3600);
            return successReturn(['msg' => 'send sms success!'. $code]);
        } else {
            return errorReturn('send sms error!');
        }
    }
}

//发送上课通知
if (!function_exists('SendSmsClassNotice')) {
    function SendSmsClassNotice($mobile_arr, $template_param_arr)
    {
//return successReturn(['msg' => 'send sms success!']);
        //$template_param_arr --> ${course}  ${start} ${end}.
        if (empty($mobile_arr)) return errorReturn(['msg' => 'empty phone!']);
        $sign = env('sms.alisms_sign_name');

        $sign_arr = [];
        foreach ($mobile_arr as $item) {
            $sign_arr[] = $sign;
        }

        $sms_param = [
            'phoneNumberJson' => json_encode($mobile_arr),
            'signNameJson' => json_encode($sign_arr),
            'templateCode' => 'SMS_467605096',
            'templateParamJson' => json_encode($template_param_arr),
        ];
//pj([$key, $sms_param]);
        $sms_service = new \app\common\service\AliyunSmsService();
        $return_msg = $sms_service->sendBatchSms($sms_param);
//pj($return_msg);
        if ($return_msg['Code'] == 'OK') {
//            \think\facade\Cache::set($key, $code, 3600);
            return successReturn(['msg' => 'send sms success!']);
        } else {
            return errorReturn(json_encode($return_msg));
        }
    }
}
