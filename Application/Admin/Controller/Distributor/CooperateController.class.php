<?php
namespace Admin\Controller\Distributor;
use Admin\Controller\FController;
use Common\Basic\Pager;

class CooperateController extends FController {
	protected $DistributorService;
	private $m_sys_id = 2;
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'distributor',
			'ac'=>'distributor_cooperate_index',
		);
		$this->sbset($set);
		
		$this->DistributorService = D('Distributor', 'Service');
    }
    
    public function indexAction(){
    	$get = I('get.');
    	$this->assign('get', $get);
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
				'keyword'=>$get['keyword'],
				'start_time'=>$get['start_time'],
				'end_time'=>$get['end_time'],
    	);
    	$datas = $this->cooperateService()->getPagerList($params);
    	$this->assign('list', $datas['list']);
		$this->assign('users', $datas['users']);
		$this->assign('regions', $datas['regions']);
    	$pager = new Pager($datas['count'], $this->pagesize);
    	$this->assign('pager', $pager->show());
    	
    	$this->display();
    }
	
	public function addAction(){
		if(IS_POST){			
			$post = I('post.');
			try {
				$this->DistributorService->createOrModify($post);
				$this->success('添加成功', U('index'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		
		$this->assign('region_list', $this->regionService()->getAllRegions(false));
		
		$this->display('edit');
	}
	
	public function editAction($distributor_id = 0){
		$info = $this->DistributorService->getInfo($distributor_id);
		if(empty($info)){
			$this->error('数据不存在');
		}
		$this->assign('info', $info);
		
		if(IS_POST){
			$post = I('post.');
			try {
				$this->DistributorService->createOrModify($post);
				$this->success('编辑成功', U('index'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		
		$this->assign('region_list', $this->regionService()->getAllRegions(false));
		
		$this->display();
	}
	
	public function delAction($id = 0){
		try {
			$this->DistributorService->delete($id);
			$this->success('删除成功');
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	private function regionService(){
		return D('Region', 'Service');
	}
	
	private function cooperateService(){
		return D('Cooperate', 'Service');
	}
	
}