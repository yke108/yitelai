<?php
namespace Brand\Controller\Distributor;
use Brand\Controller\FController;
use Common\Basic\Pager;
use Common\Basic\Status;

class IndexController extends FController {
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'distributor',
			'ac'=>'distributor_index_index',
		);
		$this->sbset($set);
    }
    
    public function indexAction(){
    	session('back_url', __SELF__);
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'keyword'=>trim($get['keyword']),
    			'status'=>$get['status'],
    			'is_self_distributor'=>$get['is_self_distributor'],
    			'start_time'=>$get['start_time'],
    			'end_time'=>$get['end_time'],
    			'brands_id'=>$this->brand_info['brands_id'],
    	);
    	$datas = $this->distributorService()->getPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $this->pagesize);
    	$this->assign('pager', $pager->show());
    	$this->assign('area_list', $datas['area_list']);
    	
    	$this->publicAssign();
    	
    	$this->display();
    }
	
	private function publicAssign() {
		//等级列表
		$ranks = $this->distributorRankService()->getFieldList();
		$this->assign('ranks', $ranks);
		
		//地区
		$this->assign('region_list', $this->regionService()->getAllRegions(false));
		
		//所有品牌
		$brand_list = $this->goodsBrandService()->getAllList();
		$this->assign('brand_list', $brand_list);
		
		//所有区域
		$area_list = $this->AreaService()->getAllList();
		$this->assign('area_list', $area_list);
		
		//店铺状态
		$this->assign('status_list', Status::$distributorStatusList);
	}
	
	private function distributorService(){
		return D('Distributor', 'Service');
	}
	
	private function regionService(){
		return D('Region', 'Service');
	}
	
	private function areaService(){
		return D('Area', 'Service');
	}
	
	private function goodsBrandService(){
		return D('GoodsBrand', 'Service');
	}
	
	private function distributorRankService(){
		return D('Distributor\DistributorRank', 'Service');
	}
	
	private function merchantService(){
		return D('Merchant', 'Service');
	}
	
	private function smsService(){
		return D('Sms', 'Service');
	}
	
	private function smsapiService(){
		return D('Smsapi', 'Service');
	}
}