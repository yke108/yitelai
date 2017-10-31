<?php
namespace News\Controller\User;
use News\Controller\WapController;
use Common\Basic\Genre;

class TopController extends WapController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
    }
	
    public function indexAction(){
    	//文章排行
    	$params = array('is_open'=>1, 'type'=>0, 'orderby'=>'sort_order DESC');
    	$result = $this->newsService()->infoPagerList($params);
    	$news_list = array();
    	if (!empty($result['list'])) {
    		foreach ($result['list'] as $k => $v) {
    			$v['key'] = ($k + 1);
    			$news_list[] = $v;
    		}
    	}
    	$this->assign('news_list', $news_list);
    	
    	//视频排行
    	$params = array('is_open'=>1, 'type'=>1, 'orderby'=>'sort_order DESC');
    	$result = $this->newsService()->infoPagerList($params);
    	$video_list = array();
    	if (!empty($result['list'])) {
    		foreach ($result['list'] as $k => $v) {
    			$v['key'] = ($k + 1);
    			$video_list[] = $v;
    		}
    	}
    	$this->assign('video_list', $video_list);
    	
    	//打赏排行
    	$params = array('is_open'=>1, 'orderby'=>'reward_amount DESC');
    	$result = $this->newsService()->infoPagerList($params);
    	$reward_list = array();
    	if (!empty($result['list'])) {
    		foreach ($result['list'] as $k => $v) {
    			$v['key'] = ($k + 1);
    			$reward_list[] = $v;
    		}
    	}
    	$this->assign('reward_list', $reward_list);
    	
    	$this->assign('page_title', '排行榜');
		$this->display();
    }
    
    public function listAction(){
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'is_open'=>1,
    			//'type'=>I('type'),
    	);
    	if (I('type')) {
    		$params['type'] = I('type');
    	}
    	$result = $this->newsService()->infoPagerList($params);
    	$news_list = array();
    	if (!empty($result['list'])) {
    		$key = ($params['page'] - 1) * $params['pagesize'];
    		foreach ($result['list'] as $k => $v) {
    			$key++;
    			$v['key'] = $key;
    			$news_list[] = $v;
    		}
    	}
    	$this->assign('news_list', $news_list);
    	
    	if(empty($news_list)){
    		$clist = '';
    	}else{
    		if ($params['type'] == 0) {
    			$clist = $this->renderPartial('_news');
    		}elseif ($params['type'] == 1) {
    			$clist = $this->renderPartial('_video');
    		}else {
    			$clist = $this->renderPartial('_index');
    		}
    	}
    	$this->ajaxReturn(array('html'=>$clist, 'p'=>$params['page']+1));
    }
    
	private function newsService(){
		return D('Information\News', 'Service');
	}
}