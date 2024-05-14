<?php
namespace app\shop\controller;

use app\common\service\StatisticsService;


class Index extends Base {

    function index() {

        // 设置模板变量
        $this -> view -> assign('title', 'Auth权限管理系统');
        // 渲染模板
        return $this -> view -> fetch('index');
    }

    function index_auth() {

        // 设置模板变量
        $this -> view -> assign('title', 'Auth权限管理系统');

        // 渲染模板
        return $this -> view -> fetch('index_auth');
    }

    // 控制台
    function console(){
        // 设置模板变量
        $this -> view -> assign('title', 'Auth权限管理系统');

        // 渲染模板
        return $this -> view -> fetch('console');
    }

    function swagger(){
        $path = 'C:/work/zzsupei.com/application'; //你想要哪个文件夹下面的注释生成对应的API文档
        $swagger = \OpenApi\scan($path);
        $swagger->saveAs('C:/work/zzsupei.com/public/swagger.json');

        $this->success('生成成功','/dist', '', 1);
        /*
        $swagger = \OpenApi\scan($path);
        // header('Content-Type: application/json');
        // echo $swagger;
        $swagger_json_path = 'D:/WampServer/WWW/tpSwagger/tp5/swaggerApi/swagger.json';
        $res = file_put_contents($swagger_json_path, $swagger);
        if ($res == true) {
            $this->redirect('http://localhost/tpSwagger/swagger-ui/dist/index.html');
        }

        \Swagger\scan($path = 'C:/work/zzsupei.com/application')
        $swagger=\Swagger\scan(__DIR__);
        $res=$swagger->saveAs('./swagger.json');
        */
    }

    /*
    首页统计 校区, 总会员数量, 在读会员数量, 总剩余课时->有效课时,
    Y轴课时消耗,
    */
    function homepage(){
        $StatisticsService = new StatisticsService();
        $memberInfo = $StatisticsService->homepageStatistics($this->shop_id);
        $this->assign('memberInfo',$memberInfo);
//pr($memberInfo);
        return $this->fetch('homepage');
    }

    public function ajaxBookDay(){
        $statisticsService = new StatisticsService();

//        $day_num = 30;
//        $orderSevenDay = $statisticsService->getBookLastNumDay($day_num, $this->shop_id);

        $start_date = input('start_date', '');
        $end_date = input('end_date', '');
        if(empty($start_date) || empty($end_date)){
            return adminOut('');
        }
        $orderSevenDay = $statisticsService->getBookStartEnd($start_date, $end_date, $this->shop_id);
        $sum_count = 0;
        foreach( $orderSevenDay as $key => $val){
            $o_s_days[] = $val['day'];
            $o_s_count[] = (int)$val['count'];
            $sum_count += (int)$val['count'];
        }
        $orderSevenDayArr = [
            'day_arr'  => $o_s_days,
            'count_arr' => $o_s_count,
        ];
        return adminOut(['data'=>$orderSevenDayArr, 'sum_count' => $sum_count,]);
    }



    //获取指定最近天数的订单数据
    public function ajaxOrderDay(){
        $day_num = 30;
        $StatisticsService = new StatisticsService();
        $orderSevenDay = $StatisticsService->getOrderLastNumDay($day_num);
        foreach( $orderSevenDay as $key => $val){
            $o_s_days[] = $val['day'];
            $o_s_count[] = (int)$val['count'];
            $o_s_amount[] = pennyToRmb($val['amount']);
        }
        $orderSevenDayArr = [
            'day_arr'  => $o_s_days,
            'count_arr' => $o_s_count,
            'amount_arr'    =>  $o_s_amount,
        ];
        return adminOut(['data'=>$orderSevenDayArr]);
    }

}
