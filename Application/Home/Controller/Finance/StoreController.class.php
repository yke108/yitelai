<?php
namespace Home\Controller\Finance;
use Home\Controller\BaseController;
use Common\Basic\Status;

class StoreController extends BaseController {	
	public function _initialize(){
		parent::_initialize();
    }
    
    protected function _purviewCheck() {
    	$this->purviewCheck('index');
    }
	
    public function indexAction() { /*店铺结算 */
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    	);
    	if (I('keyword')) {
    		$params['keyword'] = I('keyword');
    	}
    	if (I('distributor_id')) {
    		$params['distributor_id'] = I('distributor_id');
    	}
    	$result = $this->distributorCashApplyService()->getPagerList($params);
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
    	
    	//店铺筛选
    	if (session('sys_id') == Status::SysIdPlatform) {
    		$map = array('status'=>Status::DistributorStatusNormal);
    		$distributor_list = $this->distributorService()->getAllList($map);
    		$this->assign('distributor_list', $distributor_list);
    	}
    	
    	$this->assign('page_title', '店铺结算');
		$this->display();
    }
    
    public function listAction($store_id = 0) {
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'map'=>array('status'=>array('in', array(Status::CashNotAgree,Status::CashRemit,Status::CashRemitFail))),
    	);
    	if (I('keyword')) {
    		$params['keyword'] = I('keyword');
    	}
    	if (I('store_id')) {
    		$params['distributor_id'] = I('store_id');
    	}
    	$result = $this->distributorCashApplyService()->getPagerList($params);
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
    	
    	//店铺筛选
    	if (session('sys_id') == Status::SysIdPlatform) {
    		$map = array('status'=>Status::DistributorStatusNormal);
    		$distributor_list = $this->distributorService()->getAllList($map);
    		$this->assign('distributor_list', $distributor_list);
    	}
    	
    	$this->assign('page_title', '店铺结算');
    	$this->display();
    }
    
    public function infoAction($apply_id = 0) {
    	$info = $this->distributorCashApplyService()->getInfo($apply_id);
    	if (empty($info)) $this->error('提现记录不存在');
    	$this->assign('info', $info);
    	
    	//店铺
    	$distributor_info = $this->distributorService()->getInfo($info['distributor_id']);
    	if (empty($distributor_info)) $this->error('店铺不存在');
    	$this->assign('distributor', $distributor_info);
    	
    	//本月销售额
    	$params = array(
    			'distributor_id'=>$distributor_info['distributor_id'],
    			'pay_status'=>Status::PayStatusPaid,
    	);
    	$order_amount = $this->orderService()->getOrderAmount($params);
    	$this->assign('order_amount', $order_amount);
    	
    	//进货成本
    	$params = array(
    			'distributor_id'=>$distributor_info['distributor_id'],
    			'pay_status'=>Status::PayStatusPaid,
    	);
    	$stock_amount = $this->orderService()->getStockAmount($params);
    	$this->assign('stock_amount', $stock_amount);
    	
    	//店铺订单
    	$params = array(
    			'distributor_id'=>$distributor_info['distributor_id'],
    			'pay_status'=>Status::PayStatusPaid,
    	);
    	$result = $this->orderService()->getOrderList($params);
    	$this->assign('order_list', $result['list']);
    	
    	$this->assign('page_title', '店铺结算-详情');
    	$this->display();
    }
    
    public function remitAction($apply_id = 0) {
    	$params = array(
    			'apply_id'=>$apply_id,
    			'status'=>Status::CashRemit,
    	);
    	try{
    		$this->distributorCashApplyService()->remitMoney($params);
    	}catch(\Exception $e){
    		$this->error($e->getMessage());
    	}
    	$this->success('结算成功', session('back_url'));
    }
    
    private function distributorService() {
    	return D('Distributor', 'Service');
    }
    
	private function distributorCashApplyService() {
    	return D('Distributor\CashApply', 'Service');
    }
    
    private function orderService() {
    	return D('Order', 'Service');
    }
}