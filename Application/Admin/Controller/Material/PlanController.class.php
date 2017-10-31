<?php
namespace Admin\Controller\Material;
use Admin\Controller\FController;
use Common\Basic\Pager;

class PlanController extends FController {
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'material',
			'ac'=>'material_plan_index',
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
		$datas = $this->materialPlanService()->getPagerList($params);
		$this->assign('list', $datas['list']);
		$pager = new Pager($datas['count'], $this->pagesize);
		$this->assign('pager', $pager->show());
		
		$this->display('index');
	}
	
	public function addAction(){
		if(IS_POST){			
			$post = I('post.');
			try {
				$this->materialPlanService()->createOrModify($post);
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
		
		$this->display('edit');
	}
	
	public function editAction($material_id = 0){
		$info = $this->materialPlanService()->getInfo($material_id);
		if(empty($info)){
			$this->error('数据不存在');
		}
		
		if(IS_POST){
			$post = I('post.');
			try {
				$this->materialPlanService()->createOrModify($post);
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
		
		$this->display();
	}
	
	public function delAction($material_id = 0){
		try {
			$this->materialPlanService()->delete($material_id);
			$this->success('删除成功', session('back_url'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	//批量删除
	public function delallAction(){
		if(IS_POST){
			try {
				$this->materialPlanService()->delall(I('material_ids'));
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
				$this->materialPlanService()->up(I('material_ids'));
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
				$this->materialPlanService()->down(I('material_ids'));
				$this->success('操作成功', session('back_url'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
	}
	
	public function recommendAction($material_id){
		try {
			$this->materialPlanService()->recommend($material_id);
			$this->success('操作成功', session('back_url'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	public function showAction($material_id){
		try {
			$this->materialPlanService()->show($material_id);
			$this->success('操作成功', session('back_url'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	private function materialPlanService() {
		return D('MaterialPlan', 'Service');
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
}