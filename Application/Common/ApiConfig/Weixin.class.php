<?php
namespace Common\ApiConfig;

class Weixin{
	const JS_MCH_ID = ''; //微信端对应的商户号
	const APP_MCH_ID = ''; //App端对应的商户号
	public function jsapiConfig(){
		return array(
			'appid'=>'wxe947fb74dc0fff75', //微信端AppId
			'appsecret'=>'d268d7f2ba64468aa49fc7e8f309748c', //
			'token'=>'lGfjdEZGI9Q4K7fyDSnlGG', //
			//'aeskey'=>'ExEwLFP8AGbPntbKICf3ckGWxEBXRBxoAbX9kKhJvvE', //
			'key'=>'', //支付Key
			'jsapi_call_url'=>DK_DOMAIN.'/wap/Wxpay/pay.html',
			'notify_url'=>DK_DOMAIN.'/wap/Wxpay/callback.html',
			'curl_timeout'=>'30',
			'mch_id'=>self::JS_MCH_ID,
			'sslcert_path'=>API_CERT_PATH.'Wx'.self::JS_MCH_ID.'/apiclient_cert.pem',
			'sslkey_path'=>API_CERT_PATH.'Wx'.self::JS_MCH_ID.'/apiclient_key.pem',
		);
	}
	
	public function appConfig(){
		return array(
			'appid'=>'', //App端AppId
			'key'=>'', //
			'notify_url'=>DK_DOMAIN.'/wap/Wxpay/callback.html',
			'curl_timeout'=>'30',
			'mch_id'=>self::APP_MCH_ID,
			'sslcert_path'=>API_CERT_PATH.'Wx'.self::APP_MCH_ID.'/apiclient_cert.pem',
			'sslkey_path'=>API_CERT_PATH.'Wx'.self::APP_MCH_ID.'/apiclient_key.pem',
		);
	}
	
	public function configForMch($mch_id){
		if($mch_id == self::JS_MCH_ID){
			return self::jsapiConfig();
		} else if($mch_id == self::APP_MCH_ID) {
			return self::appConfig();
		}
		return array();
	}
}
