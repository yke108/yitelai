<?php
namespace Information\Controller\News;
use Information\Controller\FController;
use Common\Basic\Pager;

class AuthorController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'news',
			'ac'=>'news_author_index',
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
    	$datas = $this->authorService()->infoPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $this->pagesize);
    	$this->assign('pager', $pager->show());
    	
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$post = I('post.');
			try {
				$result = $this->authorService()->infoCreateOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功', U('index'));
		}
		
		$this->display('edit');
	}
	
	public function editAction($author_id = 0){
		$info = $this->authorService()->getInfo($author_id);
		if(empty($info)) $this->error('内容不存在');
		
		if(IS_POST){
			$post = I('post.');
			try {
				$result = $this->authorService()->infoCreateOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功', session('back_url'));
		}
		
		$this->assign('info', $info);
		
		$this->display();
	}
	
	public function delAction($author_id = 0){
		try {
			$result = $this->authorService()->infoDelete($author_id);
			$this->success('删除成功');
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	private function authorService(){
		return D('Information\NewsAuthor', 'Service');
	}
}