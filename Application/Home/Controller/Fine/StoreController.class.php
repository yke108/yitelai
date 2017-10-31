<?php
namespace Home\Controller\Fine;
use Home\Controller\BaseController;
use Common\Basic\Status;

class StoreController extends BaseController {	
	public function _initialize(){
		parent::_initialize();
    }
    
    protected function _purviewCheck() {
    	$this->purviewCheck('index');
    }
	
    public function indexAction() { /*罚款列表 */
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	//全部
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'distributor_id'=>session('admin_id'),
    	);
    	$result = $this->distributorFineService()->getPagerList($params);
    	$this->assign('list', $result['list']);
    	
    	if (IS_AJAX) {
    		if(empty($result['list'])){
    			$clist = '';
    		}else{
    			$clist = $this->renderPartial('_index');
    		}
    		$this->ajaxReturn(array('html'=>$clist, 'p'=>$params['page']+1));
    	}
    	
    	$this->assign('page_title', '罚款列表');
		$this->display();
    }
    
    public function infoAction($fine_id = 0) {
    	$info = $this->distributorFineService()->getInfo($fine_id);
    	if (empty($info)) $this->error('数据不存在');
    	$this->assign('info', $info);
    	
    	$this->assign('page_title', '罚款详情');
    	$this->display();
    }
    
    public function addAction() {
    	if (IS_POST) {
    		$post = I('post.');
    		try {
    			$this->distributorFineService()->createFine($post);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		$this->success('提交成功', U('index'));
    	}
    	
    	//违规类型
    	$type_list = $this->distributorFineTypeService()->getAllList();
    	$this->assign('type_list', $type_list);
    	
    	//违规品牌(店铺)
    	$map = array('status'=>Status::DistributorStatusNormal);
    	$distributor_list = $this->distributorService()->getAllList($map);
    	$this->assign('distributor_list', $distributor_list);
    	
    	$this->assign('page_title', '发布罚款');
    	$this->display();
    }
    
    public function appealAction() { /*申诉 */
    	if (IS_POST) {
    		$params = array(
    				'fine_id'=>I('get.fine_id'),
    				'status'=>I('get.status'),
    		);
    		try {
    			$this->distributorFineService()->check($params);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    	}
    	
    	$this->assign('page_title', '申诉');
    	$this->display();
    }
    
    public function appeal_infoAction() {
    	$this->assign('page_title', '申诉详情');
    	$this->display();
    }
    
    private function distributorFineService(){
    	return D('Distributor\Fine', 'Service');
    }
    
    private function distributorService(){
    	return D('Distributor', 'Service');
    }
    
    private function distributorFineTypeService() {
    	return D('Distributor\FineType', 'Service');
    }
}