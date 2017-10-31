<?php
namespace Distributor\Controller\Finance;
use Distributor\Controller\FController;
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
    	
    	$this->assign('distributor_info', $this->distributor_info);
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$page = intval(I('p')) ? intval(I('p')) : 1;
    	$params = array(
    			'page'=>$page,
    			'pagesize'=>$this->pagesize,
    			'distributor_id'=>$this->org_id,
    	);
    	$datas = $this->distributorCashApplyService()->getPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $this->pagesize);
    	$this->assign('pager', $pager->show());
    	
		$this->display();
    }
	
	public function applyAction(){
		if (IS_POST) {
			$post = I('post.');
			$post['distributor_id'] = $this->org_id;
			try {
				$this->distributorCashApplyService()->createCashApply($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('申请成功', U('index'));
		}
		
		$this->assign('distributor_info', $this->distributor_info);
		
		$params=array('pagesize'=>100);
		$bank_result=$this->distributorCashApplyService()->bankPagerList($params);
		$this->assign('bank_list',$bank_result['list']);
		
		//省市区
		$this->assign('region_list', $this->regionService()->getAllRegions(false));
		
		$this->display();
	}
	
    private function distributorCashApplyService() {
    	return D('Distributor\CashApply', 'Service');
    }
    
    private function regionService(){
    	return D('Region', 'Service');
    }
}