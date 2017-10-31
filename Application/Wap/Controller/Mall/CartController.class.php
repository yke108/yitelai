<?php
namespace Wap\Controller\Mall;
use Wap\Controller\WapController;
use Common\Basic\Genre;
use Common\Payment\WeixinPay\AppPay;
use Common\Basic\Status;

class CartController extends WapController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
    }
	
	//购物车
	function indexAction(){
		if(IS_POST){
			$post = I('post.');
			$params = array(
					'user_id'=>$this->user['user_id'],
					'cart_id_list'=>$post['gsel'],
					'number'=>$post['number'],
			);
			try {
				$this->cartService()->checkCart($params);
				$this->success('提交成功', U('confirm'));
			} catch (\Exception $e) {
				$this->assign('message',$e->getMessage());
				$this->error($e->getMessage());
			}
		}
		
		//购物车商品
		$params = array(
				'user_id'=>$this->user['user_id'],
				'user'=>$this->user
		);
		try {
			$list = $this->cartService()->listCart($params);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->assign('list', $list);
		//print_r($list);die();
		//购物车统计
		$params = array(
				'user_id'=>$this->user['user_id'],
		);
		try {
			$result = $this->cartService()->accountCart($params);
		} catch (\Exception $e) {
			//$this->error($e->getMessage());
		}
		$this->assign('result', $result);
		
		//获取购物车总价
		$amount_map=array('user_id'=>$this->user['user_id']);
		$cart_amount=$this->cartService()->getCartPriceAmount($amount_map);
		//var_dump($cart_amount);die();
		
		$this->display();
	}
	
	//更新购物车
	function updateAction(){
		$params = array(
			'user_id'=>$this->user['user_id'],
			'cart_id'=>intval($_POST['cart_id']),
			'number'=>intval($_POST['number']),
		);
		try {
			$this->cartService()->updateCart($params);
		} catch (\Exception $e) {
			if ($e->getMessage() == '缺少用户参数') {
				$jumpUrl = U('index/site/login');
			}
			$this->error($e->getMessage());
		}
		$this->success('操作成功');
	}
	
	//添加购物车
	/* function addAction($act_type = 0){
		$post = I('post.');
		$get = I('get.');
		
		$id = intval($post['id']) ? intval($post['id']) : intval($get['id']);
		$number = intval($post['number']) ? intval($post['number']) : intval($get['number']);
		$act_type = intval($post['act_type']) ? intval($post['act_type']) : intval($get['act_type']);
		
		$params = array(
			'user_id'=>$this->user['user_id'],
			'id'=>$id,
			'number'=>$number,
			'act_type'=>$act_type
		);
		try {
			$this->cartService()->addCart($params);
		} catch (\Exception $e) {
			if ($e->getMessage() == '缺少用户参数') {
				$jumpUrl = U('index/site/login');
			}
			$this->error($e->getMessage(), $jumpUrl);
		}
		
		//购物车数量
		$params = array(
				'user_id'=>session('userid')
		);
		$cart_num = $this->cartService()->getCartNumber($params);
		
		$url = $act_type == 1 ? U('mall/cart/index') : '';
		$this->success('添加成功', $url, array('cart_num'=>$cart_num));
	} */
	function addAction(){
		$params = array(
				'user_id'=>$this->user['user_id'],
				'id'=>intval(I('id')),
				'number'=>intval(I('number')),
				//'use_service'=>intval(I('use_service')),
				'service_id'=>intval(I('service_id')),
				'act_type'=>I('act_type')
		);
		try {
			$this->cartService()->addCart($params);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	
		//购物车数量
		$params = array(
				'user_id'=>session('userid')
		);
		$cart_num = $this->cartService()->getCartNumber($params);
		
		$post = I('post.');
		$get = I('get.');
		$act_type = intval($post['act_type']) ? intval($post['act_type']) : intval($get['act_type']);
		$url = $act_type == 1 ? U('mall/cart/index') : '';
	
		$this->success('添加成功', $url, array('cart_num'=>$cart_num));
	}
	
	//删除商品
	public function delAction(){
		$params = array(
				'user_id'=>$this->user['user_id'],
				'cart_id'=>intval($_GET['id']),
		);
		try {
			$this->cartService()->delCart($params);
			$this->success('删除成功');
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	//确认
	public function confirmAction(){
		$get = I('get.');
		$this->assign('get',$get);
		
		if (IS_POST){
			$params = I('post.');
			$params['user'] = $this->user;
			$params['act_type'] = $params['act_type'] ? $params['act_type'] : 0;
			$params['point_exchange'] = $this->sysconfig['point_exchange'];
			
			//高德API
			$rest = curl_get('http://restapi.amap.com/v3/ip?key=81131309bbab0c3362b60e87ca82c6f0');
			if ($rest->status == 1) {
				$params['region_code'] = $rest->adcode;
			}
			
			try {
				$result = $this->orderService()->createOrder($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			
			//支付
			if($params['pay_id'] == 1) { //余额支付
				$params['general_order_id'] = $result['general_order_id'];
				$params['user_id'] = $this->user['user_id'];
				try {
					$result = $this->orderService()->payOrderNow($params);
					$this->success('支付成功', U('success', array('id'=>$params['general_order_id'])));
				} catch (\Exception $e) {
					$this->error($e->getMessage());
				}
			} elseif ($params['pay_id'] == 2) { //微信支付
				$wxconf = $this->configService()->findWeixinConfigs();
				$unifiedOrder = new \Common\Payment\WeixinPay\AppPay($wxconf['js_app_id'], $wxconf['mchid'], $wxconf['key']);
				//$unifiedOrder->setParameter("openid",$this->user['openid']);//openid
				$unifiedOrder->setParameter("openid",cookie('openid_ck8'));
				$unifiedOrder->setParameter("body","支付商城商品");//商品描述
				$unifiedOrder->setParameter("out_trade_no",$result['general_order_id']);//订单号
				$unifiedOrder->setParameter("total_fee",($result['total_order_amount']*100));//总金额
				$unifiedOrder->setParameter("notify_url",DK_DOMAIN.'/wap/weixinpaynow.php');//通知地址
				$unifiedOrder->setParameter("trade_type","JSAPI");//交易类型
				//var_dump($unifiedOrder);
				$jsApiParameters = $unifiedOrder->createTradeDataForJS();
				//var_dump($jsApiParameters);exit;
				$this->assign("general_order_id",$result['general_order_id']);
				$this->assign("jsApiParameters",$jsApiParameters);
				$this->display('weixinpay');
			}else {
				$this->error('支付方式不正确');
			}
		}
	
		//收货地址
		$address_list = $this->userAddressService()->getUserAddressList($this->user['user_id'], 'is_default DESC');
		$this->assign('address_list', $address_list);
	
		//默认地址
		$address = $this->userAddressService()->findDefaultAddress($this->user['user_id']);
		$this->assign('address', $address);
	
		//省市区
		$this->assign('region_list', $this->regionService()->getAllRegions(false));
	
		$params = array(
				'user_id'=>$this->user['user_id'],
				'user'=>$this->user,
				'discount'=>$this->user['rank']['discount'],
				'district'=>$address['region_code']
		);
		$params['act_type'] = $get['act_type'] ? $get['act_type'] : 0;
		try {
			$result = $this->cartService()->accountCart($params);
		} catch (\Exception $e) {
			//$this->error($e->getMessage());
		}
		$this->assign('list', $result['list']);
		$this->assign('info', $result['info']);
	
		//添加地址返回
		session('back_url', __SELF__);
	
		$this->assign('step', 1);
	
		$this->display();
	}
	
	//团购订单确认页面
	public function team_confirmAction(){
		$get = I('get.');
		$this->assign('get',$get);
		if (IS_POST){
			$order_sn = I('order_sn');
			$params=array('general_order_id'=>$order_sn,'user_id'=>session('userid'));
			$result=$this->orderService()->getGeneralOrderInfo($params);
			if(empty($result)){
				$this->error('支付失败');
			}
			
			//支付
			if($params['pay_id'] == 1) { //余额支付
				$params['general_order_id'] = $result['general_order_id'];
				$params['user_id'] = $this->user['user_id'];
				try {
					$result = $this->orderService()->payOrderNow($params);
					$this->success('支付成功', U('success', array('id'=>$params['general_order_id'])));
				} catch (\Exception $e) {
					$this->error($e->getMessage());
				}
			} elseif ($params['pay_id'] == 2) { //微信支付
				$wxconf = $this->configService()->findWeixinConfigs();
				$unifiedOrder = new \Common\Payment\WeixinPay\AppPay($wxconf['js_app_id'], $wxconf['mchid'], $wxconf['key']);
				$unifiedOrder->setParameter("openid",$this->user['openid']);//商品描述
				$unifiedOrder->setParameter("body","支付商城商品");//商品描述
				$unifiedOrder->setParameter("out_trade_no",$result['general_order_id']);//订单号
				$unifiedOrder->setParameter("total_fee",($result['total_order_amount']*100));//总金额
				$unifiedOrder->setParameter("notify_url",DK_DOMAIN.'/wap/weixin.php');//通知地址
				$unifiedOrder->setParameter("trade_type","JSAPI");//交易类型
				//var_dump($unifiedOrder);
				$jsApiParameters = $unifiedOrder->createTradeDataForJS();
				//var_dump($jsApiParameters);exit;
				
				$this->assign("jsApiParameters",$jsApiParameters);
				$this->display('weixinpay');
			}else {
				$this->error('支付方式不正确');
			}
		}
		
		//默认地址
		$address = $this->userAddressService()->findDefaultAddress($this->user['user_id']);
		$this->assign('address', $address);
		
		$act_id=$get['act_id'];
		$post_id=$get['post_id'];
		$num=intval($get['num'])>0?intval($get['num']):1;
		$order_sn=I('order_sn')?I('order_sn'):I('get.order_sn');
		$orer_info=$this->orderService()->getGeneralOrderInfo();
		if(empty($order_info)){
			if($act_id!=''){
				$team_info=$this->activityService()->getTeam($act_id,$num);
				$this->assign('team_info',$team_info);
			}
			if($post_id!=''){
				$team_post_info=$this->activityService()->getTeamPost($post_id);
				$this->assign('team_post_info',$team_post_info);
			}
			if(empty($team_info) && empty($team_post_info)){
				$this->redirect('mall/category/groupbuy');
			}
		}

		$this->assign('act_id',$act_id);
		$this->assign('num',$team_info['price_info']['number']);
	
		$this->display();
	}
	
	public function team_payAction(){
		$order_sn = I('order_sn')?I('order_sn'):I('get.order_sn');
		$pay_id=I('pay_id')?I('pay_id'):I('get.pay_id');
		$params=array('general_order_id'=>$order_sn,'user_id'=>session('userid'),'pay_id'=>$pay_id);
		$result=$this->orderService()->getGeneralOrderInfo($params);
		if(empty($result)){
			$this->error('支付失败');
		}
		
		//支付
		if($params['pay_id'] == 1) { //余额支付
			$params['general_order_id'] = $result['general_order_id'];
			$params['user_id'] = $this->user['user_id'];
			try {
				$result = $this->orderService()->payOrderNow($params);
				$this->redirect('team_success', array('id'=>$params['general_order_id']));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		} elseif ($params['pay_id'] == 2) { //微信支付
			$wxconf = $this->configService()->findWeixinConfigs();
			$unifiedOrder = new \Common\Payment\WeixinPay\AppPay($wxconf['js_app_id'], $wxconf['mchid'], $wxconf['key']);
			$unifiedOrder->setParameter("openid",$this->user['openid']);//商品描述
			$unifiedOrder->setParameter("body","支付商城商品");//商品描述
			$unifiedOrder->setParameter("out_trade_no",$result['general_order_id']);//订单号
			$unifiedOrder->setParameter("total_fee",($result['total_order_amount']*100));//总金额
			$unifiedOrder->setParameter("notify_url",DK_DOMAIN.'/wap/weixin.php');//通知地址
			$unifiedOrder->setParameter("trade_type","JSAPI");//交易类型
			//var_dump($unifiedOrder);
			$jsApiParameters = $unifiedOrder->createTradeDataForJS();
			//var_dump($jsApiParameters);exit;
			
			$this->assign("jsApiParameters",$jsApiParameters);
			$this->display('weixinpay');
		}else {
			$this->error('支付方式不正确');
		}
	}
	
	//支付
	public function payAction($id = 0){
		$get = I('get.');
		$this->assign('get', $get);
	
		$params = array(
				'general_order_id'=>$id,
				'user_id'=>$this->user['user_id']
		);
		$info = $this->orderService()->getGeneralOrderInfo($params);
		if (empty($info)) {
			$this->error('订单不存在');
		}
		if ($info['orders'][0]['pay_status'] == Status::PayStatusPaid) {
			$this->error('订单已支付');
		}
	
		//判断库存
		foreach ($info['orders'] as $order) {
			foreach ($order['order_goods'] as $goods) {
				//查找分销商商品
				$distributor_product = $this->distributorGoodsProductService()->getInfo($goods['product_id']);
				if ($goods['goods_number'] > $distributor_product['stock_num']) {
					$this->error('订单已支付');
				}
			}
		}
	
		$this->assign('info', $info);
	
		$teamp_post_info=$this->activityService()->getTeamPost($info['orders'][0]['team_post_id']);
		$this->assign('teamp_post_info',$teamp_post_info);
	
	
		if (IS_POST){
			$post = I('post.');
			
			if ($post['pay_id'] == '') {
				$this->error('请选择支付方式');
			}
			if (!in_array($post['pay_id'], array(0,1,2))) {
				$this->error('支付方式不正确');
			}
				
			$params['pay_id'] = $post['pay_id'];
			try {
				$result = $this->orderService()->payOrderNow($params);
				$this->success('支付成功', U('success', array('id'=>$id)));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			
			if($post['pay_id'] == 1) { //余额支付
				
			} elseif ($post['pay_id'] == 2) { //微信支付
				$this->weixinpay($id);
			}else {
				
			}
		}
	
		$this->assign('step', 2);
	
		$this->display();
	}
	
	private function weixinpay($general_order_id) {
		$params = array(
				'general_order_id'=>$general_order_id,
				'user_id'=>$this->user['user_id']
		);
		$general_order_info = $this->orderService()->getGeneralOrderInfo($params);
		//_p($general_order_info);
		
		$this->assign("general_order_id",$general_order_id);
		
		$user = $this->userInfoDao()->getRecord($params['user_id']);
		$wxconf = $this->configService()->findWeixinConfigs();
		$unifiedOrder = new \Common\Payment\WeixinPay\AppPay($wxconf['js_app_id'], $wxconf['mchid'], $wxconf['key']);
		//$unifiedOrder->setParameter("openid",$user['openid']);//openid
		$unifiedOrder->setParameter("openid",cookie('openid_ck8'));
		$unifiedOrder->setParameter("body","支付商城商品");//商品描述
		$unifiedOrder->setParameter("out_trade_no",$params['order_id']);//订单号
		$unifiedOrder->setParameter("total_fee",($params['order_amount']*100));//总金额
		$unifiedOrder->setParameter("notify_url",DK_DOMAIN.'/wap/weixin.php');//通知地址
		$unifiedOrder->setParameter("trade_type","JSAPI");//交易类型
		//var_dump($unifiedOrder);
		$jsApiParameters = $unifiedOrder->createTradeDataForJS();
		
		//$jsApiParameters = $result['js_api_params'];
		$this->assign("jsApiParameters",$jsApiParameters);
		$this->display('weixinpay');
	}
	
	//提交
	public function buynowAction(){
		$params = array(
			'user_id'=>$this->user['user_id'],
			'id'=>intval($_GET['id']),
			'number'=>intval($_GET['number']),
			'act_type'=>1
		);
		try {
			$this->cartService()->addCart($params);
		} catch (\Exception $e) {
			if ($e->getMessage() == '缺少用户参数') {
				$jumpUrl = U('index/site/login');
			}
			$this->error($e->getMessage(), $jumpUrl);
		}
		
		$params = array(
			'user_id'=>$this->user['user_id'],
			'goods_id_list'=>$_GET['goods_id'],
		);
		/* try {
			$this->cartService()->payCart($params);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		} */
		$this->success('操作成功',U('pay',array('act_type'=>1)));
	}
	
	public function successAction($id = 0) {
		$params = array(
				'general_order_id'=>$id,
				'user_id'=>$this->user['user_id']
		);
		$info = $this->orderService()->getGeneralOrderInfo($params);
		if (empty($info)) {
			$this->error('订单不存在');
		}
		$this->assign('info', $info);
		
		$this->display();
	}
	
	public function team_successAction($id = 0) {
		$params = array(
				'general_order_id'=>$id,
				'user_id'=>$this->user['user_id']
		);
		$info = $this->orderService()->getGeneralOrderInfo($params);
		if (empty($info)) {
			$this->error('订单不存在');
		}
		$this->assign('info', $info);
		
		$this->display();
	}
	
	private function goodsService(){
		return D('Goods', 'Service');
	}
	
	private function cartService(){
		return D('Cart', 'Service');
	}
	
	private function orderService(){
		return D('Order', 'Service');
	}
	
	private function userAddressService(){
		return D('UserAddress', 'Service');
	}
	
	private function activityService(){
		return D('Activity', 'Service');
	}
	
	private function regionService(){
		return D('Region', 'Service');
	}
}