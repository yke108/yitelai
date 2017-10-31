<?php
return array(
'SESSION_AUTO_START' => true, //是否开启session
	
	'DB_TYPE' => 'mysql', // 数据库类型
    'DB_HOST' => DK_DB_HOST, // 服务器地址
    'DB_NAME' => DK_DB_NAME,  // 数据库名
    'DB_USER' => DK_DB_USER,       // 用户名
    'DB_PWD'  => DK_DB_PWD, // 密码
    'DB_PORT' => DK_DB_PORT,    // 端口
    'DB_PREFIX' => DK_DB_PREFIX, // 数据库表前缀

    'URL_CASE_INSENSITIVE' => true,
    'TMPL_CACHE_ON' => false, //不生缓存模版.
    'DB_FIELD_CACHE'=> false,
    'HTML_CACHE_ON' => false,
	'URL_ROUTER_ON'   => true, 	
	
	'ACTION_SUFFIX' => 'Action',
	 'TMPL_PARSE_STRING' =>array(
		 '__PUBLIC__' => DK_PUBLIC_URL,
		 '__UPLOAD__' => DK_UPLOAD_URL,
		 '__QRCURL__' => QR_CODE_URL,
		 '__DOMAIN__' => DK_DOMAIN,
	 ),
);