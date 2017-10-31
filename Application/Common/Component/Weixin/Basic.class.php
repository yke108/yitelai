<?php
namespace Common\Component\Weixin;

class Basic {
	/**
	 * 	作用：以post方式提交xml到对应的接口url
	 */
	public function postCurl($url,$xml,$second=30){		
        //初始化curl        
       	$ch = curl_init();
		//设置超时
		curl_setopt($ch, CURLOP_TIMEOUT, $second);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
		//设置header
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		//post提交方式
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		//运行curl
        $data = curl_exec($ch);
		$error = curl_errno($ch);
		curl_close($ch);
		if(empty($data)){
			throw new \Exception('curl出错', $error);
		}
		return $data;
	}

	/**
	 * 	作用：使用证书，以post方式提交xml到对应的接口url
	 */
	function postSSLCurl($url,$xml,$second=30){
		$ch = curl_init();
		//超时时间
		curl_setopt($ch,CURLOPT_TIMEOUT,$second);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
		//设置header
		curl_setopt($ch,CURLOPT_HEADER,FALSE);
		//要求结果为字符串且输出到屏幕上
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
		//设置证书
		//使用证书：cert 与 key 分别属于两个.pem文件
		//默认格式为PEM，可以注释
		curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
		curl_setopt($ch,CURLOPT_SSLCERT, $this->SSLCERT_PATH);
		//默认格式为PEM，可以注释
		curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
		curl_setopt($ch,CURLOPT_SSLKEY, $this->SSLKEY_PATH);
		//post提交方式
		curl_setopt($ch,CURLOPT_POST, true);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$xml);
		$data = curl_exec($ch);
		$error = curl_errno($ch);
		curl_close($ch);
		if(empty($data)){
			throw new \Exception('curl出错',$error);
		}
		return $data;
	}
}