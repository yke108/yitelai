<?php
namespace Distributor\Controller\Finance;
use Distributor\Controller\FController;
use Common\Basic\Pager;

class IndexController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'finance',
			'ac'=>'finance_index_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	$map = array('change_type'=>array('neq', 11));
    	$this->listDislay($map);
    }
    
    public function commissionAction(){
    	$this->sbset('finance_index_commission');
    	$map = array('change_type'=>11);
    	$this->listDislay($map);
    }
    
    private function listDislay($map = array()){
    	//店铺
    	//$distributor_list = $this->distributorService()->getAllList();
    	//$this->assign('distributor_list', $distributor_list);
    	
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
    			'distributor_id'=>$this->org_id,
    			'page'=>$page,
    			'pagesize'=>$this->pagesize,
    			'map'=>$map
    	);
    	if (!empty($get['nick_name'])) {
    		$params['nick_name'] = $get['nick_name'];
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
    	$pager = new Pager($datas['count'], $this->pagesize);
    	$this->assign('pager', $pager->show());
    	
    	/* $ctypes = $this->UserAccountService()->getCtypes();
    	unset($ctypes['11']);
    	$this->assign('ctypes', $ctypes); */
    	
    	//分利总金额
    	$total_amount_change = $this->UserAccountService()->getCommissionAmount($params);
    	$this->assign('total_amount_change', $total_amount_change);
    	 
    	$this->display();
    }
    
    private function UserAccountService() {
    	return D('UserAccount', 'Service');
    }
    
    private function UserAccountDao() {
    	return D('Common/User/UserAccount');
    }
}