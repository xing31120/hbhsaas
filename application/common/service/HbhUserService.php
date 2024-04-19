<?php


namespace app\common\service;


use app\common\model\HbhUsers;

class HbhUserService
{
    private static $model = null;

    public static function getModel()
    {
        self::$model = new HbhUsers();
        return self::$model;
    }

    public static function updateByWhere($upData, $where)
    {
        return HbhUsers::update($upData, $where);
    }


    function updateById($id, $upData){
        return (new HbhUsers())->updateById($id, $upData);
    }
}
