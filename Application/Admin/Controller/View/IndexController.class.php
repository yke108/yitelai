<?php
namespace Admin\Controller\View;
use Admin\Controller\FController;
use Common\Basic\Pager;
use Admin\Basic\Purview;

class IndexController extends FController {
	public function _initialize(){
		parent::_initialize();

	}
	// 浏览列表
    public function viewAction()
	{
		$set = array(
			'in'=>'view',
			'ac'=>'view_index_view',
		);
		$this->sbset($set);
		
    	$get = I('get.');
    	$this->assign('get',$get);
    	
		$pagesize = 10;
    	$params = array(
			'page' => $get['p'],
			'pagesize' => $pagesize,
			'start' => $get['start'],
			'end' => $get['end'],
    	);
		
    	$result = $this->ViewService()->getlist($params);
		$this->assign('list', $result['list']);
		$pager = new Pager($result['count'], $size);
		$this->assign('pager', $pager->show());
		
    	$this->display();
    }
	// 操作日志
	public function logAction()
	{ 
		$set = array(
			'in'=>'view',
			'ac'=>'view_index_log',
		);
		$this->sbset($set);
		
    	$get = I('get.');
    	$this->assign('get',$get);
    	
		$pagesize = 10;
    	$params = array(
			'page' => $get['p'],
			'pagesize' => $pagesize,
			'fullname' => $get['fullname'],
			'start' => $get['start'],
			'end' => $get['end'],
    	);
		
    	$result = $this->ViewService()->getlog($params);
		$this->assign('list', $result['list']);
		$pager = new Pager($result['count'], $size);
		$this->assign('pager', $pager->show());
		
    	$this->display();
	}
	private function ViewService(){
		return D('View','Service');
	}
}