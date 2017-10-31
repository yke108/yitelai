<?php
namespace Main\Controller\Beauty;
use Main\Controller\MainController;
use Common\Basic\Status;
use Common\Basic\Pager;

class IndexController extends MainController {
	public function _initialize(){
		parent::_initialize();
    }
	
    public function indexAction(){
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$cat_list = $this->beautyCatService()->catAllList();
    	foreach ($cat_list as $k => $v) {
    		//最美会员
    		$params = array(
    				'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    				'pagesize'=>6,
    				'cat_id'=>$v['cat_id'],
    		);
    		$result = $this->beautyService()->infoPagerList($params);
    		$cat_list[$k]['list'] = $result['list'];
    		
    		//Top10排行榜
    		$params = array(
    				'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    				'pagesize'=>10,
    				'cat_id'=>$v['cat_id'],
    				'orderby'=>'vote_count desc',
    		);
    		$result = $this->beautyService()->infoPagerList($params);
    		$cat_list[$k]['top_list'] = $result['list'];
    	}
    	$this->assign('cat_list', $cat_list);
    	
    	$this->display();
    }
    
    public function listAction(){
    	$this->display();
    }
    
    public function infoAction($id = 0){
    	$info = $this->beautyService()->getInfo($id);
    	if(empty($info)) $this->error('内容不存在');
    	
    	$params = array(
    			'user_id'=>$this->user['user_id'],
    			'beauty_id'=>$id,
    	);
    	$this->beautyReadService()->infoCreate($params);
    	
    	$this->assign('info', $info);
    	
    	$this->display();
    }
    
    public function voteAction($id = 0) {
    	if (empty($this->user)) {
    		$this->error('请先登录', U('index/site/login'));
    	}
    	
    	$params = array(
    			'beauty_id'=>$id,
    			'user_id'=>$this->user['user_id'],
    	);
    	try {
    		$result = $this->beautyVoteService()->infoCreateOrModify($params);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	$this->success('投票成功');
    }
    
    public function aboutAction(){
    	$this->page(34);
    }
    
    public function rulesAction(){
    	$this->page(35);
    }
    
    public function knowledgeAction(){
    	$this->page(36);
    }
    
    public function feedbackAction(){
    	$this->page(37);
    }
    
    private function page($page_id = 0) {
    	$info = $this->pageService()->getInfo($page_id);
    	if(empty($info)) $this->error('内容不存在');
    	$this->assign('info', $info);
    	$this->display('page');
    }
    
    private function beautyService(){
    	return D('Beauty', 'Service');
    }
    
    private function beautyCatService(){
    	return D('BeautyCat', 'Service');
    }
    
    private function beautyVoteService(){
    	return D('BeautyVote', 'Service');
    }
    
    private function beautyReadService(){
    	return D('BeautyRead', 'Service');
    }
    
    private function pageService(){
    	return D('Page', 'Service');
    }
}