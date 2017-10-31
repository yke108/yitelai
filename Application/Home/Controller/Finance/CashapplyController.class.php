<?php
namespace Home\Controller\Finance;
use Home\Controller\BaseController;
use Common\Basic\Status;

class CashapplyController extends BaseController {	
	public function _initialize(){
		parent::_initialize();
    }
    
    protected function _purviewCheck(){
    	$this->purviewCheck('index');
    }
	
    public function indexAction() { /*提现审核 */
    	$get = I('get.');
    	$get['type'] = $get['type'] ? $get['type'] : 1;
    	if ($get['city']) {
    		$get['region_name'] = $this->regionService()->getRegionNameCity($get['city']);
    	}
    	$this->assign('get', $get);
    	
    	//待审核
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'status'=>Status::CashWait,
    			'user_type'=>Status::UserTypeDistributorman,
    			'orderby'=>'update_time DESC',
    	);
    	if (I('keyword')) {
    		$params['keyword'] = I('keyword');
    	}
    	if (I('city')) {
    		$params['city'] = I('city');
    	}
    	if (I('store_id')) {
    		$params['store_id'] = I('store_id');
    	}
    	$result = $this->cashApplyService()->getPagerList($params);
    	$this->assign('wait_list', $result['list']);
    	
    	//已审核
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'status'=>Status::CashAgree,
    			'user_type'=>Status::UserTypeDistributorman,
    			'orderby'=>'update_time DESC',
    	);
    	if (I('keyword')) {
    		$params['keyword'] = I('keyword');
    	}
    	if (I('city')) {
    		$params['city'] = I('city');
    	}
    	if (I('store_id')) {
    		$params['store_id'] = I('store_id');
    	}
    	$result = $this->cashApplyService()->getPagerList($params);
    	$this->assign('agree_list', $result['list']);
    	
    	//审核不通过
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'status'=>Status::CashNotAgree,
    			'user_type'=>Status::UserTypeDistributorman,
    			'orderby'=>'update_time DESC',
    	);
    	if (I('keyword')) {
    		$params['keyword'] = I('keyword');
    	}
    	if (I('city')) {
    		$params['city'] = I('city');
    	}
    	if (I('store_id')) {
    		$params['store_id'] = I('store_id');
    	}
    	$result = $this->cashApplyService()->getPagerList($params);
    	$this->assign('notagree_list', $result['list']);
    	
    	//店铺筛选
    	if (session('sys_id') == Status::SysIdPlatform) {
    		$map = array('status'=>Status::DistributorStatusNormal);
    		$distributor_list = $this->distributorService()->getAllList($map);
    		$this->assign('distributor_list', $distributor_list);
    	}
    	
    	$this->assign('page_title', '提现审核');
		$this->display();
    }
    
    public function listAction() {
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'user_type'=>Status::UserTypeDistributorman,
    			'orderby'=>'update_time DESC',
    	);
    	if (I('type') == 1) {
    		$params['status'] = Status::CashWait;
    	}
    	if (I('type') == 2) {
    		$params['status'] = Status::CashAgree;
    	}
    	if (I('type') == 3) {
    		$params['status'] = Status::CashNotAgree;
    	}
    	if (I('keyword')) {
    		$params['keyword'] = I('keyword');
    	}
    	if (I('city')) {
    		$params['city'] = I('city');
    	}
    	if (I('store_id')) {
    		$params['store_id'] = I('store_id');
    	}
    	$result = $this->cashApplyService()->getPagerList($params);
    	$this->assign('list', $result['list']);
    	
    	if(empty($result['list'])){
    		$clist = '';
    	}else{
    		$clist = $this->renderPartial('_index');
    	}
    	$this->ajaxReturn(array('html'=>$clist, 'p'=>$params['page']+1));
    }
    
    public function infoAction($apply_id = 0) {
    	$info = $this->cashApplyService()->getInfo($apply_id);
    	if (empty($info)) $this->error('提现数据不存在');
    	$this->assign('info', $info);
    	
    	//用户
    	$user = $this->userService()->getUserInfo($info['user_id']);
    	//if (empty($user)) $this->error('用户不存在');
    	$this->assign('user', $user);
    	
    	//分利明细
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'user_id'=>$info['user_id'],
    			'change_type'=>Status::ChangeTypeCommission,
    	);
    	$list = $this->userAccountService()->getAllList($params);
    	$this->assign('list', $list);
    	
    	//合计
    	$params = array(
    			'user_id'=>$info['user_id'],
    			'change_type'=>Status::ChangeTypeCommission,
    	);
    	$total_amount = $this->userAccountService()->getTotalAmount($params);
    	$this->assign('total_amount', $total_amount);
    	
    	$this->assign('page_title', '提现审核-详情');
    	$this->display();
    }
    
    public function apply_listAction() {
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    	);
    	if (I('user_id')) {
    		$params['user_id'] = I('user_id');
    	}
    	$result = $this->cashApplyService()->getPagerList($params);
    	$this->assign('list', $result['list']);
    	$this->assign('count', $result['count']);
    	
    	if (IS_AJAX) {
    		if(empty($result['list'])){
    			$clist = '';
    		}else{
    			$clist = $this->renderPartial('_apply_list');
    		}
    		$this->ajaxReturn(array('html'=>$clist, 'p'=>$params['page']+1));
    	}
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$this->display();
    }
    
    public function checkAction() {
    	$params = array(
    			'apply_id'=>I('get.apply_id'),
    			'status'=>I('get.status'),
    	);
    	try {
    		$this->cashApplyService()->check($params);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	$this->success('审核成功');
    }
    
    public function remitAction($apply_id = 0) {
    	$info = $this->cashApplyService()->getInfo($apply_id);
    	if (empty($info)) $this->error('数据不存在');
    	
    	if (IS_POST) {
    		$post = I('post.');
    		
    		if ($post['certify']) {
    			$image_datas = createBase64Image($post['certify']);
    			$post['certify'] = $image_datas ? implode(',', $image_datas) : '';
    		}
    		
    		try{
    			$this->cashApplyService()->remitMoney($post);
    		}catch(\Exception $e){
    			$this->error($e->getMessage());
    		}
    		$this->success('打款成功', U('index'));
    	}
    	
    	$this->assign('info', $info);
    	
    	$this->assign('page_title', '提现审核-填写发放状态');
    	$this->display();
    }
    
    private function cashApplyService() {
    	return D('CashApply', 'Service');
    }
    
    private function userAccountService() {
    	return D('UserAccount', 'Service');
    }
    
    protected function distributorService() {
    	return D ( 'Distributor', 'Service' );
    }
    
    protected function regionService() {
    	return D ( 'Region', 'Service' );
    }
}