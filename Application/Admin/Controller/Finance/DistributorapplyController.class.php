<?php
namespace Admin\Controller\Finance;
use Admin\Controller\FController;
use Common\Basic\Pager;
use Common\Basic\Status;

class DistributorapplyController extends FController {
	public function _initialize(){
		$this->purviewCheck(false);
		parent::_initialize();
		
		$set = array(
			'in'=>'finance',
			'ac'=>'finance_distributorapply_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	session('back_url', __SELF__);
    	$map=array();
    	$this->conditionAction($map);
		$this->display();
    }
	
	//提现待审核列表
	public function waitAction(){
		session('back_url', __SELF__);
		$this->conditionAction(0);
		$set['ac'] ='finance_cashapply_wait';
		$this->sbset($set);
		$this->display('index');
	}
	
	//提现已审核列表
	public function alreadyAction(){
		session('back_url', __SELF__);
		$this->conditionAction(2);
		$set['ac'] ='finance_cashapply_already';
		$this->sbset($set);
		$this->display('index');
	}
	
	public function conditionAction($status=''){
		$get = I('get.');
		$get['status'] = $get['status'] ? $get['status'] : 'all';
    	$this->assign('get', $get);
		
    	$page = intval(I('p')) ? intval(I('p')) : 1;
    	$params = array(
    			'page'=>$page,
    			'pagesize'=>$this->pagesize,
    	);
    	if ($get['keyword']) {
    		$params['keyword'] = $get['keyword'];
    	}
    	if ($get['status'] != 'all') {
    		$params['status'] = intval($get['status']);
    	}
    	if ($get['start_time']) {
    		$params['start_time'] = $get['start_time'];
    	}
    	if ($get['end_time']) {
    		$params['end_time'] = $get['end_time'];
    	}
    	$datas = $this->distributorCashApplyService()->getPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $this->pagesize);
    	$this->assign('pager', $pager->show());
	}
	
    public function checkAction($apply_id = 0){
    	$info = $this->distributorCashApplyService()->getInfo($apply_id);
    	if (empty($info)) {
    		$this->error('数据不存在');
    	}
    	$this->assign('info', $info);
    	
    	if (IS_POST) {
    		$post = I('post.');
    		
    		if (empty($post['status'])) {
    			$this->error('请选择是否通过审核');
    		}
    		if ($post['status'] == 1 && $post['remark'] == '') {
    			$this->error('意见反馈不能为空');
    		}
    		
    		try {
    			$this->distributorCashApplyService()->check($post);
    			$this->success('审核成功', session('back_url'));
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    	}
    	
    	$this->display();
    }
	
	public function remitAction(){
		$apply_id=I('apply_id')?I('apply_id'):I('get.apply_id');
		$params['apply_id']=$apply_id;
		$params['status']=Status::CashRemit;
		try{
			$this->distributorCashApplyService()->remitMoney($params);
		}catch(\Exception $e){
			$this->error($e->getMessage());
		}	
		$this->success('打款成功', session('back_url'));
	}
    
    private function distributorCashApplyService() {
    	return D('Distributor\CashApply', 'Service');
    }
}