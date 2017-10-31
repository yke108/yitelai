<?php
function node_merge($node, $pid=0, $level = 1,$merge_id='id'){
	$arr = array();
	foreach ($node as $v){
		if ($v["parent_id"] == $pid){
			$v["children"] = node_merge($node, $v[$merge_id], $level + 1);
			$v['lvl'] = $level;
			$arr[] = $v;
		}
	}
	return $arr;
}

function hasPurview($priv_str){
	$action_list = session('action_list');
    if ($action_list !== 'all' && strpos(','.$action_list.',', ','.$priv_str.',') === false){
		return false;
    }
	return true;
}

function uhy($get, $srt){
	$get['sby'] = ($get['srt'] == $srt && $get['sby'] == 'asc') ? 'desc' : 'asc';
	$get['srt'] = $srt;
	return U('', $get);
}

function picurl($path, $thumb = ''){
	$path = trim($path);
	if(empty($path)){
		return '';
	}
	if(strpos($path, '://') > 0){
		return $path;
	}
	if ($thumb) {
		return DK_DOMAIN.DK_UPLOAD_URL."/thumbs/".$thumb.'/'.$path;
	}else {
		return DK_DOMAIN.DK_UPLOAD_URL.'/'.$path;
	}
}

function avatarUrl(){
	return DK_DOMAIN.DK_PUBLIC_URL.'/img/avatar_holder.png';
}

function picurls($gallery, $thumb = ''){
	if (is_array($gallery)){
		$gl = $gallery;
	} else {
		$gl = explode(',', $gallery);
	}
	$list = array();
	foreach ($gl as $vo){
		if (empty($vo)) continue;
		$list[] = picurl($vo, $thumb);
	}
	return $list;
}

function m_checked($v1, $v2){
	if(is_array($v2)){
		if(in_array($v1, $v2)){
			echo 'checked'; 
		}
	} elseif($v1 == $v2){
		echo 'checked';
	}
}

function m_selected($v1, $v2){
	if(is_array($v2)){
		if(in_array($v1, $v2)){
			echo 'selected'; 
		}
	} elseif($v1 == $v2){
		echo 'selected';
	}
}

function CheckPurview($priv_str){
	$action_list = session('action_list');
    if ($action_list !== 'all' && strpos(','.$action_list.',', ','.$priv_str.',') === false){
		return 'admin_not_purview';
    }
	return '';
}

function hydate($format, $time){
	if($time < 1) return date($format);
	return date($format, $time);
}

function _p($val, $die = true)
{
	header("content-Type: text/html; charset=Utf-8");
	$out  = "<pre style=\"background: #000; color: #ccc; font: 12px 'fixedsys'; text-align: left; width: 100%; padding: 5px\">\n";
	$out .= print_r($val, true);
	$out .= "</pre>\n";
	echo $out;
	if ($die) {
		exit();
	}
}

/**
 * 获取全部下级
 * $id 需要查询的ID
 * $list  数组 直接等级关系
 * $ids 数组 查询的结果
 */
function child_list($id, $list, &$ids){
	$ids[] = $id;
	$ln = $list[$id];
	if(!empty($ln)){
		foreach($ln as $v){
			child_list($v, $list, $ids);
		}
	}
}

/**
 * 无限极分类菜单
 * @param array 	$array 		未分类数组
 * @param string 	$prefix 	字段前缀
 * @param int 		$pid 		父级id
 * @param int 		$level 		等级
 */
function no_limit_cate($array,$prefix,$pid=0,$level=0){
	static $arr=array();
	foreach($array as $val){
		if($val[$prefix.'_pid']==$pid){
			$val['level']=$level;
			$arr[]=$val;
			no_limit_cate($array,$prefix,$val[$prefix.'_id'],$level+1);
		}
	}
	return $arr;
}

function genTree($items,$id='id',$pid='pid',$son = 'children'){
	$tree = array(); //格式化的树
	$tmpMap = array();  //临时扁平数据

	foreach ($items as $item) {
		$tmpMap[$item[$id]] = $item;
	}

	foreach ($items as $item) {
		if (isset($tmpMap[$item[$pid]])) {
			$tmpMap[$item[$pid]][$son][] = &$tmpMap[$item[$id]];
		} else {
			$tree[] = &$tmpMap[$item[$id]];
		}
	}
	unset($tmpMap);
	return $tree;
}

function genTreeField($items,$id='id',$pid='pid',$son = 'children'){
	$tree = array(); //格式化的树
	$tmpMap = array();  //临时扁平数据

	foreach ($items as $item) {
		$tmpMap[$item[$id]] = $item;
	}

	foreach ($items as $key => $item) {
		if (isset($tmpMap[$item[$pid]])) {
			$tmpMap[$item[$pid]][$son][] = &$tmpMap[$item[$id]];
		} else {
			$tree[$key] = &$tmpMap[$item[$id]];
		}
	}
	unset($tmpMap);
	return $tree;
}

function strpos_array($haystack, $needles) {
	if ( is_array($needles) ) {
		foreach ($needles as $str) {
			if ( is_array($str) ) {
				$pos = strpos_array($haystack, $str);
			} else {
				$pos = strpos($haystack, $str);
			}
			if ($pos !== FALSE) {
				return $pos;
			}
		}
	} else {
		return strpos($haystack, $needles);
	}
}

/**
 * 验证码检查
 */
function check_verify($code, $id = ""){
	$verify = new \Think\Verify();
	return $verify->check($code, $id);
}

function curl_get($url) {
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); //不验证证书
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); //不验证证书
	$data = curl_exec($curl);
	curl_close($curl);
	$obj = json_decode($data);
	return $obj;
}

//通过curl post数据
function curlPost($url, $post_data=array(), $timeout=5,$header="")
{
	$header=empty($header)?'':$header;
	//$post_string = http_build_query($post_data);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST , false);
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array($header));//模拟的header头
	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}

function curl_file($url,$post_data) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	$rs = curl_exec($ch);
	curl_close($ch);
	$ret = json_decode($rs, true);
	return $ret;
}

function https_request($url, $data = null)
{
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
	if (!empty($data)){
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
	}
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	$output = curl_exec($curl);
	curl_close($curl);
	return json_decode($output, true);
}


//过滤数组的空值
function array_filter_value($array){
	if(empty($array)){return;}
	foreach($array as $key=>$val){
		if($val==''){
			unset($array[$key]);
		}
	}
	return $array;
}

function createBase64Image($image_datas) {
	$savePath = 'editor/'.date('Ym').'/'; // 设置附件上传（子）目录
	$dir_path =  UPLOAD_PATH.$savePath;
	if(!is_dir($dir_path)){
		mkdir($dir_path);
	}
	if (!is_array($image_datas)) {
		$image_datas = array($image_datas);
	}
	$images = array();
	foreach ($image_datas as $k=>$v) {
		//保存base64字符串为图片
		//匹配出图片的格式
		if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $v, $result)){
			$rand = mt_rand(1,5);
			$type = $result[2];
			$savename  = time()."_".$k.$rand.".{$type}";
			$new_file = $dir_path.$savename;
			if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $v)))){
				$images[] = $savePath.$savename;
			}else {
				return false;
			}
		}
	}
	return $images;
}

function Service($name) {
	return D($name, 'Service');
}

if(!function_exists('msubstr')){
	function msubstr($str, $start=0, $length, $charset="utf-8", $suffix=true,$word='...')
	{
		if(function_exists("mb_substr")){
			if($suffix){
			 if(mb_strlen($str,$charset)>$length){
				 return mb_substr($str, $start, $length, $charset).$word;
			 }else{
			 	return mb_substr($str, $start, $length, $charset);
			 }

			}else{
			 return mb_substr($str, $start, $length, $charset);
			}
		}
		elseif(function_exists('iconv_substr')) {
			if($suffix)
			 if(mb_strlen($str,$charset)>$length){
				return iconv_substr($str,$start,$length,$charset).$word;
			}else{
				return iconv_substr($str,$start,$length,$charset);
			}
			else
			 return iconv_substr($str,$start,$length,$charset);
		}
		$re['utf-8']   = "/[x01-x7f]|[xc2-xdf][x80-xbf]|[xe0-xef][x80-xbf]{2}|[xf0-xff][x80-xbf]{3}/";
		$re['gb2312'] = "/[x01-x7f]|[xb0-xf7][xa0-xfe]/";
		$re['gbk']    = "/[x01-x7f]|[x81-xfe][x40-xfe]/";
		$re['big5']   = "/[x01-x7f]|[x81-xfe]([x40-x7e]|xa1-xfe])/";
		preg_match_all($re[$charset], $str, $match);
		$slice = join("",array_slice($match[0], $start, $length));
		if($suffix){
			if(mb_strlen($str,$charset)>$length){
				return $slice.$word;
			}else{
				return $slice;
			}
		}

	}
}

//设计师案例浏览数加1
function caseAddClick($case_id){
	D('DesignerCase','Service')->caseAddClickNumber($case_id);
}

//粉丝故事会浏览数+1
function storyAddViewNum($story_id){
	D('Story','Service')->addViewNum($story_id);
}

function checkDiscount($discount){
	if (!is_int($discount || $discount > 100)) {
		return true;
	}else {
		return false;
	}
}

function get_real_ip(){
	$ip=false;
	if(!empty($_SERVER["HTTP_CLIENT_IP"])){
		$ip = $_SERVER["HTTP_CLIENT_IP"];
	}
	if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
		if ($ip) { array_unshift($ips, $ip); $ip = FALSE; }
		for ($i = 0; $i < count($ips); $i++) {
			if (!eregi ("^(10|172\.16|192\.168)\.", $ips[$i])) {
				$ip = $ips[$i];
				break;
			}
		}
	}
	return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
}

function Getzimu($str)
{
	$str= iconv("UTF-8","gb2312", $str);//如果程序是gbk的，此行就要注释掉
	if (preg_match("/^[\x7f-\xff]/", $str))
	{
		$fchar=ord($str{0});
		if($fchar>=ord("A") and $fchar<=ord("z") )return strtoupper($str{0});
		$a = $str;
		$val=ord($a{0})*256+ord($a{1})-65536;
		if($val>=-20319 and $val<=-20284)return "A";
		if($val>=-20283 and $val<=-19776)return "B";
		if($val>=-19775 and $val<=-19219)return "C";
		if($val>=-19218 and $val<=-18711)return "D";
		if($val>=-18710 and $val<=-18527)return "E";
		if($val>=-18526 and $val<=-18240)return "F";
		if($val>=-18239 and $val<=-17923)return "G";
		if($val>=-17922 and $val<=-17418)return "H";
		if($val>=-17417 and $val<=-16475)return "J";
		if($val>=-16474 and $val<=-16213)return "K";
		if($val>=-16212 and $val<=-15641)return "L";
		if($val>=-15640 and $val<=-15166)return "M";
		if($val>=-15165 and $val<=-14923)return "N";
		if($val>=-14922 and $val<=-14915)return "O";
		if($val>=-14914 and $val<=-14631)return "P";
		if($val>=-14630 and $val<=-14150)return "Q";
		if($val>=-14149 and $val<=-14091)return "R";
		if($val>=-14090 and $val<=-13319)return "S";
		if($val>=-13318 and $val<=-12839)return "T";
		if($val>=-12838 and $val<=-12557)return "W";
		if($val>=-12556 and $val<=-11848)return "X";
		if($val>=-11847 and $val<=-11056)return "Y";
		if($val>=-11055 and $val<=-10247)return "Z";
	}
	else
	{
		return false;
	}
}

function HumanReadableFilesize($size) {
	$mod = 1024;
	$units = explode(' ','B KB MB GB TB PB');
	for ($i = 0; $size > $mod; $i++) {
		$size /= $mod;
	}
	return round($size, 2) . ' ' . $units[$i];
}

//判断是否是一周
function BoolWeek($start,$end){
	$bool_type =false;
	$begindate = strtotime($start);
	$enddate = strtotime($end);
	$cha = intval(abs($begindate-$enddate)/(3600*24));//两个时间相差是否是6
	$w = date("N",$begindate); //判断是星期一
	$s = date("N",$enddate); //判断是星期一
	if($w==1 && $s==7 && $cha==6){
		$bool_type = true;
	}
	return $bool_type;
}

function getAgentInfo(){
	$agent = $_SERVER['HTTP_USER_AGENT'];
	$brower = array(
			'MSIE' => 1,
			'Firefox' => 2,
			'QQBrowser' => 3,
			'QQ/' => 3,
			'UCBrowser' => 4,
			'MicroMessenger' => 9,
			'Edge' => 5,
			'Chrome' => 6,
			'Opera' => 7,
			'OPR' => 7,
			'Safari' => 8,
			'Trident/' => 1
	);
	$system = array(
			'Windows Phone' => 4,
			'Windows' => 1,
			'Android' => 2,
			'iPhone' => 3,
			'iPad' => 5
	);
	$browser_num = 0;//未知
	$system_num = 0;//未知
	foreach($brower as $bro => $val){
		if(stripos($agent, $bro) !== false){
			$browser_num = $val;
			break;
		}
	}
	foreach($system as $sys => $val){
		if(stripos($agent, $sys) !== false){
			$system_num = $val;
			break;
		}
	}
	return array('sys' => $system_num, 'bro' => $browser_num);
}

//获取浏览器以及版本号
function getbrowser() {
	global $_SERVER;
	$agent  = $_SERVER['HTTP_USER_AGENT'];
	$browser  = '';
	$browser_ver  = '';

	if (preg_match('/OmniWeb\/(v*)([^\s|;]+)/i', $agent, $regs)) {
		$browser  = 'OmniWeb';
		$browser_ver   = $regs[2];
	}

	if (preg_match('/Netscape([\d]*)\/([^\s]+)/i', $agent, $regs)) {
		$browser  = 'Netscape';
		$browser_ver   = $regs[2];
	}

	if (preg_match('/safari\/([^\s]+)/i', $agent, $regs)) {
		$browser  = 'Safari';
		$browser_ver   = $regs[1];
	}

	if (preg_match('/MSIE\s([^\s|;]+)/i', $agent, $regs)) {
		$browser  = 'Internet Explorer';
		$browser_ver   = $regs[1];
	}

	if (preg_match('/Opera[\s|\/]([^\s]+)/i', $agent, $regs)) {
		$browser  = 'Opera';
		$browser_ver   = $regs[1];
	}

	if (preg_match('/NetCaptor\s([^\s|;]+)/i', $agent, $regs)) {
		$browser  = '(Internet Explorer ' .$browser_ver. ') NetCaptor';
		$browser_ver   = $regs[1];
	}

	if (preg_match('/Maxthon/i', $agent, $regs)) {
		$browser  = '(Internet Explorer ' .$browser_ver. ') Maxthon';
		$browser_ver   = '';
	}
	if (preg_match('/360SE/i', $agent, $regs)) {
		$browser       = '(Internet Explorer ' .$browser_ver. ') 360SE';
		$browser_ver   = '';
	}
	if (preg_match('/SE 2.x/i', $agent, $regs)) {
		$browser       = '(Internet Explorer ' .$browser_ver. ') 搜狗';
		$browser_ver   = '';
	}

	if (preg_match('/FireFox\/([^\s]+)/i', $agent, $regs)) {
		$browser  = 'FireFox';
		$browser_ver   = $regs[1];
	}

	if (preg_match('/Lynx\/([^\s]+)/i', $agent, $regs)) {
		$browser  = 'Lynx';
		$browser_ver   = $regs[1];
	}

	if(preg_match('/Chrome\/([^\s]+)/i', $agent, $regs)){
		$browser  = 'Chrome';
		$browser_ver   = $regs[1];

	}

	if ($browser != '') {
		return array('browser'=>$browser,'version'=>$browser_ver);
	} else {
		return array('browser'=>'unknow browser','version'=>'unknow browser version');
	}
}

function frontStartEntTime($startTime = '', $endTime = ''){
	if ($startTime && $endTime) {
		$frontStarTime = strtotime(date('Y-m-d H:i:s', (strtotime($startTime . ' 00:00:01'))));
		$frontEndTime = strtotime(date('Y-m-d H:i:s', (strtotime($endTime . ' 23:59:59'))));
	} else {
		$frontStarTime = strtotime(date('Y-m-d H:i:s', (strtotime(date('Y-m-d 00:00:01')) - 86400)));
		$frontEndTime = strtotime(date('Y-m-d H:i:s', (strtotime(date('Y-m-d 23:59:59')) - 86400)));
	}
	return array(
		'starTime' => $frontStarTime,
		'endTime' => $frontEndTime,
	);
}

function toStartEntTime($startTime = '', $endTime = ''){
	if ($startTime && $endTime) {
		$toStarTime = strtotime(date('Y-m-d H:i:s', (strtotime($startTime . ' 00:00:01'))));
		$toEndTime = strtotime(date('Y-m-d H:i:s', (strtotime($endTime . ' 23:59:59'))));
	} else {
		$toStarTime = strtotime(date('Y-m-d H:i:s', strtotime(date('Y-m-d 00:00:01'))));
		$toEndTime = strtotime(date('Y-m-d H:i:s', strtotime(date('Y-m-d 23:59:59'))));
	}
	return array(
		'starTime' => $toStarTime,
		'endTime' => $toEndTime,
	);
}

function diffValue($value)
{
	if($value > 0){
		return "<em>+" . $value . "</em>";
	} else {
		return "<span>" . $value . "</span>";
	}
}

function showFrontTimeName($front_start_time, $front_end_time)
{
	if ($front_start_time && $front_end_time) {
		return "前日期";
	} else {
		return "昨天";
	}
}

function showToTimeName($to_start_time, $to_end_time){
	if ($to_start_time && $to_end_time) {
		return "后日期";
	} else {
		return "今天";
	}
}

//返回最近12个月
function this_last_year()
{
	$this_year = (int)date('m');
	$last_year = (int)date('m', strtotime('-12 months'));
	$month_list = array();
	for ($j = ($last_year + 1); $j <= 12; $j++) {
		$_month_j = '';
		if ($j < 10) {
			$_month_j .= '0' . $j;
		} else {
			$_month_j .= $j;
		}
		$last_year_strtotime = strtotime('-12 months');
		$month_list[] = array('monty' => date('Y年', $last_year_strtotime) . $j . '月份', 'value' => date('Y-', $last_year_strtotime) . $_month_j);
	}
	for ($i = 1; $i <= $this_year; $i++) {
		$_month_i = '';
		if ($i < 10) {
			$_month_i .= '0' . $i;
		} else {
			$_month_i .= $i;
		}
		$month_list[] = array('monty' => date('Y年') . $i . '月份', 'value' => date('Y-') . $_month_i);
	}
	return $month_list;
}

/**
 * 求两个日期之间相差的天数
 * (针对1970年1月1日之后，求之前可以采用泰勒公式)
 * @param string $day1
 * @param string $day2
 * @return number
 */
function diffBetweenTwoDays ($second1, $second2)
{
	//$second1 = strtotime($day1);
	//$second2 = strtotime($day2);

	if ($second1 < $second2) {
		$tmp = $second2;
		$second2 = $second1;
		$second1 = $tmp;
	}
	return ($second1 - $second2) / 86400;
}

/**
 * 更为方便的字符串截断+省略号
 * @param $text 字符串，$length 截取长度
 */
function subtext($text, $length)
{
	if(mb_strlen($text, 'utf8') > $length)
		return mb_substr($text, 0, $length, 'utf8').'...';
	return $text;
}





