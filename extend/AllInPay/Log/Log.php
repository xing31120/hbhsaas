<?php

namespace AllInPay\Log;
use Exception;
use think\facade\Env;

/**
 * PHP log 类
 */
class Log{
    private $LogFile;
    private $logLevel;
    const DEBUG = 100;
    const INFO  = 75;
    const NOTICE = 50;
    const WARNING =25;
    const ERROR  = 10;
    const CRITICAL = 5;
    const LOG_CONFIG = [
        'LOG_FILE'=> 'yunLog.txt',
        'LOG_LEVEL'=>777 //INFO
    ];

    public function __construct(){
//        $cfg = Config::getConfig();
        $logPath = Env::get('root_path').'runtime/log/'.date('Ym') .'/AllInPay-'.date('d').'.txt';

        $cfg = LOG::LOG_CONFIG;
        $this->logLevel = isset($cfg['LOG_LEVEL']) ? $cfg['LOG_LEVEL']:LOG::INFO;
        if(!isset($logPath) && strlen($logPath)){
            throw new Exception('can\'t set file to empty');
        }
        $this->LogFile = @fopen($logPath,'a+');
        if(!is_resource($this->LogFile)){
            throw new Exception('invalid file Stream');
        }
    }
    public static function getInstance(){
        static $obj;
        if(!isset($obj)){
            $obj = new Log();
        }
        return $obj;
    }
    public function LogMessage($msg, $logLevel = Log::INFO,$module = null){
        if($logLevel > $this->logLevel){
            return ;
        }
        date_default_timezone_set('Asia/shanghai');
        $time = strftime('%x %X',time());
        $msg = str_replace("\t",'',$msg);
        $msg = str_replace("\n",'',$msg);
        $strLogLevel = $this->levelToString($logLevel);
        if(isset($module)){
            $module = str_replace(array("\n","\t"),array("",""),$module);
        }
        $logLine = "$time\t$msg\t$strLogLevel\t$module\r\n";
        fwrite($this->LogFile,$logLine);
    }
    public function levelToString($logLevel){
        $ret = '[unknow]';
        switch ($logLevel){
            case LOG::DEBUG:
                $ret = 'LOG::DEBUG';
                break;
            case LOG::INFO:
                $ret = 'LOG::INFO';
                break;
            case LOG::NOTICE:
                $ret = 'LOG::NOTICE';
                break;
            case LOG::WARNING:
                $ret = 'LOG::WARNING';
                break;
            case LOG::ERROR:
                $ret = 'LOG::ERROR';
                break;
            case LOG::CRITICAL:
                $ret = 'LOG::CRITICAL';
                break;
        }
        return $ret;
    }
}
?>
