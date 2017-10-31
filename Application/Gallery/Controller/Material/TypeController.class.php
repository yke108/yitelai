<?php
namespace Gallery\Controller\Material;
use Gallery\Controller\FController;
use Common\Basic\Pager;

class TypeController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'material',
			'ac'=>'material_type_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize
    	);
		$datas = $this->materialTypeService()->getPagerList($params);
		$this->assign('list', $datas['list']);
		$pager = new Pager($datas['count'], $this->pagesize);
		$this->assign('pager', $pager->show());
		
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$post = I('post.');
			try {
				$this->materialTypeService()->createOrModify($post);
				$this->success('添加成功', U('index'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		$this->display('edit');
	}
	
	public function editAction($type_id = 0){
		$info = $this->materialTypeService()->getInfo($type_id);
		if(empty($info)){
			$this->error('数据不存在');
		}
		$this->assign('info', $info);
		if(IS_POST){
			$post = I('post.');
			try {
				$this->materialTypeService()->createOrModify($post);
				$this->success('编辑成功', U('index'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		$this->display();
	}
	
	public function delAction($type_id = 0){
		try {
			$this->materialTypeService()->delete($type_id);
			$this->success('删除成功', U('index'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	private function materialTypeService() {
		return D('MaterialType', 'Service');
	}
}