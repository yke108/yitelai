<?php
namespace Admin\Controller\Designer;
use Admin\Controller\FController;
use Common\Basic\Pager;
use Admin\Basic\Purview;

class StatusController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'designer',
			'ac'=>'designer_status_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction($id = 0){
    	$get = I('get.');
    	$params = array(
    		'page'=>$get['p'],
    		'pagesize'=>10,
    	);
    	$result = $this->orderstatusService()->StatusPagerList($params);
    	if ($result['count'] > 0){
    		$this->assign('list', $result['list']);
    		$pager = new Pager($result['count'], $result['pagesize']);
    		$this->assign('pager', $pager->show());
    	}
		$this->assign('get', $get);
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$params = I('post.');
			try {
				$result = $this->orderstatusService()->statusCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功',U('index'),0);
		}
		$this->display('edit');
	}
	
	public function editAction($id = 0){
		$info = $this->orderstatusService()->getStatus($id);
		if(empty($info)) $this->error('内容不存在');
		if(IS_POST){
			$params = I('post.');
			$params['status_id'] = $info['status_id'];
			try {
				$result = $this->orderstatusService()->statusCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功',U('index'),0);
		}
		$this->assign('info', $info);
		$this->display();
	}
	
	public function delAction($id = 0){
		$info = $this->orderstatusService()->getStatus($id);
		if(empty($info)) $this->error('内容不存在');
		try {
			$result = $this->orderstatusService()->statusDelete($info['status_id']);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功',U('index'),0);
	}
	
	private function orderstatusService(){
		return D('Designer', 'Service');
	}
}