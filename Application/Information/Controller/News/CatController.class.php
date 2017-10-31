<?php
namespace Information\Controller\News;
use Information\Controller\FController;
use Common\Basic\Pager;

class CatController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'news',
			'ac'=>'news_cat_index',
		);
		$this->sbset($set);
    }
	

    public function indexAction(){
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$params = array('map'=>array('type'=>0));
    	$all_cat_list = $this->newsService()->catTrList($params);
    	$this->assign('all_cat_list', $all_cat_list);
		
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$params = I('post.');
			try {
				$result = $this->newsService()->catCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功', U('index'));
		}
		
		//顶级分类
		$map = array('parent_id'=>0, 'type'=>0);
		$toplist = $this->newsService()->catAllList($map);
		$this->assign('toplist', $toplist);
		
		$this->display('edit');
	}
	
	public function editAction($cat_id = 0){
		$info = $this->newsService()->getCat($cat_id);
		if(empty($info)) $this->error('内容不存在');
		
		if(IS_POST){
			$params = I('post.');
			$params['cat_id'] = $info['cat_id'];
			try {
				$result = $this->newsService()->catCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功', U('index'));
		}
		
		$this->assign('info', $info);
		
		//顶级分类
		$map = array('parent_id'=>$info['cat_id']);
		$child = $this->newsService()->getCatInfo($map);
		if (empty($child)) {
			$map = array('parent_id'=>0, 'type'=>0);
			$toplist = $this->newsService()->catAllList($map);
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