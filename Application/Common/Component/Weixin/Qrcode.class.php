<?php
namespace Common\Component\Weixin;
	
class Qrcode extends Basic{
    private $token;

  	public function __construct($token) {
  		$this->token = $token;
  	}
  	
  	public function createQrcode($scene_id, $time){
  		return $time > 0 ? $this->createSceneQrcode($scene_id, $time) : $this->createLimitSceneQrcode($scene_id);
  	}
	
	//获取临时二维码
	function createSceneQrcode($scene_id, $seconds = 2592000){
		$seconds < 1 && $seconds = 2592000;
		$data = '{"expire_seconds":'.$seconds.', "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": '.$scene_id.'}}}';
		$url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=".$this->token;
		$res = $this->postCurl($url, $data);
		$result = json_decode($res, true);
		$qrcode_url = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=".$result["ticket"];
		return array(
			'qrcode_url'=>$qrcode_url,
			'expire_time'=>NOW_TIME + $seconds - 86400,
		);
	}
	
	
	//获取永久二维码
	function createLimitSceneQrcode($scene_id){
		$data = '{"action_name": "QR_LIMIT_SCENE", "action_info": {"scene": {"scene_id": '.$scene_id.'}}}';
		$url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=".$this->token;
		$res = $this->postCurl($url, $data);
		$result = json_decode($res, true);
		$qrcode_url = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=".$result["ticket"];
		return array(
			'qrcode_url'=>$qrcode_url,
			'expire_time'=>0,
		);
	}

}