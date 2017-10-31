<?php
namespace Home\Controller\Order;
use Home\Controller\BaseController;
use Common\Basic\Status;

class CustomController extends BaseController {
	public function _initialize(){
		parent::_initialize();
    }
	
    protected function _purviewCheck() {
    	$this->purviewCheck('index');
    }
	
    public function indexAction() { /*定制订单*/
    	$this->assign('page_title', '全部订单');
    	$this->listDisplay();
    }
    
    //待设计、测量
    public function designAction() {
    	$map = array(
    			'custom_order_status'=>Status::CustomOrderStatusDesign,
    	);
    	$this->assign('page_title', '待设计、测量');
    	$this->listDisplay($map);
    }
    
    //生产待审
    public function pendingcheckAction() {
    	$map = array(
    			'custom_order_status'=>Status::CustomOrderStatusPendingCheck,
    	);
    	$this->assign('page_title', '生产待审');
    	$this->listDisplay($map);
    }
    
    //成本待审
    public function checkpassAction() {
    	$map = array(
    			'custom_order_status'=>Status::CustomOrderStatusCheckPass,
    	);
    	$this->assign('page_title', '成本待审');
    	$this->listDisplay($map);
    }
    
    //待生产
    public function pendingproduceAction() {
    	$map = array(
    			'custom_order_status'=>Status::CustomOrderStatusPendingProduce,
    	);
    	$this->assign('page_title', '待生产');
    	$this->listDisplay($map);
    }
    
    //生产中
    public function producingAction() {
    	$map = array(
    			'custom_order_status'=>Status::CustomOrderStatusProducing,
    	);
    	$this->assign('page_title', '生产中');
    	$this->listDisplay($map);
    }
    
    //已入库
    public function storageAction() {
    	$map = array(
    			'custom_order_status'=>Status::CustomOrderStatusStorage,
    	);
    	$this->assign('page_title', '已入库');
    	$this->listDisplay($map);
    }
    
    //已发货
    public function shippedAction() {
    	$map = array(
    			'custom_order_status'=>Status::CustomOrderStatusShipped,
    	);
    	$this->assign('page_title', '已入库');
    	$this->listDisplay($map);
    }
    
    //待安装
    public function pendinginstallAction() {
    	$map = array(
    			'custom_order_status'=>Status::CustomOrderStatusPendingInstall,
    	);
    	$this->assign('page_title', '待安装');
    	$this->listDisplay($map);
    }
    
    //已安装
    public function installedAction() {
    	$map = array(
    			'custom_order_status'=>Status::CustomOrderStatusInstalled,
    	);
    	$this->assign('page_title', '已安装');
    	$this->listDisplay($map);
    }
    
    //已完成
    public function finishAction() {
    	$map = array(
    			'custom_order_status'=>Status::CustomOrderStatusFinish,
    	);
    	$this->assign('page_title', '已完成');
    	$this->listDisplay($map);
    }
    
    //已评论
    public function commentAction() {
    	$map = array(
    			'custom_order_status'=>Status::CustomOrderStatusComment,
    	);
    	$this->assign('page_title', '已评论');
    	$this->listDisplay($map);
    }
    
    //回访记录
    public function visitAction() {
    	$this->assign('page_title', '回访记录');
    	//$this->display();
    }
    
    //保养记录
    public function keepAction() {
    	$this->assign('page_title', '保养记录');
    	//$this->display();
    }
    
    //缺补单
    public function patchAction() {
    	$this->assign('page_title', '缺补单');
    	//$this->display();
    }
    
    //已取消
    public function cancelAction() {
    	$map = array(
    			'custom_order_status'=>Status::CustomOrderStatusCancel,
    	);
    	$this->assign('page_title', '已取消');
    	$this->listDisplay($map);
    }
    
    private function listDisplay($map = array()){
    	if (session('sys_id') == Status::SysIdBrand) { //品牌商
    		$map_brand = array('brands_id'=>session('org_id'));
    		$distributor_list = $this->distributorService()->getAllList($map_brand);
    		if (empty($distributor_list)) {
    			return array();
    		}
    		$distributor_ids = array_keys($distributor_list);
    		$map['distributor_id'] = array('in', $distributor_ids);
    	}elseif (session('sys_id') == Status::SysIdDistributor) { //店铺
    		$map['distributor_id'] = session('distributor_id');
    	}
    	if (I('keyword')) {
    		$params['keyword'] = I('keyword');
    	}
    	if (I('order_id')) {
    		$params['order_id'] = I('order_id');
    	}
    	if (I('start_time')) {
    		$map['start_time'] = I('start_time');
    	}
    	if (I('end_time')) {
    		$map['end_time'] = I('end_time');
    	}
    	if (I('min_order_amount')) {
    		$map['order_amount'][] = array('egt', I('min_order_amount'));
    	}
    	if (I('max_order_amount')) {
    		$map['order_amount'][] = array('elt', I('max_order_amount'));
    	}
    	if (I('store_id')) {
    		$map['distributor_id'] = I('store_id');
    	}
    	if (I('user_id')) {
    		$map['user_id'] = I('user_id');
    	}
    	$params = array('map'=>$map);
    	$params['order_type'] = Status::OrderTypeCustom;
    	
    	$page = intval(I('p')) > 0 ? intval(I('p')) : 1;
    	$pagesize = $this->pagesize;
    	$result = $this->orderService()->getOrderList($params, $page, $pagesize);
    	$this->assign('list', $result['list']);
    	$this->assign('count', $result['count']);
    	
    	if (IS_AJAX) {
    		if(empty($result['list'])){
    			$clist = '';
    		}else{
    			$clist = $this->renderPartial('_index');
    		}
    		$page = intval(I('p')) > 0 ? intval(I('p')) : 1;
    		$this->ajaxReturn(array('html'=>$clist, 'p'=>$page+1));
    	}
    	
    	//店铺筛选
    	if (session('sys_id') == Status::SysIdPlatform) { //平台
    		$map = array('status'=>Status::DistributorStatusNormal);
    		$distributor_list = $this->distributorService()->getAllList($map);
    		$this->assign('distributor_list', $distributor_list);
    	}elseif (session('sys_id') == Status::SysIdBrand) { //品牌商
    		$map = array('brands_id'=>session('org_id'));
    		$distributor_list = $this->distributorService()->getAllList($map);
    		$this->assign('distributor_list', $distributor_list);
    	}
    	
    	//客户标签
    	$map = array();
    	$user_list = $this->orderService()->selectDistinctUserid($map);
    	$user_ids = array();
    	if ($user_list) {
    		foreach ($user_list as $v) {
    			$user_ids[] = $v['user_id'];
    		}
    	}
    	$map = array();
    	if ($user_ids) {
    		$map['user_id'] = array('in', $user_ids);
    	}
    	$user_list = $this->userService()->userAllList($map);
    	$this->assign('user_list', $user_list);
    	
    	$this->display('index');
    }
    
    public function infoAction($id = 0) { /*NoPurview*/
    	session('back_url', __SELF__);
    	
    	$this->assign('sys_id', session('sys_id'));
    	
    	//订单
    	$params = array(
    			'order_id'=>$id,
    	);
    	$info = $this->orderService()->getOrderInfo($params);
    	if (empty($info)) $this->error('订单不存在');
    	//查看物流
    	if ($info['shipping_code']) {
    		$callbackurl = DK_DOMAIN.U('info', array('id'=>$info['order_id']));
    		$ship_url = 'http://m.kuaidi100.com/index_all.html?type='.$info['shipping_code'].'&postid='.$info['shipping_no'].'&callbackurl='.$callbackurl;
    		$info['ship_url'] = $ship_url;
    	}
    	$this->assign('info', $info);
    
    	//用户
    	$user_info = $this->userService()->getUserInfo($info['user_id']);
    	$this->assign('user', $user_info);
    
    	//评价
    	foreach ($info['order_goods'] as $v) {
    		$order_goods_ids[] = $v['id'];
    	}
    	$map = array('order_goods_id'=>array('in', $order_goods_ids));
    	$params = array(
    			'map'=>$map,
    	);
    	$result = $this->goodsCommentService()->getPagerList($params);
    	$this->assign('comment_list', $result['list']);
    	 
    	//图纸资料
    	$file_list = $this->orderService()->getFileList($info['order_id']);
    	$this->assign('file_list', $file_list);
    	 
    	//商品明细
    	$detail_list = $this->orderService()->getDetailList($info['order_id']);
    	$this->assign('detail_list', $detail_list);
    	
    	$this->assign('admin_id', session('uid'));
    	
    	$this->assign('page_title', '订单详情');
    	$this->display();
    }
    
    //材料审批
    public function check_listAction($type = 0) { /*NoPurview*/
    	if (IS_AJAX) {
    		$result = $this->orderlist();
    		$this->assign('list', $result['list']);
    
    		if(empty($result['list'])){
    			$clist = '';
    		}else{
    			$clist = $this->renderPartial('_check_list');
    		}
    		$page = intval(I('p')) > 0 ? intval(I('p')) : 1;
    		$this->ajaxReturn(array('html'=>$clist, 'p'=>$page+1));
    	}
    	 
    	$get = I('get.');
    	$this->assign('get', $get);
    	 
    	//生产待审
    	$map = array(
    			'custom_order_status'=>Status::CustomOrderStatusPendingCheck,
    	);
    	$result = $this->orderlist($map);
    	$this->assign('pendingcheck_list', $result['list']);
    	
    	//成本待审
    	$map = array(
    			'custom_order_status'=>Status::CustomOrderStatusCheckPass,
    	);
    	$result = $this->orderlist($map);
    	$this->assign('checked_list', $result['list']);
    	 
    	$this->assign('page_title', '订单列表');
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
    		try {
    			$this->orderService()->check_drawing($post);
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
    
    //修改价格
    public function edit_priceAction($id = 0) {
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
    		$this->success('审核成功', session('back_url'));
    	}
    	 
    	$this->assign('info', $info);
    	 
    	//用户
    	$user_info = $this->userService()->getUserInfo($info['user_id']);
    	$this->assign('user', $user_info);
    	 
    	//图纸文件
    	$file_list = $this->orderService()->getFileList($info['order_id']);
    	$this->assign('file_list', $file_list);
    	 
    	//明细
    	$detail_list = $this->orderService()->getDetailList($info['order_id']);
    	$this->assign('detail_list', $detail_list);
    	 
    	$this->assign('page_title', '修改价格');
    	$this->display();
    }
    
    //报价审价
    public function offerAction($id = 0) {
    	$post = I('post.');
    	$post['admin_id'] = session('uid');
    	try {
    		$this->orderService()->offer($post);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	$this->success('审核成功', session('back_url'));
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
    		$this->success('审核成功', session('back_url'));
    	}
    	
    	$this->assign('info', $info);
    	
    	//用户
    	$user_info = $this->userService()->getUserInfo($info['user_id']);
    	$this->assign('user', $user_info);
    	
    	$this->assign('page_title', '申请特批');
    	$this->display();
    }
    
    //审核申请特批
    public function confirm_delay_payAction($id = 0) {
    	$post = array(
    			'id'=>$id,
    			'admin_id'=>session('uid'),
    			'delay_pay'=>I('get.delay_pay'),
    	);
    	try {
    		$this->orderService()->confirm_delay_pay($post);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	$this->success('审核成功', session('back_url'));
    }
    
    //生产列表
    public function produce_listAction() { /*NoPurview*/
    	if (IS_AJAX) {
    		$result = $this->orderlist();
    		$this->assign('list', $result['list']);
    		
    		if(empty($result['list'])){
    			$clist = '';
    		}else{
    			$clist = $this->renderPartial('_produce_list');
    		}
    		$page = intval(I('p')) > 0 ? intval(I('p')) : 1;
    		$this->ajaxReturn(array('html'=>$clist, 'p'=>$page+1));
    	}
    
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	//待生产
    	$map = array(
    			'custom_order_status'=>Status::CustomOrderStatusPendingProduce,
    	);
    	$result = $this->orderlist($map);
    	$this->assign('pendingproduce_list', $result['list']);
    	
    	//生产中
    	$map = array(
    			'custom_order_status'=>Status::CustomOrderStatusProducing,
    	);
    	$result = $this->orderlist($map);
    	$this->assign('producing_list', $result['list']);
    	
    	$this->assign('page_title', '订单列表');
    	$this->display();
    }
    
    //确认生产
    public function confirm_produceAction($id = 0) {
    	$post = array(
    			'id'=>$id,
    			'admin_id'=>session('uid'),
    	);
    	try {
    		$this->orderService()->confirm_produce($post);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	$this->success('审核成功', session('back_url'));
    }
    
    //发货列表
    public function ship_listAction($type = 0) { /*NoPurview*/
    	if (IS_AJAX) {
    		$result = $this->orderlist();
    		$this->assign('list', $result['list']);
    
    		if(empty($result['list'])){
    			$clist = '';
    		}else{
    			$clist = $this->renderPartial('_produce_list');
    		}
    		$page = intval(I('p')) > 0 ? intval(I('p')) : 1;
    		$this->ajaxReturn(array('html'=>$clist, 'p'=>$page+1));
    	}
    
    	$get = I('get.');
    	$this->assign('get', $get);
    	 
    	//已入库
    	$map = array(
    			'custom_order_status'=>Status::CustomOrderStatusStorage,
    	);
    	$result = $this->orderlist($map);
    	$this->assign('storage_list', $result['list']);
    	
    	//已发货
    	$map = array(
    			'custom_order_status'=>Status::CustomOrderStatusShipped,
    	);
    	$result = $this->orderlist($map);
    	$this->assign('shipped_list', $result['list']);
    	 
    	$this->assign('page_title', '订单列表');
    	$this->display();
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
    	$this->success('审核成功', session('back_url'));
    }
    
    //确认发货
    public function platform_shippedAction($id = 0) {
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
    			$this->orderService()->platform_shipped($post);
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
    
    	//物流公司
    	$params = array(
    			'distributor_id'=>$this->org_id,
    			'enabled'=>1,
    	);
    	$shipping_list = $this->shippingService()->getShippingList($params);
    	$this->assign('shipping_list', $shipping_list);
    
    	$this->display();
    }
    
    //确认发货
    public function confirm_shippedAction($id = 0) {
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
    			$this->orderService()->confirm_shipped($post);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		$this->success('审核成功', session('back_url'));
    	}
    	
    	$this->assign('info', $info);
    	
    	//物流公司
    	$shipping_list = M('ShippingInfo')->where(array('distributor_id'=>$this->org_id))->getField('shipping_id, shipping_name');
    	$this->assign('shipping_list', $shipping_list);
    	
    	$this->assign('page_title', '确认发货');
    	$this->display();
    }
    
    //安装列表
    public function install_listAction($type = 0) { /*NoPurview*/
    	if (IS_AJAX) {
    		$result = $this->orderlist();
    		$this->assign('list', $result['list']);
    
    		if(empty($result['list'])){
    			$clist = '';
    		}else{
    			$clist = $this->renderPartial('_produce_list');
    		}
    		$page = intval(I('p')) > 0 ? intval(I('p')) : 1;
    		$this->ajaxReturn(array('html'=>$clist, 'p'=>$page+1));
    	}
    
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	//已安装
    	$map = array(
    			'custom_order_status'=>Status::CustomOrderStatusInstalled,
    	);
    	$result = $this->orderlist($map);
    	$this->assign('installed_list', $result['list']);
    	
    	//已完成
    	$map = array(
    			'custom_order_status'=>Status::CustomOrderStatusFinish,
    	);
    	$result = $this->orderlist($map);
    	$this->assign('finish_list', $result['list']);
    	
    	$this->assign('page_title', '订单列表');
    	$this->display();
    }
    
    protected function confirm_installerBefore(){
    	$this->purviewCheck('confirm_installer');
    }
    
    //安装人员
    public function confirm_installerAction($id = 0) { /* 安装人员 */
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
    	 
    	//安装人员
    	$map = array(
    			'distributor_id'=>session('distributor_id'),
    			//'oa_action_list'=>array('like', '%order/custom/confirm_installer%'),
    			'role_id'=>55,
    	);
    	$admin_list = $this->adminService()->adminAllList($map);
    	$this->assign('admin_list', $admin_list);
    	 
    	$this->assign('page_title', '指派安装');
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
    		try {
    			$this->orderService()->confirm_installed($post);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		$this->success('审核成功', session('back_url'));
    	}
    	
    	$this->assign('info', $info);
    	
    	$this->assign('page_title', '安装凭证');
    	$this->display();
    }
    
    public function comment_delAction($comment_id) { /*NoPurview*/
    	try {
    		$this->goodsCommentService()->delete($comment_id);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	$this->success('删除成功');
    }
	
    public function set_statusAction($comment_id) { /*NoPurview*/
    	try {
    		$this->goodsCommentService()->setCommentStatus($comment_id);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	$this->success('操作成功');
    }

    function closeAction($id = 0){
    	$params = array(
    			'order_id'=>$id,
    			'distributor_id'=>session('distributor_id'),
    			'admin_id'=>session('uid'),
    	);
    	try {
    		$this->orderService()->cancelByDistributor($params);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	$this->success('取消成功');
    }
	
    private function orderService(){
    	return D('Order', 'Service');
    }

    private function goodsCommentService(){
    	return D('GoodsComment', 'Service');
    }

    private function distributorService(){
    	return D('Distributor', 'Service');
    }

    protected function afterSalesService(){
    	return D('AfterSales', 'Service');
    }
    
    private function shippingService(){
    	return D('Shipping', 'Service');
    }
}