<?php
namespace Main\Controller\Mall;
use Main\Controller\MainController;
use Common\Basic\Genre;
use Common\Basic\Pager;
use Common\Basic\Status;

class GoodsController extends MainController {	
	public function _initialize(){
		parent::_initialize();
		
		$this->assign('page_title', '商品详情');
    }
	
    public function indexAction(){
		$this->display();
    }
	
	public function infoAction($id = 0){
		$info = $this->distributorGoodsService()->getInfo($id);
		if (empty($info)) $this->error('商品不存在');
		$this->assign('info', $info);
		
		//分成方案
		if ($info['distribution_id']) {
			$distribution_info = $this->goodsDistributionService()->getInfo($info['distribution_id']);
			$this->assign('distribution_info', $distribution_info);
			
			if ($info['is_seckill'] == 1) {
				$give_points = round($info['product']['seckill_price'] * $distribution_info['user_ratio'] / 100);
			}else {
				$give_points = round($info['product']['product_price'] * $distribution_info['user_ratio'] / 100);
			}
			$this->assign('give_points', $give_points);
		}
		
		//随机积分商品图片
		$map = array(
				'point'=>array('elt', $give_points)
		);
		$point_gift = $this->pointGiftService()->findInfo($map);
		$this->assign('point_gift', $point_gift);
		
		//记录商品足迹
		$agent_info = getAgentInfo();
		$browser = getbrowser();
		$data = array(
				'id_value'=>$id,
				'user_id'=>$this->user['user_id'],
				'collect_type'=>Genre::CollectTypeGoodsFoot,
				'system'=>$agent_info['sys'],
				'browser'=>$browser['browser'],
				'version'=>$browser['version'],
		);
		try {
			$res = $this->collectService()->collect($data);
		} catch (\Exception $e) {
			//$this->error($e->getMessage());
		}
		
		//记录商品浏览量
		$this->goodsService()->viewCount($info['goods_id']);
		
		//记录分销商商品浏览量
		$this->distributorGoodsService()->viewCount($info['record_id']);
		
		//货品列表
		$params = array('map'=>array('record_id'=>$id));
		$product_list = $this->distributorGoodsProductService()->getAllList($params);
		$new_product_list = array();
		foreach ($product_list as $v) {
			$new_product_list[$v['product_items']] = $v;
		}
		$this->assign('product_list', $new_product_list);
		$this->assign('product', current($new_product_list));
		
		//配送服务
		/* $service_info = $this->goodsServiceService()->getInfo($info['service_id']);
		$this->assign('service_info', $service_info); */
		
		//商品服务
		$map = array(
				'service_id'=>array('in', explode(',', $info['service_id']))
		);
		$service_list = $this->goodsServiceService()->getAllList($map);
		$new_service_list = array();
		foreach ($service_list as $v) {
			$new_service_list[$v['service_id']] = $v;
		}
		$this->assign('service_info', current($new_service_list));
		$this->assign('service_list', $new_service_list);
		
		//分销商
		$distributor = $this->distributorService()->getInfo($info['distributor_id']);
		$distributor['region'] = $this->regionService()->getDistrictFullName($distributor['region_code']);
		$this->assign('distributor', $distributor);
		
		//分销商设置
		$distributor_config = $this->distributorConfigService()->findConfigs('system', $distributor['distributor_id']);
		$this->assign('distributor_config', $distributor_config);
		
		//分销商商品分组
		$map = array('distributor_id'=>$info['distributor_id']);
		$self_cat_list = $this->distributorGoodsCatService()->getAllList($map);
		$this->assign('self_cat_list', $self_cat_list);
		
		//店铺热销
		$params = array(
				'distributor_id'=>$info['distributor_id'],
				'pagesize'=>5,
				'orderby'=>'a.total_sale_count DESC'
		);
		$datas = $this->distributorGoodsService()->getPagerList($params);
		$this->assign('sale_list', $datas['list']);
		
		//店铺推荐
		$params = array(
				'distributor_id'=>$info['distributor_id'],
				'is_hot'=>1,
				'pagesize'=>6
		);
		$datas = $this->distributorGoodsService()->getPagerList($params);
		$this->assign('hot_list', $datas['list']);
		
		//商品足迹
		$params = array(
				'user_id'=>$this->user['user_id'],
				'collect_type'=>Genre::CollectTypeGoodsFoot,
		);
		$datas = $this->collectService()->getCollectGoodsList($params);
		$this->assign('history_list', $datas['list']);
		
		//是否收藏
		$params = array(
				'record_id'=>$id,
				'user_id'=>$this->user['user_id'],
		);
		$collect = $this->collectService()->getCollectInfo($params);
		$is_collect = $collect ? 1 : 0;
		$this->assign('is_collect', $is_collect);
		
		//店铺是否收藏
    	$collect_params = array(
    			'user_id'=>$this->user['user_id'],
    			'id_value'=>$distributor['distributor_id'],
    			'collect_type'=>Genre::CollectTypeStore,
    	);
    	$collect = $this->collectService()->findInfo($collect_params);
    	$this->assign('store_collect', $collect);
		
		//商品评价
		$params = array(
				'goods_id'=>$id,
				'status'=>1
		);
		$datas = $this->goodsCommentService()->getPagerList($params);
		$this->assign('comment_list', $datas['list']);
		$pager = new Pager($datas['count'], $params['pagesize']);
		$this->assign('pages', $pager->show_pc());
		
		//累计评价
		$this->assign('comment_count', $datas['count']);
		
		//本店好评商品
		$params = array(
				'distributor_id'=>$info['distributor_id'],
				'pagesize'=>8,
				'orderby'=>'stars_count DESC',
				'map'=>array('stars_count'=>array('gt', 0))
		);
		$datas = $this->distributorGoodsService()->getPagerList($params);
		$this->assign('best_list', $datas['list']);
		
		//猜你喜欢
		$map = array(
				'a.product_num'=>array('gt', 0),
				'c.status'=>Status::DistributorStatusNormal,
				'_string'=>'record_id >= (
		(SELECT MAX(record_id) FROM hy_distributor_goods) - (SELECT MIN(record_id) FROM hy_distributor_goods)
	) * RAND() + (SELECT MIN(record_id) FROM hy_distributor_goods)',
		);
		$params = array(
				'pagesize'=>4,
				'orderby'=>'rand()',
				'map'=>$map,
		);
		$datas = $this->distributorGoodsService()->getPagerList($params);
		$this->assign('like_list', $datas['list']);
		
		//微信端地址
		$store_url = DK_DOMAIN.U('store/index/index', array('store_id'=>$distributor['distributor_id']));
		$store_url = str_replace('index.php', 'wap/index.php', $store_url);
		$this->assign('store_url', $store_url);
		
		$wap_url = DK_DOMAIN.U('mall/goods/info', array('id'=>$info['record_id']));
		$wap_url = str_replace('index.php', 'wap/index.php', $wap_url);
		$this->assign('wap_url', $wap_url);
		
		//商品规格
		$map = array('goods_id'=>$info['goods_id'], 'sku_value'=>array('neq', ''));
		$list = M('goods_sku')->where($map)->order('sku_id ASC')->select();
		$sku_list = array();
		foreach ($list as $v) {
			//规格图片
			$sku_image = '';
			foreach ($new_product_list as $k2 => $v2) {
				$product_items = explode(',', $k2);
				if ($product_items[0] == $v['sku_id']) {
					if (!empty($v2['product_image'])) {
						$sku_image = $v2['product_image'];
					}
				}
			}
			$v['sku_image'] = $sku_image;
			
			$sku_list[$v['sku_name']][] = $v;
		}
		$this->assign('sku_list', $sku_list);
		
		$this->display();
	}
	
	public function previewAction($id = 0){
		$info = $this->goodsService()->getInfo($id);
		if (empty($info)) {
			die();
		}
		$this->assign('info', $info);
		
		//商品评价
		$params = array(
				'goods_id'=>$id,
				'status'=>1
		);
		$datas = $this->goodsCommentService()->getPagerList($params);
		$this->assign('comment_list', $datas['list']);
		$pager = new Pager($datas['count'], $params['pagesize']);
		$this->assign('pages', $pager->show_pc());
		
		//货品列表
		$product_list=$this->goodsProductService()->getPagerList(array('map'=>array('goods_id'=>$id)));
		foreach($product_list['list'] as $key=>$val){
			$new_product_list[$val['product_items']]=$val;
		}
		$this->assign('product_list', $new_product_list);
		
		//商品规格
		$map = array('goods_id'=>$info['goods_id'], 'sku_value'=>array('neq', ''));
		$list = M('goods_sku')->where($map)->order('sku_id ASC')->select();
		$sku_list = array();
		foreach ($list as $v) {
			//规格图片
			$sku_image = '';
			foreach ($new_product_list as $k2 => $v2) {
				$haystack = substr( $k2, 0, 3 );
				if ($haystack == $v['sku_id']) {
					if (!empty($v2['product_image'])) {
						$sku_image = $v2['product_image'];
					}
				}
			}
			$v['sku_image'] = $sku_image;
			
			$sku_list[$v['sku_name']][] = $v;
		}
		$this->assign('sku_list', $sku_list);
		
		//累计评价
		$this->assign('comment_count', $datas['count']);
		
		$this->display();
	}
	
	//团购商品详情页
	public function groupbuyAction($id = 0){
		$act_id=I('act_id')?I('act_id'):I('get.act_id');
		//获取这个商品的团购活动
		$team_map=array('act_id'=>$act_id);
		$team_info=$this->activityService()->getFind($team_map);
		$this->assign('team_info',$team_info);
		if(empty($team_info)){
			$this->error('团购活动不存在');
		}
		
		$info = $this->distributorGoodsService()->getInfo($team_info['goods_id']);
		if (empty($info)) {
			$this->error('商品不存在');
		}
		$this->assign('info', $info);
		
		//获取已发起的团购活动
		if($team_info['is_show_page']==1){
			$post_params=array('page'=>1,'is_going'=>1,'pagesize'=>1000,'map'=>array('act_id'=>$team_info['act_id'],'joined_num'=>array('gt',0)));
			$post_result=$this->activityService()->teamPostPagerList($post_params);
			$this->assign('team_post_list',$post_result['list']);
		}
		
		//记录商品足迹
		$data = array(
				'id_value'=>$id,
				'user_id'=>$this->user['user_id'],
				'collect_type'=>Genre::CollectTypeGoodsFoot
		);
		try {
			$res = $this->collectService()->add($data);
		} catch (\Exception $e) {
	
		}
		
		//记录商品浏览量
		$this->goodsService()->viewCount($info['goods_id']);
		
		//货品列表
		$params = array('map'=>array('record_id'=>$id));
		$product_list = $this->distributorGoodsProductService()->getAllList($params);
		$this->assign('product_list', $product_list);
		
		//配送服务
		$service_info = $this->goodsServiceService()->getInfo($info['service_id']);
		$this->assign('service_info', $service_info);
		
		//分销商
		$distributor = $this->distributorService()->getInfo($info['distributor_id']);
		$distributor['region'] = $this->regionService()->getDistrictFullName($distributor['region_code']);
		$this->assign('distributor', $distributor);
		
		//分销商商品分组
		$map = array('distributor_id'=>$info['distributor_id']);
		$self_cat_list = $this->distributorGoodsCatService()->getAllList($map);
		$this->assign('self_cat_list', $self_cat_list);
		
		//店铺热销
		$params = array(
				'distributor_id'=>$info['distributor_id'],
				'pagesize'=>5,
				'orderby'=>'a.total_sale_count DESC'
		);
		$datas = $this->distributorGoodsService()->getPagerList($params);
		$this->assign('sale_list', $datas['list']);
		
		//店铺推荐
		$params = array(
				'distributor_id'=>$info['distributor_id'],
				'is_hot'=>1,
				'pagesize'=>5
		);
		$datas = $this->distributorGoodsService()->getPagerList($params);
		$this->assign('hot_list', $datas['list']);
		
		//商品足迹
		$params = array(
				'user_id'=>$this->user['user_id'],
				'collect_type'=>Genre::CollectTypeGoodsFoot,
		);
		$datas = $this->collectService()->getCollectGoodsList($params);
		$this->assign('history_list', $datas['list']);
		
		//是否收藏
		$params = array(
				'record_id'=>$id,
				'user_id'=>$this->user['user_id'],
		);
		$collect = $this->collectService()->getCollectInfo($params);
		$is_collect = $collect ? 1 : 0;
		$this->assign('is_collect', $is_collect);
		
		//商品评价
		$params = array(
				'goods_id'=>$id,
				'status'=>1
		);
		$datas = $this->goodsCommentService()->getPagerList($params);
		$this->assign('comment_list', $datas['list']);
		$pager = new Pager($datas['count'], $params['pagesize']);
		$this->assign('pages', $pager->show_pc());
		
		//累计评价
		$this->assign('comment_count', $datas['count']);
		
		//本店好评商品
		$params = array(
				'distributor_id'=>$info['distributor_id'],
				'pagesize'=>8,
				'orderby'=>'stars_count DESC',
				'map'=>array('stars_count'=>array('gt', 0))
		);
		$datas = $this->distributorGoodsService()->getPagerList($params);
		$this->assign('best_list', $datas['list']);
		
		//猜你喜欢
		$params = array(
				'pagesize'=>4,
				'orderby'=>'rand()'
		);
		$datas = $this->distributorGoodsService()->getPagerList($params);
		$this->assign('like_list', $datas['list']);
		
		$this->display();
	}
	
	//团购详情
	public function  team_infoAction(){
		$post_id=I('post_id')?I('post_id'):I('get.post_id');
		$map=array('post_id'=>$post_id,'joined_num'=>array('gt',0));
		$post_info=$this->activityService()->getTeamPostfind($map);
		if(empty($post_info)){
			$this->error('团购活动不存在');	
		}
		$this->assign('team_post_info',$post_info);
		$id=$post_info['goods_id'];
		
		//获取同一个商品下的不同团购活动
		$team_post_list=$this->ActivityService()->sameGoodsTeam($id);
		$this->assign('team_post_list',$team_post_list);
		
		$info = $this->distributorGoodsService()->getInfo($id);
		if (empty($info)) {
		//	$this->error('商品不存在');
		}
		$info['product_name']=str_replace(",","，",$info['product_name']);
		$this->assign('info', $info);
		
		//获取团购成员列表
		$team_member_list=$this->activityService()->teamUserList($post_id);
		$this->assign('team_member_list',$team_member_list);
		foreach($team_member_list as $key=>$val){
			$team_member_id[]=$val['user_id'];
		}
		$this->assign('team_member_id',$team_member_id);
		$this->assign('self_user_id',session('userid'));
		
		//查找用户是否已经生成了订单
		$order_map=array('order_type'=>1,'team_post_id'=>$post_id,'user_id'=>session('userid'));
		$order_info=$this->orderService()->findOrderInfo($order_map);
		$this->assign('order',$order_info);
		//var_dump($order_info);die();
		
		$this->display();
	}
	
	//发起团购
	public function build_teamAction(){
		$act_id=I('act_id')?I('act_id'):I('get.act_id');
		$person_number=I('person_number')?I('person_number'):I('get.person_number');
		$user_id=session('userid');
		if(empty($user_id)){
			$this->ajaxReturn(array('error'=>2,'msg'=>'请登陆'));
		}
		$params=array('act_id'=>$act_id,'user_id'=>$user_id,'member_num'=>$person_number);
		try{
			$result=$this->activityService()->buildTeam($params);
		}catch(\Exception $e){
			$this->ajaxReturn(array('error'=>1,'msg'=>$e->getMessage()));
		}
		$this->ajaxReturn(array('error'=>0,'msg'=>'发起团购成功','general_order'=>$result['general_order_id']));
	}
	
	//参加团购
	public function join_team_postAction(){
		$user_id=session('userid');
		if(empty($user_id)){
			$this->ajaxReturn(array('error'=>2,'msg'=>'请登陆'));
		}
		$post_id=I('post_id');
		$data=array(
			'team_post_id'=>$post_id,
			'user_id'=>session('userid'),
		);
		
		try{
			$result=$this->orderService()->createTeamOrder($data);
		}catch(\Exception $e){
			$this->ajaxReturn(array('error'=>1,'msg'=>$e->getMessage()));
		}
		$this->ajaxReturn(array('error'=>0,'general_order'=>$result['general_order_id']));
	}
	
	public function collectAction($id = 0){
		if (empty($this->user['user_id'])) {
			$this->error('请先登录', U('index/site/login'));
		}
		
		$agent_info = getAgentInfo();
		$browser_info = getbrowser();
		$data = array(
				'id_value'=>$id,
				'user_id'=>session('userid'),
				'collect_type'=>Genre::CollectTypeGoods,
				'system'=>$agent_info['sys'],
				'browser'=>$browser_info['browser'],
				'version'=>$browser_info['version'],
		);
		try {
			$res = $this->collectService()->add($data);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('关注成功');
	}
	
	//导航
	public function mapAction($id) {
		//店铺
		$distributor = $this->distributorService()->getInfo($id);
		if (empty($distributor)) {
			$this->error('店铺不存在');
		}
		$this->assign('distributor', $distributor);
		
		//百度API
		$ip = get_real_ip();
		$rest = curl_get('https://api.map.baidu.com/location/ip?ak=Hrp8Uwkdl487PZhGup8oikL4&coor=bd09ll&ip='.$ip);
		$this->assign('city_name', $rest->content->address_detail->city);
		$point = $rest->content->point;
		$lng = $point->x;
		$lat = $point->y;
		$this->assign('lng', $lng);
		$this->assign('lat', $lat);
		
		$this->assign('page_title', '门店导航');
		$this->display();
	}
	
	private function distributorGoodsService(){
		return D('Distributor\Goods', 'Service');
	}
	
	private function distributorGoodsProductService(){
		return D('Distributor\GoodsProduct', 'Service');
	}
	
	private function distributorGoodsCatService(){
		return D('Distributor\GoodsCat', 'Service');
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
	
	private function goodsService(){
		return D('Goods', 'Service');
	}
	
	private function goodsServiceService(){
		return D('GoodsService', 'Service');
	}
	
	
	private function orderService(){
		return D('Order', 'Service');
	}
	
	private function activityService(){
		return D('Activity','Service');
	}
	
	private function goodsCommentService(){
		return D('GoodsComment','Service');
	}
	
	private function distributorConfigService(){
		return D('Distributor\Config','Service');
	}
	
	private function goodsProductService(){
		return D('GoodsProduct','Service');
	}
	
	private function goodsDistributionService() {
		return D('GoodsDistribution', 'Service');
	}
	
	private function pointGiftService(){
		return D('PointGift', 'Service');
	}
}