<?php
namespace Information\Controller\Community;
use Information\Controller\FController;
use Common\Basic\Pager;

class TopicController extends FController {
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'community',
			'ac'=>'community_topic_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	session('back_url', __SELF__);
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$params = array();
    	$this->assign('all_cat_list', $this->blockService()->blockOptionList($params, $get['block_id']));
    	
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    	);
    	if (!empty($get['keyword'])) {
    		$params['keyword'] = $get['keyword'];
    	}
    	if (!empty($get['block_id'])) {
    		$params['block_id'] = $get['block_id'];
    	}
    	$datas = $this->topicService()->infoPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $this->pagesize);
    	$this->assign('pager', $pager->show());
    	
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$params = I('post.');
			try {
				$result = $this->topicService()->infoCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功',U('index'),0);
		}
		
		$this->assign('catlist', $this->blockService()->blockTreeList());
		
		$this->display('edit');
	}
	
	public function editAction($topic_id = 0){
		$info = $this->topicService()->getInfo($topic_id);
		if(empty($info)) $this->error('内容不存在');
		
		if(IS_POST){
			$post = I('post.');
			try {
				$result = $this->topicService()->infoCreateOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功', session('back_url'));
		}
		
		$this->assign('info', $info);
		
		$this->assign('catlist', $this->topicService()->blockTreeList());
		
		$this->display();
	}
	
	public function delAction($topic_id = 0){
		try {
			$result = $this->topicService()->infoDelete($topic_id);
			$this->success('删除成功', session('back_url'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	public function recommendAction($topic_id = 0){
		try {
			$result = $this->topicService()->infoRecommend($topic_id);
			$this->success('操作成功', session('back_url'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	public function openAction($topic_id = 0){
		try {
			$result = $this->topicService()->infoOpen($topic_id);
			$this->success('操作成功', session('back_url'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	private function topicService(){
		return D('Information\Topic', 'Service');
	}
	
	private function blockService(){
		return D('Information\Block', 'Service');
	}
}