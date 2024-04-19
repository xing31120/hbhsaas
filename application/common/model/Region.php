<?php
namespace app\common\model;
use app\common\model\basic\Single;

//地区模型
class Region extends Single{

    public $mcName = 'region_';
    public $selectTime = 3600;
    protected $connection = ['database'=>'saas_zzsupei_com_config'];
    protected $autoWriteTimestamp = false;//地区表不需要创建时间和修改时间

    //获取省份列表, 进行缓存
    function getProvincesList(){
        $op['where'][] = ['level','=',1];
        return $this->getList($op);
    }

    /**
     * 获取当前省份ID下所有信息, 进行缓存
     * @param $regionTopId  省份ID必填
     * @return basic\type|array
     * User: 宋星 DateTime: 2020/10/29 10:46
     */
    function getRegionRelationData( $regionTopId){
        if($regionTopId <= 0){
            return errorReturn('顶级ID错误');
        }

        $op['where'][] = ['top_id','=',$regionTopId];
        return $this->getList($op);
    }


    //
    function getLevelInfo($provincesID = null, $parentId = null){

        if($parentId <=0 && $provincesID <=0){
            return $this->getProvincesList();
        }

        $data = [];
        $allData = $this->getRegionRelationData($provincesID);
        $allData['count'] = count($allData['list']);
        $allDataNew = array_column($allData['list'], null,'id');

        $groupArr = array_group_by($allDataNew, 'pid');

        $data['count'] = count($groupArr[$parentId]);
        $data['data'] = array_values($groupArr[$parentId]);
        return $data;
    }

}
