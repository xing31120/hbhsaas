<?php


namespace app\common\service;

use \PHPExcel_Style_Alignment;

class PhpExcelService{
    const ASCII_BASE = 65;

    //导出表格第一行基础配置
    static function exportBase($config,$title){
        $minLetter = false;
        $maxCount = count($config) -1;
        $maxLetter = self::letterComputerByInt($maxCount);
        $objPHPExcel = new \PHPExcel();
        // 操作第一个工作表
        $objPHPExcel->setActiveSheetIndex(0);
        // 设置sheet名
        $objPHPExcel->getActiveSheet()->setTitle($title);
        //设置第一行内容
        $row_num = 1;
        $configNew = [];
        foreach ($config as $letterKey => $v){
            $ziMu = self::letterComputerByInt($letterKey);

            $configNew[$ziMu] = $v;
            // 设置表格宽度, 和列名
            $objPHPExcel->getActiveSheet()->getColumnDimension($ziMu)->setWidth($v['width']);
            $objPHPExcel->getActiveSheet()->setCellValue($ziMu . $row_num, $v['title']);
            if($minLetter === false && isset($v['is_merge']) && $v['is_merge']){
                $minLetter = $ziMu;
            }
        }
        $objPHPExcel->getActiveSheet()->getStyle('A'.$row_num.':'.$maxLetter.$row_num)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);//水平居中
        $objPHPExcel->getActiveSheet()->getStyle('A'.$row_num.':'.$maxLetter.$row_num)->getFont()->setBold(true)->setSize(13);//设置字体大小
        $objPHPExcel->getActiveSheet()->getstyle("A".$row_num.":".$maxLetter.$row_num)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中

        return ['objPHPExcel' => $objPHPExcel, 'minLetter' => $minLetter, 'config' =>$configNew];
    }

    /**
     * Notes:订单导出
     * @param $dataList 循环数据
     * @param $config 配置
     * @param $title 标题
     * @return \PHPExcel
     * @author chenyanmu
     * @date 2021-04-21 10:40
     */
    static function exportOrderNew($dataList,$config,$title){
        $returnBase = self::exportBase($config, $title);
        $objPHPExcel = $returnBase['objPHPExcel'];
        $configNew = $returnBase['config'];
        $minLetter = $returnBase['minLetter'];
        $maxLetter = self::letterComputerByInt(count($config) -1);

        $row_num = 2;
        // 向每行单元格插入数据
        foreach ($dataList as $key => $value) {
            $objPHPExcel->getActiveSheet()->getRowDimension($row_num)->setRowHeight(24);
            $objPHPExcel->getActiveSheet()->getStyle('A'.$row_num.':'.$maxLetter.$row_num)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);//水平居中
            $objPHPExcel->getActiveSheet()->getstyle("A".$row_num.":".$maxLetter.$row_num)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中

            //订单商品数量合并到第几列
            $maxRowNum = $goods_num = count($value['order_goods']) + $row_num - 1;
            //分佣明细数量合并到第几列
            $process_detail_num = isset($value['process_detail']) ? count($value['process_detail']) + $row_num - 1 : 0;
            if($process_detail_num > $maxRowNum){
                $maxRowNum = $process_detail_num;
            }

            //如果查询没有数据，最大行数要一致
            if ($maxRowNum < $row_num) $maxRowNum = $row_num;


            foreach ($configNew as $letter => $v) {
                if (isset($v['is_merge']) && $v['is_merge']) continue;
                $objPHPExcel->getActiveSheet()->mergeCells($letter . $row_num . ':' . $letter . $maxRowNum);
                $objPHPExcel->getActiveSheet()->setCellValue($letter . $row_num, $value[$v['column']] ?? '');
            }

            $mergeData = [];
            for ($i = 0; $i< $maxRowNum - $row_num + 1; $i++){
                $arrProcess = $arrGoods = [];
                if(isset($value['process_detail'][$i])){
                    $arrProcess = $value['process_detail'][$i];
                }
                if(isset($value['order_goods'][$i])){
                    $arrGoods = $value['order_goods'][$i];
                }

                $temp = array_merge($arrProcess, $arrGoods);
                $mergeData[] = $temp;
            }

            //商品+分佣明细
            $order_goods_num = $row_num;
            foreach ($mergeData as $order_good) {
                $objPHPExcel->getActiveSheet()->getRowDimension($order_goods_num)->setRowHeight(24);
                $objPHPExcel->getActiveSheet()->getStyle($minLetter.$order_goods_num.':'.$maxLetter.$order_goods_num)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);//水平居中
                foreach ($configNew as $letter => $v) {
                    if (!isset($v['is_merge']) || !$v['is_merge']) continue;
                    $cellValue = $order_good[$v['column']] ?? '';
                    $objPHPExcel->getActiveSheet()->setCellValue($letter . $order_goods_num, $cellValue);
                }
                $order_goods_num ++;
            }

            $maxRowNum++;
            $row_num = $maxRowNum;
        }
        return $objPHPExcel;
    }

    /**
     * Notes:订单导出
     * @param $dataList 循环数据
     * @param $config 配置
     * @param $title 标题
     * @return \PHPExcel
     * @author chenyanmu
     * @date 2021-04-21 10:40
     */
    static function exportNormal($dataList, $config, $title){
        $returnBase = self::exportBase($config, $title);
        $objPHPExcel = $returnBase['objPHPExcel'];
        $configNew = $returnBase['config'];
        $minLetter = $returnBase['minLetter'];
        $maxLetter = self::letterComputerByInt(count($config) -1);

        $row_num = 2;
        // 向每行单元格插入数据
        foreach ($dataList as $key => $value) {
            $objPHPExcel->getActiveSheet()->getRowDimension($row_num)->setRowHeight(24);
            $objPHPExcel->getActiveSheet()->getStyle('A'.$row_num.':'.$maxLetter.$row_num)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);//水平居中
            $objPHPExcel->getActiveSheet()->getstyle("A".$row_num.":".$maxLetter.$row_num)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
            $objPHPExcel->getActiveSheet()->getstyle("A".$row_num.":".$maxLetter.$row_num)->getAlignment()->setWrapText(true);//支持换行

            foreach ($configNew as $letter => $v) {
//                if (isset($v['is_merge']) && $v['is_merge']) continue;
//                $objPHPExcel->getActiveSheet()->mergeCells($letter . $row_num . ':' . $letter . $maxRowNum);
                $objPHPExcel->getActiveSheet()->setCellValue($letter . $row_num, $value[$v['column']] ?? '');
//                pj($value[$v['column']], 0);
            }
//            pj($value);
//            foreach ($configNew as $letter => $v) {
//                $cellValue = $order_good[$v['column']] ?? '';
//                $objPHPExcel->getActiveSheet()->setCellValue($letter . $order_goods_num, $cellValue);
//            }

            $row_num++;
        }
        return $objPHPExcel;
    }



    public static function exportOrder($dataList,$config,$title,$letter){

        $letterArr = ['R','S','T','U','V','W','X','Y','Z','AA'];
        $minLetter = 'R';
        $maxLetter = 'AB';
        $letterArr = $letter['letterArr'];
        $minLetter = $letter['minLetter'];
        $maxLetter = $letter['maxLetter'];

        $objPHPExcel = new \PHPExcel();
        // 操作第一个工作表
        $objPHPExcel->setActiveSheetIndex(0);
        // 设置sheet名
        $objPHPExcel->getActiveSheet()->setTitle($title);
        // 设置表格宽度
        foreach ($config as $letter => $v){
            $objPHPExcel->getActiveSheet()->getColumnDimension($letter)->setWidth($v['width']);
        }
        //设置第一行内容
        $row_num = 1;
        $objPHPExcel->getActiveSheet()->getStyle('A'.$row_num.':'.$maxLetter.$row_num)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);//水平居中
        $objPHPExcel->getActiveSheet()->getStyle('A'.$row_num.':'.$maxLetter.$row_num)->getFont()->setBold(true)->setSize(13);//设置字体大小

        foreach ($config as $letter => $v) {
            $objPHPExcel->getActiveSheet()->setCellValue($letter . $row_num, $v['title']);
        }

        $row_num ++;
        // 向每行单元格插入数据

        foreach ($dataList as $key => $value) {
            $objPHPExcel->getActiveSheet()->getRowDimension($row_num)->setRowHeight(24);
            $objPHPExcel->getActiveSheet()->getStyle('A'.$row_num.':'.$maxLetter.$row_num)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);//水平居中
            $objPHPExcel->getActiveSheet()->getstyle("A".$row_num.":".$maxLetter.$row_num)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直居中
            //订单
            $goods_num = count($value['order_goods']) + $row_num - 1;
            //如果查询没有数据，行数要一致
            if ($goods_num < $row_num) $goods_num = $row_num;

            foreach ($config as $letter => $v) {
                if (in_array($letter,$letterArr)) continue;
                $objPHPExcel->getActiveSheet()->mergeCells($letter . $row_num . ':' . $letter . $goods_num);
                $objPHPExcel->getActiveSheet()->setCellValue($letter . $row_num, $value[$v['column']]??'');
            }

            //商品
            $order_goods_num = $row_num;
            foreach ($value['order_goods'] as $order_good) {
                $objPHPExcel->getActiveSheet()->getRowDimension($order_goods_num)->setRowHeight(24);
                $objPHPExcel->getActiveSheet()->getStyle($minLetter.$order_goods_num.':'.$maxLetter.$order_goods_num)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);//水平居中
                foreach ($config as $letter => $v) {
                    if (!in_array($letter,$letterArr)) continue;
                    $objPHPExcel->getActiveSheet()->setCellValue($letter . $order_goods_num, $order_good[$v['column']]??'');
                }
                $order_goods_num ++;
            }

            $goods_num++;
            $row_num = $goods_num;
        }
        return $objPHPExcel;
    }

    /**
     * Notes:Excel输出
     * @param $title 输出标题
     * @param $ret 输出内容
     * @author chenyanmu
     * @date 2021-04-21 10:42
     */
    public static function excelOut($title,$ret){
        $outputFileName = $title.time().'.xls';
        $xlsWriter      = new \PHPExcel_Writer_Excel5($ret);
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition:inline;filename="' . $outputFileName . '"');
        header("Content-Transfer-Encoding: binary");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        $xlsWriter->save("php://output");
        exit;
    }

    static function letterComputerByInt($letterCount){
        $ziMuLoop = intval($letterCount / 26);
        if($ziMuLoop <= 0){
            $ziMu = chr(self::ASCII_BASE + $letterCount);
        }else{
            $ziMu = '';
            for ($i = 0; $i< $ziMuLoop; $i++){
                $ziMu .= chr(self::ASCII_BASE);
            }
            $remainder = $letterCount % 26;
            $ziMu .= chr(self::ASCII_BASE + $remainder);
        }

        return $ziMu;
    }

    /**
     * 导出csv文件(大数据量处理)
     * @param $file_name string 文件名
     * @param $header array 第一行标题
     * @param $data array 导出的数据
     * @author lfcheng
     * @date 8/19/21 2:06 PM
     */
    public static function exportCsv($file_name,$header,$data){
        set_time_limit(0);
        header('Content-Encoding: UTF-8');
        header('Content-type:application/vnd.ms-excel;charset=UTF-8');
        header('Content-Disposition: attachment;filename="' . $file_name . date('YmdHis') . '.csv"');
        //打开php标准输出流
        $fp = fopen('php://output', 'a');
        //添加BOM头，以UTF8编码导出CSV文件，如果文件头未添加BOM头，打开会出现乱码。
        fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF));
        //添加导出标题
        fputcsv($fp, $header);
        $num = 0;
        //数据导出
        foreach ($data as $item) {
            fputcsv($fp, $item);
            $num++;
            //每5000条数据就刷新缓冲区
            if($num == 5000){
                ob_flush();
                flush();
                $num = 0;
            }
        }
        exit;
    }
}