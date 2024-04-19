<?php


namespace app\web\controller;

use app\web\service\shopUserService;
use app\web\controller\baseController;

use think\Db;
use think\facade\Hook;

/**
 * @OA\Info(title="Saas项目", version="1.0.0")
 */
class User extends baseController{

    /**
     * @OA\Post(
     *     path="/web/user/login",
     *     tags={"测试分类"},
     *     summary="测试接口",
     *     description="token测试接口description",
     *     @OA\Parameter(name="username", in="query", @OA\Schema(type="string"), required=true, description="用户名", example="aaa"),
     *     @OA\Parameter(name="password", in="query", @OA\Schema(type="string"), required=true, description="密码", example="123456"),
     *     @OA\Response(
     *         response="200",
     *         description="success",
     *         @OA\JsonContent(type="object",
     *              @OA\Property(property="result", type="boolean"),
     *              @OA\Property(property="msg", type="string"),
     *              @OA\Property(property="code", type="integer"),
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(type="object",
     *                      @OA\Property(property="id", type="integer", description="用户id"),
     *                      @OA\Property(property="name", type="string", description="用户名"),
     *                      @OA\Property(property="email", type="string", description="email地址"),
     *                  ),
     *              ),
 *             ),
     *     )
     * )
     */
    function login(){
        $uid = 123; //根据用户名密码查库 获取 uid
        $data = [
            'token' => $this->getToken($uid),
            'msg' => 'abcd',
        ];

        return apiOut($data);
    }

    // 验证用户登录
    function shopUserLogin(){
        // 获取数据
        $data = input();
        $shopUserService = new shopUserService();
        return $shopUserService->shopUserLogin($data);

    }

    /**
     * @OA\Post(
     *     path="/web/user/userList",
     *     tags={"测试分类"},
     *     summary="用户列表",
     *     description="token测试用户列表接口",
     *     @OA\Parameter(name="keywords", in="query", @OA\Schema(type="string"), required=false, description="模糊搜索", example="aa"),
     *     @OA\Response(
     *         response="200",
     *         description="success",
     *         @OA\JsonContent(type="object",
     *              @OA\Property(property="result", type="boolean"),
     *              @OA\Property(property="msg", type="string"),
     *              @OA\Property(property="code", type="integer"),
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(type="object",
     *                      @OA\Property(property="id", type="integer", description="用户id, uid"),
     *                      @OA\Property(property="name", type="string", description="用户名"),
     *                      @OA\Property(property="loginip", type="string", description="登录IP"),
     *                      @OA\Property(property="status", type="integer", description="是否启用 1:正常 0:禁用"),
     *                  ),
     *              ),
     *             ),
     *     )
     * )
     */
    function userList(){
        $data = input();
        $shopUserService = new shopUserService();
        return $shopUserService->getList($data);
//        return $userList;
    }

    // 添加用户
    function Add(){
        // 1. 获取的用户提交的信息
        $data = input();

        $data['logintime'] = time();
        $data['loginip'] = $this -> request -> ip();

        // 2. 执行新增操作，多表插入记录，开启事务操作
        Db::startTrans();
        try {
            // 插入用户表
            $user = UserModel::create($data);

            // 定义用户角色关联表字段
            $role['role_id'] = $data['role_id'];
            $role['user_id'] = $user -> id;

            // 用户角色关联表插入数据
            $res = AuthGroupAccess::create($role);

            // 提交事务处理
            Db::commit();
        } catch ( \Exception $e ) {
            // 回滚事务
            Db::rollback();
            $this -> error('用户添加失败，请检查');
        }
        $this -> success('用户添加成功', 'index');
    }

    function del(){

        $this->Output();
    }
}