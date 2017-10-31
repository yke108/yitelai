<?php
namespace Admin\Controller\Finance;
use Admin\Controller\FController;
use Common\Basic\Pager;
use Common\Basic\Status;

class CashapplyController extends FController {
	public function _initialize(){
		$this->purviewCheck(false);
		parent::_initialize();
		
		$set = array(
			'in'=>'finance',
			'ac'=>'finance_cashapply_index',
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
	
	public function conditionAction($status = ''){
		$get = I('get.');
    	$this->assign('get', $get);
		
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    	);
    	if ($status) {
    		$params['status'] = $status;
    	}
    	if ($get['keyword']) {
    		$params['keyword'] = $get['keyword'];
    	}
    	if ($get['start_time']) {
    		$params['start_time'] = $get['start_time'];
    	}
    	if ($get['end_time']) {
    		$params['end_time'] = $get['end_time'];
    	}
    	$datas = $this->cashApplyService()->getPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $this->pagesize);
    	$this->assign('pager', $pager->show());
	}
	
    public function checkAction($apply_id = 0){
    	$info = $this->cashApplyService()->getInfo($apply_id);
    	if (empty($info)) $this->error('数据不存在');
    	
    	if (IS_POST) {
    		$post = I('post.');
    		try {
    			$this->cashApplyService()->check($post);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		$this->success('审核成功', session('back_url'));
    	}
    	
    	$this->assign('info', $info);
    	
    	$this->display();
    }
	
	public function remitAction(){
		$apply_id = I('apply_id')?I('apply_id'):I('get.apply_id');
		$params['apply_id'] = $apply_id;
		$params['status'] = Status::CashRemit;
		
		try{
			$this->cashApplyService()->remitMoney($params);
		}catch(\Exception $e){
			$this->error($e->getMessage());
		}	
		$this->success('打款成功', session('back_url'));
	}
    
    private function cashApplyService() {
    	return D('CashApply', 'Service');
    }
}