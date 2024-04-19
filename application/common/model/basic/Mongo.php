<?php
namespace app\common\model\basic;
use think\Model;

//mongodb模型
class Mongo extends Model{
    /**
     * 缓存主键
     * @var type
     */
    protected $pk = '_id';
    /**
     * 连接配置
     * @var string
     */
    protected $connection = 'mongodb';
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
     * 删除缓存
     * @param type $id
     * @param type $uid 强制uid
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
     * 查看主键信息
     * @param type $id 自定义的主键$mcPkId
     * @param type $field 字段
     * @return type
     */
    public function info($id, $field = '') {
        if (empty($id)) {
            return false;
        }
        //判断是否开启缓存
        if ($this->mcOpen) {
            $mcKey = $this->mcName . '_' . $id;
            $rs = cache($mcKey);
            if ($rs === false) {
                $rs = $this->where(array($this->pk => $id))->find()->toArray();
                $time = $this->mcTimeOut > 0 ? $this->mcTimeOut : 0;
                cache($mcKey, $rs, $time);
            }
        } else {
            $rs = $this->where(array($this->pk => $id))->find()->toArray();
        }
        return $field ? $rs[$field] : $rs;
    }

    /**
     * 插入
     * @param type $insert
     * @return boolean
     */
    public function insert($insert) {
        $id = parent::insertGetId($insert);
        if ($id === false) {
            return false;
        }
        $this->insert_after($id, $insert);
        return $id;
    }

    public function insert_after($id, $insert){

    }

    //批量插入
    public function insertAll(array $dataSet = [], $replace = false, $limit = null){
        return parent::insertAll($dataSet, $replace, $limit);
    }

    /**
     * 更新
     * @param type $id 自定义的主键$mcPkId
     * @param type $update
     * @return boolean
     */
    public function updateById($id, $update) {
        if (empty($id) || empty($update)) {
            return false;
        }
        $data = $this->info($id);
        $where[$this->pk] = $id;
        if (parent::update($update,$where) === false) {
            return false;
        }

        //更新缓存
        if ($this->mcOpen) {
            $data = array_merge($data, $update);
            $mcKey = $this->mcName . '_' . $id;
            $time = $this->mcTimeOut > 0 ? $this->mcTimeOut : 0;
            cache($mcKey, $data, $time);
        }
        $this->update_after($id, $update);
        return true;
    }

    public function update_after($id, $update){

    }

    //批量更新（$update中需要带主键ID）
    public function updateAll($update){
        if (empty($update)) {
            return false;
        }
        $res = $this->saveAll($update);
        if($res !== false){
            //更新缓存
            if ($this->mcOpen) {
                foreach ($update as $v) {
                    $data = $this->info($v[$this->submeterId]);
                    $data = array_merge($data, $v);
                    $mcKey = $this->mcName . '_' . $v[$this->submeterId];
                    $time = $this->mcTimeOut > 0 ? $this->mcTimeOut : 0;
                    cache($mcKey, $data, $time);
                }
            }
            return true;
        }
        return false;
    }

    /**
     * 删除
     * @param type $id
     * @param type $uid
     * @return boolean
     */
    public function del($id) {
        if (empty($id)) {
            return false;
        }
        $where[$this->pk] = $id;
        if ($this->where($where)->delete() === false) {
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
     * @param type $uid 专门为了分表用不可当做where条件
     * @param type $options
     * @param type $limit
     * @return type
     */
    public function getList($options) {
        $where = $options['where'] ?? [];//where条件
        $field = $options['field'] ?? '*';//field的字段
        $page = $options['page'] ?? 0;//分页
        $limit = $options['limit'] ?? 0;//分页时每页的分页数量
        $order = $options['order'] ?? [];//排序
        $doPage = $options['doPage'] ?? false;//是否开启分页
        $data = array();
        //判断是否要合计
        if ($doPage) {
            $data['count'] = $this->selectTime > 0 ? $this->cache(true, $this->selectTime)->where($where)->count() : $this->where($where)->count();
            $list = $this->selectTime > 0 ?
                $this->cache(true, $this->selectTime)->field($field)->where($where)->page($page,$limit)->order($order)->select()->toArray() :
                $this->field($field)->where($where)->page($page,$limit)->order($order)->select()->toArray();
        }else{
            $data['count'] = null;
            $list = $this->selectTime > 0 ?
                $this->cache(true, $this->selectTime)->field($field)->where($where)->limit($limit)->order($order)->select()->toArray() :
                $this->field($field)->where($where)->limit($limit)->order($order)->select()->toArray();
        }
        $data['list'] = $list;
        return $data;
    }

}
