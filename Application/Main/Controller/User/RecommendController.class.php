<?php
namespace Main\Controller\User;
use Main\Controller\MainController;
use Common\Basic\Pager;

class RecommendController extends MainController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
		
		$this->assign('page_title', '个人中心');
    }
	
    public function indexAction(){
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	//店铺列表
//     	$map = array('status'=>2);
//     	$distributor_list = $this->distributorService()->getAllList($map);
//     	$this->assign('distributor_list', $distributor_list);
    	
    	//下级会员列表
    	$pagesize = 6;
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$pagesize,
    			'user_id'=>$this->user['user_id']
    	);
    	$map = array();
    	if (!empty($get['nick_name'])) {
    		$map['nick_name'] = array('like', '%'.$get['nick_name'].'%');
    	}
    	if (!empty($get['store_id'])) {
    		$map['distributor_id'] = $get['store_id'];
    	}
    	if(!empty($get['start_time'])){
    		$map['reg_time'][] = array('egt', strtotime($get['start_time']));
    	}
    	if(!empty($get['end_time'])){
    		$map['reg_time'][] = array('elt', strtotime($get['end_time']) + 86400);
    	}
    	$params['map'] = $map;
    	$datas = $this->userService()->juniorListNew($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $pagesize);
    	$this->assign('pages', $pager->show_pc());
		
		$distributor_info=$this->distributorService()->getInfo($this->user['distributor_id']);
		$this->assign('distributor_info',$distributor_info);
		
		//上级业务员
		$salesman = $this->userService()->getUserInfo($this->user['parent_id']);
		$this->assign('salesman', $salesman);
		
		//分销商设置
		$distributor_config = $this->distributorConfigService()->findConfigs('system', $distributor_info['distributor_id']);
		$this->assign('distributor_config', $distributor_config);
    	
		$this->display();
    }
    
    public function ordersAction($user_id = 0) {
    	//判断下级会员是否存在
    	$map = array(
    			'user_id'=>$user_id,
    			'parent_id'=>$this->user['user_id']
    	);
    	$user_info = $this->userService()->getUser($map);
    	if (empty($user_info)) {
    		$this->error('会员不存在');
    	}
    	
    	//下级会员订单商品总金额和订单数
    	$map = array('user_id'=>$user_id);
    	$params = array(
    			'map'=>$map,
    	);
    	$goods_amount = $this->orderService()->getOrderGoodsAmount($params);
    	$this->assign('goods_amount', $goods_amount);
    	
    	$order_number = $this->orderService()->getOrdersCount($map);
    	$this->assign('order_number', $order_number);
    	
    	//下级会员订单列表
    	$page = intval(I('p')) > 0 ? intval(I('p')) : 1;
    	$pagesize = 6;
    	$params = array(
    			'map'=>$map,
    			'page'=>$page,
    			'pagesize'=>$pagesize
    	);
    	$datas = $this->orderService()->getOrderList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $pagesize);
    	$this->assign('pages', $pager->show_pc());
    	
    	$this->display();
    }
    
    public function commissionAction($user_id = 0){
    	$pagesize = 6;
    	$params = array(
    			'user_id'=>$this->user['user_id'],
    			'change_type'=>11,
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$pagesize
    	);
    	if ($user_id > 0) {
    		$params['ref_user_id'] = $user_id;
    	}
    	$datas = $this->userAccountService()->getPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $pagesize);
    	$this->assign('pages', $pager->show_pc());
    	
    	$this->display();
    }
    
    public function applyAction() {
    	$this->display();
    }
    
    private function orderService(){
    	return D('Order', 'Service');
    }
    
    private function distributorService(){
    	return D('Distributor', 'Service');
    }
    
    private function userAccountService(){
    	return D('UserAccount', 'Service');
    }
    
    private function distributorConfigService(){
    	return D('Distributor\Config', 'Service');
    }
}
