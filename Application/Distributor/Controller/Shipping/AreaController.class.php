<?php
namespace Distributor\Controller\Shipping;
use Distributor\Controller\FController;
use Common\Basic\Pager;

class AreaController extends FController {
	private $ShippingService;
	
	public function _initialize(){
		parent::_initialize();
			
		$set = array(
			'in'=>'shipping',
			'ac'=>'shipping_area_index',
		);
		$this->sbset($set);
		
		$this->ShippingService = D('Shipping', 'Service');
		
		$this->assign('province_list', $this->RegionService()->getAllRegionsField(array('region_level'=>1)));
    }
	
    public function indexAction(){
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'distributor_id'=>$this->org_id,
    	);
    	$datas = $this->ShippingService->getPagerShippingareaList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $this->pagesize);
    	$this->assign('pager', $pager->show());
    	 
    	$this->display();
    }
    
    public function addAction(){
    	if(IS_POST){
    		$post = I('post.');
    		$post['distributor_id'] = $this->org_id;
    		try {
    			$this->ShippingService->shippingareaCreateOrModify($post);
    			$this->success('添加成功', U('index'));
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    	}
    	$this->display('edit');
    }
    
    public function editAction($shipping_area_id = 0){
    	$info = $this->ShippingService->getShippingareaInfo($shipping_area_id);
    	if (empty($info)) {
    		$this->error('数据不存在');
    	}
    	$this->assign('info', $info);
    
    	if(IS_POST){
    		$post = I('post.');
    		$post['distributor_id'] = $this->org_id;
    		try {
    			$this->ShippingService->shippingareaCreateOrModify($post);
    			$this->success('修改成功', U('index'));
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    	}
    
    	$this->display();
    }
    
    public function delAction($shipping_area_id = 0){
    	$info = $this->ShippingService->getShippingareaInfo($shipping_area_id);
    	if (empty($info)) {
    		$this->error('数据不存在');
    	}
    
    	try {
    		$map = array(
    				'shipping_area_id'=>$shipping_area_id,
    				'distributor_id'=>$this->org_id,
    		);
    		$this->ShippingService->shippingareaDelete($map);
    		$this->success('删除成功', U('index'));
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    }
    
    private function RegionService() {
    	return D('Region', 'Service');
    }
}