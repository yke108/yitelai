<?php
namespace Admin\Controller\Company;
use Admin\Controller\FController;
use Common\Basic\Pager;

class IndexController extends FController {
	protected $DistributorService;
	private $m_sys_id = 2;
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'company',
			'ac'=>'company_index_index',
		);
		$this->sbset($set);
		
		$this->DistributorService = D('Distributor', 'Service');
    }
    
    public function indexAction(){
    	session('back_url', __SELF__);
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'keyword'=>trim($get['keyword']),
    			'is_show'=>$get['is_show'],
    			'is_self_distributor'=>$get['is_self_distributor'],
    			'start_time'=>$get['start_time'],
    			'end_time'=>$get['end_time'],
    			'distributor_type'=>1
    	);
    	$datas = $this->DistributorService->getPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $this->pagesize);
    	$this->assign('pager', $pager->show());
    	
    	$this->display();
    }
	
	public function addAction(){
		if(IS_POST){			
			$post = I('post.');
			$post['distributor_type'] = 1;
			try {
				$this->DistributorService->createOrModify($post);
				$this->success('添加成功', U('index'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		
		$this->assign('region_list', $this->regionService()->getAllRegions(false));
		
		//所有品牌
		$brand_list = $this->goodsBrandService()->getAllList();
		$this->assign('brand_list', $brand_list);
		
		$this->display('edit');
	}
	
	public function editAction($distributor_id = 0){
		$info = $this->DistributorService->getInfo($distributor_id);
		if(empty($info)){
			$this->error('数据不存在');
		}
		
		if(IS_POST){
			$post = I('post.');
			try {
				$this->DistributorService->createOrModify($post);
				$this->success('编辑成功', session('back_url'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		
		$info['brand_ids'] = $info['brand_ids'] ? explode(',', $info['brand_ids']) : array();
		$this->assign('info', $info);
		
		$this->assign('region_list', $this->regionService()->getAllRegions(false));
		
		//所有品牌
		$brand_list = $this->goodsBrandService()->getAllList();
		$this->assign('brand_list', $brand_list);
		
		$this->display();
	}
	
	public function delAction($distributor_id = 0){
		try {
			$this->DistributorService->delete($distributor_id);
			$this->success('删除成功');
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	public function isshowAction($id = 0){
		try {
			$this->DistributorService->isShow($id);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('操作成功', session('back_url'));
	}
	
	private function regionService(){
		return D('Region', 'Service');
	}
	
	private function goodsBrandService(){
		return D('GoodsBrand', 'Service');
	}
}