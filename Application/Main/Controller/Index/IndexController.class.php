<?php
namespace Main\Controller\Index;
use Main\Controller\MainController;

class IndexController extends MainController {	
	public function _initialize(){
		parent::_initialize();
		
		$this->assign('page_title', '首页');
    }
	
    public function indexAction(){
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
    	$orderby = 'sort_order ASC';
    	$brand_list = $this->goodsBrandService()->getAllList($map, $orderby);
    	$this->assign('brand_list', $brand_list);
    	
    	//楼层
    	$floor_no = 0;
    	foreach ($this->cat_list as $k => $v) {
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
    			
    			//场景
    			$scene_list = array();
    			if ($v['scene']) {
    				$map = array('scene_id'=>array('in', $v['scene']));
    				$scene_list = $this->goodsSceneService()->selectAllList($map);
    			}
    			$v['scene_list'] = $scene_list;
    			
    			$floor_list[] = $v;
    		}
    	}
    	$this->assign('floor_list', $floor_list);
    	
    	//省市区
    	$this->assign('region_list', $this->regionService()->getAllRegions(false));
    	
		//粉丝故事会推荐首页文章
		$index_story_map=array('status'=>1,'is_index'=>1);
		$index_story_params=array('map'=>$index_story_map,'pagesize'=>3);
		$index_story_result=$this->storyService()->infoPagerList($index_story_params);
		$this->assign('index_story_list',$index_story_result['list']);
		
		
		
		//粉丝类别文章
		$story_cat=$this->storyService()->IndexCatStory();
		$this->assign('story_cat',$story_cat);
		
		//推荐到首页的设计师
		$designer_map=array('is_index'=>1);
		$designer_params=array('pagesize'=>4,'map'=>$designer_map);
		$designer_result=$this->designerService()->infoPagerList($designer_params);
		$this->assign('designer_list',$designer_result['list']);
		
		//重点推荐到首页的设计师
		$designer_info_map=array('is_key_index'=>1);
		$designer_info_params=array('pagesize'=>1,'map'=>$designer_info_map);
		if (I('get.city')) {
			$designer_info_params['city'] = I('get.city');
		}
		$designer_info_result=$this->designerService()->infoPagerList($designer_info_params);
		$designer_info=$designer_info_result['list'][0];
		if(!empty($designer_info)){
			$case_params=array('pagesize'=>4,'designer_id'=>$designer_info['designer_id']);
			$case_result=$this->designerCaseService()->infoPagerList($case_params);
			$designer_info['case_list']=$case_result['list'];
		}
		$this->assign('desinger_info',$designer_info);
		
		//设计师置顶列表
		$designer_recommend_params=array(
				'is_index'=>1,
				'pagesize'=>6,
		);
		if (I('get.city')) {
			$designer_recommend_params['city'] = I('get.city');
		}
		$designer_recommend_result=$this->designerService()->infoPagerList($designer_recommend_params);
		$this->assign('designer_recommend_list',$designer_recommend_result['list']);
		//var_dump($designer_recommend_result['list']);die();
		
		
		$this->display();
    }

    public function inquiryAction() {
    	$post = I('post.');
    	$post['user_id'] = $this->user['user_id'];
    	try {
    		$story_count = $this->designerMessageService()->infoCreateOrModify($post);
    		$this->success('提交成功', U('index'), array('story_count'=>$story_count));
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    }
    
    public function defaultcityAction($region_code = 0) {
    	if (session('city', $region_code) === 'null') {
    		$this->error('error');
    	}
    	$this->success('success');
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
	
	private function goodsBrandService(){
		return D('GoodsBrand', 'Service');
	}
	
	private function goodsSceneService(){
		return D('GoodsScene', 'Service');
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
}