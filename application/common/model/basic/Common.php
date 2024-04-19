<?php
namespace app\common\model\basic;
use think\Db;
use think\Model;
use app\common\tools\SysEnums;

//多表模型
class Common extends Model{
    /**
     * 缓存主键
     * @var type
     */
    protected $pk = 'id';
    /**
     * 缓存名称
     * @var type
     */
    public $mcName = '';
    /**
     * 缓存过期时间
     * @var type
     */
    protected $mcTimeOut = 600;   //默认缓存过期时间
    /**
     * 缓存开关
     * @var type
     */
    protected $mcOpen = true;   //缓存开关
    /**
     * 自动时间戳(默认是create_time和update_time)
     * 若需要调整则自定义以下两个参数
     * protected $createTime
     * protected $updateTime
     * @var bool
     */
    protected $autoWriteTimestamp = true;
    /**
     * 分库标记字段
     * @var type
     */
    protected $submeterId = 'shop_id';
    /**
     * 是否分表
     * @var type
     */
    protected $isSubmeter = true;
    /**
     * 分表数量
     * @var type
     */
    protected $submeterNum = 10;
    /**
     * 列表查询缓存时间
     * @var type
     */
    protected $selectTime = 0;

    /**
     * 分表
     * @param type $uid
     * @return type
     */
    public function submeter($uid) {
        $num = $this->submeterNum > 0 ? $this->submeterNum : 10;
        $remainder = (int)$uid % $num;
        $remainder = 0;
        if ($remainder == 0) {
            $database = 'finance';
        } else {
            $database = 'finance' . '_' . $remainder;
        }
        $this->connection = array_merge(config('database.'),['database'=>$database]);
    }

    /**
     * 删除缓存
     * @param type $id
     * @param type $uid 强制uid
     */
    public function delCache($id = 0, $uid = 0) {
        if($this->mcOpen && $id > 0 && $uid > 0){
            $mcKey = $this->mcName . '_' . $uid . '_' . $id;
            cache($mcKey, null);
        }
        return true;
    }

    /**
     * 设置列表缓存时间
     * @param type $time
     */
    public function setSelectTime($time = 0) {
        $this->selectTime = $time;
    }

    /**
     * 查看主键信息
     * @param type $id 自定义的主键$mcPkId
     * @param type $field 字段
     * @return type
     */
    public function info($id, $uid, $field = '') {
        if (!is_numeric($id) || !is_numeric($uid)) {
            return false;
        }
        //分库
        if ($this->isSubmeter) {
            $this->submeter($uid);
        }
        //判断是否开启缓存
        if ($this->mcOpen) {
            $mcKey = $this->mcName . '_' . $uid . '_' . $id;
            $rs = cache($mcKey);
            if ($rs === false) {
                //,$this->submeterId=>$uid
                $rs = $this->where(array($this->pk => $id))->findOrEmpty()->toArray();
                $time = $this->mcTimeOut > 0 ? $this->mcTimeOut : 0;
                if(!empty($rs)){
                    cache($mcKey, $rs, $time);
                }
            }
        } else {
            //,$this->submeterId=>$uid
            $rs = $this->where(array($this->pk => $id))->findOrEmpty()->toArray();
        }
        return $field ? $rs[$field] : $rs;
    }

    /**
     * 根据 bizUserId 获取用户信息
     * @param [type] $bizUserId
     * @return void
     * @date 2020-11-17
     */
    function infoByBizUserId( $bizUserId){
        if (empty($bizUserId)) {
            return false;
        }
        $appUid = substr($bizUserId,0,4);
        $bizUid = substr($bizUserId,4);
        return $this->infoByBizUid( $appUid, $bizUid );
    }

    /**
     * 插入
     * @param type $insert
     * @return boolean
     */
    public function insert($uid, $insert) {
        if (!is_numeric($uid)) {
            echo '缺少'.$this->submeterId;
            exit;
        }
        //分库
        if ($this->isSubmeter) {
            $this->submeter($uid);
        }
//        if(!isset($insert[$this->submeterId])){
//            $insert[$this->submeterId] = $uid;
//        }
        $rs = $this->save($insert);
        if ($rs === false) {
            return false;
        }
        $id = $this[$this->pk];
        $this->insert_after($id, $uid, $insert);
        return $id;
    }

    public function insert_after($id, $uid, $insert){

    }

    //根据submeterId分表条件的批量插入
    public function insertAll($uid, $dataSet = [], $replace = false, $limit = null){
        if (!is_numeric($uid)) {
            echo '缺少'.$this->submeterId;
            exit;
        }
        //分库
        if ($this->isSubmeter) {
            $this->submeter($uid);
        }
        return parent::insertAll($dataSet, $replace, $limit);
    }

    /**
     * 更新
     * @param type $id 自定义的主键$mcPkId
     * @param type $update
     * @return boolean
     */
    public function updateById($id, $uid, $update) {
        if (!is_numeric($id) || !is_numeric($uid) || empty($update)) {
            return false;
        }
        //分库
        if ($this->isSubmeter) {
            $this->submeter($uid);
        }
        $data = $this->info($id, $uid);
        $where[$this->pk] = $id;
//        $where[$this->submeterId] = $uid;
        if (parent::update($update,$where) === false) {
            return false;
        }

        //更新缓存
        if ($this->mcOpen) {
            $data = array_merge($data, $update);
            $mcKey = $this->mcName . '_' . $uid . '_' . $id;
            $time = $this->mcTimeOut > 0 ? $this->mcTimeOut : 0;
            if(!empty($data)){
                cache($mcKey, $data, $time);
            }
        }
        $this->update_after($id, $uid, $update);
        return true;
    }

    public function update_after($id, $uid, $update){

    }

    //批量更新（$update中需要带主键ID）
    public function updateAll($uid, $update){
        if (!is_numeric($uid) || empty($update)) {
            return false;
        }
        //分库
        if ($this->isSubmeter) {
            $this->submeter($uid);
        }
//        $res = $this->saveAll($update);
        Db::init($this->connection);
        Db::startTrans();
        try {
            $result = [];
            foreach ($update as $key => $d) {
                if (!isset($d[$this->pk])) {
                    Db::rollback();
                    return false;
                }
                $result[$key] = self::update($d);
            }

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
        //更新数据为空, 直接返回
        if(empty($result)){
            return false;
        }
        $this->updateAllCache($uid, $update);
        return true;

    }

    public function updateAllCache($uid, $update){
        //没开启缓存, 直接返回
        if (!$this->mcOpen) {
            return true;
        }

        //更新缓存
        foreach ($update as $v) {
            $this->delCache($v[$this->pk],$uid);
        }
    }

    /**
     * 删除
     * @param type $id
     * @param type $uid
     * @return boolean
     */
    public function del($id, $uid) {
        if (empty($id) || !is_numeric($uid)) {
            return false;
        }
        //分库
        if ($this->isSubmeter) {
            $this->submeter($uid);
        }
        $where[$this->pk] = $id;
//        $where[$this->submeterId] = $uid;
        if ($this->where($where)->delete() === false) {
            return false;
        }
        if ($this->mcOpen) {
            //更新缓存
            $mcKey = $this->mcName . '_' . $uid . '_' . $id;
            cache($mcKey, null);
        }
        $this->del_after($id, $uid);
        return true;
    }

    public function del_after($id, $uid){

    }

    //删除多条数据
    public function delAll($ids, $uid){
        if (empty($ids) || !is_numeric($uid)) {
            return false;
        }
        if(is_string($ids)){
            $arr = explode(',',$ids);
        }else{
            $arr = $ids;
        }
        foreach ($arr as $v) {
            $this->del($v,$uid);
        }
        return true;
    }

    /**
     * 获取列表
     * @param type $uid 专门为了分表用不可当做where条件
     * @param type $options
     * @param type $limit
     * @return type
     */
    public function getList($uid, $options) {
        if (!is_numeric($uid)) {
            return false;
        }
        //分库
        if ($this->isSubmeter) {
            $this->submeter($uid);
        }
        //默认条件
        if ($this->submeterId) {
//            $options['where'][] = [$this->submeterId,'=',$uid];
        }
        $where = $options['where'] ?? [];//where条件
        $field = $options['field'] ?? '*';//field的字段
        $page = $options['page'] ?? 0;//分页
        $limit = $options['limit'] ?? 0;//分页时每页的分页数量
        $order = $options['order'] ?? [];//排序
        $doPage = $options['doPage'] ?? false;//是否开启分页
        $group = $options['group'] ?? '';
        $data = array();
        //判断是否要合计
        if ($doPage) {
            if(!empty($group)){
                $data['count'] = $this->selectTime > 0 ? $this->cache(true, $this->selectTime)->where($where)->group($group)->count() : $this->where($where)->group($group)->count();
            }else{
                $data['count'] = $this->selectTime > 0 ? $this->cache(true, $this->selectTime)->where($where)->count() : $this->where($where)->count();
            }
            $list = $this->selectTime > 0 ?
                $this->cache(false, $this->selectTime)->field($field)->where($where)->page($page,$limit)->order($order):
                $this->field($field)->where($where)->page($page,$limit)->order($order);
        }else{
            $data['count'] = null;
            $list = $this->selectTime > 0 ?
                $this->cache(false, $this->selectTime)->field($field)->where($where)->limit($limit)->order($order):
                $this->field($field)->where($where)->limit($limit)->order($order);
        }
        if(!empty($group)){
            $list = $list->group($group);
        }
        $list = $list->select()->toArray();
        $data['list'] = $list;
        return $data;
    }

    //新增或者修改单条记录
    function saveData($uid, $paramData){
        if ( !is_numeric($uid) ) {
            echo '缺少'.$this->submeterId;exit;
        }
        if ( empty($paramData))     return false;
        //分库
        if ( $this->isSubmeter )      $this->submeter($uid);

//        if (!isset($paramData[$this->pk])) {
//            throw new \Exception('必须传递主键', SysEnums::ApiParamMissing);
//        }
        //主键(id)不传或者为空表示为新增
        if ( !isset($paramData[$this->pk]) || empty($paramData[$this->pk]) ) {
            unset($paramData[$this->pk]);
            $resID = $this->save($paramData);
        }else{  //根据主键 进行修改
            $where[$this->pk] = $paramData[$this->pk];
            $resID = $this->save($paramData,$where);
        }

        //新增or修改 失败
        if ( $resID === false ) {
            return false;
        }

        $id = $this[$this->pk];
        if ($this->mcOpen) {
            $data = $this;
            $mcKey = $this->mcName . '_' . $uid . '_' . $id;
            $time = $this->mcTimeOut > 0 ? $this->mcTimeOut : 0;
            cache($mcKey, $data, $time);
        }

        //主键(id)不传或者为空表示为新增
        if ( !isset($paramData[$this->pk]) || empty($paramData[$this->pk]) ) {
            $this->insert_after($id, $uid, $paramData);
        }else{
            $this->update_after($id, $uid, $paramData);
        }
        return $this;
    }

     /**
     * 根据业务ID 查询客户信息,
     * 无缓存，根据实际业务调用
     * @param $appUid
     * @param $bizUid
     * @return array|bool|mixed
     * User: 宋星 DateTime: 2020/11/11 15:59
     */
    function infoByBizUid($appUid, $bizUid ){
        if (empty($bizUid)) {
            return false;
        }

        if($bizUid == -1 || $bizUid == -10){
            return $this->getPlatformUser($appUid, $bizUid);
        }

        $this->submeter($appUid);
        $where[] = ['app_uid','=',$appUid];
        $where[] = ['biz_uid','=',$bizUid];
        return  $this->where($where)->find();

    }

    //获取平台账户信息
    function getPlatformUser($appUid, $bizUid){
        $where[] = ['biz_uid','=', $bizUid];
        $platform = $this->where($where)->findOrEmpty()->toArray();
        if(!empty($platform)) {
            return $platform;
        }
        $data['app_uid'] = 0;
        $data['biz_uid'] = $bizUid;
        $data['biz_user_id'] = $bizUid;
        $data['member_type'] = 3;
        $data['all_amount'] = 0;
        $data['freezen_amount'] = 0;
        $data['status'] = 10;
        $data['id'] = model('Users')::create($data);
        if(!$data['id']){
            return errorReturn('创建平台会员失败');
        }
        return $data;

    }







}
