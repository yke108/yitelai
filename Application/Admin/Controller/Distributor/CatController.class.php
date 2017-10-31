<?php
namespace Admin\Controller\Distributor;
use Admin\Controller\FController;

class CatController extends FController {
	protected $GoodsCatService;
	
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'distributor',
			'ac'=>'distributor_index_index',
		);
		$this->sbset($set);
		
		$this->GoodsCatService = D('GoodsCat', 'Service');
    }
	
    public function indexAction(){
    	$get = I('get.');
    	$params = array(
    			'distributor_id'=>$get['distributor_id'],
    	);
    	$categorys = $this->GoodsCatService->getDisCatTrList($params);
		$this->assign('categorys', $categorys);
		$this->display();
    }
	
	public function stAction(){
		$get = I('get.');
		if ($get['cat_id'] < 1 || $get['distributor_id'] < 1){
			$this->error('系统错误');
		}
		try {
			$params = array(
					'cat_id'=>$get['cat_id'],
					'distributor_id'=>$get['distributor_id'],
			);
			$this->GoodsCatService->setDisCat($params);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('操作成功');
	}
	
	private function goodsCatDistributorService() {
		return D('GoodsCatDistributor', 'Service');
	}
}