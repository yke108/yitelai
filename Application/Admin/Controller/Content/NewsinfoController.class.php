<?php
namespace Admin\Controller\Content;
use Admin\Controller\FController;
use Common\Basic\Pager;

class NewsinfoController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'help',
			'ac'=>'content_newsinfo_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$params = array(
    			
    	);
    	$this->assign('all_cat_list', $this->newsinfoService()->catOptionList($params, $get['cat_id']));
    	
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    	);
    	if (!empty($get['keyword'])) {
    		$params['keyword'] = $get['keyword'];
    	}
    	if (!empty($get['cat_id'])) {
    		$params['cat_id'] = $get['cat_id'];
    	}
    	$datas = $this->newsinfoService()->infoPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $this->pagesize);
    	$this->assign('pager', $pager->show());
    	
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$params = I('post.');
			try {
				$result = $this->newsinfoService()->infoCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功',U('index'),0);
		}
		
		$this->assign('catlist', $this->newsinfoService()->catTreeList());
		
		$this->display('edit');
	}
	
	public function editAction($news_id = 0){
		$info = $this->newsinfoService()->getInfo($news_id);
		if(empty($info)) $this->error('内容不存在');
		if(IS_POST){
			$params = I('post.');
			$params['news_id'] = $info['news_id'];
			try {
				$result = $this->newsinfoService()->infoCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功',U('index'),0);
		}
		$this->assign('info', $info);
		
		$this->assign('catlist', $this->newsinfoService()->catTreeList());
		
		$this->display();
	}
	
	public function delAction($news_id = 0){
		$info = $this->newsinfoService()->getInfo($news_id);
		if(empty($info)) $this->error('内容不存在');
		try {
			$result = $this->newsinfoService()->infoDelete($info['news_id']);
			$this->success('删除成功');
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	public function recommendAction($news_id = 0){
		$info = $this->newsinfoService()->getInfo($news_id);
		if(empty($info)) $this->error('内容不存在');
		try {
			$result = $this->newsinfoService()->infoRecommend($info);
			$this->success('操作成功', U('index'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	public function openAction($news_id = 0){
		$info = $this->newsinfoService()->getInfo($news_id);
		if(empty($info)) $this->error('内容不存在');
		try {
			$result = $this->newsinfoService()->infoOpen($info);
			$this->success('操作成功', U('index'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	private function newsinfoService(){
		return D('News', 'Service');
	}
}