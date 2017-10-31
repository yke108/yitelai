<?php
namespace Admin\Controller\Distributor;
use Admin\Controller\FController;

class TypeController extends FController {
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'merchant',
			'ac'=>'distributor_type_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	session('back_url', __SELF__);
    	$get = I('get.');
    	$this->assign('get', $get);
    	$categorys = $this->distributorTypeService()->getTrList();
		$this->assign('categorys', $categorys);
		$this->display();
    }
    
    public function addAction(){
    	if(IS_POST){
    		$post = I('post.');
    		try {
    			$result = $this->distributorTypeService()->createOrModify($post);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		$this->success('添加成功', U('index'));
    	}
    	
    	$top_list = $this->distributorTypeService()->getTopList();
    	$this->assign('top_list', $top_list);
    	
    	$this->display('edit');
    }
    
    public function editAction($type_id = 0){
    	$info = $this->distributorTypeService()->getInfo($type_id);
    	if(empty($info)) $this->error('内容不存在');
    
    	if(IS_POST){
    		$post = I('post.');
    		try {
    			$result = $this->distributorTypeService()->createOrModify($post);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		$this->success('修改成功', session('back_url'));
    	}
    	
    	$this->assign('info', $info);
    	
    	$type_ids = $this->distributorTypeService()->getCatChilds($info['type_id']);
    	$map['type_id'] = array('not in', $type_ids);
    	$top_list = $this->distributorTypeService()->getTopList($map);
    	$this->assign('top_list', $top_list);
    	
    	$this->display();
    }
    
    public function delAction($type_id = 0){
    	try {
    		$result = $this->distributorTypeService()->delete($type_id);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	$this->success('删除成功', session('back_url'));
    }
	
	private function distributorTypeService() {
		return D('Distributor\Type', 'Service');
	}
}