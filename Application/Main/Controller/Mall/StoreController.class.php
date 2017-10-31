<?php
namespace Main\Controller\Mall;
use Main\Controller\MainController;
use Common\Basic\Pager;
use Common\Basic\Status;

class StoreController extends MainController {	
	public function _initialize(){
		parent::_initialize();
    }
	
	function indexAction(){
		$get = I('get.');
		$this->assign('get', $get);
		
		$params = array(
				'page'=>intval(I('p')) ? intval(I('p')) : 1,
				'pagesize'=>24,
				'status'=>Status::DistributorStatusNormal,
		);
		if ($get['keyword']) {
			$params['keyword'] = $get['keyword'];
		}
		
		//百度API
		$ip = get_real_ip();
		$rest = curl_get('https://api.map.baidu.com/location/ip?ak=Hrp8Uwkdl487PZhGup8oikL4&coor=bd09ll&ip='.$ip);
		$point = $rest->content->point;
		$lng = $point->x;
		$lat = $point->y;
		$params['distance'] = "ACOS(SIN(('".$lat."' * 3.1415) / 180 ) *SIN((latitude * 3.1415) / 180 ) +COS(('".$lat."' * 3.1415) / 180 ) * COS((latitude * 3.1415) / 180 ) *COS(('".$lng."'* 3.1415) / 180 - (longitude * 3.1415) / 180 ) ) * 6380";
		$params['orderby'] = 'distance ASC';
		
		$datas = $this->distributorService()->getPagerList($params);
		$this->assign('list', $datas['list']);
		$pager = new Pager($datas['count'], $params['pagesize']);
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
	
	private function distributorService(){
		return D('Distributor', 'Service');
	}
	
	private function distributorGoodsService(){
		return D('Distributor\Goods', 'Service');
	}
}