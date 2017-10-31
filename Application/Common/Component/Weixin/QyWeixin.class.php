<?php
namespace Common\Component\Weixin;
class QyWeixin {
	private $domain_url = 'https://qyapi.weixin.qq.com/';
	private $corpId = '';
	private $corpSecret = '';
	private $token = '';
	private $token_expire = '';
	private $config = array();
	
	function __construct(){
		$config = $this->configService()->findQyWeixinConfigs();
		$this->config = $config;
		$this->corpId = $config['corp_id'];
		$this->corpSecret = $config['corp_secret'];
		$this->token = $config['corp_token'];
		$this->token_expire = $config['token_expire'];
	}
	
	//创建通讯录成员
	public function userCreate($data){
		$token = $this->getToken();
		if(empty($token)){
			return false;
		}
		$url  = 'cgi-bin/user/create';
		$url .= '?access_token='.$token;
		return $this->cpost($url, $data);
	}
	
	public function userUpdate($data){
		$token = $this->getToken();
		if(empty($token)){
			return false;
		}
		$url  = 'cgi-bin/user/update';
		$url .= '?access_token='.$token;
		return $this->cpost($url, $data);
	}
	
	//删除通讯录成员
	public function userDelete($userid){
		$token = $this->getToken();
		if(empty($token)){
			return false;
		}
		$url  = 'cgi-bin/user/delete';
		$url .= '?access_token='.$token;
		$url .= '&userid='.$userid;
		return $this->cget($url);
	}
	
	//获取token值
	public function getToken(){
		$token = $this->getCachedToken();
		if(!empty($token)){
			return $token;
		}
		
		$url  = 'cgi-bin/gettoken';
		$url .= '?corpid='.$this->corpId;
		$url .= '&corpsecret='.$this->corpSecret;
		
		$info = $this->cget($url);
		if(empty($info['access_token'])){
			return '';
		} else {
			$this->saveCachedToken($info);
			return $info['access_token'];
		}
	}
	
	//获取缓存的token
	private function getCachedToken(){
		if($this->config['token_expire'] < NOW_TIME - 5)
			return '';
		return $this->config['corp_token'];
	}
	
	private function saveCachedToken($info){
		$this->config['corp_token'] = $info['access_token'];
		$this->config['token_expire'] = NOW_TIME + intval($info['expires_in']);
		$this->configService()->updateConfigs($this->config['_type'], $this->config);
	}
	
	//post数据
    public function cpost($url, $data){
		$string = '';
		foreach($data as $key => $val){
			if(!empty($string)){
				$string .= ',';
			}
			if(is_array($val)){
				$string .= '"'.$key.'":'.json_encode($val);
			} else {
				$string .= '"'.$key.'":"'.$val.'"';
			}
		}
		$data = '{'.$string.'}';		
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER,0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $this->domain_url.$url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $rst = curl_exec($ch);
        curl_close($ch);
        return json_decode($rst, true);
    }
    
	//get数据
    public function cget($url){
        $str = file_get_contents($this->domain_url.$url);
        return json_decode($str, true);
    }
    
	public function getAccessData($code){
		$access_token = $this->getToken();
		$url = "https://qyapi.weixin.qq.com/cgi-bin/user/getuserinfo?access_token={$access_token}&code={$code}";
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
	
	private function configService(){
		return D('Config', 'Service');
	}
}
