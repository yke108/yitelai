<?php
//管理员密码md5处理
function admin_password_md5($password, $salt = ''){
	$str = md5(md5($password).$salt);
	$str = md5(substr($str, 3, 26));
	return $str;
}
