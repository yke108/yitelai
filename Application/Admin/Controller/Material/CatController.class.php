<?php
namespace Admin\Controller\Material;
use Admin\Controller\FController;

class CatController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'material',
			'ac'=>'material_cat_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	$categorys = $this->materialCatService()->getTrList();
		$this->assign('categorys', $categorys);
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$post = I('post.');
			try {
				$this->materialCatService()->createOrModify($post);
				$this->success('添加成功', U('index'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		
		//分类列表
		$map = array('parent_id'=>0);
		$categorys = $this->materialCatService()->getOptionList(0, $map);
		$this->assign('categorys', $categorys);
		
		//素材类型
		$type_list = $this->materialTypeService()->getAllList();
		$this->assign('type_list', $type_list);
		
		$this->display('edit');
	}
	
	public function editAction($cat_id = 0){
		$info = $this->materialCatService()->getInfo($cat_id);
		if(empty($info)){
			$this->error('数据不存在');
		}
		$this->assign('info', $info);
		
		if(IS_POST){			
			$post = I('post.');
			try {
				$this->materialCatService()->createOrModify($post);
				$this->success('编辑成功', U('index'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		
		//分类列表
		$map = array(
				'parent_id'=>0,
				'cat_id'=>array('neq', $info['cat_id'])
		);
		$categorys = $this->materialCatService()->getOptionList($info['parent_id'], $map);
		$this->assign('categorys', $categorys);
		
		//素材类型
		$type_list = $this->materialTypeService()->getAllList();
		$this->assign('type_list', $type_list);
		
		$this->display();
	}
	
	public function delAction($cat_id = 0){
		try {
			$this->materialCatService()->delete($cat_id);
			$this->success('删除成功');
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	public function recommendAction($cat_id){
		try {
			$this->materialCatService()->recommend($cat_id);
			$this->success('操作成功', U('index'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	private function materialCatService() {
		return D('MaterialCat', 'Service');
	}
	
	private function materialTypeService() {
		return D('MaterialType', 'Service');
	}
}