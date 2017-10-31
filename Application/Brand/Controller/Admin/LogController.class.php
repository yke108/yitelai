<?php
namespace Brand\Controller\Admin;
use Brand\Controller\FController;
use Common\Basic\Pager;

class LogController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'admin',
			'ac'=>'admin_log_index',
		);
		$this->sbset($set);
		$this->purviewCheck();
    }
	
    public function indexAction($id = 0){
    	$get = I('get.');
    	$params = array(
    		'page'=>$get['p'],
    		'sys_id'=>$this->sys_id,
    		'org_id'=>$this->org_id,
    	);
    	$result = $this->adminService()->logPagerList($params);
    	if ($result['count'] > 0){
    		$this->assign('list', $result['list']);
    		$pager = new Pager($result['count'], $result['pagesize']);
    		$this->assign('pager', $pager->show());
    	}
		$this->assign('get', $get);
		$this->display();
    }
}