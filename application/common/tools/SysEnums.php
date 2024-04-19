<?php
 namespace app\common\tools;

class SysEnums
{  
    //  错该误码范围划段
    // 10000 ~ 20000 致命性错误(系统内部错误, 提示给用户) 
    CONST DebugError = 10000;//调试异常
    CONST NotLogged = 10001; //未登入
    CONST UnAuthorized = 10002; //未授权
    CONST IdentityWrong = 10003; //身份错误
    CONST RemoteLanding = 10004; //异地登入
    CONST PasswordError = 10005; //密码错误
    CONST SqlError = 10006; //SQL语句执行报错	
    CONST UnknownDBError = 10007; //数据库未知异常
    CONST ThirdPartyError = 10008;//第三方接口异常
    CONST DataNotExist = 10009;//数据库记录不存在
    CONST DataCountError = 10010;//数据库记录笔数异常(与期望的不符)
    CONST FileNotExist = 10011;//文件不存在
    CONST SysIOError = 10012;//系统IO读写文件异常
    CONST ApiParamMissing = 10013;//接口参数不存在
    CONST ApiParamWrong = 10014;//参数校验错误

    
    CONST AffairError = 20000;//系统业务错误
    CONST ValidateError = 20100;//验证错误错误
    CONST TokenError = 20200;//token错误
    CONST TokenExpiredError = 20202;//token过期错误

    CONST UnPayStatusError = 20300;  //未支付：unpay 失败：fail
    CONST PayStatusError = 20400;  //未支付：unpay 失败：fail
    CONST SumAmountError = 20500;  //未支付：unpay 失败：fail

    CONST CcbAccountingRulesAlreadyExist = 21000;  //规则已经存在, (在新增商家时会自动增加6条规则, 建行会重复推送)

    CONST ExceptionError = 50000;//抛异常致命错误

	// 30001 ~ 40000 告警性错误(系统内部错误(不产生中断), 提示给用户)
	// 40001 ~ 50000 告警性错误(系统内部错误(不产生中断), 统一显示为请重试)
    // 50000 ~ 60000 致命性错误(系统内部错误, 统一显示为请重试)//

}