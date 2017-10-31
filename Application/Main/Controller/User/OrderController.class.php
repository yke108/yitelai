<?php
namespace Main\Controller\User;
use Main\Controller\MainController;
use Common\Basic\Pager;
use Common\Basic\Status;
use Common\Payment\WeixinPay\AppPay;
use Common\Logic\PointLogic;

class OrderController extends MainController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
		
		$this->assign('page_title', '个人中心');
    }
	
    public function indexAction(){
    	$type = I('type');
    	if ($type == 1) { //待付款
    		$map = array(
    				'user_id'=>session('userid'),
    				'pay_status'=>Status::PayStatusNone,
    				'order_status'=>Status::OrderStatusNone,
    		);
    	}elseif ($type == 2) { //待发货
    		$map = array(
    				'user_id'=>session('userid'),
    				'pay_status'=>Status::PayStatusPaid,
    				'shipping_status'=>Status::ShippingStatusNone,
    		);
    	}elseif ($type == 3) { //待收货
    		$map = array(
    				'user_id'=>session('userid'),
    				'shipping_status'=>Status::ShippingStatusDelivering,
    		);
    	}elseif ($type == 4) { //已完成
    		$map = array(
    				'user_id'=>session('userid'),
    				'order_status'=>Status::OrderStatusSuccess,
    		);
    	}elseif ($type == 5) { //待评价
    		$map = array(
    				'user_id'=>session('userid'),
    				'shipping_status'=>Status::ShippingStatusReceived,
    		);
    	}elseif ($type == 6) { //已取消
    		$map = array(
    				'user_id'=>session('userid'),
    				'order_status'=>Status::OrderStatusCancel,
    		);
    	}else {
    		$map = array(
    				'user_id'=>session('userid'),
    		);
    	}
		$this->listDisplay($map);
    }
	
	//我的拼单列表
	public function teamAction(){
		$user_id=session('userid');
		$p=I('p')?I('p'):I('get.p');
		$p=$p?$p:1;
		$size=12;
		$map=array('team_post_id'=>array('gt',0));
		$params=array('user_id'=>$user_id,'map'=>$map,'order_type'=>'all');
		
		$result=$this->orderService()->getOrderList($params,$p,$size);
		$list=$result['list'];
		foreach($list as $key=>$val){
			$post_team_id[]=$val['team_post_id'];
		}
		$team_list=$this->activityService()->teamPostFieldPagerList(array('post'=>array('in'=>$post_team_id)),'post_id,user_id,post_sn,act_id,member_num,price,add_time,joined_num,closing_time,end_time,member_limit');
		
		foreach($list as $key=>$val){
			$list[$key]['team_info']=$team_list[$val['team_post_id']];
		}
		
		$pager=new Pager($result['count'],$size);
		$this->assign('list',$list);
		$this->assign('page',$pager->show());
		
		$this->display();
	}
    
    private function listDisplay($map = array()){
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$page = intval(I('p')) ? intval(I('p')) : 1;
    	$pagesize = 6;
    	
    	$params['map'] = $map;
    	if (!empty($get['start_time'])) {
    		$params['start_time'] = $get['start_time'];
    	}
    	if (!empty($get['end_time'])) {
    		$params['end_time'] = $get['end_time'];
    	}
    	if (!empty($get['order_id'])) {
    		$params['order_id'] = $get['order_id'];
    	}
    	
		$datas = $this->orderService()->getOrderList($params, $page, $pagesize);
		$this->assign('list', $datas['list']);
		$pager = new Pager($datas['count'], $pagesize);
		$this->assign('pages', $pager->show_pc());
		
		$this->display('index');
    }
    
	public function payAction($id = 0){
		$get = I('get.');
		$this->assign('get', $get);
		
		$params = array(
				'order_id'=>$id,
				'user_id'=>$this->user['user_id']
		);
		/* if ($get['start_time']) {
			$params['start_time'] = $get['start_time'];
		}
		if ($get['end_time']) {
			$params['end_time'] = $get['end_time'];
		}
		if ($get['order_id']) {
			$params['order_id'] = $get['order_id'];
		} */
		$info = $this->orderService()->getOrderInfo($params);
		if(empty($info)){
			$this->error('订单不存在');
		}
		$this->assign('info', $info);
		
		if(IS_POST){
			$post = I('post.');
			
			if ($post['pay_id'] == '') {
				$this->error('请选择支付方式');
			}
			if (!in_array($post['pay_id'], array(0,1,2))) {
				$this->error('支付方式不正确');
			}
			
			$params['pay_id'] = $post['pay_id'];
			if($params['pay_id'] == 3) { //支付宝支付
				//$this->success('正在转向支付宝支付页面', U('alipay', array('id'=>$id)));
			} elseif ($params['pay_id'] == 2) { //微信支付
				$this->success('正在转向微信支付页面', U('weixinpay', array('id'=>$id)));
			}else {
				try {
					$result = $this->orderService()->payOrder($params);
				} catch (\Exception $e) {
					$this->error($e->getMessage());
				}
				$this->success('支付成功', U('success', array('id'=>$id)));
			}
		}
		
		$this->display();
	}
	
	//定制订单支付
	public function payallAction($id = 0){
		$params = array(
				'order_id'=>$id,
				'user_id'=>$this->user['user_id']
		);
		$info = $this->orderService()->getOrderInfo($params);
		if(empty($info)) $this->error('订单不存在');
		
		if(IS_POST){
			$post = I('post.');
				
			if ($post['pay_id'] == '') {
				$this->error('请选择支付方式');
			}
			if (!in_array($post['pay_id'], array(0,1,2))) {
				$this->error('支付方式不正确');
			}
				
			$params['pay_id'] = $post['pay_id'];
			if($params['pay_id'] == 3) { //支付宝支付
				//$this->success('正在转向支付宝支付页面', U('alipay', array('id'=>$id)));
			} elseif ($params['pay_id'] == 2) { //微信支付
				$this->success('正在转向微信支付页面', U('weixinpay', array('id'=>$id)));
			}else { //余额支付
				try {
					$result = $this->orderService()->payCustomOrder($params);
					$this->success('支付成功', U('success', array('id'=>$id)));
				} catch (\Exception $e) {
					$this->error($e->getMessage());
				}
			}
		}
		
		$get = I('get.');
		$this->assign('get', $get);
		
		$this->assign('info', $info);
		
		//定制商品明细
		$detail_list = $this->orderService()->getDetailList($info['order_id']);
		$this->assign('detail_list', $detail_list);
		
		$this->display();
	}
	
	//分批付款
	public function paymentAction($payment_id = 0){
		$params = array(
				'payment_id'=>$payment_id,
				'user_id'=>$this->user['user_id']
		);
		$payment_info = $this->orderService()->findPaymentInfo($params);
		if(empty($payment_info)) $this->error('支付单不存在');
		
		$order_info = $this->orderService()->getOrderInfo(array('order_id'=>$payment_info['order_id']));
		if(empty($order_info)) $this->error('订单不存在');
		
		if(IS_POST){
			$post = I('post.');
			
			if ($post['pay_id'] == '') {
				$this->error('请选择支付方式');
			}
			if (!in_array($post['pay_id'], array(0,1,2))) {
				$this->error('支付方式不正确');
			}
			
			$params['pay_id'] = $post['pay_id'];
			if($params['pay_id'] == 3) { //支付宝支付
				//$this->success('正在转向支付宝支付页面', U('alipay', array('id'=>$id)));
			} elseif ($params['pay_id'] == 2) { //微信支付
				$this->success('正在转向微信支付页面', U('weixinpay', array('id'=>$payment_info['payment_id'])));
			}else { //余额支付
				try {
					$result = $this->orderService()->payPayment($params);
				} catch (\Exception $e) {
					$this->error($e->getMessage());
				}
				$this->success('支付成功', U('successpayment', array('id'=>$payment_info['payment_id'])));
			}
		}
		
		$get = I('get.');
		$this->assign('get', $get);
		
		$this->assign('payment_info', $payment_info);
		$this->assign('order_info', $order_info);
		
		//定制商品明细
		$detail_list = $this->orderService()->getDetailList($order_info['order_id']);
		$this->assign('detail_list', $detail_list);
		
		$this->display();
	}
	
	/**
	 * 支付宝支付
	 * @param array 提交信息的数组
	 * @return mixed false or null
	 */
	public function alipayAction() {
		$payment = array(
				'body'=>'购买商品',
				'payment_id'=>'1477383484800',
				'cur_money'=>'100',
		);
		\Common\Payment\Alipay::dopay($payment);
	}
	
	//微信支付
	public function weixinpayAction($id = 0){
		header("Content-type:text/html;charset=utf-8");
		
		$map = array(
				'order_id'=>$id,
				'user_id'=>$this->user['user_id']
		);
		$order = $this->orderService()->findOrderInfo($map);
		if (empty($order)) {
			$this->error('数据不存在');
		}
		
		$order_id = ($order['order_type'] == Status::OrderTypeCustom) ? 'C'.$order['order_id'] : $order['order_id'];
		
		$wxconf = \Common\ApiConfig\Weixin::jsapiConfig();
		$unifiedOrder = new AppPay($wxconf['appid'], $wxconf['mch_id'], $wxconf['key']);
		$unifiedOrder->setParameter("body", "购买商品");//商品描述
		$unifiedOrder->setParameter("out_trade_no", $order_id);//商户订单号
		$unifiedOrder->setParameter("total_fee", $order['order_amoount'] * 100);//总金额
		$unifiedOrder->setParameter("notify_url", DK_DOMAIN.'/wap/weixinpay.php');//通知地址
		$unifiedOrder->setParameter("trade_type","NATIVE");//交易类型
		$unifiedOrder->setParameter("spbill_create_ip", get_client_ip());//交易类型
		$result = $unifiedOrder->getResult();
		$order['code_url'] = DK_DOMAIN.'/qrc/?data='.$result['code_url'];
		
		$this->assign('order', $order);
		
		$this->display();
	}
	
	public function weixinpaymentAction($id = 0){
		header("Content-type:text/html;charset=utf-8");
	
		$map = array(
				'payment_id'=>$id,
				'user_id'=>$this->user['user_id']
		);
		$payment = $this->orderService()->findPaymentInfo($map);
		if (empty($payment)) {
			$this->error('数据不存在');
		}
		
		$wxconf = \Common\ApiConfig\Weixin::jsapiConfig();
		$unifiedOrder = new AppPay($wxconf['appid'], $wxconf['mch_id'], $wxconf['key']);
		$unifiedOrder->setParameter("body", "购买商品");//商品描述
		$unifiedOrder->setParameter("out_trade_no", $payment['payment_id']);//商户订单号
		$unifiedOrder->setParameter("total_fee", $payment['pay_amoount'] * 100);//总金额
		$unifiedOrder->setParameter("notify_url", DK_DOMAIN.'/wap/payment.php');//通知地址
		$unifiedOrder->setParameter("trade_type","NATIVE");//交易类型
		$unifiedOrder->setParameter("spbill_create_ip", get_client_ip());//交易类型
		$result = $unifiedOrder->getResult();
		$order['code_url'] = DK_DOMAIN.'/qrc/?data='.$result['code_url'];
		
		$this->assign('payment', $payment);
		
		$this->display();
	}
	
	public function paychkAction($id = 0) {
		$params = array(
				'order_id'=>$id,
				'user_id'=>$this->user['user_id']
		);
		$info = $this->orderService()->getOrderInfo($params);
		if (empty($info)) {
			$this->error('数据不存在');
		}
		if ($info['order_type'] == Status::OrderTypeCustom) {
			if ($info['custom_pay_status'] == Status::CustomOrderStatusPaid) {
				$this->success('订单支付成功', U('success', array('id'=>$id)));
			}
		}elseif ($info['pay_status'] == Status::PayStatusPaid) {
			$this->success('订单支付成功', U('success', array('id'=>$id)));
		}
		$this->error('订单未支付');
	}
	
	public function paymentchkAction($id = 0) {
		$params = array(
				'payment_id'=>$id,
				'user_id'=>$this->user['user_id']
		);
		$info = $this->orderService()->findPaymentInfo($params);
		if (empty($info)) {
			$this->error('数据不存在');
		}
		if ($info['pay_status'] == 1) {
			$this->success('支付成功', U('successpayment', array('id'=>$id)));
		}
		$this->error('订单未支付');
	}
	
	public function successAction($id = 0) {
		$params = array(
				'order_id'=>$id,
				'user_id'=>$this->user['user_id']
		);
		$info = $this->orderService()->getOrderInfo($params);
		if (empty($info)) {
			$this->error('订单不存在');
		}
		$this->assign('info', $info);
	
		$this->assign('step', 2);
	
		$this->display();
	}
	
	public function successpaymentAction($id = 0) {
		$params = array(
				'payment_id'=>$id,
				'user_id'=>$this->user['user_id']
		);
		$info = $this->orderService()->findPaymentInfo($params);
		if (empty($info)) {
			$this->error('支付单不存在');
		}
		$this->assign('info', $info);
		
		$this->display();
	}
	
    public function infoAction($id = 0){
    	//订单
    	$params = array(
    		'order_id'=>$id,
			'user_id'=>$this->user['user_id']
    	);
		$info = $this->orderService()->getOrderInfo($params);
		if (empty($info)) $this->error('订单不存在');
		$this->assign('info', $info);
		
		//订单日志
		$log_list = $this->orderService()->getLogList($info['order_id']);
		$new_log_list = array();
		foreach ($log_list as $v) {
			$new_log_list[$v['log_type']] = $v;
		}
		$this->assign('log_list', $new_log_list);
		
		//图纸资料
		$file_list = $this->orderService()->getFileList($info['order_id']);
		$this->assign('file_list', $file_list);
		
		//明细
		$detail_list = $this->orderService()->getDetailList($info['order_id']);
		$this->assign('detail_list', $detail_list);
		
		//分期付款
		$payment_list = $this->orderService()->getPaymentList($info['order_id']);
		$this->assign('payment_list', $payment_list);
		$this->assign('payment_count', count($payment_list));
		
    	$this->display();
    }
    
    public function cancelAction($id = 0){
		try{
			$this->orderService()->cancelByUser($this->user, $id);
		} catch(\Exception $e){
			$this->error($e->getMessage());
		}
    	$this->success('订单取消成功');
    }
	
    //确认收货
	public function receiveAction($id = 0){
		try{
			$this->orderService()->ReceiveByUser($this->user, $id);
		} catch(\Exception $e){
			$this->error($e->getMessage());
		}
    	$this->success('确认收货成功', '', array('refresh'=>1));
	}
	
	//商品评价
	public function commentAction($id = 0){
		$params = array(
				'id'=>$id,
				'user_id'=>$this->user['user_id']
		);
		$info = $this->orderService()->getOrderGoods($params);
		if ($info['back_status'] == 1) {
			$this->error('商品已评价');
		}
		
		if (IS_POST) {
			$post = I('post.');
			if (count($post['image_datas']) > 3) {
				$this->error('最多可上传3张图片');
			}
			$images = createBase64Image($post['image_datas']);
			if ($images) {
				$post['pictures'] = $images;
			}
			try{
				$result = $this->goodsCommentService()->CommentByUser($this->user, $post);
			} catch(\Exception $e){
				$this->error($e->getMessage());
			}
			
			//评论送积分
			$point_config = $this->ConfigService()->findSystemConfigs('point','fkey,fval,ftitle,fdesc,field_type');
			$params = array(
					'user_id'=>$this->user['user_id'],
					'point'=>$point_config['comment']['fval'],
					'type'=>PointLogic::PointTypeComment,
					'ref_id'=>$result['comment_id']
			);
			$result = $this->pointService()->addUserPoint($params);
			if($result === false){
				$this->error('赠送积分失败');
			}
			
			$this->success('评论成功', U('commentlist'));
		}
		
		$this->assign('info', $info['info']);
		$this->assign('order', $info['order']);
		
		$this->display();
	}
	
	public function commentdelAction($id = 0){
		try {
			$result = $this->goodsCommentService()->CommentDelByUser($this->user, $id);
			$this->success('删除成功', U('commentlist'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	public function commentlistAction(){
		$pagesize = 5;
		$params = array(
				'page'=>intval(I('p')) ? intval(I('p')) : 1,
				'pagesize'=>$pagesize,
				'user_id'=>$this->user['user_id']
		);
		$datas = $this->goodsCommentService()->getPagerList($params);
		$this->assign('list', $datas['list']);
		$pager = new Pager($datas['count'], $pagesize);
		$this->assign('pages', $pager->show_pc());
		
		$this->display();
	}
	
	//退货
	public function backlistAction(){
		$get = I('get.');
		$this->assign('get', $get);
		
		if (IS_POST) {
			$post = I('post.');
			$post['user_id'] = $this->user['user_id'];
			$post['status'] = 3;
			try {
				$this->afterSalesService()->modifyAfterSale($post);
				$this->success('提交成功', U('backlist'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		
		$pagesize = 6;
		$params = array(
				'user_id'=>$this->user['user_id'],
				'page'=>intval(I('p')) ? intval(I('p')) : 1,
				'pagesize'=>$pagesize,
				'type'=>Status::AfterSaleBack
		);
		$datas = $this->afterSalesService()->getPagerList($params);
		$this->assign('list', $datas['list']);
		$pager = new Pager($datas['count'], $pagesize);
		$this->assign('pages', $pager->show_pc());
		
		$shipping_list = M('shipping_code')->select();
		$this->assign('shipping_list', $shipping_list);
		
		$this->display();
	}
	
	public function repairlistAction(){
		$get = I('get.');
		$this->assign('get', $get);
	
		if (IS_POST) {
			$post = I('post.');
			$post['user_id'] = $this->user['user_id'];
			$post['status'] = 3;
			try {
				$this->afterSalesService()->modifyAfterSale($post);
				$this->success('提交成功', U('backlist'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
	
		$pagesize = 6;
		$params = array(
				'user_id'=>$this->user['user_id'],
				'page'=>intval(I('p')) ? intval(I('p')) : 1,
				'pagesize'=>$pagesize,
				'type'=>Status::AfterSaleRepair
		);
		$datas = $this->afterSalesService()->getPagerList($params);
		$this->assign('list', $datas['list']);
		$pager = new Pager($datas['count'], $pagesize);
		$this->assign('pages', $pager->show_pc());
	
		$shipping_list = M('shipping_code')->select();
		$this->assign('shipping_list', $shipping_list);
	
		$this->display();
	}
	
	//申请退货
	public function backAction($id = 0){
		$params = array(
				'id'=>$id,
				'user_id'=>$this->user['user_id']
		);
		try {
			$info = $this->orderService()->getOrderGoods($params);
			if ($info['info']['back_status'] == 1) {
				$this->error('商品不能重复退货');
			}
			$this->assign('info', $info['info']);
			$this->assign('order', $info['order']);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		
		if (IS_POST) {
			$post = I('post.');
			
			if (count($post['image_datas']) > 5) {
				$this->error('最多可上传5张图片');
			}
			
			$params = array(
					'item_id'=>$post['id'],
					'type'=>Status::AfterSaleBack,
					'amount'=>$post['number'],
					'reason'=>$post['content'],
					'invoice'=>$post['invoice'] ? 1: 0,
					'user_id'=>$this->user['user_id'],
					'order_id'=>$info['info']['order_id'],
					'distributor_id'=>$info['order']['distributor_id']
			);
			
			$images = createBase64Image($post['image_datas']);
			if($images){
				$params['pictures'] = implode('#', $images);
			}
			
			try {
				$this->afterSalesService()->createAfterSale($params);
				$this->success('申请成功', U('backlist'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		
		$this->display();
	}
	
	public function backviewAction($id = 0){
		$params = array(
				'id'=>$id,
				'user_id'=>$this->user['user_id']
		);
		try {
			$info = $this->orderService()->getOrderGoods($params);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->assign('info', $info['info']);
		$this->assign('order', $info['order']);
		
		$this->display();
	}
	
	//申请维修
	public function repairAction($id = 0){
		$params = array(
				'id'=>$id,
				'user_id'=>$this->user['user_id']
		);
		try {
			$info = $this->orderService()->getOrderGoods($params);
			if ($info['info']['repair_status'] == 1) {
				$this->error('商品不能重复申请维修');
			}
			$this->assign('info', $info['info']);
			$this->assign('order', $info['order']);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		
		if (IS_POST) {
			$post = I('post.');
			
			if (count($post['image_datas']) > 5) {
				$this->error('最多可上传5张图片');
			}
			
			$params = array(
					'item_id'=>$post['id'],
					'type'=>Status::AfterSaleRepair,
					'amount'=>$post['number'],
					'reason'=>$post['content'],
					'invoice'=>$post['invoice'] ? 1: 0,
					'user_id'=>$this->user['user_id'],
					'order_id'=>$info['info']['order_id'],
					'distributor_id'=>$info['order']['distributor_id'],
					'confirm_time'=>$info['order']['confirm_time'],
					'repair_time'=>$info['info']['repair_time']
			);
			$images = createBase64Image($post['image_datas']);
			if($images){
				$params['pictures'] = implode('#', $images);
			}
			
			try {
				$this->afterSalesService()->createRepair($params);
				$this->success('申请成功', U('repairlist'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		
		$this->display();
	}
	
	public function feedbackAction($store_id = 0){
		$distributor = $this->distributorService()->getInfo($store_id);
		if (empty($distributor)) {
			$this->error('店铺不存在');
		}
		
		if (IS_POST) {
			$post = I('post.');
			//处理上传图片
			if (count($post['image_datas']) > 3) {
				$this->error('最多可上传3张图片');
			}
			$images = createBase64Image($post['image_datas']);
			if ($images) {
				$post['pictures'] = implode(',', $images);
			}
			$post['user_id'] = session('userid');
			$post['type'] = Status::FeedbackTypeComplain;
			$post['client'] = Status::FeedbackClientPc;
			try {
				$result = $this->feedbackService()->createOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('提交成功', U('index'));
		}
		
		$this->assign('distributor', $distributor);
		
		$this->assign('wx_title', '投诉建议');
		$this->display();
	}
	
	private function orderEvent(){
		return D('Order', 'Event');
	}
	
	private function orderService(){
		return D('Order', 'Service');
	}
	
	private function afterSalesService(){
		return D('AfterSales', 'Service');
	}
	
	private function activityService(){
		return D('Activity', 'Service');
	}
	
	private function goodsCommentService(){
		return D('GoodsComment', 'Service');
	}
	
	private function pointService(){
		return D('Point', 'Service');
	}
	
	private function distributorService(){
		return D('Distributor', 'Service');
	}
	
	private function feedbackService() {
		return D('Feedback', 'Service');
	}
}