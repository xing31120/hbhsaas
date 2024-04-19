<?php
namespace app\common\model;
use app\common\model\basic\Common;
use think\model\concern\SoftDelete;
class BankCard extends Common{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;

    public $mcName = 'bank_card_';
    public $selectTime = 6;
    public $mcTimeOut = 6;
    public $status = [0 => '待审核', 10 =>'成功', 40 =>'失败'];
    public $cardType = [2 => '对公账户',3 => '个人银行卡',5 => '法人个人银行卡'];


    public function infoByTranceNum($appUid,$trance_num){
        if (empty($trance_num)) {
            return false;
        }
        $this->submeter($appUid);
        $where[] = ['trance_num','=',$trance_num];
        return $this->where($where)->find();
    }
    







}

