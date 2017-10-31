<?php
namespace Gallery\Controller\Material;
use Gallery\Controller\FController;
use Common\Basic\Pager;

class SpecController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'material',
			'ac'=>'material_type_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction($type_id = 0){
    	session('back_url', __SELF__);
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'type_id'=>$type_id
    	);
		$datas = $this->materialSpecService()->getPagerList($params);
		$this->assign('list', $datas['list']);
		$pager = new Pager($datas['count'], $this->pagesize);
		$this->assign('pager', $pager->show());
		$this->display();
    }
	
	public function addAction(){
		$get = I('get.');
		$this->assign('get', $get);
		
		if(IS_POST){
			$post = I('post.');
			try {
				$this->materialSpecService()->createOrModify($post);
				$this->success('添加成功', U('index', array('type_id'=>$post['type_id'])));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		$this->display('edit');
	}
	
	public function editAction($spec_id = 0){
		$get = I('get.');
		$this->assign('get', $get);
		
		$info = $this->materialSpecService()->getInfo($spec_id);
		if(empty($info)){
			$this->error('数据不存在');
		}
		$this->assign('info', $info);
		if(IS_POST){
			$post = I('post.');
			$post['spec_values'] = $post['spec_type'] == 1 ? $post['spec_values'] : '';
			try {
				$this->materialSpecService()->createOrModify($post);
				$this->success('编辑成功', U('index', array('type_id'=>$post['type_id'])));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		$this->display();
	}
	
	public function delAction($spec_id = 0){
		try {
			$this->materialSpecService()->delete($spec_id);
			$this->success('删除成功', session('back_url'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	private function materialSpecService() {
		return D('MaterialSpec', 'Service');
	}
}