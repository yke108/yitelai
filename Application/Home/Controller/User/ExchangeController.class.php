<?php
namespace Home\Controller\User;
use Home\Controller\BaseController;
use Common\Basic\Status;

class ExchangeController extends BaseController {	
	public function _initialize(){
		$this->purviewCheck('user/index/info');
		parent::_initialize();
    }
	
    public function indexAction() {  /*NoPurview*/
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    	);
    	if (I('user_id')) {
    		$params['user_id'] = I('user_id');
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
    	$result = $this->pointGiftService()->orderPagerList($params);
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
    	
    	$this->assign('page_title', '兑换记录');
    	$this->display();
    }
    
    private function pointGiftService() {
    	return D('PointGift', 'Service');
    }
}
