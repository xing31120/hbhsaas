<?php
 namespace app\common\tools;

class AllInPayEnums
{  
    //  错该误码范围划段
    //详见文档 https://cloud.allinpay.com/ts-cloud-dev-web/#/apiCenter/index?params=y&key=299
    const STANDARD_BALANCE_ACCOUNT_SET = '100001';//标准余额账户集
    const STANDARD_MARGIN_ACCOUNT_SET = '100002';//标准保证金账户集
    const RESERVE_LIMIT_ACCOUNT_SET = '100003';//准备金额度账户集
    const INTERMEDIATE_ACCOUNT_SET_A = '100004';//中间账户集A
    const INTERMEDIATE_ACCOUNT_SET_B = '100005';//中间账户集B
    const STANDARD_MARKETING_ACCOUNT_SET = '2000000';//标准营销账户集

}