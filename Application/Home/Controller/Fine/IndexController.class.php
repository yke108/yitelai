<?php
namespace Home\Controller\Fine;
use Home\Controller\BaseController;
use Common\Basic\Status;

class IndexController extends BaseController {	
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
    	);
    	if ($get['keyword']) {
    		$params['keyword'] = $get['keyword'];
    	}
    	$result = $this->distributorFineService()->getPagerList($params);
    	$this->assign('list', $result['list']);
    	
    	//未处理
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'status'=>Status::FineStatusAppeal,
    	);
    	if ($get['keyword']) {
    		$params['keyword'] = $get['keyword'];
    	}
    	$result = $this->distributorFineService()->getPagerList($params);
    	$this->assign('appeal_list', $result['list']);
    	
    	//已批准
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'status'=>Status::FineStatusAgree,
    	);
    	if ($get['keyword']) {
    		$params['keyword'] = $get['keyword'];
    	}
    	$result = $this->distributorFineService()->getPagerList($params);
    	$this->assign('agree_list', $result['list']);
    	
    	$this->assign('page_title', '罚款列表');
		$this->display();
    }
    
    public function listAction() { /*NoPurview*/
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    	);
    	if (I('keyword')) {
    		$params['keyword'] = I('keyword');
    	}
    	if (I('status') != '') {
    		$params['status'] = I('status');
    	}
    	$result = $this->distributorFineService()->getPagerList($params);
    	$this->assign('list', $result['list']);
    	
    	if(empty($result['list'])){
    		$clist = '';
    	}else{
    		$clist = $this->renderPartial('_index');
    	}
    	$this->ajaxReturn(array('html'=>$clist, 'p'=>$params['page']+1));
    }
    
    public function infoAction($fine_id = 0) { /*NoPurview*/
    	$info = $this->distributorFineService()->getInfo($fine_id);
    	if (empty($info)) $this->error('数据不存在');
    	$this->assign('info', $info);
    	
    	//审核流程
    	$params = array('fine_id'=>$info['fine_id']);
    	$log_list = $this->distributorFineService()->logAllList($params);
    	$this->assign('log_list', $log_list);
    	
    	$this->assign('page_title', '罚款详情');
    	$this->display();
    }
    
    protected function addBefore(){
    	$this->purviewCheck();
    }
    
    public function addAction() {
    	if (IS_POST) {
    		$post = I('post.');
    		
    		if ($post['image_datas']) {
    			$image_datas = createBase64Image($post['image_datas']);
    			$post['pictures'] = implode(',', $image_datas);
    		}
    		$post['ref_id'] = session('uid');
    		$post['ref_type'] = Status::RefTypeAdmin;
    		
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
    
    protected function checkBefore(){
    	$this->purviewCheck();
    }
    
    public function checkAction() {
    	$params = array(
    			'fine_id'=>I('get.fine_id'),
    			'status'=>I('get.status'),
    			'admin_id'=>session('uid'),
    	);
    	try {
    		$this->distributorFineService()->check($params);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	$this->success('审核成功');
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