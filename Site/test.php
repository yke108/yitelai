<?php
//$url = 'http://yitelai.t.weixinren.cn/weixin/cartpay.php';
$url = 'http://127.0.0.1/weixinren/yitelai/site/weixin/cartpay.php';

$data = '<xml><appid><![CDATA[wx79737d6ff1bade4a]]></appid>
<bank_type><![CDATA[CFT]]></bank_type>
<cash_fee><![CDATA[1]]></cash_fee>
<fee_type><![CDATA[CNY]]></fee_type>
<is_subscribe><![CDATA[N]]></is_subscribe>
<mch_id><![CDATA[1352029801]]></mch_id>
<nonce_str><![CDATA[vlrq187mjvwnfwraybmjxyn0hmo6xsmr]]></nonce_str>
<openid><![CDATA[oiLwCxAjAy_XYxzetWojQbT3OGd4]]></openid>
<out_trade_no><![CDATA[1612121543255065]]></out_trade_no>
<result_code><![CDATA[SUCCESS]]></result_code>
<return_code><![CDATA[SUCCESS]]></return_code>
<sign><![CDATA[51CDB604D8479E7D9FADF88529A454D6]]></sign>
<time_end><![CDATA[20161128222039]]></time_end>
<total_fee>1</total_fee>
<trade_type><![CDATA[JSAPI]]></trade_type>
<transaction_id><![CDATA[4009282001201611281125494086]]></transaction_id>
</xml>';


$result = postXmlCurl($data, $url);
var_dump($result);

function postXmlCurl($xml,$url,$second=30)
	{	
		//var_dump($xml);
        //初始化curl        
       	$ch = curl_init();
		//设置超时
		//curl_setopt($ch, CURLOP_TIMEOUT, $second);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
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
		//curl_close($ch);
		//返回结果
		if($data)
		{
			curl_close($ch);
			return $data;
		}
		else 
		{ 
			$error = curl_errno($ch);
			echo "curl出错，错误码:$error"."<br>"; 
			echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
			curl_close($ch);
			return false;
		}
	}
