<?php
namespace Wap\Controller\Weixin;
use Wap\Controller\WapController;
use Common\Payment\WeixinPay\AppPay;
use Common\Basic\Status;

class NotifyController extends WapController {
	public function indexAction(){
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
					'pay_id'=>2,
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
	
	public function paynowAction(){
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
					'general_order_amount'=>$array_data['total_fee'],
					'pay_id'=>2
			);
			$result = $this->orderService()->getGeneralOrderInfo($params);
		} catch (\Exception $e) {
			$this->returnMsgToWeixin(false);
		}
		
		foreach ($result['orders'] as $order) {
			try {
				$params = array(
						'order_id'=>$order['order_id'],
						'order_amount'=>$order['order_amount'],
						'pay_id'=>2
				);
				$result = $this->orderService()->dealAfterPay($params);
				if ($result === true){
					$this->returnMsgToWeixin(true);
				}
			} catch (\Exception $e) {
				$this->returnMsgToWeixin(false);
			}
		}
		
		$this->returnMsgToWeixin(false);
	}
	
	public function payAction(){
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
					'pay_id'=>2
			);
			$result = $this->orderService()->dealAfterPay($params);
			if ($result === true){
				$this->returnMsgToWeixin(true);
			}
		} catch (\Exception $e) {
			$this->returnMsgToWeixin(false);
		}
		$this->returnMsgToWeixin(false);
	}
	
	//支付单
	public function paymentAction(){
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
					'payment_id'=>$array_data['out_trade_no'],
					'money_paid'=>$array_data['total_fee'],
					'pay_id'=>2,
			);
			$result = $this->orderService()->paymentSuccess($params);
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
}