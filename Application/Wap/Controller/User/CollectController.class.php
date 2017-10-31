<?php
namespace Wap\Controller\User;
use Wap\Controller\WapController;
use Common\Basic\Genre;

class CollectController extends WapController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
    }
	
    public function indexAction(){
		$params = array(
				'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
				'pagesize'=>10,
				'user_id'=>session('userid'),
				'collect_type'=>Genre::CollectTypeGoods,
		);
		$datas = $this->collectService()->getCollectGoodsList($params);
		$this->assign('list', $datas['list']);
		
		if(IS_AJAX){
			if(empty($datas['list'])){
				$clist = '';
			}else{
				$clist = $this->renderPartial('_index');
			}
			die($clist);
		}
		
		$this->display();
    }
    
    public function storeAction(){
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>10,
    			'user_id'=>$this->user['user_id'],
    			'collect_type'=>Genre::CollectTypeStore,
    	);
    	$datas = $this->collectService()->getPagerList($params);
    	$this->assign('list', $datas['list']);
    	
    	if(IS_AJAX){
    		if(empty($datas['list'])){
    			$clist = '';
    		}else{
    			$clist = $this->renderPartial('_store');
    		}
    		die($clist);
    	}
    	
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