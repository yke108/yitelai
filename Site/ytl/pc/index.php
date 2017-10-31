<?php
// 开启调试模式 建议开发阶段开启
define('APP_DEBUG',true);

define('MULTI_MODULE', false);
define('BIND_MODULE','Main');
// 绑定访问控制器
define('BIND_CONTROLLER','Mall/Gift');
// 绑定访问操作
define('BIND_ACTION','index');

//引入公共参数配置文件 huang test2222
include_once('../../../config.db.php');