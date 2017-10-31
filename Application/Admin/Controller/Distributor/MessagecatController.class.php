<?php
namespace Admin\Controller\Distributor;
use Admin\Controller\FController;
use Common\Basic\Pager;

class MessagecatController extends FController {
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'distributor',
			'ac'=>'distributor_messagecat_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$all_cat_list = $this->messageCatService()->catTrList();
    	$this->assign('all_cat_list', $all_cat_list);
		
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$params = I('post.');
			try {
				$result = $this->messageCatService()->catCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功',U('index'),0);
		}
		
		//顶级分类
		$map = array('parent_id'=>0);
		$toplist = $this->messageCatService()->catAllList($map);
		$this->assign('toplist', $toplist);
		
		$this->display('edit');
	}
	

	public function editAction($cat_id = 0){
		$info = $this->messageCatService()->getCat($cat_id);
		if(empty($info)) $this->error('内容不存在');
		if(IS_POST){
			$params = I('post.');
			$params['cat_id'] = $info['cat_id'];
			try {
				$result = $this->messageCatService()->catCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功',U('index'),0);
		}
		
		$this->assign('info', $info);
		
		//顶级分类
		$map = array('parent_id'=>$info['cat_id']);
		$child = $this->messageCatService()->getCatInfo($map);
		if (empty($child)) {
			$map = array('parent_id'=>0);
			$toplist = $this->messageCatService()->catAllList($map);
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
		$info = $this->messageCatService()->getCat($cat_id);
		if(empty($info)) $this->error('内容不存在');
		try {
			$result = $this->messageCatService()->catDelete($info['cat_id']);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功');
	}
	
	private function messageCatService(){
		return D('DistributorMessageCat', 'Service');
	}
}