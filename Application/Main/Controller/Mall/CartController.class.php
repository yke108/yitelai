<?php
namespace Main\Controller\Mall;
use Main\Controller\MainController;
use Common\Basic\Genre;
use Common\Payment\WeixinPay\AppPay;
use Common\Basic\Status;

class CartController extends MainController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
		
		$this->assign('page_title', '购物车');
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
		
		//我的关注
		$params = array(
				'user_id'=>$this->user['user_id'],
				'collect_type'=>Genre::CollectTypeGoods,
		);
		$datas = $this->collectService()->getCollectGoodsList($params);
		$this->assign('collect_list', $datas['list']);
		
		//最近浏览
		$params = array(
				'user_id'=>$this->user['user_id'],
				'collect_type'=>Genre::CollectTypeGoodsFoot,
		);
		$datas = $this->collectService()->getCollectGoodsList($params);
		$this->assign('history_list', $datas['list']);
		
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
		$this->success('更新成功');
	}
	
	//添加购物车
	function addAction(){
		$params = array(
				'user_id'=>$this->user['user_id'],
				'discount'=>$this->user['rank']['discount'],
				'id'=>intval(I('id')),
				'number'=>intval(I('number')),
				//'use_service'=>intval(I('use_service')),
				'service_id'=>intval(I('service_id')),
				'act_type'=>0
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
		
		$this->success('加入购物车成功', '', array('cart_num'=>$cart_num));
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
	
	//批量删除
	public function delallAction(){
		$params = array(
				'user_id'=>$this->user['user_id'],
				'cart_ids'=>I('cart_ids'),
		);
		try {
			$this->cartService()->delAllCart($params);
			$this->success('删除成功');
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	public function collectallAction(){
		$params = array(
				'user_id'=>$this->user['user_id'],
				'cart_ids'=>I('cart_ids'),
				'collect_type'=>Genre::CollectTypeGoods
		);
		try {
			$this->collectService()->addAll($params);
			$this->success('关注成功');
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	//确认
	public function confirmAction(){
		if (IS_POST){
			$params = I('post.');
			$params['user'] = $this->user;
			$params['act_type'] = $params['act_type'] ? $params['act_type'] : 0;
			$params['point_exchange'] = $this->sysconfig['point_exchange'];
			$params['region_code'] = $this->region_code;
			try {
				$result = $this->orderService()->createOrder($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('提交订单成功', U('pay', array('id'=>$result['general_order_id'])));
		}
		
		$get = I('get.');
		$this->assign('get',$get);
		
		//收货地址
		$address_list = $this->userAddressService()->getUserAddressList($this->user['user_id'], 'is_default DESC');
		$this->assign('address_list', $address_list);
		
		//默认地址
		$address = $this->userAddressService()->findDefaultAddress($this->user['user_id']);
		$this->assign('address', $address);
		
		//省市区		$this->assign('region_list', $this->regionService()->getAllRegions(false));
		
		//购物车商品
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
			$this->error($e->getMessage());
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
		
		$act_id=$get['act_id'];
		$post_id=$get['post_id'];
		$num=intval($get['num'])>0?intval($get['num']):1;
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
		
		$this->assign('team_info',$team_info);
		$this->assign('act_id',$act_id);
		$this->assign('num',$team_info['price_info']['number']);
		
		//获取省份
		$province=$this->regionService()->getChildList();
		$this->assign('province',$province);
		
		//收货地址
		$address_list = $this->userAddressService()->getUserAddressList($this->user['user_id']);
		$this->assign('address_list', $address_list);
		
		//默认地址
		$address = $this->userAddressService()->findDefaultAddress($this->user['user_id']);
		$this->assign('address', $address);
		
		$params = array(
				'user_id'=>$this->user['user_id'],
				'district'=>$address['region_code']
		);
		$params['act_type'] = $get['act_type'] ? $get['act_type'] : 0;
		
		$this->assign('list', $result['list']);
		$this->assign('info', $result['info']);
		
		//添加地址返回
		session('back_url', __SELF__);
		$order_info['orders'][0]['order_type']=1;
		$this->assign('step', 1);
		$this->assign('info',$order_info);
		
		
		$this->display();
	}
	
	public function teamAddressDefAction($address_id = 0) {
		try {
			$result = $this->userAddressService()->setDefaultAddress($address_id, $this->user['user_id']);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('设置成功');
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
		/* foreach ($info['orders'] as $order) {
			foreach ($order['order_goods'] as $goods) {
				//查找分销商商品
				$distributor_product = $this->distributorGoodsProductService()->getInfo($goods['product_id']);
				if ($goods['goods_number'] > $distributor_product['stock_num']) {
					$this->error('订单已支付');
				}
			}
		} */
		
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
			if($post['pay_id'] == 3) { //支付宝支付
				//$this->success('正在转向支付宝支付页面', U('alipay', array('id'=>$id)));
			} elseif ($post['pay_id'] == 2) { //微信支付
				$this->success('正在转向微信支付页面', U('weixinpay', array('id'=>$id)));
			}else {
				try {
					$result = $this->orderService()->payOrderNow($params);
					$this->success('支付成功', U('success', array('id'=>$id)));
				} catch (\Exception $e) {
					$this->error($e->getMessage());
				}
			}
		}
		
		$this->assign('step', 2);
		
		$this->display();
	}
	
	
	/**
	 * 支付宝支付
	 * @param array 提交信息的数组
	 * @return mixed false or null
	 */
	public function alipayAction($id = 0) {
		$params = array(
				'general_order_id'=>$id,
				'user_id'=>$this->user['user_id']
		);
		$info = $this->orderService()->getGeneralOrderInfo($params);
		if (empty($info)) {
			$this->error('订单不存在');
		}
		$this->assign('info', $info);
		
		$payment = array(
				'body'=>'购买商品',
				'payment_id'=>'1477383484800',
				'cur_money'=>'100',
		);
		\Common\Payment\Alipay::dopay($payment);
	}
	
	//微信支付
	public function weixinpayAction($id = 0){
		$params = array(
				'general_order_id'=>$id,
				'user_id'=>$this->user['user_id']
		);
		$info = $this->orderService()->getGeneralOrderInfo($params);
		if (empty($info)) {
			$this->error('数据不存在');
		}
		
		header("Content-type:text/html;charset=utf-8");
		//$wxconf = \Common\ApiConfig\Weixin::jsapiConfig();
		$wxconf = $this->configService()->findWeixinConfigs();
		$unifiedOrder = new AppPay($wxconf['js_app_id'], $wxconf['mchid'], $wxconf['key']);
		$unifiedOrder->setParameter("body", "购买商品");//商品描述
		$unifiedOrder->setParameter("out_trade_no", $id);//商户订单号
		$unifiedOrder->setParameter("total_fee", $info['total_order_amount'] * 100);//总金额
		$unifiedOrder->setParameter("notify_url", DK_DOMAIN.'/wap/weixin.php');//通知地址
		$unifiedOrder->setParameter("trade_type","NATIVE");//交易类型
		$unifiedOrder->setParameter("spbill_create_ip", get_client_ip());//交易类型
		$result = $unifiedOrder->getResult();
		$info['code_url'] = DK_DOMAIN.'/qrc/?data='.$result['code_url'];
		
		$this->assign('info', $info);
		
		$this->display();
	}
	
	public function paychkAction($id = 0) {
		$params = array(
				'general_order_id'=>$id,
				'user_id'=>$this->user['user_id']
		);
		$info = $this->orderService()->getGeneralOrderInfo($params);
		if (empty($info)) {
			$this->error('数据不存在');
		}
		
		$pay_status = 1;
		foreach ($info['orders'] as $order) {
			if ($order['pay_status'] == 0) {
				$pay_status = 0;
			}
		}
		if ($pay_status == 1) {
			$this->success('订单支付成功', U('success', array('id'=>$id)));
		}
		$this->error('订单未支付');
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
		
		$step=$info['orders'][0]['order_type'] !=1?2:3;
		
		$this->assign('step', $step);
		
		$this->display();
	}
	
	//提交
	public function buynowAction(){
		$params = array(
			'user_id'=>$this->user['user_id'],
			'id'=>intval(I('id')),
			'number'=>intval(I('number')),
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
		$this->success('添加成功',U('pay',array('act_type'=>1)));
	}
	
	public function addressDefAction($address_id = 0) {
		try {
			$result = $this->userAddressService()->setDefaultAddress($address_id, $this->user['user_id']);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		try {
			//默认地址
			$address = $this->userAddressService()->findDefaultAddress($this->user['user_id']);
			$params = array(
					'user_id'=>$this->user['user_id'],
					'district'=>$address['region_code'],
					'act_type'=>0
			);
			$result = $this->cartService()->accountCart($params);
			$total_amount = $result['info']['GoodsAmount'] + $result['info']['ShippingFee'];
			$this->success('设置成功', '', array('shipping_fee'=>$result['info']['ShippingFee'], 'total_amount'=>$total_amount));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	public function addressInfoAction($address_id = 0) {
		try {
			$info = $this->userAddressService()->getAddressById($address_id, $this->user['user_id']);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		
		$this->assign('info', $info);
		
		//省市区
		$this->assign('region_list', $this->regionService()->getAllRegions(false));
		
		$form = $this->renderPartial('_form');
		
		$this->success('获取成功', '', array('form'=>$form));
	}
	
	//获取收货地址页面跟修改收货地址
	public function save_addressAction(){
		if(IS_GET){
			$get=I('get.');
			$info=$this->userAddressService()->getAddressById($get['address_id'],$this->user['user_id']);
			$this->assign('info',$info);
			$province_code=intval($info['region_code']/10000)*10000;
			$city_code=intval($info['region_code']/100)*100;
			$province_list=$this->regionService()->getChildList();
			$city_list=$this->regionService()->getChildList($province_code);
			$district_list=$this->regionService()->getChildList($city_code);
			
			$this->assign('province',$province_list);
			$this->assign('city',$city_list);
			$this->assign('district',$district_list);
			$this->assign('info',$info);
			$this->assign('province_code',$province_code);
			$this->assign('city_code',$city_code);
			
			
			$html=$this->renderPartial('Mall/Cart/_address');
			$this->ajaxReturn(array('html'=>$html));
			die();
		}
		if(IS_GET){
			
		}
	}
	
	private function goodsService(){
		return D('Goods', 'Service');
	}
	
	private function orderService(){
		return D('Order', 'Service');
	}
	
	private function userAddressService(){
		return D('UserAddress', 'Service');
	}
	
	private function collectService(){
		return D('Collect', 'Service');
	}
	
	private function activityService(){
		return D('Activity', 'Service');
	}
	
	private function regionService(){
		return D('Region', 'Service');
	}
	
	private function distributorGoodsProductService(){
		return D('Distributor\GoodsProduct', 'Service');
	}
	
	
}