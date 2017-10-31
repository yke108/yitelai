<?php
namespace Wap\Controller\Store;
use Wap\Controller\WapController;
use Common\Basic\Genre;

class IndexController extends WapController {	
	public function _initialize(){
		parent::_initialize();
		
		$this->assign('page_title', '店铺首页');
		
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
    	
    	//统计访问量
    	//高德API
    	$rest = curl_get('http://restapi.amap.com/v3/ip?key=81131309bbab0c3362b60e87ca82c6f0');
    	if ($rest->status == 1) {
    		$region_code = $rest->adcode;
    	}
    	$params = array(
    			'user_id'=>session('userid'),
    			'region_code'=>$region_code,
    			'shop_id'=>$store_id,
    	);
    	try {
    		if (session('userid')) {
    			$this->statisticsService()->createUserAsk($params);
    		}else {
    			$this->statisticsService()->createTouristAsk($params);
    		}
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	try {
    		$this->statisticsService()->createWechatAsk($params);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	try {
    		$this->statisticsService()->createTotalAsk($params);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	
    	//店铺是否收藏
    	$collect_params = array(
    			'user_id'=>$this->user['user_id'],
    			'id_value'=>$distributor['distributor_id'],
    			'collect_type'=>Genre::CollectTypeStore,
    	);
    	$collect = $this->collectService()->findInfo($collect_params);
    	$this->assign('collect', $collect);
    	
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
    	
    	//店铺广告
    	$params = array(
    			'distributor_id'=>$distributor['distributor_id']
    	);
    	$store_ad_list = $this->adService()->infoAllList($params);
    	$this->assign('store_ad_list', $store_ad_list);
    	
    	//分销商商品分组
    	$map = array('distributor_id'=>$distributor['distributor_id']);
    	$self_cat_list = $this->distributorGoodsCatService()->getAllList($map);
    	$this->assign('self_cat_list', $self_cat_list);
    	
    	//爆款推荐
    	$params = array(
    			'distributor_id'=>$distributor['distributor_id'],
    			'is_hot'=>1,
    			'a.product_num'=>array('gt', 0),
    			'pagesize'=>8
    	);
    	$datas = $this->distributorGoodsService()->getPagerList($params);
    	$this->assign('hot_list', $datas['list']);
    	
    	//新品推荐
    	$params = array(
    			'distributor_id'=>$distributor['distributor_id'],
    			'is_new'=>1,
    			'a.product_num'=>array('gt', 0),
    			'pagesize'=>8
    	);
    	$datas = $this->distributorGoodsService()->getPagerList($params);
    	$this->assign('new_list', $datas['list']);
    	
    	//销量排行榜TOP5
    	$params = array(
    			'distributor_id'=>$distributor['distributor_id'],
    			'orderby'=>'a.total_sale_count DESC',
    			'a.product_num'=>array('gt', 0),
    			'pagesize'=>5
    	);
    	$datas = $this->distributorGoodsService()->getPagerList($params);
    	$this->assign('sale_list', $datas['list']);
    	
    	//楼层
    	foreach ($self_cat_list as $k => $v) {
    		if ($v['is_floor'] == 1) {
    			$params = array(
    					'distributor_id'=>$distributor['distributor_id'],
    					'a.product_num'=>array('gt', 0),
    					'self_cat_id'=>$v['cat_id'],
    					'pagesize'=>8
    			);
    			$goods_datas = $this->distributorGoodsService()->getPagerList($params);
    			$v['goods_list'] = $goods_datas['list'];
    			
    			$floor_list[] = $v;
    		}
    	}
    	$this->assign('floor_list', $floor_list);
    	
    	//微信分享
    	$share = array(
    			'title'=>$distributor['distributor_name'],
    			'desc'=>strip_tags(htmlspecialchars_decode($distributor['distributor_intro'])),
    			'url'=>DK_DOMAIN.U('store/index/index', array('store_id'=>$distributor['distributor_id'])),
    			'img'=>picurl($distributor['distributor_image']),
    	);
    	$this->assign('share', $share);
    	
		$this->display();
    }
	
	public function collectAction($id = 0){
		$collect_params = array(
				'user_id'=>$this->user['user_id'],
				'id_value'=>$id,
				'collect_type'=>Genre::CollectTypeStore,
		);
		$collect = $this->collectService()->findInfo($collect_params);
		
		if ($collect) {
			$map = array(
					'id_value'=>$id,
					'user_id'=>session('userid'),
					'collect_type'=>Genre::CollectTypeStore
			);
			try {
				$res = $this->collectService()->delCollect($map);
				$this->success('取消收藏成功', '', array('is_collect'=>0));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}else {
			$data = array(
					'id_value'=>$id,
					'user_id'=>session('userid'),
					'collect_type'=>Genre::CollectTypeStore
			);
			try {
				$res = $this->collectService()->addStore($data);
				$this->success('收藏成功', '', array('is_collect'=>1));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
	}
	
	private function distributorGoodsService(){
		return D('Distributor\Goods', 'Service');
	}
	
	private function collectService(){
		return D('Collect', 'Service');
	}
	
	private function distributorService(){
		return D('Distributor', 'Service');
	}
	
	private function regionService(){
		return D('Region', 'Service');
	}
	
	private function distributorGoodsCatService(){
		return D('Distributor\GoodsCat', 'Service');
	}
	
	private function goodsLabelService(){
		return D('GoodsLabel', 'Service');
	}
}