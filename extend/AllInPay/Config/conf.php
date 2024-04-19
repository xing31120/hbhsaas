<?php

namespace AllInPay\Config;


class conf
{
   protected static $config;
    
    // 加载配置文件
    public function loadConf($confFile){
        $confFile = dirname(__FILE__).$confFile;
        if (is_file($confFile)){
             self::$config = require($confFile);
        }
    }
    
    public static function getInstance(){
        static $obj;
        if(!isset($obj)){
          $obj = new conf();
        }
        return $obj;
    }

    public function getConf($name){
        if(isset(self::$config[$name])){
            return self::$config[$name];
        }else{
            return " config $name is undefined ";
        }
    }

}
