<?php
namespace app\common\exception;

use app\common\service\workSendMessage\WorkSendMessageService;
use app\common\tools\SysEnums;
use Exception;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\ValidateException;
use thans\jwt\exception\JWTException;
use thans\jwt\exception\TokenBlacklistException;
use thans\jwt\exception\TokenExpiredException;

class ApiException extends Handle{

    public function render(Exception $e){
        $returnData = [
            'result' => false,
            'msg' => '系统异常错误',
            'code' => SysEnums::ExceptionError,
        ];

        // 请求异常
        if ($e instanceof HttpException && (request()->isAjax() || request()->isPost())) {
            $returnData['msg'] = $e->getMessage();
            $returnData['code'] = $e->getStatusCode();
        }

        // 参数验证错误
        if ($e instanceof ValidateException) {
            $returnData['msg'] = $e->getError();
            $returnData['code'] = SysEnums::ValidateError;  //20100
        }

        if($e instanceof JWTException || $e instanceof TokenBlacklistException){
            $returnData['msg'] = 'token错误';
            $returnData['code'] = SysEnums::TokenError; //20200
        }

        if($e instanceof TokenExpiredException ){
            $returnData['msg'] = 'token已过期';
            $returnData['code'] = SysEnums::TokenExpiredError;  //20202 token过期
        }

        //正式环境  直接返回统一的错误
        if(!config('app.app_debug')){
            return json($returnData);
        }
        //调试模式下, 还是能够输出异常错误
        return parent::render($e);
    }


    protected function alarm(Exception $exception)
    {
        if(env('SAASWORK.SAAS_ERROR_PUSH',false)) {
            try {
                //将异常所在文件,以及行数 通知开发者 方便排查异常原因
                $date_time = date('Y-m-d H:i:s');
                $request = request();
                // 路径
                $url = $request->url(true);
//                $uri = $request->header('app-from');
//                $url = $url.$uri;
//            $requestRoot = $request->root();
//            $requestUri = $request->getRequestUri();
                // 来路


                $origin = $request->header('origin');
                $uri = urldecode($request->header('app-from'));
                $origin = $origin."/".$uri;
                $from_type = $request->header('app-type');

//        $user_agent = $request->userAgent();
                // 来路IP

                $ip = $request->ip();
                //请求方式
                $method = $request->method();
                //参数
                $requestAll = input();
                // 脱敏替换操作
                $replaceArr = ['password' => '******'];
                $replaceArr = array_intersect_key($replaceArr, $requestAll);
                $requestAll = array_replace_recursive($requestAll, $replaceArr);

                if (!empty($requestAll)) {
                    // 删除 URI 操作
                    $uri = "/" . $request->path();
                    if (array_key_exists($uri, $requestAll)) {
                        unset($requestAll[$uri]);
                    }
                    $request_all_to_json = json_encode($requestAll, JSON_UNESCAPED_UNICODE);
                } else {
                    $request_all_to_json = '';
                }
                $message = $this->getMessage($exception);

                $code = $this->getCode($exception);
                $file = $exception->getFile();
                $file = addslashes($file);
//                $file = str_replace("/\/",'\/',$file);
                $line = $exception->getLine();
//                $outArr['app_version'] = '未知';
//                if ($request->header('app-version')) {
//                    $app_version = $request->header('app-version', '');
//                    $outArr['app_version'] = $app_version;
//                }
                $admin_user['shop_uid'] = session('shop_uid') ?? 0;
                $admin_user['user_name'] = session('username') ?? '';
                $admin_user = json_encode($admin_user);
                $shop_txt['shop_uid'] =$request->shop_uid ?? 0;
                $shop_txt['uid'] =$request->uid ?? 0;
                $shop_txt = json_encode($shop_txt);

                $content = <<<EOF
### <font color="warning">骚年有BUG哦，了解一下！！！</font>
> 时间：{$date_time}
> 路径：{$url}
> 来路：{$origin}
> IP ：{$ip} 
> 方式：{$method} 
> 参数：{$request_all_to_json}
> 后台用户信息：{$admin_user}
> 用户信息：{$shop_txt}
> 错误：{$message} 
> 代码：{$code} 
> 文件：{$file}
> 位置：{$line} 
> 应用来路：{$from_type}
EOF;
                $errmsg = $exception->getMessage();
                (new WorkSendMessageService(env('SAASWORK.SAAS_ERROR_PUSH', '')))->sendMarkDown($content);
            } catch (Exception $e) {
                //需手动捕获异常,防止上文异常后死循环
                trace('消息发送失败:' . $exception->getMessage() . $errmsg, 'error');
            }
        }
    }

    public function report(Exception $e)
    {
        //过滤token异常通知
        if(!$e instanceof JWTException && !$e instanceof TokenExpiredException &&  !$e instanceof TokenBlacklistException){
            //异常通知
            $this->alarm($e);
        }
        //交由Thinkphp框架继续处理
        parent::report($e);
    }
}