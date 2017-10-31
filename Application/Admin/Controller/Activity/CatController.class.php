<?php
namespace Admin\Controller\Activity;
use Admin\Controller\FController;
use Common\Basic\Pager;

class CatController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'activity',
			'ac'=>'activity_cat_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	session('back_url', __SELF__);
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$all_cat_list = $this->activityCatService()->catTrList();
    	$this->assign('all_cat_list', $all_cat_list);
		
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$params = I('post.');
			try {
				$result = $this->activityCatService()->catCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功', U('index'));
		}
		
		//顶级分类
		$map = array('parent_id'=>0);
		$toplist = $this->activityCatService()->catAllList($map);
		$this->assign('toplist', $toplist);
		
		$this->display('edit');
	}
	
	public function editAction($cat_id = 0){
		$info = $this->activityCatService()->getCat($cat_id);
		if(empty($info)) $this->error('内容不存在');
		
		if(IS_POST){
			$post = I('post.');
			try {
				$result = $this->activityCatService()->catCreateOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功', session('back_url'));
		}
		
		$this->assign('info', $info);
		
		//顶级分类
		$map = array('parent_id'=>$info['cat_id']);
		$child = $this->activityCatService()->getCatInfo($map);
		if (empty($child)) {
			$map = array('parent_id'=>0);
			$toplist = $this->activityCatService()->catAllList($map);
			$newtoplist = array();
			foreach ($toplist as $v) {
				if ($v['cat_id'] != $info['cat_id']) {
					$newtoplist[] = $v;
				}
			}
			$this->assign('toplist', $newtoplist);
		}
		
		$this->display();
	}
	
	public function delAction($cat_id = 0){
		try {
			$result = $this->activityCatService()->catDelete($cat_id);
			$this->success('删除成功');
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	private function activityCatService(){
		return D('ActivityCat', 'Service');
	}
}