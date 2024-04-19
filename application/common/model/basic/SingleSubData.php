<?php
namespace app\common\model\basic;
use app\common\model\mongo\AdminActionLogMongo;
use app\common\tools\SysEnums;
use think\Db;
use think\Model;

/**
 * Class Single 单表模型
 * @author lfcheng
 * @date 2020-11-14 14:10
 */
class SingleSubData extends Model{
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
     * 列表查询缓存时间
     * @var type
     */
    protected $selectTime = 0;
    /**
     *  分数据字段
     * @var type
     */
    public $subDataId = 'shop_id';
    /**
     * 是否分数据
     * @var type
     */
    protected $isSubData = true;

    //来源类型(Single和Common模型都有)
    public $source_type = [
        self::SOURCE_PLATFORM => '平台',
        self::SOURCE_SELF => '自营',
        self::SOURCE_FACTORY => '工厂',
        self::SOURCE_ZZSP => '中装速配',
        self::SOURCE_TEMPLATE => '模版数据',
        self::SOURCE_EXCEL => 'excel导入',
    ];
    public $source_type_name = [
        self::SOURCE_PLATFORM => '平台',
        self::SOURCE_SELF => '自营',
        self::SOURCE_FACTORY => '工厂',
        self::SOURCE_ZZSP => '中装速配',
        self::SOURCE_TEMPLATE => '模版数据',
        self::SOURCE_EXCEL => 'excel导入',
        self::SOURCE_MARKET => '卖场',
        self::SOURCE_STORE => '门店',
        self::SOURCE_FACTORY_PLATFORM => '工厂',
    ];
    const SOURCE_PLATFORM = 1;//平台
    const SOURCE_SELF = 2;//自营
    const SOURCE_ZZSP = 3;//中装速配导入数据
    const SOURCE_TEMPLATE = 4;//模版数据
    const SOURCE_EXCEL = 5;//excel导入数据
    const SOURCE_FACTORY = 6; //工厂
    const SOURCE_MARKET = 7; //卖场
    const SOURCE_STORE = 8;//门店
    const SOURCE_FACTORY_PLATFORM = 9; //工厂平台

    public function __construct($data = []){
        parent::__construct($data);
        Db::init(config('database.'));
    }

    /**
     * 删除缓存
     * @param $id
     * @return mixed
     */
    public function delCache($id = 0) {
        if($this->mcOpen && $id > 0){
            $mcKey = $this->mcName . '_' . $id;
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
     * 重置返回值数组
     * @param $rs
     * @param array $field
     * @return mixed
     * @author lfcheng
     * @date 11/17/20 6:50 PM
     */
    public function resetRs($rs, $field = []){
        if(empty($rs) || empty($field)){
            return $rs;
        }
        if (is_array($field)) {
            foreach ($field as $value) {
                $newRs[$value] = $rs[$value];
            }
            return $newRs;
        } else {
            return $rs[$field];
        }
    }

    /**
     * 查看主键信息
     * @param $id 自定义的主键$mcPkId
     * @param $field 字段
     * @return mixed
     */
    public function info($id, $field = '') {
        if (!is_numeric($id)) {
            return false;
        }
        //判断是否开启缓存
        if ($this->mcOpen) {
            $mcKey = $this->mcName . '_' . $id;
            $rs = cache($mcKey);
            if ($rs === false || $rs === null) {
                $rs = $this->where(array($this->pk => $id))->findOrEmpty()->toArray();
                $time = $this->mcTimeOut > 0 ? $this->mcTimeOut : 0;
                if(!empty($rs)){
                    cache($mcKey, $rs, $time);
                }
            }
        } else {
            $rs = $this->where(array($this->pk => $id))->findOrEmpty()->toArray();
        }
        return $this->resetRs($rs, $field);
    }

    /**
     * 插入
     * @param $insert
     * @return boolean
     */
    public function insert($insert) {
        $rs = $this->isUpdate(false)->save($insert);
        if ($rs === false) {
            return false;
        }
        $id = $this[$this->pk];
        unset($this[$this->pk]);
        $this->insert_after($id, $insert);
        return $id;
    }

    public function insert_after($id, $insert){
        $log = $insert;
        $log[$this->pk] = $id;
        RedisQueue::setAdminActionLogQueue(AdminActionLogMongo::OPERATE_TYPE['INSERT'], $log, $insert['shop_uid'] ?? 0);
    }

    //批量插入
    public function insertAll(array $dataSet = [], $replace = false, $limit = null){
        $insert = $dataSet[0] ?? [];
        RedisQueue::setAdminActionLogQueue(AdminActionLogMongo::OPERATE_TYPE['INSERT'], $insert, $insert['shop_uid'] ?? 0);
        return parent::insertAll($dataSet, $replace, $limit);
    }

    /**
     * 更新
     * @param $id 自定义的主键$mcPkId
     * @param $update
     * @return boolean
     */
    public function updateById($id, $update) {
        if (!is_numeric($id) || empty($update)) {
            return false;
        }
        $info = $this->info($id);
        $where[$this->pk] = $id;
        if (parent::update($update,$where) === false) {
            return false;
        }

        //删除缓存
        $this->delCache($id);
//        if ($this->mcOpen) {
//            $data = array_merge($info, $update);
//            $mcKey = $this->mcName . '_' . $id;
//            $time = $this->mcTimeOut > 0 ? $this->mcTimeOut : 0;
//            if(!empty($data)){
//                cache($mcKey, $data, $time);
//            }
//        }
        $this->update_after($id, $update, $info);
        return true;
    }

    public function update_after($id, $update, $info){
        $log = $update;
        $log[$this->pk] = $id;
        RedisQueue::setAdminActionLogQueue(AdminActionLogMongo::OPERATE_TYPE['UPDATE'], $log, $update['shop_uid'] ?? 0);
    }

    /**
     * 恢复软删除数据
     * @param $id
     * @return bool
     * @author lfcheng
     * @date 12/5/20 5:30 PM
     */
    public function revert($id){
        $where = [$this->pk => $id];
        $this->restore($where);
        return true;
    }

    //批量更新（$update中需要带主键ID）
    public function updateAll($update){
        if (empty($update)) {
            return false;
        }
        $res = $this->isUpdate(true)->saveAll($update);
        if($res !== false){
            //更新缓存
            if ($this->mcOpen) {
                foreach ($update as $v) {
                    $mcKey = $this->mcName . '_' . $v[$this->pk];
                    cache($mcKey, null);
                }
            }
            return true;
        }
        return false;
    }

    /**
     * 删除
     * @param $id
     * @return boolean
     */
    public function del($id) {
        if (empty($id)) {
            return false;
        }
        $where[$this->pk] = $id;
        $bool = $this::destroy(function ($query) use ($where){
            $query->where($where);
        });
        if ($bool === false) {
            return false;
        }
        if ($this->mcOpen) {
            //更新缓存
            $mcKey = $this->mcName . '_' . $id;
            cache($mcKey, null);
        }
        $this->del_after($id);
        return true;
    }

    public function del_after($id){
        RedisQueue::setAdminActionLogQueue(AdminActionLogMongo::OPERATE_TYPE['DEL'], $id, 0);
    }

    //删除多条数据
    public function delAll($ids){
        if (empty($ids)) {
            return false;
        }
        if(is_string($ids)){
            $arr = explode(',',$ids);
        }else{
            $arr = $ids;
        }
        foreach ($arr as $v) {
            $this->del($v);
        }
        return true;
    }

    /**
     * 获取列表
     * @param $options
     * @param bool $submeterWhere
     * @return mixed
     */
    public function getList($options, $subDataWhere = true) {
        if ($this->subDataId && $subDataWhere && session($this->subDataId)) {
            $options['where'][] = [$this->subDataId,'=', session($this->subDataId)];
        }

        $where  = $options['where'] ?? [];//where条件
        $field  = $options['field'] ?? '*';//field的字段
        $page   = $options['page'] ?? 1;//分页
        $limit  = $options['limit'] ?? 20;//分页时每页的分页数量
        $order  = $options['order'] ?? [];//排序
        $doPage = $options['doPage'] ?? true;//是否开启分页
        $trashed = $options['trashed'] ?? false;//是否获取软删除列表数据
        if($trashed){
            return $this->getTrashedList($options);
        }
        $data = array();
        //判断是否要分页
        if ($doPage) {
            $data['count'] = $this->selectTime > 0 ?
                $this->cache(true, $this->selectTime)->where($where)->count() :
                $this->where($where)->count();

            $list = $this->selectTime > 0 ?
                $this->cache(true, $this->selectTime)->field($field)->where($where)->page($page,$limit)->order($order)->select()->toArray() :
                $this->field($field)->where($where)->page($page,$limit)->order($order)->select()->toArray();
        }else{
            $data['count'] = null;
            $list = $this->selectTime > 0 ?
                $this->cache(true, $this->selectTime)->field($field)->where($where)->order($order)->select()->toArray() :
                $this->field($field)->where($where)->order($order)->select()->toArray();
        }
        $data['list'] = $list;
        return $data;
    }

    //获取软删除列表数据
    private function getTrashedList($options){
        $where = $options['where'] ?? [];//where条件
        $field = $options['field'] ?? '*';//field的字段
        $page = $options['page'] ?? 0;//分页
        $limit = $options['limit'] ?? 0;//分页时每页的分页数量
        $order = $options['order'] ?? [];//排序
        $doPage = $options['doPage'] ?? false;//是否开启分页
        $data = array();
        //判断是否要合计
        if ($doPage) {
            $data['count'] = $this->selectTime > 0 ? $this->onlyTrashed()->cache(true, $this->selectTime)->where($where)->count() : $this->onlyTrashed()->where($where)->count();
            $list = $this->selectTime > 0 ?
                $this->onlyTrashed()->cache(true, $this->selectTime)->field($field)->where($where)->page($page,$limit)->order($order)->select()->toArray() :
                $this->onlyTrashed()->field($field)->where($where)->page($page,$limit)->order($order)->select()->toArray();
        }else{
            $data['count'] = null;
            $list = $this->selectTime > 0 ?
                $this->onlyTrashed()->cache(true, $this->selectTime)->field($field)->where($where)->limit($limit)->order($order)->select()->toArray() :
                $this->onlyTrashed()->field($field)->where($where)->limit($limit)->order($order)->select()->toArray();
        }
        $data['list'] = $list;
        return $data;
    }

    //获取软删除的数量
    public function getTrashedCount($where){
        return $this->onlyTrashed()->where($where??[])->count();
    }

    //新增或者修改单条记录
    public function saveData($paramData){
        if ( empty($paramData)) {
            return false;
        }

//        if (!isset($paramData[$this->pk])) {
//            throw new \Exception('必须传递主键', SysEnums::ApiParamMissing);
//        }
        //主键(id)不传或者为空表示为新增
        if ( !isset($paramData[$this->pk]) || empty($paramData[$this->pk]) ) {
            unset($paramData[$this->pk]);
            $resID = $this->isUpdate(false)->save($paramData);
        }else{  //根据主键 进行修改
            $where[$this->pk] = $paramData[$this->pk];
            $resID = $this->isUpdate(true)->save($paramData,$where);
        }

        //新增or修改 失败
        if ( $resID === false ) {
            return false;
        }

        $id = $this[$this->pk];
        $info = $this->info($id);
        if ($this->mcOpen) {
            $data = array_merge($info, $paramData);
            $mcKey = $this->mcName . '_' . $id;
            $time = $this->mcTimeOut > 0 ? $this->mcTimeOut : 0;
            cache($mcKey, $data, $time);
        }

        //主键(id)不传或者为空表示为新增
        if ( !isset($paramData[$this->pk]) || empty($paramData[$this->pk]) ) {
            $this->insert_after($id, $paramData);
        }else{
            $this->update_after($id, $paramData, $info);
        }
        return $this;
    }

    /**
     * Description:关闭当前数据库连接
     * User: Vijay <1937832819@qq.com>
     * Date: 2021/9/8
     * Time: 10:26
     * @return $this
     */
    public function closeDb()
    {
        $this->db()->getConnection()->close();
        return $this;
    }
}
