<?php
namespace Home\Controller\User;
use Home\Controller\BaseController;
use Common\Basic\Status;

class CommentController extends BaseController {	
	public function _initialize(){
		$this->purviewCheck('user/index/info');
		parent::_initialize();
    }
	
    public function indexAction() { /*NoPurview*/
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    	);
    	if ($get['user_id']) {
    		$params['user_id'] = $get['user_id'];
    	}
    	$result = $this->goodsCommentService()->getPagerList($params);
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
    	
    	$this->assign('page_title', '客户评价');
		$this->display();
    }
    
    private function goodsCommentService() {
    	return D('GoodsComment', 'Service');
    }
}
