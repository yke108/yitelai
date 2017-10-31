<?php
namespace Wap\Controller\Weixin;
use Wap\Controller\WapController;
use Common\Payment\WeixinPay\AppPay;

class TestController extends WapController {
	public function indexAction(){
		$data = '<xml><appid><![CDATA[wxe947fb74dc0fff75]]></appid>
<bank_type><![CDATA[CFT]]></bank_type>
<cash_fee><![CDATA[1]]></cash_fee>
<fee_type><![CDATA[CNY]]></fee_type>
<is_subscribe><![CDATA[Y]]></is_subscribe>
<mch_id><![CDATA[1329731201]]></mch_id>
<nonce_str><![CDATA[bck2e1wk0c2ws5l7di2v4kmzpgi039rz]]></nonce_str>
<openid><![CDATA[o85QiwSHdMyVEnm5PVdFPCWLX3_I]]></openid>
<out_trade_no><![CDATA[V1608022129351608]]></out_trade_no>
<result_code><![CDATA[SUCCESS]]></result_code>
<return_code><![CDATA[SUCCESS]]></return_code>
<sign><![CDATA[176FDCFF9D94E5D1E8922C1EEB6BAB23]]></sign>
<time_end><![CDATA[20160802212953]]></time_end>
<total_fee>1</total_fee>
<trade_type><![CDATA[JSAPI]]></trade_type>
<transaction_id><![CDATA[4009282001201608020410456246]]></transaction_id>
</xml>';
		
		$url = 'http://yunhu.t.weixinren.cn/web8/weixin.php';
        //初始化curl        
       	$ch = curl_init();
		//设置超时
		curl_setopt($ch, CURLOP_TIMEOUT, 30);
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
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		//运行curl
        $data = curl_exec($ch);
		curl_close($ch);
		var_dump($data);
	}
	
}