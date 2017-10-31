<?php
namespace Common\Service;
use \Common\Component\Weixin\Wxjssdk;
use \Common\Component\Weixin\Qrcode;
//use Think\Log;

class WeixinService{
	
	public function jsSignPackage(){
		$configs = $this->configService()->findWeixinConfigs();
		$access_token = $this->getAccessToken($configs['js_app_id'], $configs['js_app_secret']);
		$jssdk = new Wxjssdk($configs['js_app_id'], $configs['js_app_secret'], $access_token);
		return $jssdk->GetSignPackage();
	}
	
	public function getAccessToken($appid, $secret){
		$configService = $this->configService();
		$configs = $configService->findWeixinToken();
	    //Log::write($configs, Log::INFO);
		if(!empty($configs) && $configs['expire_time'] > NOW_TIME){
			return $configs['access_token'];
		}
		
		$url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential';
		$url .= '&appid='.$appid;
		$url .= '&secret='.$secret;
		$data = $this->httpGet($url);
		$result = json_decode($data);
	    if($result->access_token) {
			$configs = array(
				'access_token'=>$result->access_token,
				'expire_time'=>NOW_TIME + $result->expires_in,
			);
			$configService->updateWeixinToken($configs);
			return $configs['access_token'];
	    }
		return '';
	}
	
	public function getQrcode($id, $time){
		$configs = $this->configService()->findWeixinConfigs();
		$access_token = $this->getAccessToken($configs['js_app_id'], $configs['js_app_secret']);
		$obj = new Qrcode($access_token);
		return $obj->createQrcode($id, $time);
	} 
	
	public function getWeixinUserInfo($openid){
		$configs = $this->configService()->findWeixinConfigs();
		$access_token = $this->getAccessToken($configs['js_app_id'], $configs['js_app_secret']);
    	$url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";
    	return json_decode($this->httpGet($url), true);
	}
	
	function sendTemplateMessage($params){
		$access_token = $this->getAccessToken($params['js_app_id'], $params['js_app_secret']);
		$data = array(
				'first'=>array('value'=>urlencode($params['first']),'color'=>"#654a9b")
		);
		if ($params['keywords']) {
			foreach($params['keywords'] as $key => $value){
				$data[$key] = array('value'=>urlencode($value),'color'=>"#654a9b");
			}
		}
		$data['remark'] = array('value'=>urlencode($params['remark']),'color'=>"#FF0000");
		$template = array(
				'touser' => $params['openid'],
				'template_id' => $params['template_id'],
				'url' => $params['url'],
				'topcolor' => '#FF0000',
				'data' => $data
		);
		$json_template = json_encode($template);
		$data_url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$access_token;
		return curlPost($data_url,urldecode($json_template));
	}
	
	function sendTemplateMessageTest($params){
		$access_token = $this->getAccessToken($params['js_app_id'], $params['js_app_secret']);
		$template = array(
				'touser' => $params['openid'],
				'template_id' => $params['template_id'],
				'url' => $params['url'],
				'topcolor' => '#FF0000',
				'data' => $params['data']
		);
		$json_template = json_encode($template);
		$data_url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$access_token;
		return curlPost($data_url,urldecode($json_template));
	}
	
	private function httpGet($url){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($curl, CURLOPT_POST, 1);
	    //curl_setopt($curl, CURLOPT_HTTPHEADER, array($header));
		$data = curl_exec($curl);
		curl_close($curl);
		return $data;
	}
	
	private function configService(){
		return D('Config', 'Service');
	}
}
