<?php
namespace Common\WeixinPay;

class RedPack extends WxpayClient{
	function __construct($config) {
		$this->url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack";
		$this->curl_timeout = 30;//先写死
		parent::__construct($config);
	}
	
	/**
	 * 生成接口参数xml
	 */
	function createXml(){
		try{
			//检测必填参数
			if(empty($this->parameters["mch_billno"])) {
				throw new \Exception("缺少:mch_billno");
			}elseif(empty($this->parameters["send_name"])){
				throw new \Exception("缺少:send_name");
			}elseif(empty($this->parameters["re_openid"])){
				throw new \Exception("缺少: re_openid");
			}elseif(empty($this->parameters["total_amount"])){
				throw new \Exception("缺少:total_amount");
			}elseif(empty($this->parameters["total_num"])){
				throw new \Exception("缺少:total_num");
			}elseif(empty($this->parameters["wishing"])){
				throw new \Exception("缺少:wishing");
			}elseif(empty($this->parameters["client_ip"])){
				throw new \Exception("缺少:client_ip");
			}elseif(empty($this->parameters["act_name"])){
				throw new \Exception("缺少:act_name");
			}elseif(empty($this->parameters["remark"])){
				throw new \Exception("缺少:remark");
			}
		   	$this->parameters["wxappid"] = $this->getConfig('APPID');//公众账号ID
		   	$this->parameters["mch_id"] = $this->getConfig('MCHID');//商户号
		    $this->parameters["nonce_str"] = $this->createNoncestr();//随机字符串
		    $this->parameters["sign"] = $this->getSign($this->parameters);//签名
		    $result = $this->arrayToXml($this->parameters);
			return $result;
		}catch (\Exception $e){
			die($e->getMessage());
		}
	}
	/**
	 * 	作用：获取结果，使用证书通信
	 */
	function getResult(){		
		$this->postXmlSSL();
		$this->result = $this->xmlToArray($this->response);
		return $this->result;
	}
	
}
?>