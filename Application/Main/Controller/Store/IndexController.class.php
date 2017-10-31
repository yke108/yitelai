<?php
namespace Main\Controller\Store;
use Main\Controller\MainController;
use Common\Basic\Genre;
use Common\Logic\PointLogic;

class IndexController extends MainController {	
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
    	
    	//微信端地址
    	$store_url = DK_DOMAIN.U('store/index/index', array('store_id'=>$distributor['distributor_id']));
    	$store_url = str_replace('index.php', 'wap/index.php', $store_url);
    	$this->assign('store_url', $store_url);
    	
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
    			'distributor_id'=>$store_id,
    			'pagesize'=>15
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
    	
		
		$this->assign('page_title', '');
		$sysconfig=$this->sysconfig;
		$sysconfig['name']=$this->sysconfig['name'].$distributor['distributor_name'];
		$this->assign('sysconfig',$sysconfig);
		
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
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			
			//关注送积分
			$point_config = $this->ConfigService()->findSystemConfigs('point','fkey,fval,ftitle,fdesc,field_type');
			$params = array(
					'user_id'=>$this->user['user_id'],
					'point'=>$point_config['collect']['fval'],
					'type'=>PointLogic::PointTypeCollect,
					'ref_id'=>$id,
			);
			$result = $this->pointService()->addUserPoint($params);
			if($result === false){
				$this->error('关注送积分失败');
			}
			
			$this->success('收藏成功', '', array('is_collect'=>1));
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
	
	private function pointService(){
		return D('Point', 'Service');
	}
}