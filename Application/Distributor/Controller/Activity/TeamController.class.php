<?php
namespace Distributor\Controller\Activity;
use Distributor\Controller\FController;
use Common\Basic\Pager;
use Common\Basic\Status;

class TeamController extends FController {
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'promotion',
			'ac'=>'activity_team_index',
		);
		$this->sbset($set);
		
	}
	public function indexAction(){
		$p=I('p')?I('p'):I('get.p');
		$p=$p?$p:1;
		$size=12;
		$get=I('get.');
		$map=array('distributor_id'=>session('org_id'));
		$params=array('page'=>$p,'pagesize'=>$size,'map'=>$map);
		
		if($get['status']>0){
			$params['status']=$get['status'];
		}
		
		$result=$this->activityService()->teamPagerList($params);
		
		$pager=new Pager($result['count'],$size);
		$this->assign('list',$result['list']);
		$this->assign('page',$pager->show());
		$this->assign('get',$get);
		$this->assign('now_time',time());
		
		$this->display();
	}
	
	public function addAction(){
		if(IS_POST){
			$post=I('post.');
			$post['distributor_id']=session('org_id');
			//$product_info=$this->distributorGoodsProductService()->getInfo($post['product_id']);
			//$post['product_name']=$product_info['product_name'];
			
			try{
				$this->activityService()->teamCreateOrModify($post);
			}catch(\Exception $e){
				$this->error($e->getMessage());
			}
			$this->success('添加成功',U('index'));
		}
		$this->display('edit');
	}
	
	public function editAction($id){
		$info=$this->activityService()->getTeam($id);
		if(empty($info)){
			$this->error('活动不存在');
		}
		//var_dump($info);die();
		if(IS_POST){
			//if($info['status']>0){
//				$this->error('请先关闭该活动');
//			}
			
			$post=I('post.');
			$post['distributor_id']=session('org_id');
			$post['act_id']=$id;
			//$product_info=$this->distributorGoodsProductService()->getInfo($post['product_id']);
			//$post['product_name']=$product_info['product_name'];
			
			try{
				$this->activityService()->teamCreateOrModify($post);
			}catch(\Exception $e){
				$this->error($e->getMessage());
			}
			$this->success('修改成功',U('index'));
		}
		
		$this->assign('info',$info);
		//$product_map=array('a.record_id'=>$info['goods_id'],'a.product_id'=>array('gt',0));
//		$product_list=$this->distributorGoodsService()->getGoodsProductList($product_map);
//		$this->assign('product_list',$product_list);
		
		//货品列表
		$record_id=$info['goods_id'];
		$params = array('map'=>array('record_id'=>$record_id));
		$product_list = $this->distributorGoodsProductService()->getAllList($params);
		$this->assign('product_list',$product_list);
		$this->assign('first_product',array_shift($product_list));
		
		$this->display();
	}
	public function delAction($id){
		$info=$this->activityService()->getTeam($id); 
		if(empty($info)){
			$this->error('活动不存在');
		}
		
		/*if($info['status']>0){
			$this->error('请先关闭该活动');
		}*/
		
		$map['act_id']=$id;
		try{
			$this->activityService()->teamDelete($id);
		}catch(\Exception $e){
			$this->error($e->getMessage());
		}
		$this->success('活动删除成功');
	}
	
	public function goods_listAction(){
    	$get = I('get.');
    	$this->assign('get', $get);
    	$size=8;
		
		//平台分类列表
    	$categorys = $this->goodsCatService()->getOptionList($get['cat_id']);
    	$this->assign('platform_categorys', $categorys);
		
    	//自定义分类列表
    	$map = array('distributor_id'=>$this->org_id);
    	$categorys = $this->distributorGoodsCatService()->getOptionList($map, $get['self_cat_id']);
    	$this->assign('distributor_categorys', $categorys);
		
    	//商品列表
    	$params = array(
    			'distributor_id'=>$this->org_id,
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$size,
    	);
    	if (!empty($get['keyword'])) {
    		$params['keyword'] = $get['keyword'];
    	}
    	if (!empty($get['cat_id'])) {
    		$params['cat_id'] = $get['cat_id'];
    	}
    	if (!empty($get['self_cat_id'])) {
    		$params['self_cat_id'] = $get['self_cat_id'];
    	}
    	$datas = $this->distributorGoodsService()->getPagerList($params);
    	$this->assign('list', $datas['list']);
		
		
    	$pager = new Pager($datas['count'], $size,array(),'goods_box');
		$page=str_replace("cs_show_modal",'',$pager->show());
    	$this->assign('pager', $page);
    	
    	$this->display('goods');
    }
	
	//修改活动状态
	public function change_statusAction($id){
		$info=$this->RechargeService()->getActivity($id);
		if(empty($info)){
			$this->error('活动不存在');
		}
		$params['status']=$info['status']>0?0:1;
		$params['activity_id']=$id;
		
		try{
			$this->RechargeService()->changeStatus($params);
		}catch(\Exception $e){
			$this->error($e->getMessage());
		}
		$this->AjaxReturn(array('info'=>'修改状态成功','status'=>0,'force_redirect_page'=>1,'url'=>U('index')));
	}
	
	//获取货品列表
	public function productAction(){
		//货品列表
		$record_id=I('record_id')?I('record_id'):I('get.record_id');
		$params = array('map'=>array('record_id'=>$record_id));
		$product_list = $this->distributorGoodsProductService()->getAllList($params);
		//$this->assign('product_list', $product_list);
		foreach($product_list as $key=>$val){
			//$str.="<option value='{$val[id]}' >{$val[product_name]}</option>";
			$str.="<option value='{$val[id]}' >{$val[product_items_name]}</option>";
		}
		$this->ajaxReturn(array('html'=>$str));
	}
	
	public function teamupAction(){
		$act_id=I('act_id')?I('act_id'):I('get.act_id');
		
		$act_info=$this->ActivityService()->getTeam($act_id);
		$this->assign('act_info',$act_info);
		
		$p=I('p')?I('p'):I('get.p');
		$p=$p?$p:1;
		$size=12;
		$map=array('distributor_id'=>session('org_id'));
		if($act_id!=''){
			$map['act_id']=$act_id;
		}
		$params=array('page'=>$p,'pagesize'=>$size,'map'=>$map);
		$result=$this->ActivityService()->teamPostPagerList($params);
		$pager=new Pager($result['count'],$size);
		$this->assign('list',$result['list']);
		$this->assign('page',$pager->show());
		
		$set = array(
			'in'=>'promotion',
			'ac'=>'activity_team_teamup',
		);
		$this->sbset($set);
		
		$this->display();
	}
	
	public function build_teamAction(){
		$params=array('user_id'=>5,'team_post_id'=>11);
		try{
			$result=$this->orderService()->createTeamOrder($params);
		}catch(\Exception $e){
			echo $e->getMessage();
		}
		
	}
	
	public function orderAction(){
		$get=I('get.');
		$this->assign('get',$get);
		
		$get_post_id=I('post_id')?I('post_id'):I('get.post_id');
		
		$post_info=$this->ActivityService()->getTeamPost($get_post_id);
		$this->assign('post_info',$post_info);
		
		$shipping_status=\Common\Model\OrderInfo::ShippingStatusLabel();
		$this->assign('shipping_status',$shipping_status);		
		
		$p=I('p')?I('p'):I('get.p');
		$p=$p?$p:1;
		$size=12;	
		$map=array('team_post_id'=>array('gt',0));
		if($get_post_id!=''){
			$map['team_post_id']=$get_post_id;
		}
		
		if($get['order_id']!=''){
			$map['order_id']=array('like',"%{$get[order_id]}%");
		}
		
		if($get['start_time']!=''){
			$start_time=strtotime($get['start_time']);
			$map['add_time'][]=array('gt',$start_time);
		}
		if($get['end_time']!=''){
			$end_time=strtotime($get['end_time']);
			$map['add_time'][]=array('lt',$end_time);
		}
		
		
		
		$params=array('order_type'=>'all','distributor_id'=>session('org_id'),'map'=>$map);
		$result=$this->orderService()->getOrderList($params,$p,$size);
		$list=$result['list'];
		foreach($list as $key=>$val){
			$post_id[]=$val['team_post_id'];
		}
		if(!empty($post_id)){
			$post_list=$this->ActivityService()->getTeamPostField(array('post_id'=>array('in',$post_id)),'post_id,member_num,price,joined_num,member_limit,closing_time');
			foreach($list as $key=>$val){
				$list[$key]['team']=$post_list[$val['team_post_id']];
			}	
		}
		
		$pager=new Pager($result['count'],$size);
		$this->assign('list',$list);
		$this->assign('page',$pager->show());
		
		$set = array(
			'in'=>'promotion',
			'ac'=>'activity_team_order',
		);
		$this->sbset($set);
		
		$this->display();
	}
	
	public function orderinfoAction($id){
		$gmap = $info = array();
		$this->existChk($gmap, $info, $id);
				
//		$map = array(
//			'id'=>array('in', array($info['province'], $info['city'], $info['district'])),
//		);
//		$regions = M('Region')->where($map)->getField('id,region_name');
//		$this->assign('regions', $regions);
		
		$list = M('OrderGoods')->where('order_id='.$info['order_id'])->select();
		$this->assign('list', $list);
		$this->assign('info', $info);
		
		
		$set = array(
			'in'=>'promotion',
			'ac'=>'activity_team_order',
		);
		$this->sbset($set);
		
		$this->display();		
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
	
	public function team_memberAction(){
		$post_id=I('post_id')?I('post_id'):I('get.post_id');
		$member_list=$this->activityService()->teamUserList($post_id);
		$this->assign('list',$member_list);
		$this->display();
	}
	
   	private function activityService(){
		return D('Activity','Service');
	}
	
	private function distributorGoodsService() {
		return D('Distributor\Goods', 'Service');
	}
	
	private function distributorGoodsCatService() {
		return D('Distributor\GoodsCat', 'Service');
	}
	
	
	private function distributorGoodsProductService() {
		return D('Distributor\GoodsProduct', 'Service');
	}
	
	private function goodsCatService() {
		return D('GoodsCat', 'Service');
	}
	
	
	
	
	
}