<?php
namespace Admin\Controller\Beauty;
use Admin\Controller\FController;
use Common\Basic\Pager;

class CatController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'beauty',
			'ac'=>'beauty_cat_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	session('back_url', __SELF__);
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$all_cat_list = $this->beautyCatService()->catTrList();
    	$this->assign('all_cat_list', $all_cat_list);
		
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$params = I('post.');
			try {
				$result = $this->beautyCatService()->catCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功', U('index'));
		}
		
		$this->display('edit');
	}
	
	public function editAction($cat_id = 0){
		$info = $this->beautyCatService()->getCat($cat_id);
		if(empty($info)) $this->error('内容不存在');
		
		if(IS_POST){
			$post = I('post.');
			try {
				$result = $this->beautyCatService()->catCreateOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功', session('back_url'));
		}
		
		$this->assign('info', $info);
		
		$this->display();
	}
	
	public function delAction($cat_id = 0){
		try {
			$result = $this->beautyCatService()->catDelete($cat_id);
			$this->success('删除成功');
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	private function beautyCatService(){
		return D('BeautyCat', 'Service');
	}
}