<?php
namespace Admin\Controller\User;
use Admin\Controller\FController;
use Common\Basic\Pager;
use Admin\Basic\Purview;

class DemandController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'user',
			'ac'=>'user_demand_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	session('back_url', __SELF__);
		
		$get = I('get.');
    	$org_id = $this->org_id;
    	$params = array(
    		'page'=>$get['p'],
    		'pagesize'=>10,
    	);
    	$result = $this->userDemandService()->infoPagerList($params);
    	if ($result['count'] > 0){
    		$this->assign('list', $result['list']);
    		$pager = new Pager($result['count'], $result['pagesize']);
    		$this->assign('pager', $pager->show());
    	}
    	
		$this->assign('get', $get);
		
		$this->display();
    }
	
	public function addAction($designer_id=0){
		if(IS_POST){
			$params = I('post.');
			try {
				$result = $this->userDemandService()->infoCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功', U('index'));
		}
		$this->display('edit');
	}
	
	public function editAction($demand_id = 0){
		$info = $this->userDemandService()->getInfo($demand_id);
		if(empty($info)) $this->error('内容不存在');
		if(IS_POST){
			$params = I('post.');
			try {
				$result = $this->userDemandService()->infoCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功', session('back_url'));
		}
		
		$this->assign('info', $info);
		
		$this->display();
	}
	
	public function delAction($demand_id = 0){
		try {
			$result = $this->userDemandService()->infoDelete($demand_id);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功');
	}
	
	public function is_showAction($demand_id = 0){
		try {
			$result = $this->userDemandService()->isShow($demand_id);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('操作成功', session('back_url'));
	}
	
	private function userDemandService(){
		return D('UserDemand', 'Service');
	}
}