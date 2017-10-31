<?php
namespace Admin\Controller\Material;
use Admin\Controller\FController;

class LabelController extends FController {
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'material',
			'ac'=>'material_label_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	$categorys = $this->materialLabelService()->getTrList();
		$this->assign('categorys', $categorys);
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$post = I('post.');
			try {
				$this->materialLabelService()->createOrModify($post);
				$this->success('添加成功', U('index'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		
		//标签列表
		$map = array('parent_id'=>0);
		$categorys = $this->materialLabelService()->getOptionList(0, $map);
		$this->assign('categorys', $categorys);
		
		$this->display('edit');
	}
	
	public function editAction($label_id = 0){
		$info = $this->materialLabelService()->getInfo($label_id);
		if(empty($info)){
			$this->error('数据不存在');
		}
		$this->assign('info', $info);
		
		if(IS_POST){			
			$post = I('post.');
			try {
				$this->materialLabelService()->createOrModify($post);
				$this->success('编辑成功', U('index'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		
		//标签列表
		$map = array(
				'parent_id'=>0,
				'label_id'=>array('neq', $info['label_id'])
		);
		$categorys = $this->materialLabelService()->getOptionList($info['parent_id'], $map);
		$this->assign('categorys', $categorys);
		
		$this->display();
	}
	
	public function delAction($label_id = 0){
		try {
			$this->materialLabelService()->delete($label_id);
			$this->success('删除成功', U('index'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	public function recommendAction($label_id){
		try {
			$this->materialLabelService()->recommend($label_id);
			$this->success('操作成功', U('index'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	private function materialLabelService() {
		return D('MaterialLabel', 'Service');
	}
}