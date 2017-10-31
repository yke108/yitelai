<?php
use Think\App;
define('APP_DEBUG',true);
define('MULTI_MODULE', false);
define('APP_MODE', 'api');
define('BIND_MODULE','Adminapi');
include_once('../../../config.db.php');
try {
	App::run(true);
} catch (\Exception $e){
	$data = array(
		'Message'=>$e->getMessage(),
		'Error'=>$e->getCode() > 0 ? $e->getCode() : 29,
	);
	header('Content-Type:application/json; charset=utf-8');
	exit(json_encode($data));
}