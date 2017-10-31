<?php
namespace Admin\Controller\Order;
use Admin\Controller\FController;
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
		$this->assign('info', $info);
		
		//$list = M('OrderGoods')->where('order_id='.$info['order_id'])->select();
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
		
		$this->display();
	}
	
	public function sendAction($id = 0){
		$gmap = $info = array();
		$this->existChk($gmap, $info, $id);

		if($info['pay_status'] != 1){
			$this->error('订单不能发货');
		}
		
		if(IS_POST){
			$post = I('post.');
			if($post['status']){
				if(empty($post['shipping_id'])){
					$this->error('请选择物流公司');
				}
				if(empty($post['kd_no'])){
					$this->error('请填写快递单号');
				}
			}
		
			$map = array(
				'shipping_id'=>$post['shipping_id'],
			);
			$shipping_info = M('ShippingInfo')->where($map)->find();
		
			$data = array(
				'order_status'=>Status::OrderStatusOnWay,
				'shipping_status'=>Status::ShippingStatusDelivering,
				'shipping_id'=>$shipping_info['shipping_id'],
				'shipping_code'=>$shipping_info['shipping_code'],
				'shipping_name'=>$shipping_info['shipping_name'],
				'shipping_no'=>$post['kd_no'],
				'shipping_time'=>NOW_TIME,
			);
			$res = M('OrderInfo')->where($gmap)->save($data);
			if($res < 1){
				$this->error('操作失败');
			}
			
			$this->success('操作成功');
		}
		
		$map = array(
			'order_id'=>$info['order_id'],
		);
		$list = M('OrderGoods')->where($map)->select();
		$this->assign('list', $list);
		
		$shipping_list = M('ShippingInfo')->where(array('distributor'=>1))->getField('shipping_id, shipping_name');
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
		//店铺
		$distributor_list = $this->distributorService()->getAllList();
		$this->assign('distributor_list', $distributor_list);
		
		$get = I('get.');
		$this->assign('get', $get);
		
		$map['order_type'] = array('in', array(Status::OrderTypeNormal, Status::OrderTypeGroup)); //普通订单和团购订单
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
		if (!empty($get['distributor_id'])) {
			$params['distributor_id'] = $get['distributor_id'];
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
	
	private function distributorService(){
		return D('Distributor', 'Service');
	}
	
	private function regionService(){
		return D('Region', 'Service');
	}
}