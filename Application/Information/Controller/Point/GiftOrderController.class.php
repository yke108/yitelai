<?php
namespace Information\Controller\Point;
use Information\Controller\FController;
use Common\Basic\Pager;
use Common\Basic\Status;


class GiftOrderController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'point',
			'ac'=>'point_giftorder_index',
		);
		$this->sbset($set);
		
	}
	
	//全部
	public function indexAction(){
		$set = array(
			'in'=>'finance',
			'ac'=>'point_giftorder_index',
		);
		$this->sbset($set);
		$map=array();
		$this->conditionAction($map);
		$this->display('index');
	}
	
	//待发货
	public function waitAction(){
		$map=array('shipping_status'=>Status::GiftShippingWait);
		$this->conditionAction($map);
		$this->display('index');
	}
	
	//待收货
	public function sendAction(){
		$map=array('shipping_status'=>Status::GiftShippingSend);
		$this->conditionAction($map);
		$this->display('index');
	}
	
	public function conditionAction($map){
		$page=I('p')?I('p'):I('get.p');
		$size=12;
		$keyword=I('keyword')?I('keyword'):I('get.keyword');
		$start_time=I('start_time')?I('start_time'):I('get.start_time');
		$end_time=I('end_time')?I('end_time'):I('get.end_time');
		$member_user_id=I('user_id')?I('user_id'):I('get.user_id');
		if($keyword!=''){
			$keyword_map=array('nick_name'=>array('like',"%{$keyword}%"),'mobile'=>array('like',"%{$keyword}%"),'_logic'=>'or');
			$user_id=$this->userService()->getFieldData($keyword_map,'user_id',true);
			
		//	$map['_complex'] = $keyword_map;
			if(empty($user_id)){
				$map['user_id']=0;
			}else{
				$map['user_id']=array('in',$user_id);
			}
		}
		
		$order_sn=I('order_sn')?I('order_sn'):I('get.order_sn');
		if($order_sn!=''){
			$map['order_sn']=array('like',"%{$order_sn}%");
		}
		
		$shipping_status=I('shipping_status')?I('shipping_status'):I('get.shipping_status');
		if($shipping_status!='100' && $shipping_status!=''){
			$map['shipping_status']=$shipping_status;
		}
		
		if($start_time!=''){
			$start_time=strtotime($start_time);
			$map['add_time'][]=array('egt',$start_time);
		}
		
		if($end_time!=''){
			$end_time=strtotime($end_time);
			$map['add_time'][]=array('elt',$end_time);
		}
		
		
		$params=array('page'=>$page,'pagesize'=>$size,'map'=>$map);
		$result=$this->pointGiftService()->orderPagerList($params);
		$pager=new Pager($result['count'],$size);
		$this->assign('list',$result['list']);
		$this->assign('page',$pager->show());
		$this->assign('get',$_GET);
		
		$pager = new Pager($result['count'], $size);
		$this->assign('page', $pager->show());
	}
	
	public function infoAction($id){
		$info=$this->pointGiftService()->getOrderInfo($id);
		$map=array('order_id'=>$id);
		$params=array('map'=>$map);
		$result=$this->pointGiftService()->orderGoodsPagerList($params);
		
		$shipping_params=array('distributor_id'=>0,'page'=>1,'pagesize'=>100);
		$shipping_result=$this->shippingService()->getPagerShippingList($shipping_params);
		
		$this->assign('info',$info);
		$this->assign('list',$result['list']);
		$this->assign('shipping_list',$shipping_result['list']);
		
		$this->display();
	}
	
	public function order_sendAction(){
		$post=I('post.');
		$params=array(
				'shipping_id'=>$post['shipping_id'],
				'shipping_sn'=>$post['shipping_no'],
				'order_id'=>$post['order_id'],
				);
		
		try{
			$send_time=$this->pointGiftService()->shipping($params);
		}catch(\Exception $e){
			$this->ajaxReturn(array('error'=>1,'msg'=>$e->getMessage()));
		}
		$this->ajaxReturn(array('error'=>0,'msg'=>'配送成功','send_time'=>date("Y-m-d H:i",$send_time)));
	}
	
	
	private function pointGiftService(){
		return D('PointGift',"Service");
	}
	
	private function shippingService(){
		return D('Shipping',"Service");
	}
	
	
	
	
	
}