<?php
namespace Admin\Controller\Material;
use Admin\Controller\FController;
use Common\Basic\Pager;

class IndexController extends FController {
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'material',
			'ac'=>'material_index_index',
		);
		$this->sbset($set);
    }
    
    public function indexAction(){
    	session('back_url', __SELF__);
    	
		$this->listDisplay();
    }
	
    public function singleselAction(){
    	layout('Layout/sel');
    	$this->listDisplay();
    }
    
	private function listDisplay($map = array()){
		$get = I('get.');
		$this->assign('get', $get);
		
		//分类列表
		$categorys = $this->materialCatService()->getOptionList($get['cat_id']);
		$this->assign('categorys', $categorys);
		
		//设计师列表
		$map = array();
		$designer_list = $this->designerService()->infoList();
		$this->assign('designer_list', $designer_list);
		
		//标签列表
		$map = array();
		$labels = $this->materialLabelService()->getOptionList($get['label_id'], $map);
		$this->assign('labels', $labels);
		
		$params = array(
				'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
				'pagesize'=>$this->pagesize,
		);
		if ($get['keyword']) {
			$params['keyword'] = $get['keyword'];
		}
		if ($get['cat_id']) {
			$params['cat_id'] = $get['cat_id'];
		}
		if ($get['designer_id']) {
			$params['designer_id'] = $get['designer_id'];
		}
		if ($get['label_id']) {
			$params['label_id'] = $get['label_id'];
		}
		if ($get['is_show'] != 'all') {
			$params['is_show'] = $get['is_show'];
		}
		if ($get['is_recommend']) {
			$params['is_recommend'] = $get['is_recommend'];
		}
		$datas = $this->materialService()->getPagerList($params);
		$this->assign('list', $datas['list']);
		$pager = new Pager($datas['count'], $this->pagesize);
		$this->assign('pager', $pager->show());
		
		$this->display('index');
	}
	
	public function addAction(){
		if(IS_POST){			
			$post = I('post.');
			try {
				$this->materialService()->createOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功', U('index'));
		}
		
		//分类列表
		$map = array();
		$categorys = $this->materialCatService()->getOptionList(0, $map);
		$this->assign('categorys', $categorys);
		
		//设计师列表
		$map = array();
		$designer_list = $this->designerService()->infoList();
		$this->assign('designer_list', $designer_list);
		
		//标签
		$label = $this->materialLabelService()->getAllList();
		$this->assign('label_list', $label['list']);
		
		//全部类型
		$all_cats = $this->materialCatService()->selectAllList();
		foreach ($all_cats as $v) {
			if ($v['type_id']) {
				$cat_type_list[$v['cat_id']] = $v['type_id'];
			}
		}
		$this->assign('cat_type_list', $cat_type_list);
		
		$all_type_list = $this->materialTypeService()->getOptionList();
		foreach ($all_type_list as $k => $v) {
			if (is_array($v)) {
				foreach ($v as $k2 => $v2) {
					if ($v2['spec_type'] == 1) {
						$spec_values = explode("\n", $v2['spec_values']);
						$options = '';
						foreach ($spec_values as $v3) {
							$options .= '<option value="'.$v3.'">'.$v3.'</option>';
						}
						$all_type_list[$k][$k2]['spec_values'] = $options;
					}
				}
			}
		}
		$this->assign('all_type_list', $all_type_list);
		
		$this->display('edit');
	}
	
	public function editAction($material_id = 0){
		$info = $this->materialService()->getInfo($material_id);
		if(empty($info)){
			$this->error('数据不存在');
		}
		
		if(IS_POST){
			$post = I('post.');
			try {
				$this->materialService()->createOrModify($post);
				$back_url = session('back_url');
				session('back_url', null);
				$this->success('编辑成功', $back_url);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		
		$this->assign('info', $info);
		
		//分类列表
		$map = array();
		$categorys = $this->materialCatService()->getOptionList($info['cat_id'], $map);
		$this->assign('categorys', $categorys);
		
		//设计师列表
		$map = array();
		$designer_list = $this->designerService()->infoList();
		$this->assign('designer_list', $designer_list);
		
		//标签
		$label = $this->materialLabelService()->getAllList();
		$this->assign('label_list', $label['list']);
		
		//全部类型
		$all_cats = $this->materialCatService()->selectAllList();
		foreach ($all_cats as $v) {
			if ($v['type_id']) {
				$cat_type_list[$v['cat_id']] = $v['type_id'];
			}
		}
		$this->assign('cat_type_list', $cat_type_list);
		
		$all_type_list = $this->materialTypeService()->getOptionList();
		foreach ($all_type_list as $k => $v) {
			if (is_array($v)) {
				foreach ($v as $k2 => $v2) {
					if ($v2['spec_type'] == 1) {
						$spec_values = explode("\n", $v2['spec_values']);
						$options = '';
						foreach ($spec_values as $v3) {
							$options .= '<option value="'.$v3.'">'.$v3.'</option>';
						}
						$all_type_list[$k][$k2]['spec_values'] = $options;
					}
				}
			}
		}
		$this->assign('all_type_list', $all_type_list);
		
		//商品属性
		$spec_value_list = array();
		foreach ($info['spec_list'] as $v) {
			$spec_value_list[$v['spec_id']] = $v;
		}
		$cat = $this->materialCatService()->getInfo($info['cat_id']);
		if ($cat['type_id']) {
			$params = array(
					'type_id'=>$cat['type_id']
			);
			$datas = $this->materialSpecService()->getPagerList($params);
			$spec_list = array();
			foreach ($datas['list'] as $v) {
				$spec_value = $spec_value_list[$v['spec_id']]['spec_value'];
				if ($v['spec_type'] == 1) {
					$spec_values_tmp = explode("\n", $v['spec_values']);
					$spec_values = array();
					foreach ($spec_values_tmp as $v2) {
						$is_selected = 0;
						if (trim($v2) == $spec_value) {
							$is_selected = 1;
						}
						$spec_values[] = array(
								'spec_value'=>trim($v2),
								'is_selected'=>$is_selected
						);
					}
					$v['spec_values'] = $spec_values;
				}else {
					$v['spec_value'] = $spec_value;
				}
				$spec_list[] = $v;
			}
			$this->assign('spec_list', $spec_list);
		}
		
		$this->display();
	}
	
	public function delAction($material_id = 0){
		try {
			$this->materialService()->delete($material_id);
			$this->success('删除成功');
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	//批量删除
	public function delallAction(){
		if(IS_POST){
			try {
				$this->materialService()->delall(I('material_ids'));
				$this->success('操作成功', session('back_url'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
	}
	
	//批量上架
	public function upAction(){
		if(IS_POST){
			try {
				$this->materialService()->up(I('material_ids'));
				$this->success('操作成功', session('back_url'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
	}
	
	//批量下架
	public function downAction(){
		if(IS_POST){
			try {
				$this->materialService()->down(I('material_ids'));
				$this->success('操作成功', session('back_url'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
	}
	
	public function recommendAction($material_id){
		try {
			$this->materialService()->recommend($material_id);
			$this->success('操作成功', session('back_url'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	public function showAction($material_id){
		try {
			$this->materialService()->show($material_id);
			$this->success('操作成功', session('back_url'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	private function materialService() {
		return D('Material', 'Service');
	}
	
	private function materialCatService() {
		return D('MaterialCat', 'Service');
	}
	
	private function materialLabelService() {
		return D('MaterialLabel', 'Service');
	}
	
	private function designerService() {
		return D('Designer', 'Service');
	}
	
	private function materialTypeService() {
		return D('MaterialType', 'Service');
	}
	
	private function materialSpecService() {
		return D('MaterialSpec', 'Service');
	}
}