<?php
namespace Admin\Controller\Distributorman;
use Admin\Controller\FController;
use Common\Basic\Pager;

class IndexController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'distributorman',
			'ac'=>'distributorman_index_index',
		);
		$this->sbset($set);
		$sex=\Common\Basic\User::$sex;
		$this->assign('sex',$sex);
		$user_type=\Common\Basic\User::$user_type;
		$this->assign('user_type',$user_type);	
		
		//获取店铺列表
		$store_list=$this->distributorService()->getFieldData();
		$this->assign('store_list',$store_list);
		
	}
	
    public function indexAction(){
    	session('back_url', __SELF__);
		$this->listDisplayAction();
    }
    
    public function nocheckAction(){
    	session('back_url', __SELF__);
    	$this->sbset('distributorman_index_nocheck');
    	$map = array(
    			'_string'=>"(type=1 and status=1) or (type=2 and status=1)",
    	);
    	$this->listDisplayAction($map);
    }
    
    public function passAction(){
    	session('back_url', __SELF__);
    	$this->sbset('distributorman_index_pass');
    	$map = array(
    			'status'=>3
    	);
    	$this->listDisplayAction($map);
    }
    
    public function nopassAction(){
    	session('back_url', __SELF__);
    	$this->sbset('distributorman_index_nopass');
    	$map = array(
    			'status'=>4
    	);
    	$this->listDisplayAction($map);
    }
    
    private function listDisplayAction($map = array()){
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	//品牌
    	$brand_list = $this->goodsBrandService()->getAllList();
    	$this->assign('brand_list', $brand_list);
    	
    	$size=12;
    	$params = array(
    			'page'=>$get['p'],
    			'pagesize'=>$size,
				//'keyword'=>$get['keyword'],
    			'map'=>$map
    	);
    	if (!empty($get['keyword'])) {
    		$params['keyword'] = $get['keyword'];
    	}
    	if (!empty($get['distributor_id'])) {
    		$params['distributor_id'] = $get['distributor_id'];
    	}
    	if (!empty($get['brand'])) {
    		$params['brand'] = $get['brand'];
    	}
    	if (!empty($get['start_time'])) {
    		$params['start_time'] = $get['start_time'];
    	}
    	if (!empty($get['end_time'])) {
    		$params['end_time'] = $get['end_time'];
    	}
    	$result = $this->SalemanService()->infoPagerList($params);
    	if ($result['count'] > 0){
    		//$list=node_merge($result['list'],0,1,'user_id');
    		$this->assign('list', $result['list']);
			$this->assign('users', $result['users']);
    		$pager = new Pager($result['count'], $size);
    		$this->assign('pager', $pager->show());
    	}
    	
    	$this->display('index');
    }
	
	public function checkAction($apply_id = 0){
		$apply_info=$this->salemanService()->getInfo($apply_id);
		if(empty($apply_info)){
			$this->error('申请记录不存在');
		}
		$this->assign('apply_info',$apply_info);
		$user_id=$apply_info['user_id'];
		
		$map = array(
				'user_id'=>$user_id,
		);
		$user = $this->userService()->getUser($map);
		if(empty($user)) $this->error('用户不存在');
		$this->assign('info', $user);
		
		if($apply_info['type']==1){
			$distributor_list=$this->distributorService()->getFieldData();
			$this->assign('distributor_list',$distributor_list);
		}
		
		if(IS_POST){
			$post = I('post.');
			if ($user['status'] > 2) {
				$this->error('分销员已审核');
			}
			if (!in_array($post['status'], array(3,4))) {
				$this->error('请选择审核状态');
			}
			$data = array(
					'status'=>$post['status'],
					'feedback'=>$post['status']==3?'':$post['feedback'],
					'apply_id'=>$post['apply_id'],
					'distributor_id'=>$post['distributor_id'],
			);
			
			try {
				$result = $this->SalemanService()->check($data);
				$this->success('审核成功', session('back_url'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		
		$this->display();
	}
	
	public function commissionAction(){
		$this->sbset('distributorman_index_commission');
		$map = array('change_type'=>11);
		
		//店铺
		$distributor_list = $this->distributorService()->getAllList();
		$this->assign('distributor_list', $distributor_list);
		
		$get = I('get.');
		$this->assign('get', $get);
		
		if ($get['user_id']) {
			$user = $this->userService()->getUserInfo($get['user_id']);
			if (empty($user)) {
				$this->error('用户不存在');
			}
			$this->assign('user', $user);
		}
		
		$page = intval(I('p')) ? intval(I('p')) : 1;
		$params = array(
				'page'=>$page,
				'pagesize'=>$this->pagesize,
				'map'=>$map
		);
		if (!empty($get['nick_name'])) {
			$params['nick_name'] = $get['nick_name'];
		}
		if (!empty($get['ref_id'])) {
			$params['ref_id'] = $get['ref_id'];
		}
		if (!empty($get['mobile'])) {
			$params['mobile'] = $get['mobile'];
		}
		if (!empty($get['order_id'])) {
			$params['ref_id'] = $get['order_id'];
		}
		if (!empty($get['distributor_id'])) {
			$params['distributor_id'] = $get['distributor_id'];
		}
		if (!empty($get['change_type'])) {
			$params['change_type'] = $get['change_type'];
		}
		if (!empty($get['start_time'])) {
			$params['start_time'] = $get['start_time'];
		}
		if (!empty($get['end_time'])) {
			$params['end_time'] = $get['end_time'];
		}
		$datas = $this->UserAccountService()->getPagerList($params);
		$this->assign('list', $datas['list']);
		$this->assign('orders', $datas['orders']);
		$pager = new Pager($datas['count'], $this->pagesize);
		$this->assign('pager', $pager->show());
		
		$ctypes = $this->UserAccountService()->getCtypes();
		unset($ctypes['11']);
		$this->assign('ctypes', $ctypes);
		 
		//分利总金额
		$total_amount_change = $this->UserAccountService()->getCommissionAmount($params);
		$this->assign('total_amount_change', $total_amount_change);
		
		//订单总金额
		$all_order_amount=$this->UserAccountService()->getOrderAmount();
		$this->assign('all_order_amount',$all_order_amount);
		//die($all_order_amount);
		$this->display();
	}
	
	private function distributorService(){
		return D('Distributor','Service');
	}
	
	private function SalemanService(){
		return D('Saleman','Service');
	}
	
	private function goodsBrandService(){
		return D('GoodsBrand','Service');
	}
	
	private function UserAccountService() {
		return D('UserAccount', 'Service');
	}
}