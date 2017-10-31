<?php
namespace Wap\Controller\User;
use Wap\Controller\WapController;

class RecommendController extends WapController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
		
		$this->assign('page_title', '个人中心');
    }
	
    public function indexAction(){
    	$pagesize = 12;
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$pagesize,
    			'user_id'=>$this->user['user_id']
    	);
    	$datas = $this->userService()->juniorListNew($params);
    	$this->assign('list', $datas['list']);
    	
    	if(IS_AJAX){
    		if(empty($datas['list'])){
    			$clist = '';
    		}else{
    			$clist = $this->renderPartial('_index');
    		}
    		die($clist);
    	}
		
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
    
    public function commissionAction(){
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'user_id'=>$this->user['user_id'],
    			'change_type'=>11
    	);
    	$datas = $this->userAccountService()->getPagerList($params);
    	$this->assign('list', $datas['list']);
		$this->assign('ref_users',$datas['ref_users']);
		
		$commission_amount=$this->userAccountService()->getCommissionAmount($params);
		$this->assign('commission_amount',$commission_amount);
    	
    	if(IS_AJAX){
    		if(empty($datas['list'])){
    			$clist = '';
    		}else{
    			$clist = $this->renderPartial('_commission');
    		}
    		die($clist);
    	}
    	
    	$this->display();
    }
    
    public function pointAction(){
    	$map = array(
    			'a.user_id'=>$this->user['user_id']
    	);
    	$pagesize = 5;
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$pagesize,
    			'map'=>$map
    	);
    	$datas = $this->pointService()->pointPagerList($params);
    	$this->assign('list', $datas['list']);
    	
    	if(IS_AJAX){
    		if(empty($datas['list'])){
    			$clist = '';
    		}else{
    			$clist = $this->renderPartial('_point');
    		}
    		die($clist);
    	}
    		
    	$this->display();
    }
    
    public function ordersAction($user_id = 0) {
		$this->assign('user_id',$user_id);
    	//判断下级会员是否存在
    	/* $map = array(
    			'user_id'=>$user_id,
    			'parent_id'=>$this->user['user_id']
    	);
    	$user_info = $this->userService()->getUser($map);
    	if (empty($user_info)) {
    		$this->error('会员不存在');
    	} */
    	 
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
    	$page = I('p')?I('p'):I('get.p');
		$page=$page?$page:1;
    	$pagesize = 10;
    	$params = array(
    			'map'=>$map,
    			'page'=>$page,
    			'pagesize'=>$pagesize,
				'_needCommision'=>1,
    	);
    	$datas = $this->orderService()->getOrderList($params,$page,$pagesize);
    	$this->assign('list', $datas['list']);
    	
		
		//分利总金额
		$commission_params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'user_id'=>$this->user['user_id'],
				'ref_user_id'=>$user_id,
    			'change_type'=>11
    	);
		$commission_amount=$this->userAccountService()->getCommissionAmount($commission_params);
		$this->assign('commission_amount',$commission_amount);
		
    	if(IS_AJAX){
    		if(empty($datas['list'])){
    			$clist = '';
    		}else{
    			$clist = $this->renderPartial('_orders');
    		}
    		die($clist);
    	}
    	 
    	$this->display();
    }
    
    private function userAccountService(){
    	return D('UserAccount', 'Service');
    }
    
    private function pointService(){
    	return D('Point', 'Service');
    }
    
    private function orderService(){
    	return D('Order', 'Service');
    }
    
    private function distributorService(){
    	return D('Distributor', 'Service');
    }
}
