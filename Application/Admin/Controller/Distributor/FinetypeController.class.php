<?php
namespace Admin\Controller\Distributor;
use Admin\Controller\FController;

class FinetypeController extends FController {
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'distributor',
			'ac'=>'distributor_finetype_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	session('back_url', __SELF__);
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$list = $this->distributorFineTypeService()->getAllList();
		$this->assign('list', $list);
		
		$this->display();
    }
    
    public function addAction(){
    	if(IS_POST){
    		$post = I('post.');
    		try {
    			$result = $this->distributorFineTypeService()->createOrModify($post);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		$this->success('添加成功', U('index'));
    	}
    	
    	$this->display('edit');
    }
    
    public function editAction($type_id = 0){
    	$info = $this->distributorFineTypeService()->getInfo($type_id);
    	if(empty($info)) $this->error('内容不存在');
    
    	if(IS_POST){
    		$post = I('post.');
    		try {
    			$result = $this->distributorFineTypeService()->createOrModify($post);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		$this->success('修改成功', session('back_url'));
    	}
    	
    	$this->assign('info', $info);
    	
    	$this->display();
    }
    
    public function delAction($type_id = 0){
    	try {
    		$result = $this->distributorFineTypeService()->delete($type_id);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	$this->success('删除成功', session('back_url'));
    }
	
	private function distributorFineTypeService() {
		return D('Distributor\FineType', 'Service');
	}
}