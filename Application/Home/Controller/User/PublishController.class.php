<?php
namespace Home\Controller\User;
use Home\Controller\BaseController;
use Common\Basic\Status;

class PublishController extends BaseController {	
	public function _initialize(){
		$this->purviewCheck('user/index/info');
		parent::_initialize();
    }
	
    public function indexAction() {  /*NoPurview*/
    	//文章
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
    	$result = $this->storyService()->infoPagerList($params);
    	foreach ($result['list'] as $k => $v) {
    		$result['list'][$k]['type'] = 0;
    	}
    	$this->assign('story_list', $result['list']);
    	$this->assign('count', $result['count']);
    	
    	//视频
    	$params['type'] = 1;
    	$result = $this->newsService()->infoPagerList($params);
    	foreach ($result['list'] as $k => $v) {
    		$result['list'][$k]['type'] = 1;
    	}
    	$this->assign('video_list', $result['list']);
    	
    	//图片
    	$result = $this->materialService()->getPagerList($params);
    	foreach ($result['list'] as $k => $v) {
    		$result['list'][$k]['type'] = 2;
    	}
    	$this->assign('material_list', $result['list']);
    	
    	$get = I('get.');
    	$get['type'] = $get['type'] ? $get['type'] : 0;
    	$this->assign('get', $get);
    	
    	$this->assign('page_title', '发布记录');
    	$this->display();
    }
    
    public function listAction() {
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
    	
    	if (I('type') == 0) {
    		$result = $this->storyService()->infoPagerList($params);
    		foreach ($result['list'] as $k => $v) {
    			$result['list'][$k]['type'] = 0;
    		}
    		$this->assign('list', $result['list']);
    	}elseif (I('type') == 1) {
    		$params['type'] = 1;
    		$result = $this->newsService()->infoPagerList($params);
    		foreach ($result['list'] as $k => $v) {
    			$result['list'][$k]['type'] = 1;
    		}
    		$this->assign('list', $result['list']);
    	}elseif (I('type') == 2) {
    		$result = $this->materialService()->getPagerList($params);
    		foreach ($result['list'] as $k => $v) {
    			$result['list'][$k]['type'] = 2;
    		}
    		$this->assign('list', $result['list']);
    	}
    	
    	if(empty($result['list'])){
    		$clist = '';
    	}else{
    		$clist = $this->renderPartial('_index');
    	}
    	$this->ajaxReturn(array('html'=>$clist, 'p'=>$params['page']+1));
    }
    
    private function storyService() {
    	return D('Story', 'Service');
    }
    
    private function newsService(){
    	return D('Information\News', 'Service');
    }
    
    private function materialService() {
    	return D('Material', 'Service');
    }
}
