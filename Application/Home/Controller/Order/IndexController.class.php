<?php
namespace Home\Controller\Order;
use Home\Controller\BaseController;
use Common\Basic\Status;

class IndexController extends BaseController {
	public function _initialize(){
		parent::_initialize();
    }
	
    protected function _purviewCheck() {
    	$this->purviewCheck('index');
    }
	
    public function indexAction() { /*订单管理 */
    	$this->assign('sys_id', session('sys_id'));
    	$this->assign('role_id', session('role_id'));
    	
    	//订单统计
    	if (in_array(session('sys_id'), array(Status::SysIdPlatform, Status::SysIdDistributor))) {
    		$params = array();
    		if (session('sys_id') == Status::SysIdDistributor) { //店铺
    			$params['distributor_id'] = session('distributor_id');
    		}
    		$uon = $this->orderService()->getOrderCount($params);
    		//退货单统计
    		$map = array();
    		if (session('sys_id') == Status::SysIdDistributor) { //店铺
    			$map['distributor_id'] = session('distributor_id');
    		}
    		$back_count = $this->afterSalesService()->getDistributorCount($map);
    		$uon['back'] = $back_count;
    		$this->assign('uon', $uon);
    	}
    	
    	//定制订单统计
    	if (session('sys_id') == Status::SysIdBrand) { //品牌商
    		$map = array('brands_id'=>session('org_id'));
    		$distributor_list = $this->distributorService()->getAllList($map);
    		$distributor_ids = $distributor_list ? array_keys($distributor_list) : array();
    		$map = array('distributor_id'=>array('in', $distributor_ids));
    	}elseif (session('sys_id') == Status::SysIdDistributor) { //店铺
    		$map = array('distributor_id'=>session('distributor_id'));
    	}
    	$params = array('map'=>$map);
    	$custom_uon = $this->orderService()->getCustomOrderCount($params);
    	$this->assign('custom_uon', $custom_uon);
		
    	$this->assign('page_title', '订单管理');
		$this->display();
    }

    public function listAction() { /*NoPurview*/
    	if (IS_AJAX) {
    		$result = $this->orderlist();
    		$this->assign('list', $result['list']);

    		if(empty($result['list'])){
    			$clist = '';
    		}else{
    			if (I('post.type') == 6) {
    				$clist = $this->renderPartial('_backlist');
    			}else {
    				$clist = $this->renderPartial('_list');
    			}
    		}
    		$page = intval(I('p')) > 0 ? intval(I('p')) : 1;
    		$this->ajaxReturn(array('html'=>$clist, 'p'=>$page+1));
    	}
    	
    	session('back_url', __SELF__);
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$this->assign('sys_id', session('sys_id'));
		
    	//店铺列表
    	if (session('sys_id') == 1) {
    		$map = array('status'=>Status::DistributorStatusNormal);
    		$distributor_list = $this->distributorService()->getAllList($map);
    		$this->assign('distributor_list', $distributor_list);
    	}
		
    	//客户标签
    	$user_list = $this->orderService()->selectDistinctUserid();
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
    	
    	$this->all(); //全部
    	$this->nopay(); //待付款
    	$this->wait(); //待发货
    	$this->finish(); //已完成
    	$this->cancel(); //已取消
    	$this->back(); //退换货
    	$this->comment(); //退换货
		
    	$this->assign('page_title', '订单列表');
    	$this->display();
    }

    private function all(){
    	$result = $this->orderlist();
    	$this->assign('list', $result['list']);
    }
	
    private function nopay(){
    	$map = array(
    			'pay_status'=>Status::PayStatusNone,
    			'order_status'=>Status::OrderStatusNone,
    	);
    	$result = $this->orderlist($map);
    	$this->assign('nopay_list', $result['list']);
    }
	
    private function wait(){
    	$map = array(
    			'pay_status'=>Status::PayStatusPaid,
    			'shipping_status'=>Status::ShippingStatusNone,
    	);
    	$result = $this->orderlist($map);
    	$this->assign('wait_list', $result['list']);
    }

    private function finish(){
    	$map = array(
    			'shipping_status'=>array('egt', Status::ShippingStatusReceived),
    			'order_status'=>array('egt', Status::OrderStatusSuccess)
    	);
    	$result = $this->orderlist($map);
    	$this->assign('finish_list', $result['list']);
    }

    private function cancel(){
    	$map = array(
    			'order_status'=>Status::OrderStatusCancel,
    	);
    	$result = $this->orderlist($map);
    	$this->assign('cancel_list', $result['list']);
    }

    private function back(){
    	/* $map = array(
    			'order_status'=>Status::OrderStatusOnBack,
    	);
    	$result = $this->orderlist($map);
    	$this->assign('back_list', $result['list']); */
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'type'=>Status::AfterSaleBack,
    	);
    	if (session('distributor_id')) {
    		$params['distributor_id'] = session('distributor_id');
    	}
    	$result = $this->afterSalesService()->getPagerList($params);
    	$this->assign('back_list', $result['list']);
    }

    public function checkAction($id = 0) {
    	//退货单
    	$map = array(
    			'id'=>$id,
    			//'distributor_id'=>session('distributor_id'),
    	);
    	if (session('distributor_id')) {
    		$map['distributor_id'] = session('distributor_id');
    	}
    	$info = $this->afterSalesService()->findInfo($map);
    	if (empty($info)) {
    		$this->error('数据不存在');
    	}
    	$this->assign('info', $info);

    	//会员
    	$user = $this->userService()->getUserInfo($info['user_id']);
    	if (empty($user)) {
    		$this->error('会员不存在');
    	}
    	$this->assign('user', $user);

    	//订单
    	$params = array('order_id'=>$info['order_id']);
    	$order = $this->orderService()->getOrderInfo($params);
    	if (empty($order)) {
    		$this->error('订单不存在');
    	}
    	$this->assign('order', $order);

    	if (IS_POST) {
    		if ($info['status'] != 0) {
    			$this->error('退货单已审核');
    		}
    		$status = I('get.status');
    		if (empty($status)) {
    			$this->error('请选择审核状态');
    		}

    		$params['id'] = I('get.id');
    		$params['status'] = $status;
    		$params['info'] = $info;
    		$params['user'] = $user;
    		try {
    			$this->afterSalesService()->backCheck($params);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		$this->success('审核成功');
    	}

    	$this->assign('sys_id', session('sys_id'));

    	$this->assign('page_title', '退货单详情');
    	$this->display();
    }

    private function comment(){
    	$map = array(
    			'comment_status'=>Status::CommentStatusYes,
    	);
    	$result = $this->orderlist($map);
    	$this->assign('comment_list', $result['list']);
    }

    private function orderlist($map = array()){
    	$page = intval(I('p')) > 0 ? intval(I('p')) : 1;
    	$pagesize = $this->pagesize;
		
    	if (session('distributor_id')) {
    		$map['distributor_id'] = session('distributor_id');
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
    	if (I('mobile')) {
    		$mobile = I('mobile');
    		$map['mobile'] = array('like', '%'.trim($mobile).'%');
    	}
    	$params = array('map'=>$map);
    	if (I('order_id')) {
    		$params['order_id'] = I('order_id');
    	}
    	if (I('keyword')) {
    		$params['keyword'] = I('keyword');
    	}
    	switch (I('post.type')) {
    		case 2:
    			$map = array(
    			'pay_status'=>Status::PayStatusNone,
    			'order_status'=>Status::OrderStatusNone,
    			);
    			break;
    		case 3:
    			$map = array(
    			'pay_status'=>Status::PayStatusPaid,
    			'shipping_status'=>Status::ShippingStatusNone,
    			);
    			break;
    		case 4:
    			$map = array(
    			'shipping_status'=>array('egt', Status::ShippingStatusReceived),
    			'order_status'=>array('egt', Status::OrderStatusSuccess)
    			);
    			break;
    		case 5:
    			$map = array(
    			'order_status'=>Status::OrderStatusCancel,
    			);
    			break;
    		case 6:
    			/* $map = array(
    			 'order_status'=>Status::OrderStatusOnBack,
    			); */
    			break;
    		case 7:
    			$map = array(
    			'comment_status'=>Status::CommentStatusYes,
    			);
    			break;
    	}
    	//退货
    	if (I('post.type') == 6) {
    		$params['page'] = $page;
    		$params['pagesize'] = $pagesize;
    		$params['type'] = Status::AfterSaleBack;
    		return $this->afterSalesService()->getPagerList($params);
    	}
		
    	return $this->orderService()->getOrderList($params, $page, $pagesize);
    }
	
    public function sendAction($order_id = 0) { /*NoPurview*/
    	//订单
    	$params = array(
    			'order_id'=>$order_id,
    	);
    	$info = $this->orderService()->getOrderInfo($params);
    	if (empty($info)) $this->error('订单不存在');
    	if($info['pay_status'] != 1) $this->error('订单未支付，不能发货');
		
    	if(IS_POST) {
    		$post = I('post.');
    		try {
    			$this->orderService()->orderSend($post);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		$this->success('发货成功', U('list', array('type'=>3)));
    	}
    	
    	//订单
    	$this->assign('info', $info);
		
    	//订单商品
    	$map = array(
    			'order_id'=>$info['order_id'],
    	);
    	$list = M('OrderGoods')->where($map)->select();
    	$this->assign('list', $list);
		
    	//物流公司
    	$shipping_list = M('ShippingInfo')->where(array('distributor_id'=>session('distributor_id')))->getField('shipping_id, shipping_name');
    	$this->assign('shipping_list', $shipping_list);
		
    	$this->assign('page_title', '订单发货');
    	$this->display();
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

    	$this->assign('page_title', '订单详情');
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
}