<?php
namespace Common\Payment\Yeepay;

class Client extends CommonUtil{
	//注册接口
	function memberRegister($data){
		$this->postData('/rest/v1.0/member/register', $data);
	}
	
	//实名认证
	function memberVerify($data){
		$this->postData('/rest/v1.0/member/verify', $data);
	}
	
	//实名认证确认
	function memberVerifyConfirm($data){
		$this->postData('/rest/v1.0/member/verifyConfirm', $data);
	}
	
	//重置密码之绑卡鉴权
	function memberBindCard4Reset($data){
		$this->postData('/rest/v1.0/member/bindCard4Reset', $data);
	}
	
	//绑卡
	function memberBinding($data){
		$this->postData('/rest/v1.0/member/binding', $data);
	}
	
	//绑卡确认
	function memberBindingConfirm($data){
		$this->postData('/rest/v1.0/member/bindingConfirm', $data);
	}
	
	/***************************分隔线*********************************/
	//订单支付
	function merchantPay($data){
		$this->postData('/rest/v2.0/merchant/pay', $data);
	}
	
	//支付查询
	//requestId 订单号
	function queryPayOrder($data){
		$this->postData('/rest/v2.0/merchant/queryPayOrder', $data);
	}
	
	//支付验证码确认
	//orderRequestId
	//smsCode
	function payConfirmSms($data){
		$this->postData('/rest/v2.0/merchant/payConfirmSms', $data);
	}
	
	//重发支付验证码
	//orderRequestId 订单号
	function paySendSms($data){
		$this->postData('/rest/v2.0/merchant/paySendSms', $data);
	}
}
