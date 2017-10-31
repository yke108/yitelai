<?php
namespace Wap\Controller\Store;
use Wap\Controller\WapController;
use Common\Basic\Genre;
use Common\Basic\Pager;

class PrivilegeController extends WapController {	
	public function _initialize(){
		parent::_initialize();
		
		$this->assign('page_title', '特权定金');
		
		$get = I('get.');
		$this->assign('get', $get);
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
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	//商品列表
    	$map['a.product_num'] = array('gt', 0);
    	$map['a.distributor_id'] = $get['store_id'];
    	$map['a.is_privilege'] = 1;
    	$page = $get['p'] < 1 ? 1 : $get['p'];
    	$params = array(
    			'page'=>$page,
    			'pagesize'=>20,
    			'map'=>$map
    	);
    	$datas = $this->distributorGoodsService()->getPagerList($params);
    	if ($datas[count] > 0){
    		$this->assign('goods_list', $datas['list']);
    		$pager = new Pager($datas['count'], $params['pagesize']);
    		$this->assign('pages', $pager->show_pc());
    	}
    	
		$this->display();
    }
	
	private function distributorService(){
		return D('Distributor', 'Service');
	}
	
	private function regionService(){
		return D('Region', 'Service');
	}
	
	private function distributorGoodsService(){
		return D('Distributor\Goods', 'Service');
	}
	
	private function goodsLabelService(){
		return D('GoodsLabel', 'Service');
	}
}