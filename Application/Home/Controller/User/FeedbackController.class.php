<?php
namespace Home\Controller\User;
use Home\Controller\BaseController;
use Common\Basic\Status;

class FeedbackController extends BaseController {	
	public function _initialize(){
		$this->purviewCheck('user/index/info');
		parent::_initialize();
    }
	
    public function indexAction() {  /*NoPurview*/
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'type'=>Status::FeedbackTypeComplain,
    	);
    	if ($get['user_id']) {
    		$params['user_id'] = $get['user_id'];
    	}
    	$result = $this->feedbackService()->getPagerList($params);
    	$this->assign('complain_list', $result['list']);
    	
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'type'=>Status::FeedbackTypeSuggest,
    	);
    	if ($get['user_id']) {
    		$params['user_id'] = $get['user_id'];
    	}
    	$result = $this->feedbackService()->getPagerList($params);
    	$this->assign('suggest_list', $result['list']);
    	
    	$this->assign('page_title', '客户投诉建议');
		$this->display();
    }
    
    public function listAction() {  /*NoPurview*/
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'type'=>Status::FeedbackTypeSuggest,
    	);
    	if (I('user_id')) {
    		$params['user_id'] = I('user_id');
    	}
    	if (I('type')) {
    		$params['type'] = I('type');
    	}
    	$result = $this->feedbackService()->getPagerList($params);
    	$this->assign('list', $result['list']);
    	
    	if(empty($result['list'])){
    		$clist = '';
    	}else{
    		$clist = $this->renderPartial('_index');
    	}
    	$this->ajaxReturn(array('html'=>$clist, 'p'=>$params['page']+1));
    }
    
    public function askAction() {  /*NoPurview*/
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			//'type'=>Status::FeedbackTypeAsk,
    	);
    	if (I('user_id')) {
    		$params['user_id'] = I('user_id');
    	}
    	$result = $this->feedbackService()->getPagerList($params);
    	$this->assign('list', $result['list']);
    	$this->assign('count', $result['count']);
    	
    	if (IS_AJAX) {
    		if(empty($result['list'])){
    			$clist = '';
    		}else{
    			$clist = $this->renderPartial('_ask');
    		}
    		$this->ajaxReturn(array('html'=>$clist, 'p'=>$params['page']+1));
    	}
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$this->assign('page_title', '客户咨询日志');
    	$this->display();
    }
    
    public function infoAction($log_id = 0) {  /*NoPurview*/
    	$info = $this->feedbackService()->getInfo($log_id);
    	if(empty($info)) $this->error('内容不存在');
    	$this->assign('info', $info);
    	
    	//回复列表
    	$params = array('log_id'=>$info['log_id']);
    	$reply_list = $this->feedbackService()->replyAllList($params);
    	$this->assign('reply_list', $reply_list);
    	
    	$this->assign('page_title', '客户咨询日志-详情');
    	$this->display();
    }
    
    private function feedbackService() {
    	return D('Feedback', 'Service');
    }
}
