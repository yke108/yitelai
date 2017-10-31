<?php
namespace Common\Component\Weixin;

class Wxjssdk {
  private $appId;
  private $appSecret;
  private $accessToken;

	public function __construct($appId, $appSecret,$accessToken) {
		$this->appId = $appId;
		$this->appSecret = $appSecret;
		$this->accessToken = $accessToken;
	}

  public function getSignPackage() {
    $jsapiTicket = $this->getJsApiTicket();

    // 注意 URL 一定要动态获取，不能 hardcode.
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $url = $protocol.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

    $timestamp = time();
    $nonceStr = $this->createNonceStr();

    // 这里参数的顺序要按照 key 值 ASCII 码升序排序
    $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

    $signature = sha1($string);

    $signPackage = array(
		"appId"     => $this->appId,
		"nonceStr"  => $nonceStr,
		"timestamp" => $timestamp,
		"url"       => $url,
		"signature" => $signature,
		"rawString" => $string
    );
    return $signPackage;
  }

  private function createNonceStr($length = 16) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $str = "";
    for ($i = 0; $i < $length; $i++) {
      $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
  }

	private function getJsApiTicket() {
		$path = RUNTIME_PATH.'/jsapi_ticket.json';
    	$data = json_decode(file_get_contents($path));
    	if ($data->expire_time < time()) {
	      	  $accessToken = $this->accessToken;
	      	$url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
	      	$res = json_decode($this->httpGet($url));
	      	$ticket = $res->ticket;
	      	if ($ticket) {
	        	$data->expire_time = time() + 7000;
	        	$data->jsapi_ticket = $ticket;
	       	 	$fp = fopen($path, "w");
	       	 	fwrite($fp, json_encode($data));
	        	fclose($fp);
	      	}
    	} else {
			$ticket = $data->jsapi_ticket;
    	}
		return $ticket;
	}

  public function httpGet($url) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 500);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_URL, $url);

    $res = curl_exec($curl);
    curl_close($curl);

    return $res;
  }
  
	/**
	 * 	作用：格式化参数，签名过程需要使用
	 */
	function formatBizQueryParaMap($paraMap, $urlencode)
	{
		$buff = "";
		ksort($paraMap);
		foreach ($paraMap as $k => $v)
		{
		    if($urlencode)
		    {
			   $v = urlencode($v);
			}
			//$buff .= strtolower($k) . "=" . $v . "&";
			$buff .= $k . "=" . $v . "&";
		}
		$reqPar;
		if (strlen($buff) > 0) 
		{
			$reqPar = substr($buff, 0, strlen($buff)-1);
		}
		return $reqPar;
	}
  
	function createOauthUrlForCode($redirectUrl)
	{
		$urlObj["appid"] = $this->appId;
		$urlObj["redirect_uri"] = $redirectUrl;
		$urlObj["response_type"] = "code";
		$urlObj["scope"] = "snsapi_base";
		$urlObj["state"] = "STATE"."#wechat_redirect";
		$bizString = $this->formatBizQueryParaMap($urlObj, false);
		return "https://open.weixin.qq.com/connect/oauth2/authorize?".$bizString;
	}
	
	function createOauthUrlForUserInfoCode($redirectUrl)
	{
		$urlObj["appid"] = $this->appId;
		$urlObj["redirect_uri"] = $redirectUrl;
		$urlObj["response_type"] = "code";
		$urlObj["scope"] = "snsapi_userinfo";
		$urlObj["state"] = "STATE"."#wechat_redirect";
		$bizString = $this->formatBizQueryParaMap($urlObj, false);
		return "https://open.weixin.qq.com/connect/oauth2/authorize?".$bizString;
	}

	/**
	 * 	作用：生成可以获得openid的url
	 */
	function createOauthUrlForOpenid()
	{
		$urlObj["appid"] = $this->appId;
		$urlObj["secret"] = $this->appSecret;
		$urlObj["code"] = $this->code;
		$urlObj["grant_type"] = "authorization_code";
		$bizString = $this->formatBizQueryParaMap($urlObj, false);
		return "https://api.weixin.qq.com/sns/oauth2/access_token?".$bizString;
	}
	
	public function getAccessData(){
		$url = $this->createOauthUrlForOpenid();
        //初始化curl
       	$ch = curl_init();
		//设置超时
		curl_setopt($ch, CURLOP_TIMEOUT, $this->curl_timeout);
		curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		//运行curl，结果以jason形式返回
        $res = curl_exec($ch);
		curl_close($ch);
		//取出openid
		return json_decode($res,true);
	}
	
	/**
	 * 	作用：设置code
	 */
	function setCode($code_){
		$this->code = $code_;
	}
	
	
	/**
	 * 	作用：通过curl向微信提交code，以获取openid
	 */
	function getOpenid()
	{
		$data = $this->getAccessData();
		$this->openid = $data['openid'];
		return $this->openid;
	}
  
}