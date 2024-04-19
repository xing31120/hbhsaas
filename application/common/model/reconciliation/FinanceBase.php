<?php
namespace app\common\model\reconciliation;
use app\common\model\basic\Single;

class FinanceBase extends Single {

    const CLRG_STCD = [
        1 => '未分账',
        2 => '分账成功',
        4 => '分账异常'
    ];

    /**
     * 通过文件读取数据
     * User: cwh  DateTime:2021/9/23 15:08
     * 市场编号-日期-sum.txt
     */
    function readFile($dir_name,$file_name){
        //获取文件的编码方式
        //file_get_contents() 函数把整个文件读入一个字符串中。
        $file = fopen($dir_name.$file_name, "r");
        $file_data=array();
        $i=0;
//输出文本中所有的行，直到文件结束为止。
        while(! feof($file))
        {
            $file_data[$i]= fgets($file);//fgets()函数从文件指针中读取一行
            $file_data[$i] = charAcet($file_data[$i]);
            $i++;
        }
        fclose($file);
        $file_data=array_filter($file_data);
        //组装数据
        return $file_data;
    }

    public function formData($data,$summary_header,$summary_header_rules){
        $arr = [];
        $time = time();
        foreach($data as $k=>$v){
            $v = explode("|",$v);
            foreach($v as $k1=>$v1){
                switch($summary_header_rules[$k1]){
                    case 'amount':
                        $arr[$k][$summary_header[$k1]] = bcmul($v1 , 100);
                        break;
                    default:
                        $arr[$k][$summary_header[$k1]] = $v1;
                        break;
                }
            }
            $arr[$k]['create_time'] = $time;
            $arr[$k]['update_time'] = $time;
        }
        return $arr;
    }

    /**
     * 格式化字符串和时间
     * @param $str
     * @param int $type 1 格式化日期  2格式化时间
     * @return string
     * User: cwh  DateTime:2021/11/5 10:45
     */
    public function formatData($str,$type = 1){
        if(empty($str)){
            return time();
        }
        if($type ==1){
            $date = $this->str_insert($str,4,"-");
            $date = $this->str_insert($date,7,"-");
        }else if($type ==2){
            $date = $this->str_insert($str,2,":");
            $date = $this->str_insert($date,5,":");
        }else{
            $date = $this->str_insert($str,4,"-");
            $date = $this->str_insert($date,7,"-");
            $date = $this->str_insert($date,10," ");
            $date = $this->str_insert($date,13,":");
            $date = $this->str_insert($date,16,":");
        }
        return $date;
    }

    public function str_insert($str, $i, $substr)
    {
        $startstr = "";
        $laststr = "";
        for($j=0; $j<$i; $j++){
            $startstr .= $str[$j];
        }
        for ($j=$i; $j<strlen($str); $j++){
            $laststr .= $str[$j];
        }
        $str = ($startstr . $substr . $laststr);
        return $str;
    }

}
