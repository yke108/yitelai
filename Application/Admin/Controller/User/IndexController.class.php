<?php
namespace Admin\Controller\User;
use Admin\Controller\FController;
use Common\Basic\Pager;
use Common\Basic\Status;

class IndexController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'user',
			'ac'=>'user_index_index',
		);
		$this->sbset($set);
		$sex=\Common\Basic\User::$sex;
		$this->assign('sex',$sex);
		$user_type=\Common\Basic\User::$user_type;
		$this->assign('user_type',$user_type);	
		$recharge_type=\Common\Basic\User::$recharge_type;
		$this->assign('recharge_type',$recharge_type);	
		
		//获取店铺列表
		$store_list=$this->distributorService()->getFieldData();
		$this->assign('store_list',$store_list);
		
		//获取等级列表
		$rank_list=$this->userRankService()->getFieldData();
		$this->assign('rank_list',$rank_list);
		
		//等级类型
		$user_type_list=array('1'=>'普通会员','2'=>'分销员','3'=>'业务员','4'=>'区域经理','5'=>'招商经理');
		$this->assign('user_type_list',$user_type_list);
	}
	
    public function indexAction(){
    	session('back_url', __SELF__);
    	
		$set = array(
			'in'=>'finance',
			'ac'=>'user_index_index',
		);
		$this->sbset($set);
		$this->displayList();
    }
    
    public function allAction(){
    	$set = array(
    			'in'=>'user',
    			'ac'=>'user_index_all',
    	);
    	$this->sbset($set);
    	$this->displayList();
    }
	
	public function salesmanAction(){
		session('back_url', __SELF__);
		
		$set = array(
			'in'=>'salesman',
			'ac'=>'user_index_salesman',
		);
		$this->sbset($set);
		$this->displayList(3);
	}
	
	public function displayList($user_type){
		$get = I('get.');
		$this->assign('get', $get);
		
		//等级列表
		$list = $this->userRankService()->getAllList();
		$rank_list = array();
		foreach ($list as $v) {
			$rank_list[$v['rank_id']] = $v;
		}
		$this->assign('rank_list', $rank_list);
		
		if($get['min_pay_points']){
			$map['pay_points'] = array('egt', trim($get['min_pay_points']));
		}
		if($get['max_pay_points']){
			$map['pay_points'] = array('elt', trim($get['max_pay_points']));
		}
		$size=12;
    	$params = array(
	    		'page'=>$get['p'],
				'pagesize'=>$size,
    			'map'=>$map,
    	);
    	if (!empty($get['nick_name'])) {
    		$params['nick_name'] = $get['nick_name'];
    	}
		if (!empty($get['keyword'])) {
    		$params['keyword'] = $get['keyword'];
    	}
		if (!empty($get['store_id'])) {
    		$params['store_id'] = $get['store_id'];
    	}
    	if (!empty($get['start_time'])) {
    		$params['start_time'] = $get['start_time'];
    	}
    	if (!empty($get['end_time'])) {
    		$params['end_time'] = $get['end_time'];
    	}
    	if (!empty($get['parent_id'])) {
    		$params['parent_id'] = $get['parent_id'];
    	}
		if($user_type>0){
			$params['user_type']=$user_type;
		}
		if($get['user_type']!=''){
			$params['user_type']=$get['user_type'];
		}
		if($get['rank']!=''){
			$params['rank_id']=$get['rank'];
		}
    	$result = $this->userService()->userPagerList($params);
    	if ($result['count'] > 0){
			//$list=node_merge($result['list'],0,1,'user_id');
			$this->assign('list', $result['list']);
    		$pager = new Pager($result['count'], $size);
    		$this->assign('pager', $pager->show());
    	}
    	
		if ($user_type == Status::UserTypeSalesman) {
			$this->display('salesman');
		}else {
			$this->display('index');
		}
	}
    
    public function buyusersAction($distributor_id = 0){
    	$get = I('get.');
    	$this->assign('get', $get);
    	 
    	$size=12;
    	$params = array(
    			'distributor_id'=>$distributor_id,
    			'page'=>$get['p'],
    			'pagesize'=>$size,
    	);
    	
    	$result = $this->userService()->buyUsersPagerList($params);
    	if ($result['count'] > 0){
    		$this->assign('list', $result['list']);
    		$pager = new Pager($result['count'], $size);
    		$this->assign('pager', $pager->show());
    	}
    	
    	//等级列表
    	$list = $this->userRankService()->getAllList();
    	$rank_list = array();
    	foreach ($list as $v) {
    		$rank_list[$v['rank_id']] = $v;
    	}
    	$this->assign('rank_list', $rank_list);
    	
    	$this->display('index');
    }
	
    public function listAction($keyword = ''){
    	$map['mobile|nick_name'] = array('like', '%'.$keyword.'%');
    	$params = array(
    			'_map'=>$map
    	);
    	$datas = $this->userService()->getList($params);
    
    	foreach ($datas['list'] as $v) {
    		$suggestions[]= array('title' => $v['mobile']);
    	}
    
    	echo json_encode(array('data' => $suggestions));
    }
    
	public function addAction(){
		if(IS_POST){
			$post = I('post.');
			try {
				$result = $this->userService()->userCreateOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功', U('index'));
		}
		
		//所有品牌
		$brand_list = $this->goodsBrandService()->getAllList();
		$this->assign('brand_list', $brand_list);
		
		//店铺
		$distributor_list = $this->distributorService()->getAllList();
		$this->assign('distributor_list', $distributor_list);
		
		$this->display('edit');
	}
	
	public function editAction($id = 0){
		$info = $this->userService()->getUserInfo($id);
		if(empty($info)) $this->error('用户不存在');
		
		if(IS_POST){
			$params = I('post.');
			$params['user_id'] = $info['user_id'];
			try {
				$result = $this->userService()->userCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功', session('back_url'));
		}
		
		$this->assign('info', $info);
		
		//所有品牌
		$brand_list = $this->goodsBrandService()->getAllList();
		$this->assign('brand_list', $brand_list);
		
		//店铺
		$distributor_list = $this->distributorService()->getAllList();
		$this->assign('distributor_list', $distributor_list);
		
		$this->display();
	}
	
	public function delAction($id = 0){
		$info = $this->userService()->getUserInfo($id);
		if(empty($info)) $this->error('用户不存在');
		
		try {
			$params = array(
				'user_id'=>$info['user_id'],
			);
			$result = $this->userService()->UserDelete($params);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功');
	}
	
	public function recharge_revalue_logAction(){
		$p=I('p')?I('p'):I('get.p');
		$p=$p?$p:1;
		$size=10;
		$user_id=I('user_id')?I('user_id'):I('get.user_id');
		$map=array('user_id'=>$user_id,'activity_id'=>array('gt',0));
		
		$start_time=I('start_time')?I('start_time'):I('get.start_time');
		if($start_time!=''){
			$start_time=strtotime(date("Y-m-d 00:00:00",strtotime($start_time)));
			$map['add_time'][]=array('gt',$start_time);
		}
		
		$end_time=I('end_time')?I('end_time'):I('get.end_time');
		if($end_time!=''){
			$end_time=strtotime(date("Y-m-d 23:59:59",strtotime($end_time)));
			$map['add_time'][]=array('lt',$end_time);
		}
		//var_dump($map);die();
		
		$user_info=$this->userService()->getUser(array('user_id'=>$user_id));
		$this->assign('user_info',$user_info);
		
		$params=array('map'=>$map,'page'=>$p,'pagesize'=>$size);
		$result=$this->rechargeService()->infoPagerList($params);	
		$pager=new Pager($result['count'],$size,array(),'recharege_box');
		$this->assign('list',$result['list']);
		$this->assign('pager',$pager->show());
		$this->assign('get',$_GET);
		$this->display();
	}
	
	public function recharge_logAction(){
		$p=I('p')?I('p'):I('get.p');
		$p=$p?$p:1;
		$size=12;
		$user_id=I('user_id')?I('user_id'):I('get.user_id');
		$map=array('user_id'=>$user_id,'activity_id'=>0);
		
		$keyword=I('keyword')?I('keyword'):I('get.keyword');
		if($keyword!=''){
			$user_map=array('nick_name'=>array('like',"%{$keyword}%"));
			$user_id=$this->userService()->getFieldData($user_map,'user_id',true);
			if(!empty($user_id)){
				$map=array('user_id'=>array('in',$user_id));
			}
		}
		
		$start_time=I('start_time')?I('start_time'):I('get.start_time');
		if($start_time!=''){
			$start_time=strtotime(date("Y-m-d 00:00:00",strtotime($start_time)));
			$map['add_time'][]=array('gt',$start_time);
		}
		
		$end_time=I('end_time')?I('end_time'):I('get.end_time');
		if($end_time!=''){
			$end_time=strtotime(date("Y-m-d 23:59:59",strtotime($end_time)));
			$map['add_time'][]=array('lt',$end_time);
		}
		
		
		$user_info=$this->userService()->getUser(array('user_id'=>$user_id));
		$this->assign('user_info',$user_info);
		
		$params=array('map'=>$map,'page'=>$p,'pagesize'=>$size);
		$result=$this->rechargeService()->infoPagerList($params);	
		$pager=new Pager($result['count'],$size,array(),'recharege_box');
		$this->assign('list',$result['list']);
		$this->assign('pager',$pager->show());
		$this->assign('get',$_GET);
		$this->display();
	}
	
	public function up_memberAction(){
		$get = I('get.');
		$this->assign('get', $get);
		$user_id=I('user_id')?I('user_id'):I('get.user_id');
		$size=12;
    	$params = array(
						'page'=>$get['p'],
						'pagesize'=>$size,
						'parent_id'=>$user_id,
						);		
		$result=$this->userService()->userPagerList($params);
		$pager=new Pager($result['count'],$size);
		$this->assign('list',$result['list']);
		$this->assign('pager',$pager->show());
		
		$this->display();
	}
	
	public function changepointAction(){
		if(IS_POST){
			$params = I('post.');
			try {
				$result = $this->userService()->changePoint($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功', session('back_url'));
		}
		
		$get = I('get.');
		$this->assign('get', $get);
		
		$this->display();
	}
	
	public function designateAction() {
		if(IS_POST){
			$post = I('post.');
			
			$user = $this->userService()->getUserInfo($post['user_id']);
			if (empty($user)) $this->success('用户不能为空');
			
			$data = array(
					'distributor_id'=>$post['distributor_id'],
					'designate_remark'=>$post['designate_remark'],
			);
			try {
				$result = $this->userService()->modify($user, $data);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('指派成功', session('back_url'));
		}
		
		$get = I('get.');
		$this->assign('get', $get);
		
		$set = array(
				'in'=>'user',
				'ac'=>'user_index_all',
		);
		$this->sbset($set);
		$this->display();
	}
	
	public function distributorsAction(){
		layout('Layout/sel');
		
		$p=I('p')?I('p'):I('get.p');
		$size=12;
		$map=array();
		$params=array('map'=>$map,'pagesize'=>$size,'page'=>$p);
		$result=$this->distributorService()->getPagerList($params);
		$pager=new Pager($result['count'],$size);
		$this->assign('list',$result['list']);
		$this->assign('pager',$pager->show());
		
		$this->display();
	}
	
	private function rechargeService(){
		return D('Recharge','Service');
	}
	
	private function userRankService(){
		return D('UserRank','Service');
	}
	
	private function distributorService(){
		return D('Distributor','Service');
	}
	
	private function goodsBrandService(){
		return D('GoodsBrand','Service');
	}
}