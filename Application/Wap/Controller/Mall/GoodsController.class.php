<?php
namespace Wap\Controller\Mall;
use Wap\Controller\WapController;
use Common\Basic\Genre;
use Common\Basic\Pager;
use Common\Basic\Status;

class GoodsController extends WapController {	
	public function _initialize(){
		parent::_initialize();
		
		$this->assign('page_title', '商品列表');
    }
    
    public function testAction() {
    	$distributor_list = M('distributor_info')->select();
    	foreach ($distributor_list as $v) {
    		$data = array(
    				'lng'=>$v['longitude'],
    				'lat'=>$v['latitude']
    		);
    		$result = M('distributor_goods')->where(array('distributor_id'=>$v['distributor_id']))->save($data);
    		_p($result,false);
    	}
    }
	
    public function indexAction(){
    	$map['a.product_num'] = array('gt', 0);
    	$this->listDisplay($map);
    }
    
    public function seckillAction(){
    	$map = array(
    			'a.seckill_start'=>array('elt', NOW_TIME),
    			'a.seckill_end'=>array('egt', NOW_TIME),
    			'a.seckill_status'=>1,
    			'a.total_seckill_num'=>array('gt', 0)
    	);
    	$this->listDisplay($map);
    }
    
    private function listDisplay($map = array()) {
    	$get = I('get.');
    	$get['sort'] = $get['sort'] ? $get['sort'] : 'down';
    	$this->assign('get', $get);
    	 
    	//当前分类
    	$cat = $this->goodsCatService()->getInfo($get['cat_id']);
    	$this->assign('cat', $cat);
    	 
    	//属性筛选
    	$all_attr_list = $this->goodsService()->get_filter_attr($cat['cat_id'], $get);
    	$this->assign('filter_attr_list',  $all_attr_list);
    	 
    	if (IS_POST) {
    		$post = I('post.');
    		$brand_id = $post['brand_id'] ? implode(',', $post['brand_id']) : '';
    		$region_code = $post['region_code'] ? implode(',', $post['region_code']) : '';
    	
    		$filter_attr = '';
    		for ($i = 0; $i < count($all_attr_list); $i++) {
    			if (empty($post['filter_attr'][$i])) {
    				$filter_attr .= '0.';
    			}else {
    				$filter_attr .= $post['filter_attr'][$i].'.';
    			}
    		}
    		$filter_attr = trim($filter_attr, '.');
    		
    		$keyword = $get['keyword'] ? $get['keyword'] : $post['keyword'];
    		
    		$redirect_url = U('', array('keyword'=>$keyword, 'cat_id'=>$get['cat_id'], 'min_price'=>$post['min_price'], 'max_price'=>$post['max_price'], 'brand_id'=>$brand_id, 'region_code'=>$region_code, 'filter_attr'=>$filter_attr));
    		header('Location:'.$redirect_url);
    	}
    	 
    	//品牌筛选
    	$brand_list = $cat['brand_list'];
    	if (empty($cat['brand_list'])) {
    		$map = array();
    		if ($get['dis_id']) {
    			$distributor_info = $this->distributorService()->getInfo($get['dis_id']);
    			if (empty($distributor_info)) {
    				$this->error('店铺不存在');
    			}
    			$map['brand_id'] = array('in', $distributor_info['brand_ids']);
    		}
    		$brand_list = $this->goodsBrandService()->getAllList($map);
    	}
    	$get_brand_id = explode(',', $get['brand_id']);
    	foreach ($brand_list as $k => $v) {
    		if (in_array($v['brand_id'], $get_brand_id)) {
    			$brand_list[$k]['selected'] = 1;
    		}else {
    			$brand_list[$k]['selected'] = 0;
    		}
    	}
    	$this->assign('brand_list', $brand_list);
    	 
    	//配送安装地区筛选
		$map_distributor = array('status'=>Status::DistributorStatusNormal);
    	$distributor_list = $this->distributorService()->getAllList($map_distributor);
    	if (!empty($distributor_list)) {
    		foreach ($distributor_list as $v) {
    			$region_code[] = $v['region_code'];
    		}
    	}
    	$region_list = M('region')->where(array('region_code'=>array('in', $region_code)))->select();
    	$get_region_code = explode(',', $get['region_code']);
    	foreach ($region_list as $k => $v) {
    		if (in_array($v['region_code'], $get_region_code)) {
    			$region_list[$k]['selected'] = 1;
    		}else {
    			$region_list[$k]['selected'] = 0;
    		}
    	}
    	$this->assign('region_list', $region_list);
    	 
    	/* $filter_attr_count = 0;
    	 foreach ($all_attr_list as $v) {
    	if ($v['selected'] == 0) {
    	$filter_attr_count++;
    	}
    	}
    	$this->assign('filter_attr_count',  $filter_attr_count); */
    	 
    	$filter_attr_str = isset($get['filter_attr']) ? htmlspecialchars(trim($get['filter_attr'])) : '0';
    	$filter_attr_str = trim(urldecode($filter_attr_str));
    	$filter_attr_str = preg_match('/^[\d\.]+$/',$filter_attr_str) ? $filter_attr_str : '';
    	$filter_attr = empty($filter_attr_str) ? '' : explode('.', $filter_attr_str);
    	$ext = '';
    	if (!empty($filter_attr))
    	{
    		$goods_spec = M('goods_spec')->field('spec_id')->where(array('type_id'=>$cat['type_id']))->select();
    		foreach ($goods_spec as $v) {
    			$cat_filter_attr[] = $v['spec_id'];
    		}
    		 
    		$ext_sql = "SELECT DISTINCT(b.goods_id) FROM __GOODS_SPEC_VALUES__ AS a, __GOODS_SPEC_VALUES__ AS b " .  "WHERE ";
    		$ext_group_goods = array();
    		foreach ($filter_attr AS $k => $v)                      // 查出符合所有筛选属性条件的商品id */
    		{
    			if (is_numeric($v) && $v !=0 &&isset($cat_filter_attr[$k]))
    			{
    				$sql = $ext_sql . "b.spec_value = a.spec_value AND b.spec_id = " . $cat_filter_attr[$k] ." AND a.goods_spec_value_id = " . $v;
    				$ext_group_goods_tmp = M()->query($sql);
    				$ext_group_goods = array();
    				foreach ($ext_group_goods_tmp as $v) {
    					$ext_group_goods[] = $v['goods_id'];
    				}
    				$ext .= ' AND ' . $this->db_create_in($ext_group_goods, 'a.goods_id');
    			}
    		}
    		 
    		if ($ext) {
    			$map['_string'] = '1'.$ext;
    		}
    	}
    	
    	if (!empty($get['min_price'])) {
    		$map['a.min_product_price'][] = array('egt', $get['min_price']);
    	}
    	if (!empty($get['max_price'])) {
    		$map['a.min_product_price'][] = array('elt', $get['max_price']);
    	}
    	
    	$map['a.product_num'] = array('gt', 0);
    	//$map['c.status'] = 2;
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'map'=>$map
    	);
    	if (!empty($get['cat_id'])) {
    		$params['cat_id'] = $get['cat_id'];
    	}
    	if ($get['keyword']) {
    		$params['keyword'] = $get['keyword'];
    	}
    	if (!empty($get['brand_id'])) {
    		$params['brand_id'] = $get['brand_id'];
    	}
    	if (!empty($get['region_code'])) {
    		$params['region_code'] = $get['region_code'];
    	}
    	if (I('dis_id')) {
    		$params['distributor_id'] = I('dis_id');
    	}
    	
    	//排序
    	switch ($get['order']) {
    		case 'view': $order_by = 'a.view_count'; $sort_view = ($get['sort'] == 'down') ? 'up' : 'down'; $this->assign('sort_view', $sort_view); break;
    		case 'sale': $order_by = 'a.total_sale_count'; $sort_sale = ($get['sort'] == 'down') ? 'up' : 'down'; $this->assign('sort_sale', $sort_sale); break;
    		case 'new': $order_by = 'a.is_new'; $sort_new = ($get['sort'] == 'down') ? 'up' : 'down'; $this->assign('sort_new', $sort_new); break;
    		case 'price': $order_by = 'a.min_product_price'; $sort_price = ($get['sort'] == 'down') ? 'up' : 'down'; $this->assign('sort_price', $sort_price); break;
    		case 'collect': $order_by = 'a.collect_count'; $sort_collect = ($get['sort'] == 'down') ? 'up' : 'down'; $this->assign('sort_collect', $sort_collect); break;
    	}
    	
    	//百度API
    	$ip = get_real_ip();
    	$rest = curl_get('https://api.map.baidu.com/location/ip?ak=Hrp8Uwkdl487PZhGup8oikL4&coor=bd09ll&ip='.$ip);
    	$point = $rest->content->point;
    	$lng = $point->x;
    	$lat = $point->y;
    	$params['distance'] = "ACOS(SIN(('".$lat."' * 3.1415) / 180 ) *SIN((lat * 3.1415) / 180 ) +COS(('".$lat."' * 3.1415) / 180 ) * COS((lat * 3.1415) / 180 ) *COS(('".$lng."'* 3.1415) / 180 - (lng * 3.1415) / 180 ) ) * 6380";
    	
    	if (!empty($order_by)) {
    		$sort = ($get['sort'] == 'down') ? 'DESC' : 'ASC';
    		$params['orderby'] = $order_by.' '.$sort;
    	}else {
    		//高德API
    		/* $rest = curl_get('http://restapi.amap.com/v3/ip?key=81131309bbab0c3362b60e87ca82c6f0');
    		$rectangle = explode(';', $rest->rectangle);
    		$rectangle = explode(',', $rectangle[0]);
    		$lng = $rectangle[0];
    		$lat = $rectangle[1]; */
    		$params['orderby'] = 'distance ASC';
    	}
    	
    	$datas = $this->distributorGoodsService()->getPagerList($params);
    	$this->assign('list', $datas['list']);
    	 
    	if(IS_AJAX){
    		if(empty($datas['list'])){
    			$clist = '';
    		}else{
    			$clist = $this->renderPartial('_index');
    		}
    		die($clist);
    	}
    	 
    	$this->display('index');
    }
	
	public function infoAction($id = 0){
		$info = $this->distributorGoodsService()->getInfo($id);
		if (empty($info)) $this->error('商品不存在');
		$this->assign('info', $info);
		
		if (IS_AJAX && session('userid')) {
			$data = array(
					'record_id'=>$id,
					'user_id'=>session('userid'),
			);
			try {
				$res = $this->distributorGoodsService()->share($data);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('分享成功');
		}
		
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
		
		//货品列表
		$params = array('map'=>array('record_id'=>$id));
		$product_list = $this->distributorGoodsProductService()->getAllList($params);
		$new_product_list = array();
		foreach ($product_list as $k => $v) {
			$v['product_image_url'] = picurl($v['product_image'], 'b200');
			$new_product_list[$v['product_items']] = $v;
		}
		$this->assign('product_list', $new_product_list);
		
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
		
		//店铺推荐
		$params = array(
				'distributor_id'=>$distributor['distributor_id'],
				'is_hot'=>1,
				'pagesize'=>3
		);
		$datas = $this->distributorGoodsService()->getPagerList($params);
		$this->assign('hot_list', $datas['list']);
		
		//商品评价
		$params = array(
				'goods_id'=>$id,
				'status'=>1
		);
		$datas = $this->goodsCommentService()->getPagerList($params);
		$comment_list = array();
		foreach ($datas['list'] as $v) {
			$pictures = array();
			if ($v['pictures']) {
				foreach ($v['pictures'] as $v2) {
					$filename= picurl($v2);
					$img_info = getimagesize($filename);
					$pictures[] = array(
							'picture'=>$v2,
							'width'=>$img_info[0],
							'height'=>$img_info[1]
					);
				}
			}
			$v['pictures'] = $pictures;
			$comment_list[] = $v;
		}
		$this->assign('comment_list', $comment_list);
		
		//累计评价
		$this->assign('comment_count', $datas['count']);
		
		//查询用户是否已经收藏该商品
		$collect_params=array('user_id'=>session('userid'),'goods_id'=>$id);
		$is_collect=$this->collectService()->check($collect_params);
		$this->assign('is_collect',$is_collect);
		
		//微信分享
		$goods_desc = strip_tags(htmlspecialchars_decode($info['goods_desc']));
		$share = array(
				'title'=>$info['goods_name'],
				'desc'=>$info['goods_title'] ? $info['goods_title'] : $info['goods_name'],
				'url'=>DK_DOMAIN.U('mall/goods/info', array('id'=>$info['record_id'], 'uid'=>$this->user['user_id'])),
				'img'=>picurl($info['goods_image']),
		);
		$this->assign('share', $share);
		
		//商品规格
		$list = M('goods_sku')->where(array('goods_id'=>$info['goods_id']))->order('sku_id ASC')->select();
		$sku_list = array();
		foreach ($list as $v) {
			$sku_list[$v['sku_name']][] = $v;
		}
		$this->assign('sku_list', $sku_list);
		
		$this->assign('page_title', '商品详情');
		$this->display();
	}
	
	public function collectAction($id = 0){
		if (empty($this->user)) $this->error('请先登录', U('index/site/login'));
		
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
		$collect_params=array('user_id'=>session('userid'),'goods_id'=>$id);
		$is_collect=$this->collectService()->check($collect_params);
		if($is_collect==false){
			try {
				$res = $this->collectService()->add($data);
			} catch (\Exception $e) {
				$this->ajaxReturn(array('info'=>$e->getMessage(),'status'=>0));
			}
			$is_collect=$this->collectService()->check($collect_params);
			$this->ajaxReturn(array('info'=>'收藏成功','status'=>1,'is_collect'=>$is_collect));
		}else{
			$del_data=array('user_id'=>session('userid'),'record_id'=>$id);
			try {
				$res = $this->collectService()->mapDel($del_data);
			} catch (\Exception $e) {
				$this->ajaxReturn(array('info'=>$e->getMessage(),'status'=>0));
			}
			$is_collect=$this->collectService()->check($collect_params);
			$this->ajaxReturn(array('info'=>'取消收藏成功','status'=>1,'is_collect'=>$is_collect));
		}
	}
	
	//团购商品详情页
	public function groupbuyAction($id = 0){
		$act_id=I('act_id')?I('act_id'):I('get.act_id');
		//获取这个商品的团购活动
		$team_map=array('act_id'=>$act_id);
		$team_info=$this->activityService()->getFind($team_map);
		$team_info['product_name']=str_replace(",","，",$team_info['product_name']);
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
	
	private function db_create_in($item_list, $field_name = '')
	{
		if (empty($item_list))
		{
			return $field_name . " IN ('') ";
		}
		else
		{
			if (!is_array($item_list))
			{
				$item_list = explode(',', $item_list);
			}
			$item_list = array_unique($item_list);
			$item_list_tmp = '';
			foreach ($item_list AS $item)
			{
				if ($item !== '')
				{
					$item_list_tmp .= $item_list_tmp ? ",'$item'" : "'$item'";
				}
			}
			if (empty($item_list_tmp))
			{
				return $field_name . " IN ('') ";
			}
			else
			{
				return $field_name . ' IN (' . $item_list_tmp . ') ';
			}
		}
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
	
	private function goodsService(){
		return D('Goods', 'Service');
	}
	
	private function distributorService(){
		return D('Distributor', 'Service');
	}
	
	private function regionService(){
		return D('Region', 'Service');
	}
	
	private function goodsServiceService(){
		return D('GoodsService', 'Service');
	}
	
	private function goodsCatService(){
		return D('GoodsCat', 'Service');
	}
	
	private function goodsBrandService(){
		return D('GoodsBrand', 'Service');
	}
	
	private function goodsCommentService(){
		return D('GoodsComment','Service');
	}
	
	private function collectService(){
		return D('Collect', 'Service');
	}
	
	private function cartService(){
		return D('Cart', 'Service');
	}
	
	private function activityService(){
		return D('Activity','Service');
	}
	
	private function orderService(){
		return D('Order', 'Service');
	}
	
	private function goodsDistributionService() {
		return D('GoodsDistribution', 'Service');
	}
}