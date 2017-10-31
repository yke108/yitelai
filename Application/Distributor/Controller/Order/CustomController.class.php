<?php
namespace Distributor\Controller\Order;
use Distributor\Controller\FController;
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
    	//状态筛选
    	$this->assign('status_list', Status::$customOrderStatusList);
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
	
    //待审资料
    public function pendingcheckAction(){
    	$map = array(
    			'custom_order_status'=>Status::CustomOrderStatusPendingCheck,
    	);
    	$this->sbset('order_custom_pendingcheck');
    	$this->listDisplay($map);
    }
    
    //生产审核不过
    public function nopassAction(){
    	$map = array(
    			'custom_order_status'=>Status::CustomOrderStatusCheckNoPass,
    	);
    	$this->sbset('order_custom_nopass');
    	$this->listDisplay($map);
    }
	
	//已审资料
	public function checkedAction(){
		$map = array(
			'custom_order_status'=>Status::CustomOrderStatusCheckPass,
		);
		$this->sbset('order_custom_checked');
		$this->listDisplay($map);
	}
	
	//待付全款
	public function pendingpayAction(){
		$map = array(
				'custom_order_status'=>Status::CustomOrderStatusCheckPass,
				'custom_pay_status'=>Status::CustomPayStatusNone,
		);
		$this->sbset('order_custom_pendingpay');
		$this->listDisplay($map);
	}
	
	//已付全款
	public function paidAction(){
		$map = array(
				'custom_order_status'=>Status::CustomOrderStatusCheckPass,
				'custom_pay_status'=>Status::CustomPayStatusPaidAll,
		);
		$this->sbset('order_custom_paid');
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
	
	//回访记录
	public function visitAction(){
		$map = array(
				'custom_order_status'=>Status::CustomOrderStatusVisit,
		);
		$this->sbset('order_custom_visit');
		$this->listDisplay($map);
	}
	
	//评论
	public function commentAction(){
		$map = array(
				'custom_order_status'=>Status::CustomOrderStatusComment,
		);
		$this->sbset('order_custom_comment');
		$this->listDisplay($map);
	}
	
	//保养
	public function keepAction(){
		$map = array(
				'custom_order_status'=>Status::CustomOrderStatusKeep,
		);
		$this->sbset('order_custom_keep');
		$this->listDisplay($map);
	}
	
	//缺货
	public function stockoutAction(){
		$map = array(
				'custom_order_status'=>Status::CustomOrderStatusStockout,
		);
		$this->sbset('order_custom_stockout');
		$this->listDisplay($map);
	}
	
	//补货
	public function patchAction(){
		$map = array(
				'custom_order_status'=>Status::CustomOrderStatusPatch,
		);
		$this->sbset('order_custom_patch');
		$this->listDisplay($map);
	}
	
	//取消
	public function canceledAction(){
		$map = array(
				'custom_order_status'=>Status::CustomOrderStatusCancel,
		);
		$this->sbset('order_custom_canceled');
		$this->listDisplay($map);
	}
	
	//订单详情
	public function infoAction($id = 0){
		$map = array(
				'order_id'=>$id,
				'distributor_id'=>$this->org_id,
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
	
	//上传图纸资料
	public function drawingAction($id = 0) {
		$map = array(
				'order_id'=>$id,
				'distributor_id'=>$this->org_id,
		);
		$info = $this->orderService()->findOrderInfo($map);
		if (empty($info)) $this->error('订单不存在');
		
		//管理员
		$admin_info = $this->adminService()->getAdmin(session('uid'));
		
		if (IS_POST) {
			$post = I('post.');
			$post['inner_order_id'] = $post['inner_order_id'] ? $this->distributor_info['store_no'].'-0'.date('y').$post['inner_order_id'] : '';
			$post['admin_id'] = session('uid');
			$post['admin_name'] = $admin_info['admin_name'];
			$post['sys_id'] = $this->sys_id;
			try {
				$this->orderService()->drawing($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('提交成功', session('back_url'));
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
		
		//管理员
		$this->assign('admin', $admin_info);
		
		//店铺号
		$store_no = $this->distributor_info['store_no'].'-0'.date('y');
		$this->assign('store_no', $store_no);
		
		//订单
		$info['inner_order_id'] = $info['inner_order_id'] ? str_replace($store_no, '', $info['inner_order_id']) : $info['inner_order_id'];
		$this->assign('info', $info);
		
		$this->sbset('order_custom_drawing');
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
	
	//申请特批
	public function delay_payAction($id = 0) {
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
				$this->orderService()->delay_pay($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('申请成功', session('back_url'));
		}
		
		$this->assign('info', $info);
		
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
		
		//用户
		$user_info = $this->userService()->getUserInfo($info['user_id']);
		$this->assign('user', $user_info);
		
		//图纸文件
		$file_list = $this->orderService()->getFileList($info['order_id']);
		$this->assign('file_list', $file_list);
		
		//明细
		$detail_list = $this->orderService()->getDetailList($info['order_id']);
		$this->assign('detail_list', $detail_list);
		
		//分批付款
		if ($info['pay_type'] == 1) {
			$payment_list = $this->orderService()->getPaymentList($info['order_id']);
			$this->assign('payment_list', $payment_list);
		}
		
		$this->assign('page_title', '申请特批');
		$this->display();
	}
	
	//确认报价
	public function confirm_priceAction($id = 0) {
		//订单
		$map = array(
				'order_id'=>$id,
				'distributor_id'=>$this->org_id,
		);
		$info = $this->orderService()->findOrderInfo($map);
		if (empty($info)) $this->error('订单不存在');
		
		if (IS_POST) {
			$post = I('post.');
			$post['admin_id'] = session('uid');
			try {
				$this->orderService()->confirm_price($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('操作成功', session('back_url'));
		}
		
		$this->assign('info', $info);
		
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
		
		//用户
		$user_info = $this->userService()->getUserInfo($info['user_id']);
		$this->assign('user', $user_info);
		
		//图纸文件
		$file_list = $this->orderService()->getFileList($info['order_id']);
		$this->assign('file_list', $file_list);
		
		//明细
		$detail_list = $this->orderService()->getDetailList($info['order_id']);
		$this->assign('detail_list', $detail_list);
		
		$this->display();
	}
	
	//确认发货
	public function confirm_shippedAction($id = 0) {
		$post = array(
				'id'=>$id,
				'admin_id'=>session('uid'),
		);
		try {
			$this->orderService()->confirm_shipped($post);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('操作成功', session('back_url'));
	}
	
	//安装人员
	public function confirm_installerAction($id = 0) {
		//订单
		$params = array(
				'order_id'=>$id,
		);
		$info = $this->orderService()->getOrderInfo($params);
		if (empty($info)) $this->error('订单不存在');
	
		if (IS_POST) {
			$post = I('post.');
			$post['admin_id'] = session('uid');
			try {
				$this->orderService()->confirm_installer($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('提交成功', session('back_url'));
		}
	
		$this->assign('info', $info);
		
		//图纸文件
		$file_list = $this->orderService()->getFileList($info['order_id']);
		$this->assign('file_list', $file_list);
		
		//明细
		$detail_list = $this->orderService()->getDetailList($info['order_id']);
		$this->assign('detail_list', $detail_list);
		
		//安装人员
		$map = array(
				'distributor_id'=>$this->org_id,
				'role_id'=>55,
		);
		$admin_list = $this->adminService()->adminAllList($map);
		$this->assign('admin_list', $admin_list);
		
		$this->display();
	}
	
	//安装凭证
	public function confirm_installedAction($id = 0) {
		//订单
		$params = array(
				'order_id'=>$id,
		);
		$info = $this->orderService()->getOrderInfo($params);
		if (empty($info)) $this->error('订单不存在');
		
		if (IS_POST) {
			$post = I('post.');
			$post['admin_id'] = session('uid');
			
			$admin_info = $this->adminService()->getAdmin(session('uid'));
			$post['is_admin'] = $admin_info['is_admin'];
			
			try {
				$this->orderService()->confirm_installed($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('审核成功', session('back_url'));
		}
		
		$this->assign('info', $info);
		
		//图纸文件
		$file_list = $this->orderService()->getFileList($info['order_id']);
		$this->assign('file_list', $file_list);
		
		//明细
		$detail_list = $this->orderService()->getDetailList($info['order_id']);
		$this->assign('detail_list', $detail_list);
		
		$this->display();
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
	
	private function listDisplay($map = array(), $mode = 'index'){
		session('back_url', __SELF__);
		
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
		if (!empty($get['custom_order_status'])) {
			$params['custom_order_status'] = $get['custom_order_status'];
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
		$params['distributor_id'] = $this->org_id;
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
    	
    	$this->assign('admin_id', session('uid'));
    	$admin_info = $this->adminService()->getAdmin(session('uid'));
    	$this->assign('is_admin', $admin_info['is_admin']);
		
		$this->display($mode);
	}
	
	private function regionService(){
		return D('Region', 'Service');
	}
}