<?php
namespace Main\Controller\Mall;
use Main\Controller\MainController;
use Common\Basic\Pager;
use Common\Basic\Status;

class CategoryController extends MainController {	
	public function _initialize(){
		parent::_initialize();
		
		$this->assign('page_title', '商品列表');
    }
    
    public function indexAction(){
    	$map['a.product_num'] = array('gt', 0);
    	$this->listDisplayAction($map);
    }
    
    public function seckillAction(){
    	$map = array(
    			'a.seckill_start'=>array('elt', NOW_TIME),
    			'a.seckill_end'=>array('egt', NOW_TIME),
    			'a.seckill_status'=>1,
    			'a.total_seckill_num'=>array('gt', 0)
    	);
    	$this->listDisplayAction($map);
    }
    
    public function listDisplayAction($map = array()){
    	$get = I('get.');
    	$get['spec_value_id_arr'] = $get['spec_value_id'] ? explode(',', $get['spec_value_id']) : array();
    	$this->assign('get', $get);
    	
    	//筛选（品牌）
    	if (!empty($get['brand_id'])) {
    		$map_filter = array(
    				'brand_id'=>array('in', $get['brand_id'])
    		);
    		$filter_brand = $this->goodsBrandService()->getAllList($map_filter);
    		$this->assign('filter_brand', $filter_brand);
    	}
    	//筛选（地区）
    	if (!empty($get['region_code'])) {
    		$map_filter = array(
    			'region_code'=>array('in', $get['region_code'])
    		);
    		$filter_region = M('region')->where($map_filter)->select();
    		$this->assign('filter_region', $filter_region);
    	}
    	
    	if (IS_POST) {
    		$post = I('post.');
    		$brand_id = $post['brand_id'] ? implode(',', $post['brand_id']) : $get['brand_id'];
    		$region_code = $post['region_code'] ? implode(',', $post['region_code']) : $get['region_code'];
    		$redirect_url = U('', array('keyword'=>$get['keyword'], 'cat_id'=>$get['cat_id'], 'brand_id'=>$brand_id, 'region_code'=>$region_code, 'spec_value_id'=>$get['spec_value_id'], 'order'=>$get['order']));
    		header('Location:'.$redirect_url);
    	}
    	
    	//当前分类
    	$cat = $this->goodsCatService()->getInfo($get['cat_id']);
    	$this->assign('cat', $cat);
    	
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
    	
    	//属性筛选
    	/* $all_attr_list = $this->goodsService()->get_filter_attr($cat['cat_id'], $get);
    	$this->assign('filter_attr_list',  $all_attr_list); */
    	
    	//品牌筛选
    	$brand_list = $cat['brand_list'];
    	if (empty($cat['brand_list'])) {
    		$brand_list = $this->goodsBrandService()->getAllList();
    	}
    	$this->assign('brand_list', $brand_list);
    	
    	//配送安装地区筛选
		$map_distributor = array('status'=>Status::DistributorStatusNormal);
    	$distributor_list = $this->distributorService()->getAllList($map_distributor);
    	if (!empty($distributor_list)) {
    		foreach ($distributor_list as $v) {
    			$region_code[] = intval($v['region_code'] / 100) * 100;
    		}
    	}
    	$region_list = M('region')->where(array('region_code'=>array('in', $region_code)))->select();
    	$this->assign('region_list', $region_list);
    	
    	//分销商商品列表
		$map['a.product_num'] = array('gt', 0);
		//$map['c.status'] = 2;
		$params = array(
				'page'=>$get['p'] < 1 ? 1 : $get['p'],
				'pagesize'=>24,
				'map'=>$map
		);
		if (!empty($get['keyword'])) {
			$params['keyword'] = $get['keyword'];
		}
		if (!empty($get['cat_id'])) {
			$params['cat_id'] = $get['cat_id'];
		}
		if (!empty($get['scene_id'])) {
			$params['scene_id'] = $get['scene_id'];
		}
		if (!empty($get['label_id'])) {
			$params['label_id'] = $get['label_id'];
		}
		if (!empty($get['brand_id'])) {
			$params['brand_id'] = $get['brand_id'];
		}
		if (!empty($get['region_code'])) {
			$region_codes = explode(',', $get['region_code']);
			$region_list = $this->regionService()->regionChildrenForSelect($region_codes);
			$region_code = array_keys($region_list);
			$params['region_code'] = $region_code;
		}
		/* if (!empty($get['spec_value_id'])) {
			$params['goods_spec_value_id'] = $get['spec_value_id'];
		} */
		
		//排序
		$sort = ($get['sort'] == 'asc') ? 'ASC' : 'DESC';
		switch ($get['order']) {
			case 'view': $order_by = 'a.view_count DESC'; break;
			case 'sale': $order_by = 'a.total_sale_count DESC'; break;
			case 'new': $order_by = 'a.is_new DESC'; break;
			//case 'price_desc': $order_by = 'a.min_product_price DESC'; break;
			//case 'price_asc': $order_by = 'a.min_product_price ASC'; break;
			case 'price': $order_by = 'a.min_product_price '.$sort; break;
			case 'collect': $order_by = 'a.collect_count DESC'; break;
		}
		$sort_price = ($get['sort'] == 'asc') ? 'desc' : 'asc';
		$this->assign('sort_price', $sort_price);
		
		//百度API
		$ip = get_real_ip();
		$rest = curl_get('https://api.map.baidu.com/location/ip?ak=Hrp8Uwkdl487PZhGup8oikL4&coor=bd09ll&ip='.$ip);
		$point = $rest->content->point;
		$lng = $point->x;
		$lat = $point->y;
		$params['distance'] = "ACOS(SIN(('".$lat."' * 3.1415) / 180 ) *SIN((lat * 3.1415) / 180 ) +COS(('".$lat."' * 3.1415) / 180 ) * COS((lat * 3.1415) / 180 ) *COS(('".$lng."'* 3.1415) / 180 - (lng * 3.1415) / 180 ) ) * 6380";
		
		if (!empty($order_by)) {
			$params['orderby'] = $order_by;
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
		if ($datas[count] > 0){
			$this->assign('goods_list', $datas['list']);
			$pager = new Pager($datas['count'], $params['pagesize']);
			$this->assign('pages', $pager->show_pc());
			
			//属性筛选
			if ($cat['cat_id'] > 0) {
				$all_attr_list = $this->goodsService()->get_filter_attr($cat['cat_id'], $get);
				$this->assign('filter_attr_list',  $all_attr_list);
				
				$filter_attr_count = 0;
				foreach ($all_attr_list as $v) {
					if ($v['selected'] == 0) {
						$filter_attr_count++;
					}
				}
				$this->assign('filter_attr_count',  $filter_attr_count);
			}
		}
		
		//猜你喜欢
		$params = array(
				'pagesize'=>4,
				'map'=>$map,
				'orderby'=>'rand()'
		);
		$datas = $this->distributorGoodsService()->getPagerList($params);
		$this->assign('like_list', $datas['list']);
		
		$this->display('index');
    }
	
    public function groupbuyAction(){
    	$get = I('get.');
    	$this->assign('get', $get);
		$p=$get['p'] < 1 ? 1 : $get['p'];
		$size=12;
		
		//获取团购商品列表
		$map=array();
		$params=array('page'=>$p,'pagesize'=>$size,'map'=>$map,'is_going'=>1);
		$team_result=$this->activityService()->teamPagerList($params);
		$team_list=$team_result['list'];
		
		foreach($team_list as $key=>$val){
			$distributor_id[]=$val['distributor_id'];
		}
		
		if(!empty($distributor_id)){
			$distributor_name=$this->distributorInfoService()->getFieldData(array('distributor_id'=>array('in',$distributor_id)),'distributor_id,distributor_name');
			foreach($team_list as $key=>$val){
				$team_list[$key]['distributor_name']=$distributor_name[$val['distributor_id']];
			}
		}
		
		$team_pager=new Pager($team_result['count'],$size);
		$team_pager->setConfig('header','');
		$this->assign('team_list',$team_list);
		$this->assign('team_page',$team_pager->show());
		
    	//当前分类
    	$cat = $this->goodsCatService()->getInfo($get['cat_id']);
    	$this->assign('cat', $cat);
    	
    	//品牌列表
    	$brand_list = $cat['brand_list'];
    	if (empty($cat['brand_list'])) {
    		$brand_list = $this->goodsBrandService()->getAllList();
    	}
    	$this->assign('brand_list', $brand_list);
    
    	//配送安装地区
    	$shipping_area = $this->shippingService()->getPagerShippingareaList();
    	$this->assign('shipping_area_list', $shipping_area['list']);
		
    	
    	//猜你喜欢
    	$params = array(
    			'pagesize'=>4,
    			'map'=>$map,
    			'orderby'=>'rand()'
    	);
    	$datas = $this->distributorGoodsService()->getPagerList($params);
    	$this->assign('like_list', $datas['list']);
    	 
    	$this->display();
    }
    
    public function likeAction() {
    	$map = array(
    			'a.product_num'=>array('gt', 0),
    			'c.status'=>Status::DistributorStatusNormal,
    			'_string'=>'record_id >= (
		(SELECT MAX(record_id) FROM hy_distributor_goods) - (SELECT MIN(record_id) FROM hy_distributor_goods)
	) * RAND() + (SELECT MIN(record_id) FROM hy_distributor_goods)',
    	);
    	//猜你喜欢
    	$params = array(
    			'pagesize'=>4,
    			'map'=>$map,
    			'orderby'=>'rand()'
    	);
    	$datas = $this->distributorGoodsService()->getPagerList($params);
    	if(empty($datas['list'])){
    		$clist = '';
    	}else{
    		$this->assign('like_list', $datas['list']);
    		$clist = $this->renderPartial('guess_like');
    	}
    	$this->ajaxReturn($clist);
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
	
	private function distributorInfoService(){
		return D('Distributor', 'Service');
	}
	
	private function goodsService(){
		return D('Goods', 'Service');
	}
	
	private function goodsBrandService(){
		return D('GoodsBrand', 'Service');
	}
	
	private function shippingService(){
		return D('Shipping', 'Service');
	}
	
	private function distributorService(){
		return D('Distributor', 'Service');
	}
	
	private function goodsSpecService(){
		return D('GoodsSpec', 'Service');
	}
	
	private function activityService(){
		return D('Activity','Service');
	}
	
	private function regionService(){
		return D('Region','Service');
	}
}