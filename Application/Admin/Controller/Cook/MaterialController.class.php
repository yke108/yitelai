<?php
namespace Admin\Controller\Cook;
use Admin\Controller\FController;
use Common\Basic\Pager;
use Common\Basic\Status;

class MaterialController extends FController {
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'cook',
			'ac'=>'cook_material_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	session('back_url', __SELF__);
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$this->publicAssign();
    	
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    	);
    	$datas = $this->cookMaterialService()->infoPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $this->pagesize);
    	$this->assign('pager', $pager->show());
    	
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$post = I('post.');
			try {
				$result = $this->cookMaterialService()->infoCreateOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功', U('index'));
		}
		
		$this->publicAssign();
		
		$this->display('edit');
	}
	
	public function editAction($material_id = 0){
		$info = $this->cookMaterialService()->getInfo($material_id);
		if(empty($info)) $this->error('内容不存在');
		
		if(IS_POST){
			$post = I('post.');
			try {
				$result = $this->cookMaterialService()->infoCreateOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功', session('back_url'));
		}
		
		$this->assign('info', $info);
		
		$this->publicAssign();
		
		$this->display();
	}
	
	public function delAction($material_id = 0){
		try {
			$result = $this->cookMaterialService()->infoDelete($material_id);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功', session('back_url'));
	}
	
	public function recommendAction($material_id = 0){
		try {
			$result = $this->cookMaterialService()->infoRecommend($material_id);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('操作成功', session('back_url'));
	}
	
	public function openAction($material_id = 0){
		try {
			$result = $this->cookMaterialService()->infoOpen($material_id);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('操作成功', session('back_url'));
	}
	
	private function publicAssign() {
		
	}
	
	private function cookMaterialService(){
		return D('CookMaterial', 'Service');
	}
}