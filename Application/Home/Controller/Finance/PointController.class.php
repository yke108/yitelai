<?php
namespace Home\Controller\Finance;
use Home\Controller\BaseController;
use Common\Basic\Status;

class PointController extends BaseController {	
	public function _initialize(){
		parent::_initialize();
    }
    
    protected function _purviewCheck() {
    	$this->purviewCheck('index');
    }
	
    public function indexAction() { /*积分兑换审核 */
    	//待审核
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'status'=>0,
    	);
    	if (I('keyword')) {
    		$params['order_sn'] = I('keyword');
    	}
    	if (I('city')) {
    		$params['city'] = I('city');
    	}
    	if (I('store_id')) {
    		$params['store_id'] = I('store_id');
    	}
    	$result = $this->pointGiftService()->orderPagerList($params);
    	$this->assign('wait_list', $result['list']);
    	
    	//已审核
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'status'=>1,
    	);
    	if (I('keyword')) {
    		$params['order_sn'] = I('keyword');
    	}
    	if (I('city')) {
    		$params['city'] = I('city');
    	}
    	if (I('store_id')) {
    		$params['store_id'] = I('store_id');
    	}
    	$result = $this->pointGiftService()->orderPagerList($params);
    	$this->assign('checked_list', $result['list']);
    	
    	if (IS_AJAX) {
    		if(empty($result['list'])){
    			$clist = '';
    		}else{
    			$clist = $this->renderPartial('_index');
    		}
    		$this->ajaxReturn(array('html'=>$clist, 'p'=>$params['page']+1));
    	}
    	
    	$get = I('get.');
    	$get['type'] = $get['type'] ? $get['type'] : 1;
    	$this->assign('get', $get);
    	
    	$this->assign('page_title', '积分兑换审核');
		$this->display();
    }
    
    public function listAction() {
    	//待审核
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    	);
    	if (I('type') == 1) {
    		$params['status'] = 0;
    	}elseif (I('type') == 2) {
    		$params['status'] = 1;
    	}
    	if (I('keyword')) {
    		$params['order_sn'] = I('keyword');
    	}
    	if (I('city')) {
    		$params['city'] = I('city');
    	}
    	if (I('store_id')) {
    		$params['store_id'] = I('store_id');
    	}
    	$result = $this->pointGiftService()->orderPagerList($params);
    	$this->assign('list', $result['list']);
    	
    	if(empty($result['list'])){
    		$clist = '';
    	}else{
    		$clist = $this->renderPartial('_index');
    	}
    	$this->ajaxReturn(array('html'=>$clist, 'p'=>$params['page']+1));
    }
    
    public function infoAction($id = 0) {
    	$info=$this->pointGiftService()->getOrderInfo($id);
    	$this->assign('info',$info);
    	
    	$map=array('order_id'=>$id);
    	$params=array('map'=>$map);
    	$result=$this->pointGiftService()->orderGoodsPagerList($params);
    	$this->assign('list',$result['list']);
    	
    	$shipping_params=array('distributor_id'=>0,'page'=>1,'pagesize'=>100);
    	$shipping_result=$this->shippingService()->getPagerShippingList($shipping_params);
    	$this->assign('shipping_list',$shipping_result['list']);
    	
    	$this->assign('page_title', '积分兑换审核-详情');
    	$this->display();
    }
    
    public function checkAction() {
    	$params = array(
    			'id'=>I('get.id'),
    			'status'=>I('get.status'),
    	);
    	try {
    		$this->pointGiftService()->orderCheck($params);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	$this->success('审核成功');
    }
    
    private function pointGiftService(){
    	return D('PointGift',"Service");
    }
    
    private function shippingService(){
    	return D('Shipping',"Service");
    }
    
    protected function distributorService() {
    	return D ( 'Distributor', 'Service' );
    }
    
    protected function regionService() {
    	return D ( 'Region', 'Service' );
    }
}