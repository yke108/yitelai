<?php
namespace Admin\Controller\Shipping;
use Admin\Controller\FController;
use Common\Basic\Pager;

class IndexController extends FController {
	private $ShippingService;
	
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'shipping',
			'ac'=>'shipping_index_index',
		);
		$this->sbset($set);
		
		$this->ShippingService = D('Shipping', 'Service');
		
		$this->assign("shipping_code_list", $this->ShippingService->getAllCodeList());
    }
	
    public function indexAction(){
    	$params = array(
				'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'distributor_id'=>0,
    	);
    	$datas = $this->ShippingService->getPagerShippingList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $this->pagesize);
    	$this->assign('pager', $pager->show());
    	
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$post = I('post.');
			try {
				$this->ShippingService->shippingCreateOrModify($post);
				$this->success('添加成功', U('index'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		$this->display('edit');
	}
	
	public function editAction($shipping_id = 0){
		$info = $this->ShippingService->getShippingInfo($shipping_id);
		if (empty($info)) {
			$this->error('数据不存在');
		}
		$this->assign('info', $info);
		
		if(IS_POST){
			$post = I('post.');
			try {
				$this->ShippingService->shippingCreateOrModify($post);
				$this->success('修改成功', U('index'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		
		$this->display();
	}
	
    public function delAction($shipping_id = 0){
		$info = $this->ShippingService->getShippingInfo($shipping_id);
		if (empty($info)) {
			$this->error('数据不存在');
		}
		try {
			$map = array(
					'shipping_id'=>$shipping_id,
					'distributor_id'=>$this->org_id,
			);
			$this->ShippingService->shippingDelete($map);
			$this->success('删除成功', U('index'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
    }
}