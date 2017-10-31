<?php
function haspv($priv_str = ''){
	if (empty($priv_str)) return false;
	$action_list = session('action_list');
	if ($action_list == 'all') return true;
	if (strpos($priv_str, '/') === false) $priv_str = strtolower(CONTROLLER_NAME.'/'.$priv_str);
	if (strpos(','.$action_list.',', ','.$priv_str.',') === false){
		return false;
	}
	return true;
}

function admin_avatar($pic){
	$image = picurl($pic);
	if (empty($image)){
		$image = DK_DOMAIN.DK_PUBLIC_URL.'/main/images/user_default_img.jpg';
	}
	return $image;
}
