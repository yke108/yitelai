<?php
namespace Wap\Controller\Index;
use Wap\Controller\WapController;

class IndexController extends WapController {	
	public function _initialize(){
		parent::_initialize();
    }
	
    public function indexAction(){
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	//导航栏
    	$params = array(
    			'is_show'=>1,
    			'type'=>1,
    			'distributor_id'=>0
    	);
    	if ($get['dis_id']) {
    		$params['store_id'] = $get['dis_id'];
    	}
    	$nav = $this->navService()->getPagerList($params);
    	$this->assign('nav_list', $nav['list']);
    	
    	//广告
    	$params = array(
    		'distributor_id'=>0
    	);
    	$ad_list = Service('Ad')->infoAllList($params);
    	$this->assign('ad_list', $ad_list);
    	 
    	//粉丝故事会推荐首页文章
    	//$index_story_map=array('status'=>1,'is_index'=>1);
//    	$index_story_params=array('map'=>$index_story_map,'pagesize'=>3);
//    	$index_story_result=$this->storyService()->infoPagerList($index_story_params);
//    	$this->assign('index_story_list',$index_story_result['list']);
		//var_dump($index_story_result);die();
    	
    	//谷安居乐报
    	$params = array(
    			'pagesize'=>3,
    			'is_recommend'=>1,
    			'is_open'=>1
    	);
    	$datas = $this->newsinfoService()->infoPagerList($params);
    	$this->assign('news_recommend_list', $datas['list']);
    	
    	//家居服务分类
    	$params = array(
    			'pagesize'=>9,
    	);
    	$servecat = $this->servecatService()->catPagerList($params);
    	$this->assign('servecat_list', $servecat['list']);
    	 
    	//品牌
    	$map = array('is_recommend'=>1);
    	if ($get['dis_id']) {
    		$distributor_info = $this->distributorService()->getInfo($get['dis_id']);
    		if (empty($distributor_info)) {
    			$this->error('店铺不存在');
    		}
    		$map['brand_id'] = array('in', $distributor_info['brand_ids']);
    	}
    	$params = array(
    			//'pagesize'=>4,
    			'map'=>$map,
    	);
    	$datas = $this->goodsBrandService()->getPagerList($params);
    	$this->assign('brand_list', $datas['list']);
    	
    	//楼层
    	$map = array('is_show'=>1);
    	$datas = $this->goodsCatService()->getAllList($map);
    	$floor_no = 0;
    	foreach ($datas['list'] as $k => $v) {
    		if ($v['is_floor'] == 1) {
    			//分类广告
    			$floor_no++;
    			foreach ($this->ad_list as $k2 => $v2) {
    				$pos = strpos($k2, 'pc_index_floor_'.$floor_no);
    				if ($pos !== false) {
    					$vk = str_replace('pc_index_floor_'.$floor_no.'_', '', $k2);
    					$v[$vk] = $v2;
    				}
    			}
    			
    			//销量排行榜
    			$params = array(
    					'pagesize'=>5,
    					'cat_id'=>$v['cat_id'],
    					'orderby'=>'a.total_sale_count DESC'
    			);
    			$goods_datas = $this->distributorGoodsService()->getPagerList($params);
    			$v['top_list'] = $goods_datas['list'];
    			
    			$params = array(
    					'pagesize'=>4,
    					'cat_id'=>$v['cat_id'],
    			);
    			$goods_datas = $this->distributorGoodsService()->getPagerList($params);
    			$v['goods_list'] = $goods_datas['list'];
    			
    			$floor_list[] = $v;
    		}
    	}
    	$this->assign('floor_list', $floor_list);
    	
    	//猜你喜欢
    	$params = array(
    			'pagesize'=>4,
    			'orderby'=>'rand()'
    	);
    	if (I('dis_id')) {
    		$params['distributor_id'] = I('dis_id');
    	}
    	$datas = $this->distributorGoodsService()->getPagerList($params);
    	$this->assign('like_list', $datas['list']);
    	
    	//省市区
    	$this->assign('region_list', $this->regionService()->getAllRegions(false));
    	
    	//需求提报
    	$story_count = $this->designerMessageService()->getCount();
    	$this->assign('story_count', $story_count);
    	
		//粉丝故事会推荐首页文章
		$index_story_map=array('status'=>1,'is_index'=>1);
		$index_story_params=array('map'=>$index_story_map,'pagesize'=>3);
		$index_story_result=$this->storyService()->infoPagerList($index_story_params);
		$this->assign('index_story_list',$index_story_result['list']);
		
		
		//粉丝类别文章
		$story_cat=$this->storyService()->IndexCatStory();
		$this->assign('story_cat',$story_cat);
		
		//筛选出几个地区显示在首页案例那里
		$case_country_params=array('is_index'=>1,'type'=>'country');
		$country=$this->designerCaseService()->configGetField($case_country_params,'id,name',1,20);
		if(!empty($country)){
			foreach($country as $key=>$val){
				$case_map=array('is_index'=>1,'country'=>$key);
				if (I('dis_id')) {
					$case_map['distributor_id'] = I('dis_id');
				}
				$case_params=array(
						'map'=>$case_map,
						//'pagesize'=>4
				);
				$result=$this->designerCaseService()->infoPagerList($case_params);
				$country_data[$key]['list']=$result['list'];
				$country_data[$key]['country_name']=$val;
				unset($result);
			}
			$this->assign('country_data',$country_data);
		}
		
		//筛选出几个风格显示在首页案例那里
		$case_style_params=array('is_index'=>1,'type'=>'decorate_style');
		$style=$this->designerCaseService()->configGetField($case_style_params,'id,name',1,20);
		if(!empty($style)){
			foreach($style as $key=>$val){
				$style_map=array('is_index'=>1,'decorate_type'=>$key);
				if (I('dis_id')) {
					$style_map['distributor_id'] = I('dis_id');
				}
				$style_params=array(
						'map'=>$style_map,
						//'pagesize'=>4
				);
				$result=$this->designerCaseService()->infoPagerList($style_params);
				$style_data[$key]['list']=$result['list'];
				$style_data[$key]['type_name']=$val;
				unset($result);
			}
			$this->assign('style_data',$style_data);
		}
		
		//筛选出几个户型显示在首页案例那里
		$case_layout_params=array('is_index'=>1,'type'=>'house_type');
		$layout=$this->designerCaseService()->configGetField($case_layout_params,'id,name',1,20);
		if(!empty($layout)){
			foreach($layout as $key=>$val){
				$layout_map=array('is_index'=>1,'layout_type'=>$key);
				if (I('dis_id')) {
					$layout_map['distributor_id'] = I('dis_id');
				}
				$layout_params=array(
						'map'=>$layout_map,
						//'pagesize'=>4
				);
				$result=$this->designerCaseService()->infoPagerList($layout_params);
				$layout_data[$key]['list']=$result['list'];
				$layout_data[$key]['type_name']=$val;
				unset($result);
			}
			$this->assign('layout_data',$layout_data);
		}
		
		//筛选出几个家居区域显示在首页案例那里
		$case_district_params=array('is_index'=>1,'type'=>'district');
		$district=$this->designerCaseService()->configGetField($case_district_params,'id,name',1,20);
		if(!empty($district)){
			foreach($district as $key=>$val){
				$district_map=array('is_index'=>1,'district'=>$key);
				if (I('dis_id')) {
					$district_map['distributor_id'] = I('dis_id');
				}
				$district_params=array(
						'map'=>$district_map,
						//'pagesize'=>4
				);
				$result=$this->designerCaseService()->infoPagerList($district_params);
				$district_data[$key]['list']=$result['list'];
				$district_data[$key]['type_name']=$val;
				unset($result);
			}
			$this->assign('district_data',$district_data);
		}
		
		//print_r($style_data);die();
		
		//案例字段
		$case_config=$this->designerCaseService()->configGetField(array(),'id,name');
		$this->assign('case_config',$case_config);
		
		
		//推荐到首页的设计师
		$level_params=array('is_index'=>1,'type'=>'level');
		$level=$this->designerTypeService()->getTypeValueField($level_params,'id,name',1,20);
		if(!empty($level)){
			foreach($level as $key=>$val){
				$level_designer_map=array('is_index'=>1,'level'=>$key);
				if (I('dis_id')) {
					$level_designer_map['distributor_id'] = I('dis_id');
				}
				$level_designer_params=array(
						'map'=>$level_designer_map,
						//'pagesize'=>4,
						'get_case'=>1
				);
				$result=$this->designerService()->infoPagerList($level_designer_params);
				$level_data[$key]['list']=$result['list'];
				$level_data[$key]['level_name']=$val;
				unset($result);
			}
			$this->assign('level_data',$level_data);
		}
		
		
		
		//设计师置顶列表
		$designer_recommend_params=array('is_page_top'=>1,'pagesize'=>6);
		if (I('dis_id')) {
			$designer_recommend_params['distributor_id'] = I('dis_id');
		}
		$designer_recommend_result=$this->designerService()->infoPagerList($designer_recommend_params);
		$this->assign('designer_recommend_list',$designer_recommend_result['list']);
		//var_dump($designer_recommend_result['list']);die();
		
		//定制类型
		$params = array(
				'is_show'=>1,
		);
		$result = $this->designerMessageTypeService()->infoPagerList($params);
		$this->assign('message_type_list', $result['list']);
		
		$this->display();
    }
	
	public function listAction(){
		//获取开关配置
		$configService = $this->configService();
		$result = $configService->findDisplaySwitchConfigs();
		$this->assign('switch', $result);
		
		$result = $configService->findCoinRechargeConfigs();
		$this->assign('coinConfig', $result);
		
		//获取产品列表
		$luckyService = $this->luckyService();
		$map = array(
			'is_last'=>1,
		);
		$list = $luckyService->searchIssue($map, 'is_over asc, add_time desc', 1, 20);
		$this->assign('lucky_list', $list);
		$this->display();
	}
	
	 private function newsinfoService(){
    	return D('News', 'Service');
    }
    
	private function goodsService(){
		return D('Goods', 'Service');
	}
	
	private function distributorGoodsService(){
		return D('Distributor\Goods', 'Service');
	}
	
	private function goodsCatService(){
		return D('GoodsCat', 'Service');
	}
	
	private function goodsBrandService(){
		return D('GoodsBrand', 'Service');
	}
	
	private function servecatService(){
		return D('Serve', 'Service');
	}
	
	private function inquiryService(){
		return D('Inquiry', 'Service');
	}
	
	private function regionService(){
		return D('Region', 'Service');
	}
	
	private function designerService(){
		return D('Designer','Service');
	}
	
	private function designerMessageService(){
		return D('DesignerMessage','Service');
	}
	
	private function designerCaseService(){
		return D('DesignerCase','Service');
	}
	
	private function storyService(){
		return D('Story','Service');
	}
	
	private function designerTypeService(){
		return D('DesignerType','Service');
	}
	
	private function distributorService(){
		return D('Distributor','Service');
	}
	
	private function designerMessageTypeService(){
		return D('DesignerMessageType', 'Service');
	}
}