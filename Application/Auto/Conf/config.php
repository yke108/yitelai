<?php
return array(
	'LAYOUT_ON'=>true,
	'LAYOUT_NAME'=>'Layout/main',
	'URL_MODEL' => '3', //URL模式
	'DB_TYPE' => 'mysql', 			// 数据库类型
    'DB_HOST' => DK_DB_HOST, 		// 服务器地址
    'DB_NAME' => DK_DB_NAME,  		// 数据库名
    'DB_USER' => DK_DB_USER,   		// 用户名
    'DB_PWD'  => DK_DB_PWD, 		// 密码
    'DB_PORT' => DK_DB_PORT,    	// 端口
    'DB_PREFIX' => DK_DB_PREFIX, 	// 数据库表前缀
	
	'SIDEBAR_IN' => 'in',
	'SIDEBAR_ACTIVE' =>' class="active"',
	
    //默认错误跳转对应的模板文件
    'TMPL_ACTION_ERROR' => 'Public:error',
    //默认成功跳转对应的模板文件
    'TMPL_ACTION_SUCCESS' => 'Public:success',
	
	 'TMPL_PARSE_STRING' =>array(
		 '__PUBLIC__' => DK_PUBLIC_URL,
		 '__UPLOAD__' => DK_UPLOAD_URL,
	 ),
);