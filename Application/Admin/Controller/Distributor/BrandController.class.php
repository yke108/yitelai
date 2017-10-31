<?php
namespace Admin\Controller\Distributor;
use Admin\Controller\FController;
use Common\Basic\Pager;

class BrandController extends FController {
	protected $GoodsCatService;
	
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'distributor',
			'ac'=>'distributor_index_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	session('back_url', __SELF__);
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$distributor_info = $this->distributorService()->getInfo($get['distributor_id']);
    	$brand_ids = $distributor_info['brand_ids'] ? explode(',', $distributor_info['brand_ids']) : array();
    	
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'is_show'=>1,
    	);
    	$datas = $this->goodsBrandService()->getPagerList($params);
    	foreach ($datas['list'] as $k => $v) {
    		$datas['list'][$k]['is_use'] = in_array($v['brand_id'], $brand_ids) ? 1 : 0;
    	}
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $this->pagesize);
    	$this->assign('pager', $pager->show());
    	
		$this->display();
    }
	
	public function stAction(){
		$get = I('get.');
		if ($get['brand_id'] < 1 || $get['distributor_id'] < 1){
			$this->error('系统错误');
		}
		try {
			$params = array(
					'brand_id'=>$get['brand_id'],
					'distributor_id'=>$get['distributor_id'],
			);
			$this->goodsBrandService()->setDisBrand($params);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('操作成功', session('back_url'));
	}
	
	private function goodsBrandService() {
		return D('GoodsBrand', 'Service');
	}
	
	private function distributorService(){
		return D('Distributor', 'Service');
	}
}