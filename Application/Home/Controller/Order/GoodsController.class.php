<?php
namespace Home\Controller\Order;
use Home\Controller\BaseController;
use Common\Basic\Status;

class GoodsController extends BaseController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
    }
	
    
    
	public function payAction($id = 0){
		$params = array(
				'order_id'=>$id,
				'user_id'=>$this->user['user_id']
		);
		$info = $this->orderService()->getOrderInfo($params);
		if(empty($info)){
			$this->error('订单不存在');
		}
		
		if (IS_POST) {
			$post = I('post.');
			if ($post['pay_id'] == '') {
				$this->error('请选择支付方式');
			}
			if (!in_array($post['pay_id'], array(0,1,2))) {
				$this->error('支付方式不正确');
			}
				
			$params['pay_id'] = $post['pay_id'];
			if($params['pay_id'] == 1) { //余额支付
				try {
					$result = $this->orderService()->payOrder($params);
					$this->success('支付成功', U('success', array('id'=>$id)));
				} catch (\Exception $e) {
					$this->error($e->getMessage());
				}
			} elseif ($params['pay_id'] == 2) { //微信支付
				$wxconf = $this->configService()->findWeixinConfigs();
				$unifiedOrder = new \Common\Payment\WeixinPay\AppPay($wxconf['js_app_id'], $wxconf['mchid'], $wxconf['key']);
				//$unifiedOrder->setParameter("openid",$this->user['openid']);//openid
				$unifiedOrder->setParameter("openid",cookie('openid_ck8'));
				$unifiedOrder->setParameter("body","支付商城商品");//商品描述
				$unifiedOrder->setParameter("out_trade_no",$info['order_id']);//订单号
				$unifiedOrder->setParameter("total_fee",($info['order_amount']*100));//总金额
				$unifiedOrder->setParameter("notify_url",DK_DOMAIN.'/wap/weixinpay.php');//通知地址
				$unifiedOrder->setParameter("trade_type","JSAPI");//交易类型
				//var_dump($unifiedOrder);
				$jsApiParameters = $unifiedOrder->createTradeDataForJS();
				//var_dump($jsApiParameters);exit;
				$this->assign("order_id",$info['order_id']);
				$this->assign("jsApiParameters",$jsApiParameters);
				$this->display('weixinpay');
			}else {
				$this->error('支付方式不正确');
			}
		}
		
		$this->assign('info', $info);
		
		$this->display();
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
	
		$wxconf = \Common\ApiConfig\Weixin::jsapiConfig();
		$unifiedOrder = new AppPay($wxconf['appid'], $wxconf['mch_id'], $wxconf['key']);
		$unifiedOrder->setParameter("body", "购买商品");//商品描述
		$unifiedOrder->setParameter("out_trade_no", $order['order_id']);//商户订单号
		$unifiedOrder->setParameter("total_fee", $order['order_amoount'] * 100);//总金额
		$unifiedOrder->setParameter("notify_url", DK_DOMAIN.'/wap/weixin.php');//通知地址
		$unifiedOrder->setParameter("trade_type","NATIVE");//交易类型
		$unifiedOrder->setParameter("spbill_create_ip", get_client_ip());//交易类型
		$result = $unifiedOrder->getResult();
		$order['code_url'] = DK_DOMAIN.'/qrc/?data='.$result['code_url'];
	
		$this->assign('order', $order);
	
		$this->display();
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
	
    
    
    public function cancelAction($id = 0){
		try{
			$this->orderService()->cancelByUser($this->user, $id);
		} catch(\Exception $e){
			$this->error($e->getMessage());
		}
    	$this->success('订单取消成功','', array('refresh'=>1));
    }
	
    //确认收货
	public function receiveAction($id = 0){
		try{
			$this->orderService()->ReceiveByUser($this->user, $id);
		} catch(\Exception $e){
			$this->error($e->getMessage());
		}
    	$this->success('操作成功', '', array('refresh'=>1));
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
	
		$this->display();
	}
	
	//订单评论
	public function commentAction($id){
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
				$this->goodsCommentService()->CommentByUser($this->user, $post);
				$this->success('评论成功', U('commentlist'));
			} catch(\Exception $e){
				$this->error($e->getMessage());
			}
		}
		
		$this->assign('info', $info['info']);
		$this->assign('order', $info['order']);
		
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
				$this->error('商品不能重复退换货');
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
					'type'=>2,
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
				$this->success('申请成功', U('user/order/backlist'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
	
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
		);
		$datas = $this->afterSalesService()->getPagerList($params);
		$this->assign('list', $datas['list']);
		
		if(IS_AJAX){
			if(empty($datas['list'])){
				$clist = '';
			}else{
				$clist = $this->renderPartial('_backlist');
			}
			die($clist);
		}
		
		$shipping_list = M('shipping_code')->select();
		$this->assign('shipping_list', $shipping_list);
		
		$this->display();
	}
	
	public function backviewAction($id = 0){
		$map = array(
				'id'=>$id,
				'user_id'=>$this->user['user_id']
		);
		try {
			$info = $this->afterSalesService()->findInfo($map);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		
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
		
		$this->assign('info', $info);
		
		$shipping_list = M('shipping_code')->select();
		$this->assign('shipping_list', $shipping_list);
		
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
	
	private function orderEvent(){
		return D('Order', 'Event');
	}
	
	
	
	private function goodsCommentService(){
		return D('GoodsComment', 'Service');
	}
	
	private function afterSalesService(){
		return D('AfterSales', 'Service');
	}
	
	private function activityService(){
		return D('Activity', 'Service');
	}
}