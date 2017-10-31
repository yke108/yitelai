<?php
namespace Main\Controller\Weixin;
use Main\Controller\MainController;
use Common\Payment\WeixinPay\AppPay;

class NotifyController extends MainController {
	public function cartpayAction(){
		$postStr = $GLOBALS['HTTP_RAW_POST_DATA'];	
		M('Test')->add(array('txt'=>$postStr));	
		empty($postStr) && $postStr = file_get_contents("php://input");
		
		$array_data = $this->xmlToArray($postStr);
		if($array_data['return_code'] != 'SUCCESS'){
			$this->returnMsgToWeixin(false);
		}

		$sign = $array_data['sign'];
		unset($array_data['sign']);
		
		$config = $this->configService()->findWeixinConfigs();
		
		$apppay = new AppPay($config['js_app_id'], $config['mchid'], $config['key']);
		$sign_chk = $apppay->getSign($array_data);
		
		if($sign != $sign_chk){
			$this->returnMsgToWeixin(false);
		}

		try {
			$params = array(
				'general_order_id'=>$array_data['out_trade_no'],
				'total_order_amount'=>$array_data['total_fee'],
			);
			$result = $this->orderService()->paySuccessByWeixin($params);
			if ($result === true){
				$this->returnMsgToWeixin(true);
			}
		} catch (\Exception $e) {
			$this->returnMsgToWeixin(false);
		}
		$this->returnMsgToWeixin(false);
	}
	
	public function orderpayAction(){
		$postStr = $GLOBALS['HTTP_RAW_POST_DATA'];
		M('Test')->add(array('txt'=>$postStr));
		empty($postStr) && $postStr = file_get_contents("php://input");
	
		$array_data = $this->xmlToArray($postStr);
		if($array_data['return_code'] != 'SUCCESS'){
			$this->returnMsgToWeixin(false);
		}
	
		$sign = $array_data['sign'];
		unset($array_data['sign']);
	
		$config = $this->configService()->findWeixinConfigs();
	
		$apppay = new AppPay($config['js_app_id'], $config['mchid'], $config['key']);
		$sign_chk = $apppay->getSign($array_data);
	
		if($sign != $sign_chk){
			$this->returnMsgToWeixin(false);
		}
	
		try {
			$params = array(
					'order_id'=>$array_data['out_trade_no'],
					'order_amount'=>$array_data['total_fee'],
			);
			$result = $this->orderService()->paySuccessByWeixin($params);
			if ($result === true){
				$this->returnMsgToWeixin(true);
			}
		} catch (\Exception $e) {
			$this->returnMsgToWeixin(false);
		}
		$this->returnMsgToWeixin(false);
	}
	
	public function rechargepayAction(){
		$postStr = $GLOBALS['HTTP_RAW_POST_DATA'];
		M('Test')->add(array('txt'=>$postStr));
		empty($postStr) && $postStr = file_get_contents("php://input");
	
		$array_data = $this->xmlToArray($postStr);
		if($array_data['return_code'] != 'SUCCESS'){
			$this->returnMsgToWeixin(false);
		}
	
		$sign = $array_data['sign'];
		unset($array_data['sign']);
	
		$config = $this->configService()->findWeixinConfigs();
	
		$apppay = new AppPay($config['js_app_id'], $config['mchid'], $config['key']);
		$sign_chk = $apppay->getSign($array_data);
	
		if($sign != $sign_chk){
			$this->returnMsgToWeixin(false);
		}
	
		try {
			$params = array(
					'order_sn'=>$array_data['out_trade_no'],
					'order_amount'=>$array_data['total_fee'],
					'pay_id'=>2,
			);
			$result = $this->rechargeService()->paySuccess($params);
			if ($result === true){
				$this->returnMsgToWeixin(true);
			}
		} catch (\Exception $e) {
			M('Test')->add(array('txt'=>$e->getMessage()));
			$this->returnMsgToWeixin(false);
		}
		$this->returnMsgToWeixin(false);
	}
	
	public function rewardAction(){
		$postStr = $GLOBALS['HTTP_RAW_POST_DATA'];
		M('Test')->add(array('txt'=>$postStr));
		empty($postStr) && $postStr = file_get_contents("php://input");
		
		$array_data = $this->xmlToArray($postStr);
		if($array_data['return_code'] != 'SUCCESS'){
			$this->returnMsgToWeixin(false);
		}
		
		$sign = $array_data['sign'];
		unset($array_data['sign']);
		
		$config = $this->configService()->findWeixinConfigs();
		
		$apppay = new AppPay($config['js_app_id'], $config['mchid'], $config['key']);
		$sign_chk = $apppay->getSign($array_data);
		
		if($sign != $sign_chk){
			$this->returnMsgToWeixin(false);
		}
		
		try {
			$params = array(
					'reward_id'=>$array_data['out_trade_no'],
					'reward_amount'=>$array_data['total_fee'],
			);
			$result = $this->storyService()->paySuccess($params);
			if ($result === true){
				$this->returnMsgToWeixin(true);
			}
		} catch (\Exception $e) {
			$this->returnMsgToWeixin(false);
		}
		$this->returnMsgToWeixin(false);
	}
	
	public function depositAction(){
		$postStr = $GLOBALS['HTTP_RAW_POST_DATA'];
		M('Test')->add(array('txt'=>$postStr));
		empty($postStr) && $postStr = file_get_contents("php://input");
	
		$array_data = $this->xmlToArray($postStr);
		if($array_data['return_code'] != 'SUCCESS'){
			$this->returnMsgToWeixin(false);
		}
	
		$sign = $array_data['sign'];
		unset($array_data['sign']);
	
		$config = $this->configService()->findWeixinConfigs();
	
		$apppay = new AppPay($config['js_app_id'], $config['mchid'], $config['key']);
		$sign_chk = $apppay->getSign($array_data);
	
		if($sign != $sign_chk){
			$this->returnMsgToWeixin(false);
		}
	
		try {
			$params = array(
					'merchant_id'=>$array_data['out_trade_no'],
					'deposit'=>$array_data['total_fee'] / 100,
			);
			$result = $this->merchantService()->payDepositSuccess($params);
			if ($result === true){
				$this->returnMsgToWeixin(true);
			}
		} catch (\Exception $e) {
			$this->returnMsgToWeixin(false);
		}
		$this->returnMsgToWeixin(false);
	}
	
	public function serviceAction(){
		$postStr = $GLOBALS['HTTP_RAW_POST_DATA'];
		M('Test')->add(array('txt'=>$postStr));
		empty($postStr) && $postStr = file_get_contents("php://input");
	
		$array_data = $this->xmlToArray($postStr);
		if($array_data['return_code'] != 'SUCCESS'){
			$this->returnMsgToWeixin(false);
		}
	
		$sign = $array_data['sign'];
		unset($array_data['sign']);
	
		$config = $this->configService()->findWeixinConfigs();
	
		$apppay = new AppPay($config['js_app_id'], $config['mchid'], $config['key']);
		$sign_chk = $apppay->getSign($array_data);
	
		if($sign != $sign_chk){
			$this->returnMsgToWeixin(false);
		}
	
		try {
			$params = array(
					'merchant_id'=>$array_data['out_trade_no'],
					'service_charge'=>$array_data['total_fee'] / 100,
			);
			$result = $this->merchantService()->payServiceSuccess($params);
			if ($result === true){
				$this->returnMsgToWeixin(true);
			}
		} catch (\Exception $e) {
			$this->returnMsgToWeixin(false);
		}
		$this->returnMsgToWeixin(false);
	}
	
	private function returnMsgToWeixin($rst){
		$tpl = "<xml>";
		$tpl .= "<return_code><![CDATA[%s]]></return_code>";
		$tpl .= "<return_msg><![CDATA[%s]]></return_msg>";
		$tpl .= "</xml>";
		$outstr = sprintf($tpl, ($rst?'SUCCESS':'FAIL'), 'F');
		echo $outstr;
		exit;
	}
	
	private function xmlToArray($xml){
    	$array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    	return $array_data;
    }

    private function orderService(){
    	return D('Order', 'Service');
    }
	
	private function rechargeService(){
		return D('Recharge','Service');
	}
	
	private function storyService(){
		return D('Story','Service');
	}
	
	private function merchantService(){
		return D('Merchant','Service');
	}
}