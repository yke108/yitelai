<?php
namespace Main\Controller\Activity;
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
    	
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>10,
    			'is_open'=>1,
    	);
    	if (!empty($get['keyword'])) {
    		$params['keyword'] = $get['keyword'];
    	}
    	$datas = $this->activityInfoService()->infoPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $this->pagesize);
    	$this->assign('pager', $pager->show_pc());
    	
    	$this->display();
    }
    
    public function listAction(){
    	$this->display();
    }
    
    public function infoAction($id = 0){
    	$info = $this->activityInfoService()->getInfo($id);
    	if(empty($info)) $this->error('内容不存在');
    	
    	//浏览数统计
    	$this->activityInfoService()->readCount($id);
    	
    	$this->assign('info', $info);
    	
    	//热门活动
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>4,
    			'is_recommend'=>1,
    	);
    	$datas = $this->activityInfoService()->infoPagerList($params);
    	$this->assign('recommend_list', $datas['list']);
    	
    	//评论列表
    	$params = array(
    			'page'=>intval(I('p')),
    			'pagesize'=>20,
    			'book_id'=>$info['book_id'],
    	);
    	$result = $this->activityCommentService()->infoPagerList($params);
    	$this->assign('comment_list', $result['list']);
    	$pager = new Pager($result['count'], $params['pagesize']);
    	$this->assign('pager', $pager->show_pc());
    	
    	//参加人员
    	$params = array(
    			'page'=>intval(I('p')),
    			'pagesize'=>20,
    			'activity_id'=>$info['activity_id'],
    	);
    	$result = $this->activityApplyService()->infoPagerList($params);
    	$this->assign('apply_list', $result['list']);
    	
    	//现场大牌特惠预告
    	$map = array('start_time'=>array('gt', NOW_TIME));
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>10,
    			'map'=>$map,
    	);
    	$datas = $this->activityInfoService()->infoPagerList($params);
    	$this->assign('advance_list', $datas['list']);
    	
    	//更多活动
    	$map = array(
    			'start_time'=>array('elt', NOW_TIME),
    			'end_time'=>array('egt', NOW_TIME)
    	);
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>10,
    			'map'=>$map,
    	);
    	$datas = $this->activityInfoService()->infoPagerList($params);
    	$this->assign('activity_list', $datas['list']);
    	
    	//往期活动
    	$map = array('end_time'=>array('lt', NOW_TIME));
    	$params = array(
    			'page'=>1,
    			'pagesize'=>4,
    			'map'=>$map,
    	);
    	$datas = $this->activityInfoService()->infoPagerList($params);
    	$this->assign('past_list', $datas['list']);
    	
    	$this->display();
    }
    
    public function commentAction($activity_id = 0) {
    	if (empty($this->user)) {
    		$url = DK_DOMAIN.'/index/site/login';
    		$this->error('请先登录', $url);
    	}
    	
    	$post = I('post.');
    	$post['user_id'] = $this->user['user_id'];
    	try {
    		$result = $this->activityCommentService()->infoCreateOrModify($post);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	
    	$this->assign('comment_list', array($result));
    	$html = $this->renderPartial('_comment_list');
    	$data = array('html'=>$html);
    	$this->success('评论成功', '', $data);
    }
    
    public function applyAction($activity_id = 0) {
    	if (empty($this->user)) {
    		$url = DK_DOMAIN.'/index/site/login';
    		$this->error('请先登录', $url);
    	}
    	$post = I('post.');
    	$post['user_id'] = $this->user['user_id'];
    	try {
    		$result = $this->activityApplyService()->infoCreateOrModify($post);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	$this->success('报名成功');
    }
    
    private function activityInfoService(){
    	return D('ActivityInfo', 'Service');
    }
    
    private function activityCommentService(){
    	return D('ActivityComment', 'Service');
    }
    
    private function activityApplyService(){
    	return D('ActivityApply', 'Service');
    }
}