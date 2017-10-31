<?php
namespace Main\Controller\User;
use Main\Controller\MainController;
use Common\Basic\Pager;
use Common\Basic\Genre;

class CollectController extends MainController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
		
		$this->assign('page_title', '个人中心');
    }
	
    public function indexAction(){
		$page = 1; $pagesize = 10;
		$this->pageAndSize($page, $pagesize);
		//$list = array(1,2,3,4,5);
		$params = array(
				'user_id'=>session('userid'),
				'collect_type'=>Genre::CollectTypeGoods,
		);
		$datas = $this->collectService()->getCollectGoodsList($params);
		$this->assign('list', $datas['list']);
		$pager = new Pager($datas['count'], $pagesize);
		$this->assign('pages', $pager->show_pc());
		$this->display();
    }
    
    public function storeAction(){
    	$page = 1; $pagesize = 10;
    	$this->pageAndSize($page, $pagesize);
    	//$list = array(1,2,3,4,5);
    	$params = array(
    			'user_id'=>$this->user['user_id'],
    			'collect_type'=>Genre::CollectTypeStore,
    	);
    	$datas = $this->collectService()->getPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $pagesize);
    	$this->assign('pages', $pager->show_pc());
    	$this->display();
    }
    
    public function delcollectAction($id = 0){
    	$params = array(
    			'collect_id'=>$id,
    			'user_id'=>session('userid'),
    	);
    	try {
    		$res = $this->collectService()->del($params);
    		$this->success('删除成功');
    	} catch (\Exception $e){
    		$this->error($e->getMessage());
    	}
    }
	
	private function collectService(){
		return D('Collect', 'Service');
	}
}