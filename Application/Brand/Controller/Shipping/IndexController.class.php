<?php
namespace Brand\Controller\Shipping;
use Brand\Controller\FController;
use Common\Basic\Pager;

class IndexController extends FController {
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'shipping',
			'ac'=>'shipping_index_index',
		);
		$this->sbset($set);
		
		$this->assign("shipping_code_list", $this->shippingService()->getAllCodeList());
    }
	
    public function indexAction(){
    	$params = array(
				'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'distributor_id'=>$this->org_id,
    	);
    	$datas = $this->shippingService()->getPagerShippingList($params);
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
				$this->shippingService()->shippingCreateOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功', U('index'));
		}
		$this->display('edit');
	}
	
	public function editAction($shipping_id = 0){
		$info = $this->shippingService()->getShippingInfo($shipping_id);
		if (empty($info)) $this->error('配送方式不存在');
		
		if(IS_POST){
			$post = I('post.');
			$post['distributor_id'] = $this->org_id;
			try {
				$this->shippingService()->shippingCreateOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功', U('index'));
		}
		
		$this->assign('info', $info);
		
		$this->display();
	}
	
    public function delAction($shipping_id = 0){
		$info = $this->shippingService()->getShippingInfo($shipping_id);
		if (empty($info)) $this->error('配送方式不存在');
		
		$map = array(
				'shipping_id'=>$shipping_id,
				'distributor_id'=>$this->org_id,
		);
		try {
			$this->shippingService()->shippingDelete($map);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功');
    }
    
    private function shippingService() {
    	return D('Shipping', 'Service');
    }
}