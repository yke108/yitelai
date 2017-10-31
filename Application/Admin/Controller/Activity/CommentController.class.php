<?php
namespace Admin\Controller\Activity;
use Admin\Controller\FController;
use Common\Basic\Pager;

class CommentController extends FController {
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'activity',
			'ac'=>'activity_comment_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    	);
    	if (!empty($get['keyword'])) {
    		$params['keyword'] = $get['keyword'];
    	}
    	$datas = $this->activityCommentService()->infoPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $this->pagesize);
    	$this->assign('pager', $pager->show());
    	
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$post = I('post.');
			try {
				$result = $this->activityCommentService()->infoCreateOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功', U('index'));
		}
		
		$this->display('edit');
	}
	
	public function editAction($activity_id = 0){
		$info = $this->activityCommentService()->getInfo($activity_id);
		if(empty($info)) $this->error('内容不存在');
		
		if(IS_POST){
			$post = I('post.');
			try {
				$result = $this->activityCommentService()->infoCreateOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功',U('index'),0);
		}
		$this->assign('info', $info);
		
		$this->display();
	}
	
	public function delAction($activity_id = 0){
		try {
			$result = $this->activityCommentService()->infoDelete($activity_id);
			$this->success('删除成功', session('back_url'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	public function openAction($activity_id = 0){
		try {
			$result = $this->activityCommentService()->infoOpen($activity_id);
			$this->success('操作成功', session('back_url'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	private function activityCommentService(){
		return D('ActivityComment', 'Service');
	}
}