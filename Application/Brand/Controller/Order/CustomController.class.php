<?php
namespace Brand\Controller\Order;
use Brand\Controller\FController;
use Common\Basic\Pager;
use Common\Basic\Genre;
use Common\Basic\Status;

class CustomController extends FController {
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'customorder',
			//'ac'=>'order_custom_index',
		);
		$this->sbset($set);
    }
	
	//全部订单
    public function indexAction(){
		$map = array();
		$this->sbset('order_custom_index');
		$this->listDisplay($map);
    }
    
    //生产待审
    public function pendingcheckAction(){
    	$map = array(
    			'custom_order_status'=>Status::CustomOrderStatusPendingCheck,
    	);
    	$this->sbset('order_custom_pendingcheck');
    	$this->listDisplay($map);
    }
	
	//成本待审
	public function checkpassAction(){
		$map = array(
			'custom_order_status'=>Status::CustomOrderStatusCheckPass,
		);
		$this->sbset('order_custom_checkpass');
		$this->listDisplay($map);
	}
	
	//待生产
	public function pendingproduceAction(){
		$map = array(
				'custom_order_status'=>Status::CustomOrderStatusPendingProduce,
		);
		$this->sbset('order_custom_pendingproduce');
		$this->listDisplay($map);
	}
	
	//生产中
	public function producingAction(){
		$map = array(
				'custom_order_status'=>Status::CustomOrderStatusProducing,
		);
		$this->sbset('order_custom_producing');
		$this->listDisplay($map);
	}
	
	//已生产
	public function producedAction(){
		$map = array(
				'custom_order_status'=>Status::CustomOrderStatusProduced,
		);
		$this->sbset('order_custom_produced');
		$this->listDisplay($map);
	}
	
	//已入库
	public function storageAction(){
		$map = array(
				'custom_order_status'=>Status::CustomOrderStatusStorage,
		);
		$this->sbset('order_custom_storage');
		$this->listDisplay($map);
	}
	
	//已发货
	public function shippedAction(){
		$map = array(
				'custom_order_status'=>Status::CustomOrderStatusShipped,
		);
		$this->sbset('order_custom_shipped');
		$this->listDisplay($map);
	}
	
	//已安装
	public function installedAction(){
		$map = array(
				'custom_order_status'=>Status::CustomOrderStatusInstalled,
		);
		$this->sbset('order_custom_installed');
		$this->listDisplay($map);
	}
	
	//已完成
	public function finishAction(){
		$map = array(
				'custom_order_status'=>Status::CustomOrderStatusFinish,
		);
		$this->sbset('order_custom_finish');
		$this->listDisplay($map);
	}
	
	//缺补单
	public function patchAction(){
		$map = array(
				
		);
		$this->sbset('order_custom_patch');
		$this->listDisplay($map);
	}
	
	//订单详情
	public function infoAction($id = 0){
		$map = array(
				'order_id'=>$id,
		);
		$info = $this->orderService()->findOrderInfo($map);
		if (empty($info)) $this->error('订单不存在');
		
		//发票信息
		$invoice = unserialize($info['invoice_info']);
		$this->assign('invoice', $invoice);
		
		//图纸文件
		$file_list = $this->orderService()->getFileList($info['order_id']);
		$this->assign('file_list', $file_list);
		
		//明细
		$detail_list = $this->orderService()->getDetailList($info['order_id']);
		$this->assign('detail_list', $detail_list);
		
		//分期付款
		$payment_list = $this->orderService()->getPaymentList($info['order_id']);
		$this->assign('payment_list', $payment_list);
		
		//订单
		$this->assign('info', $info);
		
		$this->display();
	}
	
	//审核资料
	public function check_drawingAction($id = 0) {
		$map = array(
				'order_id'=>$id,
		);
		$info = $this->orderService()->findOrderInfo($map);
		if (empty($info)) $this->error('订单不存在');
		
		if (IS_POST) {
			$post = I('post.');
			$post['admin_id'] = session('uid');
			$post['sys_id'] = $this->sys_id;
			try {
				$this->orderService()->check_drawing($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('保存成功', session('back_url'));
		}
		
		//图纸文件
		$file_list = $this->orderService()->getFileList($info['order_id']);
		$this->assign('file_list', $file_list);
		
		//明细
		$detail_list = $this->orderService()->getDetailList($info['order_id']);
		$this->assign('detail_list', $detail_list);
		
		//分期付款
		$payment_list = $this->orderService()->getPaymentList($info['order_id']);
		$this->assign('payment_list', $payment_list);
		
		//订单
		$this->assign('info', $info);
		
		$this->display();
	}
	
	//明细日志
	public function detail_log_listAction($detail_id) {
		$detail_info = $this->orderService()->getDetailInfo($detail_id);
		if (empty($detail_info)) {
			$this->error('明细日志不存在');
		}
		$params = array(
				'order_id'=>$detail_info['order_id'],
				'distributor_id'=>$this->org_id,
		);
		$order_info = $this->orderService()->getOrderInfo($params);
		if (empty($detail_info)) {
			$this->error('明细日志不存在');
		}
		
		$detail_list = $this->orderService()->getDetailLogList($detail_id);
		$this->assign('detail_list', $detail_list);
	
		$this->display();
	}
	
	//报价审价
	public function offerAction($id = 0) {
		$map = array(
				'order_id'=>$id,
		);
		$info = $this->orderService()->findOrderInfo($map);
		if (empty($info)) $this->error('订单不存在');
		
		if (IS_POST) {
			$post = I('post.');
			$post['admin_id'] = session('uid');
			try {
				$this->orderService()->offer($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('保存成功', session('back_url'));
		}
		
		$this->assign('info', $info);
		
		//图纸文件
		$file_list = $this->orderService()->getFileList($info['order_id']);
		$this->assign('file_list', $file_list);
		
		//明细
		$detail_list = $this->orderService()->getDetailList($info['order_id']);
		$this->assign('detail_list', $detail_list);
		
		//分期付款
		$payment_list = $this->orderService()->getPaymentList($info['order_id']);
		$this->assign('payment_list', $payment_list);
		
		$this->display();
	}
	
	//审核申请特批
	public function confirm_delay_payAction($id = 0) {
		$map = array(
				'order_id'=>$id,
		);
		$info = $this->orderService()->findOrderInfo($map);
		if (empty($info)) $this->error('订单不存在');
		
		if (IS_POST) {
			$post = array(
					'id'=>$id,
					'admin_id'=>session('uid'),
					'delay_pay'=>I('post.delay_pay'),
			);
			try {
				$this->orderService()->confirm_delay_pay($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('保存成功', session('back_url'));
		}
		
		$this->assign('info', $info);
		
		//订单商品
		$result = $this->orderService()->getOrderInfo(array('order_id'=>$info['order_id']));
		$list = $result['order_goods'];
		foreach ($list as $k => $v) {
			if ($v['back_status'] > 0) {
				$map = array('item_id'=>$v['id']);
				$list[$k]['back_info'] = $this->afterSalesService()->findInfo($map);
			}
		}
		$this->assign('list', $list);
		
		//图纸文件
		$file_list = $this->orderService()->getFileList($info['order_id']);
		$this->assign('file_list', $file_list);
		
		//明细
		$detail_list = $this->orderService()->getDetailList($info['order_id']);
		$this->assign('detail_list', $detail_list);
		
		$this->display();
	}
	
	//确认生产
	public function confirm_produceAction($id = 0) {
		$map = array(
				'order_id'=>$id,
		);
		$info = $this->orderService()->findOrderInfo($map);
		if (empty($info)) $this->error('订单不存在');
		
		if (IS_POST) {
			$post = array(
					'id'=>$id,
					'admin_id'=>session('uid'),
			);
			try {
				$this->orderService()->confirm_produce($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('操作成功', session('back_url'));
		}
		
		//图纸文件
		$file_list = $this->orderService()->getFileList($info['order_id']);
		$this->assign('file_list', $file_list);
		
		//明细
		$detail_list = $this->orderService()->getDetailList($info['order_id']);
		$this->assign('detail_list', $detail_list);
		
		//分期付款
		$payment_list = $this->orderService()->getPaymentList($info['order_id']);
		$this->assign('payment_list', $payment_list);
		
		//店铺号
		$distributor_info = $this->distributorService()->getInfo($info['distributor_id']);
		$store_no = $distributor_info['store_no'].'-0'.date('y');
		$this->assign('store_no', $store_no);
		
		//订单
		$info['inner_order_id'] = $info['inner_order_id'] ? str_replace($store_no, '', $info['inner_order_id']) : $info['inner_order_id'];
		$this->assign('info', $info);
		
		$this->display();
	}
	
	//确认生产完成
	public function confirm_producedAction($id = 0) {
		$post = array(
				'id'=>$id,
				'admin_id'=>session('uid'),
		);
		try {
			$this->orderService()->confirm_produced($post);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('操作成功', session('back_url'));
	}
	
	//确认入库
	public function confirm_storageAction($id = 0) {
		$post = array(
				'id'=>$id,
				'admin_id'=>session('uid'),
		);
		try {
			$this->orderService()->confirm_storage($post);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('操作成功', session('back_url'));
	}
	
	//确认发货
	public function confirm_shippedAction($id = 0) {
		//订单
		$map = array(
				'order_id'=>$id,
		);
		$info = $this->orderService()->findOrderInfo($map);
		if (empty($info)) $this->error('订单不存在');
		
		if (IS_POST) {
			$post = I('post.');
			$post['admin_id'] = session('uid');
			try {
				$this->orderService()->confirm_shipped($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('保存成功', session('back_url'));
		}
		
		$this->assign('info', $info);
		$list = $info['order_goods'];
		foreach ($list as $k => $v) {
			if ($v['back_status'] > 0) {
				$map = array('item_id'=>$v['id']);
				$list[$k]['back_info'] = $this->afterSalesService()->findInfo($map);
			}
		}
		$this->assign('list', $list);
		
		//图纸文件
		$file_list = $this->orderService()->getFileList($info['order_id']);
		$this->assign('file_list', $file_list);
		
		//明细
		$detail_list = $this->orderService()->getDetailList($info['order_id']);
		$this->assign('detail_list', $detail_list);
		
		//物流公司
		$params = array(
				'distributor_id'=>$this->org_id,
				'enabled'=>1,
		);
		$shipping_list = $this->shippingService()->getShippingList($params);
		$this->assign('shipping_list', $shipping_list);
		
		$this->display();
	}
	
	public function edit_shippedAction($id = 0) {
		//订单
		$map = array(
				'order_id'=>$id,
		);
		$info = $this->orderService()->findOrderInfo($map);
		if (empty($info)) $this->error('订单不存在');
	
		if (IS_POST) {
			$post = I('post.');
			$post['admin_id'] = session('uid');
			try {
				$this->orderService()->edit_shipped($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('保存成功', session('back_url'));
		}
	
		$this->assign('info', $info);
		$list = $info['order_goods'];
		foreach ($list as $k => $v) {
			if ($v['back_status'] > 0) {
				$map = array('item_id'=>$v['id']);
				$list[$k]['back_info'] = $this->afterSalesService()->findInfo($map);
			}
		}
		$this->assign('list', $list);
	
		//图纸文件
		$file_list = $this->orderService()->getFileList($info['order_id']);
		$this->assign('file_list', $file_list);
	
		//明细
		$detail_list = $this->orderService()->getDetailList($info['order_id']);
		$this->assign('detail_list', $detail_list);
	
		//物流公司
		$params = array(
				'distributor_id'=>$this->org_id,
				'enabled'=>1,
		);
		$shipping_list = $this->shippingService()->getShippingList($params);
		$this->assign('shipping_list', $shipping_list);
	
		$this->display('confirm_shipped');
	}
	
	function closeAction($id = 0){
		$gmap = $info = array();
		$this->existChk($gmap, $info, $id);
		
		if($info['order_status'] > 0 || $info['pay_status'] > 0 || $info['shipping_status'] > 0){
			$this->error('交易进行中，不能关闭。');
		}
		
		$data = array(
			'order_status'=>6,
		);
		$res = M('OrderInfo')->where($gmap)->save($data);
		if($res === false){
			$this->error('交易关闭失败');
		}
		$this->success('交易成功关闭');
	}
	
	private function existChk(&$gmap, &$info, $id = 0){
		$gmap = array(
			'order_id'=>$id,
		);
		$info = M('OrderInfo')->where($gmap)->find();
		if(empty($info)){
			$this->error('内容不存在');
		}
	}
	
	private function listDisplay($map = array()){
		session('back_url', __SELF__);
		
		//店铺
		$distributor_list = $this->distributorService()->getAllList();
		$this->assign('distributor_list', $distributor_list);
		
		$get = I('get.');
		$this->assign('get', $get);
		
		$params['map'] = $map;
		if (!empty($get['order_id'])) {
			$params['order_id'] = $get['order_id'];
		}
		if (!empty($get['consignee'])) {
			$params['consignee'] = $get['consignee'];
		}
		if (!empty($get['mobile'])) {
			$params['mobile'] = $get['mobile'];
		}
		if(!empty($get['start_time'])){
			$params['add_time'] = $get['start_time'];
		}
		if(!empty($get['end_time'])){
			$params['add_time'] = $get['end_time'];
		}
		if (!empty($get['user_id'])) {
			$params['user_id'] = $get['user_id'];
		}
		if (!empty($get['distributor_id'])) {
			$params['distributor_id'] = $get['distributor_id'];
		}
		$params['_needStockPrice']=1;//查询进货价
		$params['order_type'] = Status::OrderTypeCustom; //定制订单
		
		$page = intval(I('p')) ? intval(I('p')) : 1;
    	$datas = $this->orderService()->getOrderList($params, $page, $this->pagesize);
    	$this->assign('list',$datas['list']);
		$this->assign('stock_price_list',$datas['stock_price_list']);
		$this->assign('stock_amount',$datas['stock_amount']);
    	$pager = new Pager($datas['count'], $this->pagesize);
    	$this->assign('pager', $pager->show());
    	
    	//订单总金额
    	$total_order_amount = $this->orderService()->getOrderAmount($params);
    	$this->assign('total_order_amount', $total_order_amount);
		
		$this->display('index');
	}
	
	private function distributorService(){
		return D('Distributor', 'Service');
	}
	
	private function regionService(){
		return D('Region', 'Service');
	}
	
	private function shippingService(){
		return D('Shipping', 'Service');
	}
}