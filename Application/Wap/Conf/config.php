<?php
return array(
	'LAYOUT_ON'=>true,
	'LAYOUT_NAME'=>'Layout/main',
	'URL_MODEL' => '3', //URL模式
	'ACTION_SUFFIX' => 'Action',
	
	'CONTROLLER_LEVEL' => 2,
	'DEFAULT_CONTROLLER'=>'Index/index',
	'SESSION_PREFIX'=>'wxuser',
	
	'DB_TYPE' => 'mysql', // 数据库类型
    'DB_HOST' => DK_DB_HOST, // 服务器地址
    'DB_NAME' => DK_DB_NAME,  // 数据库名
    'DB_USER' => DK_DB_USER,       // 用户名
    'DB_PWD'  => DK_DB_PWD, // 密码
    'DB_PORT' => DK_DB_PORT,    // 端口
    'DB_PREFIX' => DK_DB_PREFIX, // 数据库表前缀

    'TMPL_CACHE_ON' => false, //不生缓存模版.
    'DB_FIELD_CACHE'=> false,
    'HTML_CACHE_ON' => false,
	'URL_ROUTER_ON' => true, 
	
	'URL_CASE_INSENSITIVE'=>true,
	
	'TMPL_PARSE_STRING' =>array(
		'__PUBLIC__' => DK_PUBLIC_URL,
		'__UPLOAD__' => DK_UPLOAD_URL,
		'__QRCURL__' => QR_CODE_URL,
		'__DOMAIN__' => DK_DOMAIN,
	),
    'APP_DEBUG' => true,
    //'SHOW_PAGE_TRACE' =>true,
    'TMPL_ACTION_ERROR' => 'Public:error',
    'TMPL_ACTION_SUCCESS' => 'Public:success',
);
