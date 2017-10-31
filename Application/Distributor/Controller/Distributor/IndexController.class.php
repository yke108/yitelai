<?php
namespace Distributor\Controller\Distributor;
use Distributor\Controller\FController;
use Common\Basic\Pager;

class IndexController extends FController {
	protected $DistributorService;
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'system',
			'ac'=>'distributor_index_edit',
		);
		$this->sbset($set);
		
		$this->DistributorService = D('Distributor', 'Service');
    }
    
	public function editAction(){
		$distributor_id = $this->org_id;
		
		$info = $this->DistributorService->getInfo($distributor_id);
		if(empty($info)){
			$this->error('数据不存在');
		}
		$this->assign('info', $info);
		
		if(IS_POST){
			$post = I('post.');
			
			$params = array(
					'distributor_image'=>$post['distributor_image'],
					'lnglat'=>$post['lnglat'],
					'region_code'=>$post['region_code'],
					'service_area'=>$post['service_area'],
					'distributor_title'=>$post['distributor_title'],
					'distributor_tel'=>$post['distributor_tel'],
					'distributor_id'=>$distributor_id,
			);
			
			try {
				$this->DistributorService->createOrModify($params);
				$this->success('编辑成功');
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		
		$this->assign('region_list', $this->regionService()->getAllRegions(false));
		
		$this->display();
	}
	
	private function regionService(){
		return D('Region', 'Service');
	}
}