<?php
namespace Admin\Controller\Beauty;
use Admin\Controller\FController;
use Common\Basic\Pager;

class IndexController extends FController {
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'beauty',
			'ac'=>'beauty_index_index',
		);
		$this->sbset($set);
		
		//分类
		$this->assign('catlist', $this->beautyCatService()->catTreeList());
    }
	
    public function indexAction(){
    	session('back_url', __SELF__);
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
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
    	$datas = $this->beautyService()->infoPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $this->pagesize);
    	$this->assign('pager', $pager->show());
    	
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$post = $_POST;
			try {
				$result = $this->beautyService()->infoCreateOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功', U('index'));
		}
		
		$this->display('edit');
	}
	
	public function editAction($beauty_id = 0){
		$info = $this->beautyService()->getInfo($beauty_id);
		if(empty($info)) $this->error('内容不存在');
		
		if(IS_POST){
			$post = $_POST;
			try {
				$result = $this->beautyService()->infoCreateOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功', session('back_url'));
		}
		
		$this->assign('info', $info);
		
		$this->display();
	}
	
	public function delAction($beauty_id = 0){
		try {
			$result = $this->beautyService()->infoDelete($beauty_id);
			$this->success('删除成功', session('back_url'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	public function recommendAction($beauty_id = 0){
		try {
			$result = $this->beautyService()->infoRecommend($beauty_id);
			$this->success('操作成功', session('back_url'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	public function openAction($beauty_id = 0){
		try {
			$result = $this->beautyService()->infoOpen($beauty_id);
			$this->success('操作成功', session('back_url'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	private function beautyService(){
		return D('Beauty', 'Service');
	}
	
	private function beautyCatService(){
		return D('BeautyCat', 'Service');
	}
}