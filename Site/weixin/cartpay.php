<?php
// 开启调试模式 建议开发阶段开启 部署阶段注释或者设为false
define('APP_DEBUG',true);

define('MULTI_MODULE', false);
define('BIND_MODULE','Main'); 

// 绑定访问Index控制器
define('BIND_CONTROLLER','Weixin/Notify');
// 绑定访问test操作
define('BIND_ACTION','cartpay');


//引入公共参数配置文件
include_once('../../config.db.php');
