<?php
namespace Information\Controller\Video;
use Information\Controller\FController;
use Common\Basic\Pager;

class CatController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'news',
			'ac'=>'video_cat_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$params = array('map'=>array('type'=>1));
    	$all_cat_list = $this->newsService()->catTrList($params);
    	$this->assign('all_cat_list', $all_cat_list);
		
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$post = I('post.');
			$post['type'] = 1;
			try {
				$result = $this->newsService()->catCreateOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功', U('index'));
		}
		
		//顶级分类
		$map = array('parent_id'=>0, 'type'=>1);
		$top_list = $this->newsService()->catOptionList($map);
		$this->assign('top_list', $top_list);
		
		$this->display('edit');
	}
	
	public function editAction($cat_id = 0){
		$info = $this->newsService()->getCat($cat_id);
		if(empty($info)) $this->error('内容不存在');
		if(IS_POST){
			$post = I('post.');
			$post['type'] = 1;
			try {
				$result = $this->newsService()->catCreateOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功', U('index'));
		}
		
		$this->assign('info', $info);
		
		//顶级分类
		$map = array(
				'parent_id'=>0,
				'type'=>1,
				'cat_id'=>array('neq', $info['cat_id'])
		);
		$top_list = $this->newsService()->catOptionList($map, $info['parent_id']);
		$this->assign('top_list', $top_list);
		
		$this->display();
	}
	
	public function delAction($cat_id = 0){
		try {
			$result = $this->newsService()->catDelete($cat_id);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功');
	}
	
	public function defaultAction($cat_id = 0){
		try {
			$result = $this->newsService()->catDefault($cat_id);
			$this->success('操作成功', U('index'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	private function newsService(){
		return D('Information\News', 'Service');
	}
}