<?php
namespace Admin\Controller\Order;
use Admin\Controller\FController;
use Common\Basic\Pager;
use Common\Basic\Genre;
use Common\Basic\Status;

class CustomController extends FController {
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'customorder',
			'ac'=>'order_custom_index',
		);
		$this->sbset($set);
    }
	
	//全部订单
    public function indexAction(){
		$map = array();
		$this->sbset('order_custom_index');
		$this->listDisplay($map);
    }
    
    //待设计、测量
    public function designAction(){
    	$map = array(
    			'custom_order_status'=>Status::CustomOrderStatusDesign,
    	);
    	$this->sbset('order_custom_design');
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
	
	//成本审核
	public function checkedAction(){
		$map = array(
			'custom_order_status'=>Status::CustomOrderStatusChecked,
		);
		$this->sbset('order_custom_checked');
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
				'custom_pay_status'=>Status::CustomPayStatusPaidAll,
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
	
	//已评论
	public function commentAction(){
		$map = array(
				'custom_order_status'=>Status::CustomOrderStatusComment,
		);
		$this->sbset('order_custom_comment');
		$this->listDisplay($map);
	}
	
	//回访记录
	public function visitAction(){
		$this->sbset('order_custom_visit');
		$this->display();
	}
	
	//保养记录
	public function keepAction(){
		$this->sbset('order_custom_keep');
		$this->display();
	}
	
	//缺补单
	public function patchAction(){
		$map = array(
				
		);
		$this->sbset('order_custom_patch');
		$this->listDisplay($map);
	}
	
	//已取消
	public function canceledAction(){
		$map = array(
				'custom_order_status'=>Status::CustomOrderStatusCancel,
		);
		$this->sbset('order_custom_canceled');
		$this->listDisplay($map);
	}
	
	//订单详情
	public function infoAction($id = 0){
		$gmap = $info = array();
		$this->existChk($gmap, $info, $id);
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
		
		//发票信息
		$invoice = unserialize($info['invoice_info']);
		$this->assign('invoice', $invoice);
		
		//图纸资料
		$file_list = $this->orderService()->getFileList($info['order_id']);
		$this->assign('file_list', $file_list);
		
		//商品明细
		$detail_list = $this->orderService()->getDetailList($info['order_id']);
		$this->assign('detail_list', $detail_list);
		
		//分期付款
		$payment_list = $this->orderService()->getPaymentList($info['order_id']);
		$this->assign('payment_list', $payment_list);
		
		$this->display();
	}
	
	//明细日志
	public function detail_log_listAction($detail_id) {
		$detail_info = $this->orderService()->getDetailInfo($detail_id);
		if (empty($detail_info)) {
			$this->error('明细日志不存在');
		}
		
		$detail_list = $this->orderService()->getDetailLogList($detail_id);
		$this->assign('detail_list', $detail_list);
		
		$this->display();
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