<?php
namespace Distributor\Controller\Feedback;
use Distributor\Controller\FController;
use Common\Basic\Pager;
use Common\Basic\Status;

class IndexController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'feedback',
			'ac'=>'feedback_index_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction() {
    	$this->listDisplay();
    }
    
    public function waitAction() {
    	$this->sbset('feedback_index_wait');
    	$map = array(
    			'status'=>Status::FeedbackStatusNone,
    	);
    	$this->listDisplay($map);
    }
    
    public function goingAction() {
    	$this->sbset('feedback_index_going');
    	$map = array(
    			'status'=>Status::FeedbackStatusOn,
    	);
    	$this->listDisplay($map);
    }
    
    public function doneAction() {
    	$this->sbset('feedback_index_done');
    	$map = array(
    			'status'=>Status::FeedbackStatusDone,
    	);
    	$this->listDisplay($map);
    }
    
    public function listDisplay($map = array()) {
    	session('bakc_url', __SELF__);
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'distributor_id'=>$this->org_id,
    			'map'=>$map,
    	);
    	if ($get['type']) {
    		$params['type'] = $get['type'];
    	}
    	if ($get['brand_id']) {
    		$params['brand_id'] = $get['brand_id'];
    	}
    	if ($get['start_time']) {
    		$params['start_time'] = $get['start_time'];
    	}
    	if ($get['end_time']) {
    		$params['end_time'] = $get['end_time'];
    	}
    	$result = $this->feedbackService()->getPagerList($params);
    	if ($result['count'] > 0){
    		$this->assign('list', $result['list']);
    		$pager = new Pager($result['count'], $result['pagesize']);
    		$this->assign('pager', $pager->show());
    	}
    	
    	//反馈类型
    	$this->assign('type_list', Status::$feedbackTypeList);
    	
    	//品牌列表
    	$brand_list = $this->goodsBrandService()->getAllList();
    	$this->assign('brand_list', $brand_list);
    	
    	//门店列表
    	$distributor_list = $this->distributorService()->getAllList();
    	$this->assign('distributor_list', $distributor_list);
    	
    	$this->display('index');
    }
	
	public function infoAction($id = 0){
		$info = $this->feedbackService()->getInfo($id);
		if(empty($info)) $this->error('内容不存在');
		$this->assign('info', $info);
		
		//回复列表
		$params = array(
				'log_id'=>$info['log_id'],
		);
		$reply_list = $this->feedbackService()->replyAllList($params);
		if ($reply_list) {
			foreach ($reply_list as $k => $v) {
				$content = ($v['is_json'] == 1) ? json_decode($v['content'],true) : $v['content'];
				$reply_list[$k]['content'] = ($v['is_json'] == 1) ? $content['qlist']['content'] : $content;
				 
				$reply_ids[] = $v['reply_id'];
			}
			$this->assign('reply_list', $reply_list);
		}
		
		$this->display();
	}
	
	private function feedbackService(){
		return D('Feedback', 'Service');
	}
	
	private function goodsBrandService() {
		return D('GoodsBrand', 'Service');
	}
}