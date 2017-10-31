<?php
namespace Home\Controller\Finance;
use Home\Controller\BaseController;
use Common\Basic\Status;

class HistoryController extends BaseController {	
	public function _initialize(){
		parent::_initialize();
    }
    
    protected function _purviewCheck(){
    	$this->purviewCheck('index');
    }
	
    public function indexAction() { /*历史结算*/
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
				'status'=>Status::DistributorStatusNormal,
    			'_needCashApply'=>1,
    	);
    	//店铺
    	if (session('distributor_id')) {
    		$params['distributor_id'] = session('distributor_id');
    	}
    	if (I('keyword')) {
    		$params['keyword'] = I('keyword');
    	}
    	$result = $this->distributorService()->getPagerList($params);
    	$this->assign('list', $result['list']);
    	$this->assign('count', $result['count']);
    	
    	if (IS_AJAX) {
    		if(empty($result['list'])){
    			$clist = '';
    		}else{
    			$clist = $this->renderPartial('_index');
    		}
    		$this->ajaxReturn(array('html'=>$clist, 'p'=>$params['page']+1));
    	}
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$this->assign('page_title', '历史结算');
		$this->display();
    }
    
    public function listAction($id = 0) { /*NoPurview*/
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'distributor_id'=>$id,
    			'status'=>Status::CashRemit,
    	);
    	if (I('keyword')) {
    		$params['keyword'] = I('keyword');
    	}
    	$result = $this->distributorCashApplyService()->getPagerList($params);
    	$this->assign('list', $result['list']);
    	$this->assign('count', $result['count']);
    	
    	if (IS_AJAX) {
    		if(empty($result['list'])){
    			$clist = '';
    		}else{
    			$clist = $this->renderPartial('_list');
    		}
    		$this->ajaxReturn(array('html'=>$clist, 'p'=>$params['page']+1));
    	}
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$this->assign('page_title', '历史结算-详情');
    	$this->display();
    }
    
    private function distributorService(){
    	return D('Distributor', 'Service');
    }
    
    private function distributorCashApplyService() {
    	return D('Distributor\CashApply', 'Service');
    }
}