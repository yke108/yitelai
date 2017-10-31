<?php
namespace Information\Controller\News;
use Information\Controller\FController;
use Common\Basic\Pager;

class SourceController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'news',
			'ac'=>'news_source_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	session('back_url', __SELF__);
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	//列表
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    	);
    	$datas = $this->sourceService()->infoPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $this->pagesize);
    	$this->assign('pager', $pager->show());
    	
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$post = I('post.');
			try {
				$result = $this->sourceService()->infoCreateOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功', U('index'));
		}
		
		$this->display('edit');
	}
	
	public function editAction($source_id = 0){
		$info = $this->sourceService()->getInfo($source_id);
		if(empty($info)) $this->error('内容不存在');
		
		if(IS_POST){
			$post = I('post.');
			try {
				$result = $this->sourceService()->infoCreateOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功', session('back_url'));
		}
		
		$this->assign('info', $info);
		
		$this->display();
	}
	
	public function delAction($source_id = 0){
		try {
			$result = $this->sourceService()->infoDelete($source_id);
			$this->success('删除成功');
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	private function sourceService(){
		return D('Information\NewsSource', 'Service');
	}
}