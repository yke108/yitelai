<?php
$no_think_php = true;
//引入公共参数配置文件
include_once('../config.db.php');
//定义尺寸
$szl = array(
	'b150'=>array(150,150),
	'b160'=>array(160,160),
	'b72'=>array(72,72),
	'b32'=>array(32,32),
	'b48'=>array(48,48),
	'w48'=>array(48,0),
	'h48'=>array(0,48),
	'h75'=>array(0,75),
	'b90'=>array(90, 90),
	'b120'=>array(120, 120),
	'b200'=>array(200, 200),
	'b300'=>array(300, 300),
	'b400'=>array(400, 400),
	'b420'=>array(420, 290),
	'b512'=>array(512, 512),
	'b660'=>array(660, 660),
	'w225h280'=>array(225,280),
	'w240h200'=>array(240,200),
	'w260h280'=>array(260,280),
	'w345h230'=>array(345,230),
	'w360h280'=>array(360,280),
	'w390h430'=>array(390,430),
	'w530h450'=>array(530,450),
	'w580h300'=>array(580,300),
	'w760h610'=>array(760,610),
	'w820h500'=>array(820,500),
	'w4h3'=>array(160, 135),
	'w147h62'=>array(147, 62),
	'w147h125'=>array(147, 125),
	'w112h106'=>array(112, 106),
	'w170h220'=>array(170, 220),
	'w260h194'=>array(260, 194),
	'w260h220'=>array(260, 220),
	'w250h165'=>array(250, 165),
	'w250h200'=>array(250, 200),
	'w170h145'=>array(170,145),
	'w362h94'=>array(362,94),
	'w165h80'=>array(165,80),
	'w108h115'=>array(108,115),
	'w420h490'=>array(420,490),
	'w732h636'=>array(732,636),
	'w316h211'=>array(316,211),
	'w80h60'=>array(80,60),
	'w230h240'=>array(230,240),
	'w220h278'=>array(220,278),
	'w220h250'=>array(220,250),
	'w242h178'=>array(242,178),
	'w388h256'=>array(388,256),
	'w420h280'=>array(420,280),
	'w175h125'=>array(175,125),
	'w215h155'=>array(215,155),
	'w618h455'=>array(618,455),
	'w666h330'=>array(666,330),
	'w265h137'=>array(265,137),
);
//分解URL
$url = parse_url($_SERVER['PHP_SELF'], PHP_URL_PATH);
$cps = explode('/', $url);
$num = count($cps);
if($num < 2){
	trick();
}

$szname = '';
for($i = 0; $i < $num; $i++){
	$str = $cps[$i];
	if($str != 'thumbs'){
		unset($cps[$i]);
	} else {
		unset($cps[$i]);
		$i++;
		$szname = $cps[$i];
		unset($cps[$i]);
		break;
	}
}

$szinfo = $szl[$szname];
if(!is_array($szinfo) || count($cps) < 1){
	trick();
}

$fpath = implode('/', $cps);
$origion_path = UPLOAD_PATH.$fpath;
$thumb_path = UPLOAD_PATH.'thumbs/'.$szname.'/'.$fpath;

//生成缩略图
imagecropper($origion_path, $szinfo[0], $szinfo[1], $thumb_path);

//输出缩略图
echo file_get_contents($thumb_path);

/************以下为相关函数 *************/

function trick(){
	exit('...');
}

function imagecropper($source_path, $target_width, $target_height, $target_path = './abc/'){
	$source_info = getimagesize($source_path);
	$source_width = $source_info[0];
	$source_height = $source_info[1];
	$source_mime = $source_info['mime'];
	$source_ratio = $source_height / $source_width;
	
	if($target_width == 0){
		$target_ratio = $source_ratio;
		$target_width = $target_height / $target_ratio;
	} 
	elseif($target_height == 0){
		$target_ratio = $source_ratio;
		$target_height = $target_width * $target_ratio;
	} 
	else {
		$target_ratio = $target_height / $target_width;
	}
	
	

	// 源图过高
	if ($source_ratio > $target_ratio){
		$cropped_width = $source_width;
		$cropped_height = $source_width * $target_ratio;
		$source_x = 0;
		$source_y = ($source_height - $cropped_height) / 2;
	}
	// 源图过宽
	elseif ($source_ratio < $target_ratio){
		$cropped_width = $source_height / $target_ratio;
		$cropped_height = $source_height;
		$source_x = ($source_width - $cropped_width) / 2;
		$source_y = 0;
	}
	// 源图适中
	else{
		$cropped_width = $source_width;
		$cropped_height = $source_height;
		$source_x = 0;
		$source_y = 0;
	}

	switch ($source_mime){
		case 'image/gif':
		$source_image = imagecreatefromgif($source_path);
		break;

		case 'image/jpeg':
		$source_image = imagecreatefromjpeg($source_path);
		break;

		case 'image/png':
		$source_image = imagecreatefrompng($source_path);
		break;

		default:
		return false;
		break;
	}
	
	header('Content-Type: '.$source_mime);
	header('Content-Author:Gao');

	$target_image = imagecreatetruecolor($target_width, $target_height);
	// 裁剪、缩放
	imagealphablending($target_image,false);//这里很重要,意思是不合并颜色,直接用$img图像颜色替换,包括透明色;
    imagesavealpha($target_image,true); //保存透明色
	imagecopyresampled($target_image, $source_image, 0, 0, $source_x, $source_y, $target_width, $target_height, $cropped_width, $cropped_height);

	//保存图片到本地(两者选一)
	@mkdir(dirname($target_path), 0777, true);
	
	switch ($source_mime){
		case 'image/gif':
		@imagegif($target_image, $target_path);
		break;

		case 'image/jpeg':
		@imagejpeg($target_image, $target_path);
		break;

		case 'image/png':
		@imagepng($target_image, $target_path);
		break;
	}
	@imagedestroy($target_image);
	@imagedestroy($source_image);
}