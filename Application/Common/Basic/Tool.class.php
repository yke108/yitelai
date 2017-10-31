<?php
namespace Common\Basic;
class Tool{
    public function is_phone($num){
    	if (strlen($num) != 11) return false;
    	$pattern = '/^1[0-9]{10}$/is';
		$ret = preg_match($pattern, $num);
		return $ret < 1 ? false : true;
    }
    
    public function is_bank_card($str){
    	if (strlen($str) < 15) return false;
    	$pattern = '/^[1-9][0-9]{14,18}$/is';
    	$ret = preg_match($pattern, $str);
    	return $ret < 1 ? false : true;
    }
    
    public function getDwz($url){
    	$ch=curl_init();
    	curl_setopt($ch,CURLOPT_URL,"http://dwz.cn/create.php");
    	curl_setopt($ch,CURLOPT_POST,true);
    	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    	$data=array('url'=>$url);
    	curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
    	$strRes=curl_exec($ch);
    	curl_close($ch);
    	$arrResponse=json_decode($strRes,true);
    	if($arrResponse['status']==0){
    		return '';
    	}
    	return $arrResponse['tinyurl'];
    }
}