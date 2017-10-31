<?php
// 检测PHP环境
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');

// 开启调试模式 建议开发阶段开启 部署阶段注释或者设为false
define('APP_DEBUG',True);
define('MULTI_MODULE', false);
define('BIND_MODULE','Main');

include_once('../config.db.php');

// 引入ThinkPHP入口文件
require DKSITE_THINKPHP.'/ThinkPHP/ThinkPHP.php';