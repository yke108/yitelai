<?php
namespace Common\Service;
use Think\Model;
use Common\Basic\Status;
use Common\Model\OrderInfo;
use Common\Model\Shipping;

class PaymentService{
	private $system_config;
	
	public function __construct(){
		$system_config = $this->configService()->getConfig('system');
		foreach($system_config as $key=>$val){
			$this->system_config[$key]=$val['fval'];
		}
	}
	
	public function payRecharge($params){
		$this->userCheck($params);
		if (!in_array($params['pay_id'], array(1, 2, 3, 4))) throw new \Exception('支付方式不正确');
		
		$params['body'] = '在线充值';
		$params['out_trade_no'] = '';
		$params['total_fee'] = $params['money'];
		$params['notify_url'] = DK_DOMAIN.'/wap/weixin.php';
		return $this->_pay($params);
	}
	
	private function _pay($params){
		if ($params['pay_id'] == 0){
			return $this->_payWithUserMoney($params);
		}elseif ($params['pay_id'] == 1){
			$payment = array(
					'body'=>$params['body'],
					'payment_id'=>$params['payment_id'],
					'cur_money'=>$params['money'],
			);
			\Common\Payment\Alipay::dopay($payment);
		}elseif ($params['pay_id'] == 2){
			$user = $this->userInfoDao()->getRecord($params['user_id']);
			$wxconf = $this->configService()->findWeixinConfigs();
			$unifiedOrder = new \Common\Payment\WeixinPay\AppPay($wxconf['js_app_id'], $wxconf['mchid'], $wxconf['key']);
			$unifiedOrder->setParameter("openid", $user['openid']);//商品描述
			$unifiedOrder->setParameter("body", $params['body']);//商品描述
			$unifiedOrder->setParameter("out_trade_no", $params['out_trade_no']);//订单号
			$unifiedOrder->setParameter("total_fee", ($params['total_fee']*100));//总金额
			$unifiedOrder->setParameter("notify_url", $params['notify_url']);//通知地址
			$unifiedOrder->setParameter("trade_type", "JSAPI");//交易类型
			//var_dump($unifiedOrder);
			$jsApiParameters = $unifiedOrder->createTradeDataForJS();
			//var_dump($jsApiParameters);exit;
			return array(
				'js_api_params'=>$jsApiParameters,
			);
		}
	}
	
	private function _payWithUserMoney($params){
		$user = $this->userInfoDao()->getRecord($params['user_id']);
		if(empty($user) || $user['user_money'] < $params['order_amount']) throw new \Exception('余额不足');
		
		//扣款
		if($this->userInfoDao()->depleteMoney($params['user_id'], $params['money']) < 1) {
			M()->rollback();
			throw new \Exception('订单支付失败');
		}
		
		$params['user_money'] = $user['user_money'];
		return $this->dealAfterPay($params);
	}
	
	private function userCheck(&$params){
		$params['user_id'] = intval($params['user_id']);
		if($params['user_id'] < 1){
			throw new \Exception('缺少用户参数');
		}
	}
	
	private function payNameLabel($pay_id){
		$l = array(
				'1' => '余额支付',
				'2' => '微信支付',
				'3' => '支付宝支付',
		);
		return $l[$pay_id];
	}
	
	private function userAccountDao(){
		return D('Common/User/UserAccount');
	}
	
	private function userInfoDao(){
		return D('Common/User/UserInfo');
	}
	
	private function configService(){
		return D('Config', 'Service');
	}
}