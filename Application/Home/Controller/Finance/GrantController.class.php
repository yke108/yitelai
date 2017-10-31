<?php
namespace Home\Controller\Finance;
use Home\Controller\BaseController;
use Common\Basic\Status;

class GrantController extends BaseController {	
	public function _initialize(){
		parent::_initialize();
    }
    
    protected function _purviewCheck(){
    	$this->purviewCheck('index');
    }
	
    public function indexAction() { /*佣金发放 */
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	//待发放
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'status'=>Status::CashAgree,
    			'orderby'=>'update_time DESC',
    	);
    	if (I('keyword')) {
    		$params['keyword'] = I('keyword');
    	}
    	if (I('start_time')) {
    		$params['start_time'] = I('start_time');
    	}
    	if (I('end_time')) {
    		$params['end_time'] = I('end_time');
    	}
    	$result = $this->cashApplyService()->getPagerList($params);
    	$this->assign('wait_list', $result['list']);
    	
    	//已发放
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'status'=>Status::CashRemit,
    			'orderby'=>'update_time DESC',
    	);
    	if (I('keyword')) {
    		$params['keyword'] = I('keyword');
    	}
    	if (I('start_time')) {
    		$params['start_time'] = I('start_time');
    	}
    	if (I('end_time')) {
    		$params['end_time'] = I('end_time');
    	}
    	$result = $this->cashApplyService()->getPagerList($params);
    	$this->assign('agree_list', $result['list']);
    	
    	$this->assign('page_title', '提现审核');
		$this->display();
    }
    
    public function listAction() {
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'orderby'=>'update_time DESC',
    	);
    	if (I('type') == 1) {
    		$map['a.status'] = Status::CashAgree;
    		$params['map'] = $map;
    	}
    	if (I('type') == 2) {
    		$map['a.status'] = Status::CashRemit;
    		$params['map'] = $map;
    	}
    	if (I('keyword')) {
    		$params['keyword'] = I('keyword');
    	}
    	if (I('start_time')) {
    		$params['start_time'] = I('start_time');
    	}
    	if (I('end_time')) {
    		$params['end_time'] = I('end_time');
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
    			'change_type'=>Status::ChangeTypeCommission,
    	);
    	$result = $this->userAccountService()->getPagerList($params);
    	$this->assign('list', $result['list']);
    	
    	$params = array('user_id'=>$info['user_id']);
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
}