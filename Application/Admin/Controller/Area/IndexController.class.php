<?php
namespace Admin\Controller\Area;
use Admin\Controller\FController;
use Common\Basic\Pager;
use Admin\Basic\Purview;

class IndexController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'area',
			'ac'=>'area_index_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction($id = 0){
    	$get = I('get.');
    	$params = array(
    		'page'=>$get['p'],
    		'pagesize'=>50,
    	);
    	$result = $this->areaService()->infoPagerList($params);
    	if ($result['count'] > 0){
    		$this->assign('list', $result['list']);
    		$pager = new Pager($result['count'], $result['pagesize']);
    		$this->assign('pager', $pager->show());
    		$this->assign('admin_list', $result['admin_list']);
    	}
		$this->assign('get', $get);
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$params = I('post.');
			try {
				$result = $this->areaService()->infoCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功',U('index'),0);
		}
		$this->display('edit');
	}
	
	public function editAction($id = 0){
		$info = $this->areaService()->getInfo($id);
		if(empty($info)) $this->error('内容不存在');
		if(IS_POST){
			$params = I('post.');
			$params['area_id'] = $info['area_id'];
			try {
				$result = $this->areaService()->infoCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功',U('index'),0);
		}
		$this->assign('info', $info);
		$this->display();
	}
	
	public function adminAction($id = 0){
		if(IS_POST){
			$params = I('post.');
			try {
				$result = $this->areaService()->adminSet($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功',U('index'),0);
		}
		$info = $this->areaService()->getInfo($id);
		if(empty($info)) $this->error('内容不存在');
		$this->assign('info', $info);
		$map = [
			'sys_id'=>$this->sys_id,
			'org_id'=>$this->org_id,
		];
		$amdin_list = $this->adminService()->adminList($map);
		$this->assign('admin_list', $amdin_list);
		$this->display();
	}
	
	public function delAction($id = 0){
		$info = $this->areaService()->getInfo($id);
		if(empty($info)) $this->error('内容不存在');
		try {
			$result = $this->areaService()->infoDelete($info['area_id']);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功',U('index'),0);
	}
	
	private function areaService(){
		return D('Area', 'Service');
	}
}