目录结构
	1.Config
		|
		|---->conf.php[主要用于加载用户配置文件]
		|---->config.php[用户的配置信息]
	2.data
		|
		|---->1581648210684.pfx 开放平台私钥
		|---->TLcert-test-new.cer 开放平台公钥
		|---->
	3.DownFile
		|---->平台对账文件下载位置
	4.Log
		|
		|---->Log.php[控制日志输入,日志文件输入至yunLog.txt]
	5.SDK
		|
		|---->yunClient.php[请求、加签、验签、AES隐私信息加密、解密]

运行环境
PHP+WAMPSERVER

运行地址
http://localhost/yunpublic/src/OrderServicedemo.php    订单接口
http://localhost/yunpublic/src/MemberServicedemo.php   会员接口
