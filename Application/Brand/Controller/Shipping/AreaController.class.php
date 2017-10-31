<?php
namespace Brand\Controller\Shipping;
use Brand\Controller\FController;
use Common\Basic\Pager;

class AreaController extends FController {
	public function _initialize(){
		parent::_initialize();
			
		$set = array(
			'in'=>'shipping',
			'ac'=>'shipping_area_index',
		);
		$this->sbset($set);
		
		$this->assign('province_list', $this->regionService()->getAllRegionsField(array('region_level'=>1)));
    }
	
    public function indexAction(){
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'distributor_id'=>$this->org_id,
    	);
    	$datas = $this->shippingService()->getShippingAreaList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $this->pagesize);
    	$this->assign('pager', $pager->show());
    	 
    	$this->display();
    }
    
    public function addAction(){
    	if(IS_POST){
    		$post = I('post.');
    		try {
    			$this->shippingService()->shippingareaCreateOrModify($post);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		$this->success('添加成功', U('index'));
    	}
    	$this->display('edit');
    }
    
    public function editAction($shipping_area_id = 0){
    	$info = $this->shippingService()->getShippingareaInfo($shipping_area_id);
    	if (empty($info)) {
    		$this->error('数据不存在');
    	}
    	$this->assign('info', $info);
    
    	if(IS_POST){
    		$post = I('post.');
    		try {
    			$this->shippingService()->shippingareaCreateOrModify($post);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		$this->success('修改成功', U('index'));
    	}
    
    	$this->display();
    }
    
    public function delAction($shipping_area_id = 0){
    	$info = $this->shippingService()->getShippingareaInfo($shipping_area_id);
    	if (empty($info)) $this->error('数据不存在');
    	
    	$map = array(
    			'shipping_area_id'=>$shipping_area_id,
    			'distributor_id'=>0,
    	);
    	try {
    		$this->shippingService()->shippingareaDelete($map);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	$this->success('删除成功');
    }
    
    private function shippingService() {
    	return D('Shipping', 'Service');
    }
    
    private function regionService() {
    	return D('Region', 'Service');
    }
}