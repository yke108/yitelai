<?php
namespace Admin\Controller\Zan;
use Admin\Controller\FController;
use Common\Basic\Pager;

class IndexController extends FController {
	protected $ZanService;
	
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'zan',
			'ac'=>'zan_index_index',
		);
		$this->sbset($set);
		
		$this->ZanService = D('Zan', 'Service');
		
		//类型列表
		$ref_types = $this->ZanService->getReftypes();
		$this->assign('ref_types', $ref_types);
    }
    
    public function indexAction($id = 0){
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'distributor_id'=>$this->org_id
    	);
    	if ($get['keyword']) {
    		$params['keyword'] = $get['keyword'];
    	}
    	$datas = $this->ZanService->getPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $this->pagesize);
    	$this->assign('pager', $pager->show());
    	
    	$this->display();
    }
    
    public function viewAction($log_id = 0){
    	$info = $this->ZanService->getInfo($log_id);
    	if (empty($info)) {
    		$this->error('数据不存在');
    	}
    	$this->assign('info', $info);
    	
    	$this->display();
    }
	
	public function delAction($record_id = 0){
		try {
			$this->DistributorGoodsService->delete($record_id);
			$this->success('删除成功');
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
}