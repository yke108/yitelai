<?php
namespace Home\Controller\User;
use Home\Controller\BaseController;
use Common\Basic\Status;

class OrderController extends BaseController {	
	public function _initialize(){
		$this->purviewCheck('user/index/info');
		parent::_initialize();
    }
	
    public function indexAction() {  /*NoPurview*/
    	$page = intval(I('p')) > 0 ? intval(I('p')) : 1;
    	$pagesize = $this->pagesize;
    	if (I('user_id')) {
    		$params['user_id'] = I('user_id');
    	}
    	$result = $this->orderService()->getOrderList($params, $page, $pagesize);
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
    	
    	$this->assign('page_title', '客户订单');
		$this->display();
    }
    
    public function infoAction($id = 0) {  /*NoPurview*/
    	$this->assign('page_title', '订单详情');
    	$this->display();
    }
    
    private function orderService(){
    	return D('Order', 'Service');
    }
}