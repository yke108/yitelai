<?php
namespace Main\Controller\User;
use Main\Controller\MainController;
use Common\Basic\Pager;
use Common\Basic\Genre;

class DesignerController extends MainController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
		
		$this->assign('page_title', '个人中心');
    }
	
    public function indexAction(){
		$p=I('p')?I('p'):I('get.p');
		$size=8;
		$map=array('designer_id'=>array('gt',0));
		$params = array(
			'page'=>$p,
			'pagesize'=>$size,
			'user_id'=>session('userid'),
			'map'=>$map,
		);
		$result=$this->designerService()->orderPagerList($params);
		$this->assign('list', $result['list']);
		$pager=new Pager($result['count'],$size);
		$this->assign('pages',$pager->show_pc());
		
		$this->display();
    }
    
    public function storeAction(){
    	$page = 1; $pagesize = 10;
    	$this->pageAndSize($page, $pagesize);
    	//$list = array(1,2,3,4,5);
    	$params = array(
    			'user_id'=>session('userid'),
    			'collect_type'=>Genre::CollectTypeGoods,
    	);
    	$datas = $this->collectService()->getCollectGoodsList($params);
    	$this->assign('list', $datas['list']);
    	$this->display();
    }
    
    public function delcollectAction($id = 0){
    	$params = array(
    			'id'=>$id,
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
	private function designerService(){
		return D('Designer', 'Service');
	}
	
}