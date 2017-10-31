<?php
namespace Distributor\Controller\Order;
use Distributor\Controller\FController;
use Common\Basic\Pager;
use Common\Basic\Genre;
use Common\Basic\Status;

class IndexController extends FController {
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'order',
			'ac'=>'order_index_index',
		);
		$this->sbset($set);
    }

    //全部订单
    public function indexAction(){
    	$map = array();
    	$this->sbset('order_index_index');
    	$this->listDisplay($map);
    }
    
    //待付款
    public function nopayAction(){
    	$map = array(
    			'pay_status'=>Status::PayStatusNone,
    			'order_status'=>Status::OrderStatusNone,
    	);
    	$this->sbset('order_index_nopay');
    	$this->listDisplay($map);
    }
    
    //待发货
    public function waitAction(){
    	$map = array(
    			'pay_status'=>Status::PayStatusPaid,
    			'shipping_status'=>Status::ShippingStatusNone,
    	);
    	$this->sbset('order_index_wait');
    	$this->listDisplay($map);
    }
    
    //待收货
    public function shippingAction(){
    	$map = array(
    			'shipping_status'=>Status::ShippingStatusDelivering,
    	);
    
    	$this->sbset('order_index_shipping');
    	$this->listDisplay($map);
    }
    
    //已完成
    public function finishAction(){
    	$map = array(
    			'order_status'=>Status::OrderStatusSuccess,
    	);
    	$this->sbset('order_index_finish');
    	$this->listDisplay($map);
    }
    
    //已取消
    public function cancelAction(){
    	$map = array(
    			'order_status'=>Status::OrderStatusCancel,
    	);
    	$this->sbset('order_index_cancel');
    	$this->listDisplay($map);
    }
    
	//订单详情
	public function infoAction($id = 0){
		$gmap = $info = array();
		$this->existChk($gmap, $info, $id);
		
//		$map = array(
//			'id'=>array('in', array($info['province'], $info['city'], $info['district'])),
//		);
//		$regions = M('Region')->where($map)->getField('id,region_name');
//		$this->assign('regions', $regions);
		
		$list = M('OrderGoods')->where('order_id='.$info['order_id'])->select();
		
		foreach ($list as $k => $v) {
			if ($v['back_status'] > 0) {
				$map = array('item_id'=>$v['id']);
				$list[$k]['back_info'] = $this->afterSalesService()->findInfo($map);
			}
		}
		
		$this->assign('list', $list);
		$this->assign('info', $info);
		
		//发票信息
		$invoice = unserialize($info['invoice_info']);
		$this->assign('invoice', $invoice);
		
		$this->display();
	}
	
	public function sendAction($id = 0){
		//订单
		$params = array(
				'order_id'=>$id,
		);
		$info = $this->orderService()->getOrderInfo($params);
		if (empty($info)) $this->error('订单不存在');
		if($info['pay_status'] != 1) $this->error('订单不能发货');
		
		if(IS_POST){
			$post = I('post.');
			$post['order_id'] = $info['order_id'];
			$post['admin_id'] = session('uid');
			try {
				$this->orderService()->orderSend($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('操作成功');
		}
		
		$map = array(
			'order_id'=>$info['order_id'],
		);
		$list = M('OrderGoods')->where($map)->select();
		$this->assign('list', $list);
		
		$shipping_list = M('ShippingInfo')->where(array('distributor_id'=>$this->org_id))->getField('shipping_id, shipping_name');
		$this->assign('shipping_list', $shipping_list);
		
		//发货地址
//		$map = array(
//			'id'=>array('in', array($info['province'], $info['city'], $info['district'])),
//		);
//		$regions = M('Region')->where($map)->getField('id,region_name');
//		$this->assign('regions', $regions);
		
		$this->assign('info', $info);
		$this->display();
	}
	
	function noteAction($id = 0){
		$gmap = $info = array();
		$this->existChk($gmap, $info, $id);
		
		$post = I('post.');
		$data = array(
			'admin_note'=>$post['note'],
		);
		$res = M('OrderInfo')->where($gmap)->save($data);
		if($res === false){
			$this->error('保存失败');
		}
		$this->success('保存成功');
	}
	
	function closeAction($id = 0){
		$params = array(
				'order_id'=>$id,
				'distributor_id'=>$this->org_id,
				'admin_id'=>session('uid'),
		);
		try {
			$this->orderService()->cancelByDistributor($params);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('取消成功', session('back_url'));
	}
	
	private function existChk(&$gmap, &$info, $id = 0){
		$gmap = array(
				'order_id'=>$id,
				'distributor_id'=>$this->org_id
		);
		$info = M('OrderInfo')->where($gmap)->find();
		if(empty($info)){
			$this->error('内容不存在');
		}
	}
	
	private function listDisplay($map = array()){
		session('back_url', __SELF__);
		
		$get = I('get.');
		$this->assign('get', $get);
		
		$map['distributor_id'] = $this->org_id;
		$params['map'] = $map;
		if (!empty($get['order_id'])) {
			$params['order_id'] = $get['order_id'];
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
		$params['_needStockPrice']=1;//查询进货价
		
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
	
	private function regionService(){
		return D('Region', 'Service');
	}
}