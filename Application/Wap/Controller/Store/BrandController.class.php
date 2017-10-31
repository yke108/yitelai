<?php
namespace Wap\Controller\Store;
use Wap\Controller\WapController;
use Common\Basic\Genre;

class BrandController extends WapController {	
	public function _initialize(){
		parent::_initialize();
		
		$get = I('get.');
		$this->assign('get', $get);
		
		$this->assign('page_title', '品牌介绍');
    }
	
    public function indexAction($store_id = 0){
    	//分销商
    	$distributor = $this->distributorService()->getInfo($store_id);
    	if (empty($distributor)) {
    		$this->error('店铺不存在');
    	}
    	$distributor['region'] = $this->regionService()->getDistrictFullName($distributor['region_code']);
    	$this->assign('distributor', $distributor);
    	
    	//导航菜单
    	$params = array(
    			'is_show'=>1,
    			'distributor_id'=>$store_id
    	);
    	$nav = $this->navService()->getPagerList($params);
    	$this->assign('store_nav_list', $nav['list']);
    	
    	//商品标签
    	$params = array(
    			'distributor_id'=>$store_id,
    			'nav_show'=>1,
    			'pagesize'=>6,
    	);
    	$datas = $this->goodsLabelService()->getPagerList($params);
    	$this->assign('goodslabel_list', $datas['list']);
    	
    	//品牌介绍
    	$brand = $this->distributorConfigService()->findConfigs('brand', $distributor['distributor_id']);
    	$this->assign('brand', $brand);
    	
    	//店铺广告
    	$params = array(
    			'distributor_id'=>$distributor['distributor_id']
    	);
    	$store_ad_list = $this->adService()->infoAllList($params);
    	$this->assign('store_ad_list', $store_ad_list);
    	
		$this->display();
    }
	
	private function distributorService(){
		return D('Distributor', 'Service');
	}
	
	private function regionService(){
		return D('Region', 'Service');
	}
	
	protected function distributorConfigService(){
		return D('Distributor\Config', 'Service');
	}
	
	private function goodsLabelService(){
		return D('GoodsLabel', 'Service');
	}
}