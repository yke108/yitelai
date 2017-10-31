<?php
namespace Distributor\Controller\Question;
use Distributor\Controller\FController;
use Common\Basic\Pager;

class CatController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'question',
			'ac'=>'question_cat_index',
		);
		$this->sbset($set);
		$params=array('parent_id'=>0);
		$top_cat=$this->questionService()->findFieldData($params,'cat_id,cat_name');
		$this->assign('top_cat',$top_cat);
		
    }
	
    public function indexAction($id = 0){
    	$get = I('get.');
    	$this->assign('get', $get);
    	$list = $this->questionService()->catList();
    	$this->assign('list', $list);
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$params = I('post.');
			try {
				$result = $this->questionService()->catCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功',U('index'),0);
		}
		$this->display('edit');
	}
	
	public function editAction($id = 0){
		$info = $this->questionService()->getCat($id);
		if(empty($info)) $this->error('内容不存在');
		if(IS_POST){
			$params = I('post.');
			$params['cat_id'] = $info['cat_id'];
			try {
				$result = $this->questionService()->catCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功',U('index'),0);
		}
		$this->assign('info', $info);
		$this->display();
	}
	
	public function delAction($id = 0){
		$info = $this->questionService()->getCat($id);
		if(empty($info)) $this->error('内容不存在');
		try {
			$result = $this->questionService()->catDelete($info['cat_id']);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功',U('index'),0);
	}
	
	private function questionService(){
		return D('Question', 'Service');
	}
}