<?php
namespace app\common\service;

class RegionService{
    static $mcTime = 604800;//默认缓存时间(7天)

    //获取单条数据
    public static function info($id, $field = ''){
        $info = model('Region')->info($id, $field);
        return $info;
    }

    //获取第一级地区数据(省份)
    public static function getProvince(){
        $key_name = 'region_province_list';
        $rs = cache($key_name);
        if($rs == false){
            $rs = model('Region')->where('pid',0)->column('id,ext_name');
            cache($key_name,$rs,self::$mcTime);
        }
        return $rs;
    }

    //获取下级地区数据
    public static function getChildren($id){
        $key_name = 'region_children_list_'.$id;
        $rs = cache($key_name);
        if($rs == false){
            $rs = model('Region')->where('pid',$id)->column('id,ext_name');
            cache($key_name,$rs,self::$mcTime);
        }
        return $rs;
    }

    //根据名称获取地区数据
    public static function getByName($name){
        $key_name = 'region_get_by_name_'.md5($name);
        $rs = cache($key_name);
        if($rs == false){
            $rs = model('Region')->where('ext_name',$name)->get();
            cache($key_name,$rs,self::$mcTime);
        }
        return $rs;
    }
}