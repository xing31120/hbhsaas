<?php

namespace app\shop\controller;

use app\common\service\AliyunOssService;
use app\common\service\AliyunSmsService;
use app\common\service\ImageInfoService;
use app\common\service\RegionService;
use app\interfaces\controller\TxLbs;
use think\facade\Lang;

class Tools extends Base
{


    /**
     * Notes:上传方法
     * User :Chenyanmu
     * datatime:2021/10/12 9:35
     * @return \think\response\Json
     */
    public function upload()
    {
//echo ROOT_PATH;exit();
        $data = $_FILES;
        $file = basename($data['file']['name']);
        $temp = explode('.', $file);
        $local_path = $data['file']['tmp_name'];
//        $yun_path = 'saas/upload/'.date('Ymd').'/'.md5($temp[0].time()).'.'.$temp[1];
//        $AliyunOssService = new AliyunOssService();
//        $bool = $AliyunOssService->upload($yun_path, $local_path);
        $move_to_path = PUBLIC_PATH.'upload/'.date('Ymd').'/'.md5($temp[0].time()).'.'.$temp[1];
        $src_path = '/upload/'.date('Ymd').'/'.md5($temp[0].time()).'.'.$temp[1];
        $dirname = dirname($move_to_path);
        //创建目录失败
        if (!file_exists($dirname) && !mkdir($dirname, 0777, true)) {
//            $this->stateInfo = $this->getStateInfo("ERROR_CREATE_DIR");
//            return;
            return adminOutError(['msg' => Lang::get('ErrorCreateDir')]);
        } else if (!is_writeable($dirname)) {
//            $this->stateInfo = $this->getStateInfo("ERROR_DIR_NOT_WRITEABLE");
//            return;
            return adminOutError(['msg' => Lang::get('ErrorDirNotWriteable')]);
        }


        if (!(move_uploaded_file($local_path, $move_to_path) && file_exists($move_to_path) )) { //文件存在, 移动失败
            $bool = false;
        } else { //移动成功
            $bool = true;
        }

        if ($bool) {
//            $yun_path = '/'.$yun_path;
            $yun_path = '/public/upload/'.date('Ymd').'/'.md5($temp[0].time()).'.'.$temp[1];
            $resData = [
                'file_name' => $file,
//                'src' => setImgUrl($yun_path),
                'src' => $src_path,         //主要看这个
                'file_path' => $yun_path
            ];
//            ImageInfoService::addInfo($resData['src']);
            return adminOut(['msg' => '上传成功', 'data' => $resData]);
        } else {
            return adminOutError(['msg' => '上传失败']);
        }
    }

    /**
     * Notes:下载文件
     * User :Chenyanmu
     * datatime:2021/10/12 9:35
     * @return bool
     */
    public function download()
    {
        $data = input();
        $data['url'] = str_replace('https://', 'http://', $data['url']);
        return downloadFile($data['url']);
    }

    public function downloadImg()
    {
        $data = input();
      //  $url = str_replace('https://', 'http://', $data['url'] ?? '');
        $url =$data['url'] ?? '';
        //图片路径
        $file_name = 'qrcode_img_'.time().'.png';    //保存的图片名称
        @header('Content-Description: File Transfer');
        @header('Content-Type: application/octet-stream');
        @header('Content-Disposition: attachment; filename='.$file_name);
        @header('Content-Transfer-Encoding: binary');
        @header('Expires: 0');
        @header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        @header('Pragma: public');
        @header('Content-Length: '.filesize($url));
        ob_clean();
        flush();
        readfile($url);
        exit;
    }

    /**
     * Notes:异步获取地区
     * User :Chenyanmu
     * datatime:2021/10/12 9:35
     * @return |null
     */
    public function ajaxGetRegion()
    {
        $id = input('id', 0);
        return apiOut(['data' => RegionService::getChildren($id)]);
    }

    /**
     * Notes:获取腾讯坐标
     * User :Chenyanmu
     * datatime:2021/10/12 9:35
     * @return float[]|int[]
     */
    public function ajaxTxMap()
    {
        $data = input();

        $lat = 39.916527;
        $lng = 116.397128;

        if (empty($data['province_id']) || empty($data['city_id']) || empty($data['district_id']) || empty($data['address'])) {
            return ['lng' => $lng, 'lat' => $lat];
        }
        $ret = txMap($data);
        return ['lng' => $ret['lng'], 'lat' => $ret['lat']];
    }

    public function sendXTuanSMS() {
        $mobile = input('mobile');
        $mobile = trim($mobile);

        if (empty($mobile)) {
            return errorReturn(['code' => 2000, 'msg' => '请输入手机号!']);
        }
        $key = 'x_tuan_bind_job_user_verify_' . $mobile;
        $tmp_value = redis()->get($key);
        if (empty($tmp_value)) {
            //测试环境
            if(!env('APP.IS_ONLINE')) {
                $code = 1234;
                redis()->set($key, $code, 600);
                return successReturn(['msg' => '短信发送成功']);
            }
            $code = mt_rand(1000, 9999);
            $sms_param = [
                'RegionId' => "cn-hangzhou",
                'PhoneNumbers' => $mobile,
                'SignName' => 'X团网',
                'TemplateCode' => 'SMS_163195078',
                'TemplateParam' => json_encode(['code' => $code]),
                'shop_uid' => $this->shop_uid,
            ];
            $sms_service = new AliyunSmsService();
            $return_msg = $sms_service->sendSms($sms_param);
            if ($return_msg['Code'] == 'OK') {
                redis()->set($key, $code, 600);
                return successReturn(['msg' => '短信发送成功']);
            } else {
                return errorReturn('短信发送失败!');
            }
        } else {
            return errorReturn('短信已发送，你有10分钟的有效时间!');
        }

    }

    public function verifyXTuanSMS() {
        $mobile = input('mobile_phone');
        $mobile = trim($mobile);
        $code = input('sms_code');
        if (empty($code)) {
            return errorReturn('请输入验证码');
        }
        if (empty($mobile)) {
            return errorReturn('请输入手机号码');
        }
        $key = 'x_tuan_bind_job_user_verify_' . $mobile;
        $cache_value = redis()->get($key);
        if ($code == $cache_value) {
            return successReturn(['msg' => '验证成功']);
        } else {
            return errorReturn('验证失败');
        }
    }
}
