<?php
namespace Admin\Controller\Feedback;
use Admin\Controller\FController;
use Common\Basic\Pager;

class AppController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'feedback',
			'ac'=>'feedback_app_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction($id = 0){
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$params = array(
	    		'page'=>$get['p'],
	    		'pagesize'=>20,
    			'start_time'=>$get['start_time'],
    			'end_time'=>$get['end_time']
    	);
    	$result = $this->feedbackService()->feedbackAppPagerList($params);
    	if ($result['count'] > 0){
    		$this->assign('list', $result['list']);
    		$this->assign('admin_list', $result['admin_list']);
    		$pager = new Pager($result['count'], $result['pagesize']);
    		$this->assign('pager', $pager->show());
    	}
		$this->display();
    }
	
	private function feedbackService(){
		return D('Feedback', 'Service');
	}
}