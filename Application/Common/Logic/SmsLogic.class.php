<?php
namespace Common\Logic;
use Common\Basic\CsException;
use Common\Basic\Status;

class SmsLogic {
	public function smsLogCreateWithTemplate($template_id, $vals){
		
	}
	
	public function smsLogCreate($mobile, $vals){
		$data = array(
				'mobile'=>$mobile,
				'sms_vals'=>json_encode($vals),
		);
		return $this->smsLogDao()->addRecord($data);
	}
	
	public function smsLogByTemplate($id, $mobile, $data = array()){
		/* 发短信 */
		$template = $this->smsTemplateDao()->getRecord($id);
		if ($template['template_sms_status'] == 1) {
			$str = $template['template_sms'];
			if(is_array($data)){
				foreach($data as $k => $v){
					$str = str_replace('{'.$k.'}', $v, $str);
				}
			}
			
			//短信记录
			$data_sms = array(
					'mobile'=>$mobile,
					'content'=>$str,
			);
			$result = $this->smsLogDao()->addRecord($data_sms);
			if ($result === false) {
				throw new CsException('发送失败');
			}
		}
		
		/* 发消息 */
		if ($template) {
			//用户信息
			if ($data['UserId']) {
				$user_ids = array($data['UserId']);
				$data_message = array(
						'type'=>1,
						'title'=>$data['Title'],
						'content'=>$str,
						'msg_type'=>$data['MsgType'],
				);
				try{
					$this->messageService()->addAllMessage($user_ids, $data_message);
				}catch(\Exception $e){
					throw new CsException($e->getMessage());
				}
			}
			
			//店铺信息
			if ($data['DistributorId']) {
				$user_ids = array($data['DistributorId']);
				$data_message = array(
						'type'=>1,
						'title'=>$data['Title'],
						'content'=>$str,
						'msg_type'=>$data['MsgType'],
				);
				try{
					$this->distributorMessageService()->addAllMessage($user_ids, $data_message);
				}catch(\Exception $e){
					throw new CsException($e->getMessage());
				}
			}
		}
		
		/* 模板消息 */
		if ($data['UserId']) {
			$user_info = $this->userInfoDao()->getRecord($data['UserId']);
			if ($user_info['openid']) {
				switch ($data['MsgType']) {
					case Status::MsgTypeShipping: //发货
						$order_info = $this->orderService()->getOrderInfo(array('order_id'=>$data['OrderId']));
						$url = DK_DOMAIN.'/wap/index.php?s=/user/order/info/id/'.$order_info['order_id'];
						$keywords = array(
								'keyword1'=>$order_info['consignee'],
								'keyword2'=>$order_info['mobile'],
								'keyword3'=>$order_info['shipping_name'],
								'keyword4'=>$order_info['shipping_no'],
								'keyword5'=>$order_info['order_id'],
								'remark'=>'感谢你的使用。',
						);
						$params = array(
								'openid'=>$user_info['openid'],
								'template_id'=>'DvQ5YZO8x7qzSpN8-zWvuuHMG_ZZlHXygl_s7Yc6eOY',
								'url'=>$url,
								'first'=>'您好，您的处方订单已送出，请保持手机畅通，以便快递及时联系您！',
								'keywords'=>$keywords,
								'remark'=>$str,
						);
						$result = $this->weixinService()->sendTemplateMessage($params);
						if ($result['errcode'] > 0) {
							throw new \Exception($result['errmsg']);
						}
						break;
			
					case Status::MsgTypePayOrder: //支付成功
						$order_info = $this->orderService()->getOrderInfo(array('order_id'=>$data['OrderId']));
						$url = DK_DOMAIN.'/wap/index.php?s=/user/order/info/id/'.$order_info['order_id'];
						$list = $this->orderService()->orderGoodsList(array('order_id'=>$order_info['order_id']));
						$order_goods = array();
						foreach ($list as $v) {
							$order_goods[] = $v['goods_name'].'*'.$v['goods_number'];
						}
						$order_goods = implode('，', $order_goods);
						$keywords = array(
								'keyword1'=>$order_info['order_id'],
								'keyword2'=>$order_goods,
								'keyword3'=>'￥'.$order_info['order_amount'],
								'keyword4'=>'支付成功',
						);
						$params = array(
								'openid'=>$user_info['openid'],
								'template_id'=>'UwbpUKyLCiellwkN9TYy6BT_RhP-SCHlY_JOTyp4RFI',
								'url'=>$url,
								'first'=>'您的订单已经支付成功',
								'keywords'=>$keywords,
								'remark'=>'感谢您的支持，点击查看订单详情',
						);
						$result = $this->weixinService()->sendTemplateMessage($params);
						if ($result['errcode'] > 0) {
							throw new \Exception($result['errmsg']);
						}
						break;
			
					case Status::MsgTypeRecharge: //充值
						$recharge_info = $this->orderService()->getOrderInfo($data['RechargeId']);
						$url = DK_DOMAIN.'/wap/index.php?s=/user/recharge/info/log';
						$keywords = array(
								'keyword1'=>'在线充值',
								'keyword2'=>'充值到谷安居用户账号',
								'keyword3'=>$recharge_info['recharge_amount'].'元',
								'keyword4'=>'微信昵称'.$user_info['nick_name'],
								'remark'=>'更多优惠活动，尽在腾讯充值！',
						);
						$params = array(
								'openid'=>$user_info['openid'],
								'template_id'=>'VLQ-QKo0E7dMkIPTG6F78SJ0Di11545vQkFe0n16Byg',
								'url'=>$url,
								'first'=>'您已充值成功',
								'keywords'=>$keywords,
								'remark'=>$str,
						);
						$result = $this->weixinService()->sendTemplateMessage($params);
						if ($result['errcode'] > 0) {
							throw new \Exception($result['errmsg']);
						}
						break;
			
					case Status::MsgTypeCash: //提现
						$cash_apply = $this->cashApplyService()->getInfo($data['ApplyId']);
						$url = DK_DOMAIN.'/wap/index.php?s=/user/recharge/info/log';
						$keywords = array(
								'keyword1'=>$cash_apply['bank_name'],
								'keyword2'=>'尾号'.$cash_apply['card'],
								'keyword3'=>$cash_apply['open_name'],
								'keyword4'=>$cash_apply['money'].'元',
								'keyword5'=>date('Y年m月d日 H:i'),
								'remark'=>'本次提现预计2个工作日内到达您指定银行账户，请注意查询！',
						);
						$params = array(
								'openid'=>$user_info['openid'],
								'template_id'=>'ZWUspvaR4z_rk56LTtd70Gmca2I3SPM-joV6tYG9f6Y',
								'url'=>$url,
								'first'=>'您好，您已申请提现成功。',
								'keywords'=>$keywords,
								'remark'=>$str,
						);
						$result = $this->weixinService()->sendTemplateMessage($params);
						if ($result['errcode'] > 0) {
							throw new \Exception($result['errmsg']);
						}
						break;
			
					case Status::MsgTypeMerchantApplyPass: //商家入驻申请审核通过
						$cash_apply = $this->cashApplyService()->getInfo($data['ApplyId']);
						$url = DK_DOMAIN.'/wap/index.php?s=/user/recharge/info/log';
						$keywords = array(
								'keyword1'=>$cash_apply['bank_name'],
								'keyword2'=>'尾号'.$cash_apply['card'],
								'keyword3'=>$cash_apply['open_name'],
								'keyword4'=>$cash_apply['money'].'元',
								'keyword5'=>date('Y年m月d日 H:i'),
								'remark'=>'本次提现预计2个工作日内到达您指定银行账户，请注意查询！',
						);
						$params = array(
								'openid'=>$user_info['openid'],
								'template_id'=>'ZWUspvaR4z_rk56LTtd70Gmca2I3SPM-joV6tYG9f6Y',
								'url'=>$url,
								'first'=>'您好，您已申请提现成功。',
								'keywords'=>$keywords,
								'remark'=>$str,
						);
						$result = $this->weixinService()->sendTemplateMessage($params);
						if ($result['errcode'] > 0) {
							throw new \Exception($result['errmsg']);
						}
						break;
				}
				
				//模板消息记录
				$data_template = array(
				 		'user_id'=>$user_info['user_id'],
						'content'=>$str,
						'add_time'=>NOW_TIME,
				);
				$result = $this->wxTemplateDao()->addRecord($data_template);
				if ($result === false) {
					throw new CsException('发送失败');
				}
			}
		}
		
		return true;
	}
    
	private function smsLogDao(){
		return D('Common/Sms/SmsLog');
	}
	
	private function userInfoDao(){
		return D('Common/User/UserInfo');
	}
	
	private function smsTemplateDao(){
		return D('Common/Sms/SmsTemplate');
	}
	
	private function wxTemplateDao(){
		return D('Common/Weixin/WxTemplate');
	}
	
	private function messageService(){
		return D('Message', 'Service');
	}
	
	private function distributorMessageService(){
		return D('DistributorMessage', 'Service');
	}
	
	private function orderService(){
		return D('Order', 'Service');
	}
	
	private function cashApplyService(){
		return D('CashApply', 'Service');
	}
	
	private function weixinService(){
		return D('Weixin', 'Service');
	}
}