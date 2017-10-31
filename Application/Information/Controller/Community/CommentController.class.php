<?php
namespace Information\Controller\Community;
use Information\Controller\FController;
use Common\Basic\Pager;

class CommentController extends FController {
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'comment',
			'ac'=>'community_comment_index',
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
    	$datas = $this->topicCommentService()->infoPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $this->pagesize);
    	$this->assign('pager', $pager->show());
    	
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$post = I('post.');
			try {
				$result = $this->topicCommentService()->infoCreateOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功', U('index'));
		}
		
		$this->display('edit');
	}
	
	public function editAction($comment_id = 0){
		$info = $this->topicCommentService()->getInfo($comment_id);
		if(empty($info)) $this->error('内容不存在');
		
		if(IS_POST){
			$post = I('post.');
			try {
				$result = $this->topicCommentService()->infoCreateOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功',U('index'),0);
		}
		$this->assign('info', $info);
		
		$this->display();
	}
	
	public function delAction($comment_id = 0){
		try {
			$result = $this->topicCommentService()->infoDelete($comment_id);
			$this->success('删除成功', session('back_url'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	public function openAction($comment_id = 0){
		try {
			$result = $this->topicCommentService()->infoOpen($comment_id);
			$this->success('操作成功', session('back_url'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	private function topicCommentService(){
		return D('Information\TopicComment', 'Service');
	}
}