<?php
namespace Information\Controller\Cook;
use Information\Controller\FController;
use Common\Basic\Pager;

class BookController extends FController {
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'cook',
			'ac'=>'cook_book_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	session('back_url', __SELF__);
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$params = array();
    	$this->assign('all_cat_list', $this->cookCatService()->catOptionList($params, $get['cat_id']));
    	
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    	);
    	if (!empty($get['keyword'])) {
    		$params['keyword'] = $get['keyword'];
    	}
    	if (!empty($get['cat_id'])) {
    		$params['cat_id'] = $get['cat_id'];
    	}
    	$datas = $this->bookService()->infoPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $this->pagesize);
    	$this->assign('pager', $pager->show());
    	
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$params = I('post.');
			try {
				$result = $this->bookService()->infoCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功', U('index'));
		}
		
		$this->assign('catlist', $this->bookService()->catTreeList());
		
		$this->display('edit');
	}
	
	public function editAction($book_id = 0){
		$info = $this->bookService()->getInfo($book_id);
		if(empty($info)) $this->error('内容不存在');
		
		if(IS_POST){
			$post = I('post.');
			try {
				$result = $this->bookService()->infoCreateOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功', session('back_url'));
		}
		
		$this->assign('info', $info);
		
		$this->assign('catlist', $this->bookService()->catTreeList());
		
		$this->display();
	}
	
	public function delAction($book_id = 0){
		
		try {
			$result = $this->bookService()->infoDelete($book_id);
			$this->success('删除成功', session('back_url'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	public function recommendAction($book_id = 0){
		try {
			$result = $this->bookService()->infoRecommend($book_id);
			$this->success('操作成功', U('index'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	public function openAction($book_id = 0){
		try {
			$result = $this->bookService()->infoOpen($book_id);
			$this->success('操作成功', session('back_url'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	private function bookService(){
		return D('Information\CookBook', 'Service');
	}
	
	private function cookCatService(){
		return D('Information\CookCat', 'Service');
	}
}