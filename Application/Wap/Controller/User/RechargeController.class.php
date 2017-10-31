<?php
namespace Wap\Controller\User;
use Wap\Controller\WapController;
use Common\Payment\WeixinPay\AppPay;
use Common\WeixinPay\Wxjspay;
use Common\Basic\Pager;

class RechargeController extends WapController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
	
		$this->assign('page_title', '个人中心');
	}
	
	public function indexAction(){
		//$list=$this->RechargeService()->revalueMoney();
		//var_dump($list);
		//die();
		if(IS_AJAX){
			$post=array(
					'user_id'=>session('userid'),
					'activity_id'=>I('activity_id'),
					'recharge_amount'=>I('amount'),
			);
				
			try{
				$recharge_sn=$this->rechargeService()->addRecharge($post);
			}catch(\Exception $e){
				$this->ajaxReturn(array('error'=>1,'msg'=>$e->getMessage()));
			}
			$this->ajaxReturn(array('error'=>0,'recharge_sn'=>$recharge_sn));
		}
		//获取充值活动
		$params=array('is_going'=>1);
		$result=$this->rechargeService()->activityPagerList($params);
		$this->assign('list',$result['list']);
		$this->display();
	}
	
	public function logAction(){
		$p=I('p')?I('p'):I('get.p');
		$p=$p?$p:1;
		$size=12;
		$type=I('type')?I('type'):I('get.type');
		$type=$type?$type:1;//默认1是充值记录，2是增值记录
		$map=array('pay_status'=>1,'user_id'=>session('userid'));
		
		$show_page='';
		if($type==2){
			$map['activity_id']=array('gt',0);
			$show_page='revalue_log';
		}else{
			$map['activity_id']=array('eq',0);
		}
		$params=array('page'=>$p,'pagesize'=>$size,'map'=>$map);
		$result=$this->rechargeService()->infoPagerList($params);
		$pager=new Pager($result['count'],$size);
	
		$this->assign('list',$result['list']);
		$this->assign('page',$pager->show());
		$this->assign('type',$type);
		$this->display($show_page);
	}
	
	public function payAction(){
	
		$recharge_sn = I('recharge_sn')?I('recharge_sn'):I('get.recharge_sn');
	
		$map=array('recharge_sn'=>$recharge_sn,'user_id'=>session('userid'));
		$recharge_info=$this->rechargeService()->findInfo($map);
		if(empty($recharge_info)){$this->error('充值失败');}
		$this->assign('recharge_info',$recharge_info);
	
		if(IS_POST){
				
			$post = I('post.');
			if($post['pay_id'] == 1) { //支付宝支付
				$this->redirect('alipay',array('recharge_sn'=>$recharge_sn));
			} elseif ($post['pay_id'] == 2) { //微信支付
				$this->redirect('weixinpay',array('recharge_sn'=>$recharge_sn));
			}else {
				try {
					$this->rechargeService()->pay($post);
				} catch (\Exception $e) {
					$this->error($e->getMessage());
				}
			}
		}
	
		$this->display();
	}
	
	/**
	 * 支付宝支付
	 * @param array 提交信息的数组
	 * @return mixed false or null
	 */
	public function alipayAction() {
		$recharge_sn = I('get.recharge_sn');
		$map=array('recharge_sn'=>$recharge_sn,'user_id'=>session('userid'));
		$recharge_info=$this->rechargeService()->findInfo($map);
		if(empty($recharge_info)){$this->error('支付宝支付失败');}
	
		$payment = array(
				'body'=>'购买商品',
				'payment_id'=>'1477383484800',
				'cur_money'=>'100',
		);
		\Common\Payment\Alipay::dopay($payment);
	}
	
	//微信支付
	public function weixinpayAction(){
	
		$recharge_sn = I('get.recharge_sn');
		$map=array('recharge_sn'=>$recharge_sn,'user_id'=>session('userid'));
		$recharge_info=$this->rechargeService()->findInfo($map);
		if(empty($recharge_info)){$this->error('微信支付失败');}
	
		header("Content-type:text/html;charset=utf-8");
		$weixin_pay_config=$this->configService()->findSystemConfigs('weixin');
		
		$jsApi=new Wxjspay($weixin_pay_config['js_app_id'], $weixin_pay_config['mchid'], $weixin_pay_config['key'],$weixin_pay_config['js_app_secret']);
		$unifiedOrder = new AppPay($weixin_pay_config['js_app_id'], $weixin_pay_config['mchid'], $weixin_pay_config['key']);
	
		//通过code获得openid
		if (!isset($_GET['code']))
		{
			//触发微信返回code码
			$url = $jsApi->createOauthUrlForCode(DK_DOMAIN.__SELF__);
			Header("Location: $url");
		}else
		{
			//获取code码，以获取openid
			$code = $_GET['code'];
			$jsApi->setCode($code);
			$openid = $jsApi->getOpenId();
		}
		
		$unifiedOrder->setParameter("openid","$openid");//openid
		$unifiedOrder->setParameter("body", "充值");//商品描述
		$unifiedOrder->setParameter("out_trade_no", $recharge_sn);//商户订单号
		$unifiedOrder->setParameter("total_fee", $recharge_info['recharge_amount']*100);//总金额
		$unifiedOrder->setParameter("notify_url", DK_DOMAIN.'/weixin/rechargepay.php');//通知地址
		$unifiedOrder->setParameter("trade_type","JSAPI");//交易类型
		$unifiedOrder->setParameter("spbill_create_ip", get_client_ip());//交易类型
		$result = $unifiedOrder->getResult();
		
		$prepay_id=$result['prepay_id'];
		$jsApi->setPrepayId($prepay_id);
		$jsApiParameters = $jsApi->getParameters();
		$this->assign("jsApiParameters",$jsApiParameters);
		
		//var_dump($jsApiParameters);die();
		
		$order['order_amount']=$recharge_info['recharge_amount'];
		$order['code_url'] = DK_DOMAIN.'/qrc/?data='.$result['code_url'];
		$order['order_id']=$recharge_info['recharge_id'];
		$order['order_sn']=$recharge_sn;
		$this->assign('order',$order);
		$this->display();
	}
	
	public function paychkAction(){
		$recharge_id=I('orderid')?I('orderid'):I('get.orderid');
		$recharge_info=$this->rechargeService()->getInfo($recharge_id);
	
		if($recharge_info['pay_status']==1){
			$_SESSION['recharge_id']=$recharge_id;
			$this->ajaxReturn(array('status'=>1,'url'=>U('success')));
		}
		$this->ajaxReturn(array('status'=>0));
	}
	
	public function successAction(){
		$recharge_id=$_SESSION['recharge_id']?$_SESSION['recharge_id']:I('get.recharge_sn');
		unset($_SESSION['recharge_id']);
		if($recharge_id==''){die();}
		$map['_string']="recharge_id='{$recharge_id}' or recharge_sn='{$recharge_id}'"; 
		$recharge_info=$this->rechargeService()->getInfoFind($map);
		$this->assign('recharge',$recharge_info);
		$this->display();
	}
	
	public function failAction(){
		$recharge_sn=I('recharge_sn')?I('recharge_sn'):I('get.recharge_sn');
		$this->assign('recharge_sn',$recharge_sn);
		$this->assign('message','');
		$this->display();
	}
	
	private function rechargeService(){
		return D('Recharge', 'Service');
	}
	
	private function yeepayEvent(){
		return D('Yeepay', 'Event');
	}
	
	private function paymentService(){
		return D('Payment', 'Service');
	}
}
