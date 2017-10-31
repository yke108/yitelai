<?php
namespace Main\Controller\Mall;
use Main\Controller\MainController;
use Common\Basic\Pager;

class BrandController extends MainController {	
	public function _initialize(){
		parent::_initialize();
    }
	
	function indexAction(){
		$get = I('get.');
		$this->assign('get', $get);
		
		//品牌列表
		$params = array(
				'page'=>intval(I('p')) ? intval(I('p')) : 1,
				'pagesize'=>24,
				'status'=>Status::DistributorStatusNormal,
		);
		if ($get['keyword']) {
			$params['keyword'] = $get['keyword'];
		}
		$result = $this->goodsBrandService()->getPagerList($params);
		$this->assign('list', $result['list']);
		$pager = new Pager($result['count'], $params['pagesize']);
		$this->assign('pager', $pager->show_pc());
		
		//猜你喜欢
		$map['a.product_num'] = array('gt', 0);
		$params = array(
				'pagesize'=>4,
				'map'=>$map,
				'orderby'=>'rand()'
		);
		$datas = $this->distributorGoodsService()->getPagerList($params);
		$this->assign('like_list', $datas['list']);
		
		$this->display();
	}
	
	private function goodsBrandService(){
		return D('GoodsBrand', 'Service');
	}
	
	private function distributorGoodsService(){
		return D('Distributor\Goods', 'Service');
	}
}