<?php
namespace Main\Controller;
use Think\Controller;
//use Think\Log;

abstract class MainController extends Controller {
	public $login_check = false;
	public $user = array();
	public $cat_list = array();
	public $ad_list = array();
	public $sysconfig = array();
	public $pagesize = 20;
	
	public function _initialize(){
		$controller_name = strtolower(CONTROLLER_NAME);
		$carr = explode('/', $controller_name);
		$this->assign('croot', $carr[0]);
		$this->assign('controller_name', $controller_name);
		$this->assign('action_name', ACTION_NAME);
		
		//session('userid', 5); //debug
		if(session('userid')){
			$userid = session('userid');
			$this->user = $this->userService()->getUserInfo($userid);
			$this->user['user_id'] < 1 && session('userid', null);
			$this->user['user_thumb_img']=$this->user['avatar']!=''?str_replace('/upload/','/upload/thumbs/b200/',$this->user['avatar']):'';
			$this->assign('user', $this->user);
		}
		
		if ($this->login_check && empty($userid)) {
			if (IS_AJAX) {
				$this->error('请先登录', U('index/site/login'));
			}
			$this->redirect('index/site/login');
		}
		
		//系统设置
		$this->sysconfig = $this->configService()->findConfigs('system');
		$this->assign('sysconfig', $this->sysconfig);
		
		//城市筛选
		$distributor_list = Service('Distributor')->getAllList();
		if (!empty($distributor_list)) {
			foreach ($distributor_list as $v) {
				$city_code[] = intval($v['region_code'] / 100) * 100;
				//$province_code[] = intval($v['region_code'] / 10000) * 10000;
			}
			$city_list = M('region')->where(array('region_code'=>array('in', $city_code)))->select();
			//$province_list = M('region')->where(array('region_code'=>array('in', $province_code)))->select();
			
			//热门城市
			$city_data = array();
			foreach ($city_list as $v) {
				$hot[] = array(
						'pid'=>$v['region_level'],
						'pname'=>$v['region_name'],
						'id'=>$v['region_code'],
						'name'=>$v['region_name'],
				);
				$city_data['hot'] = $hot;
			}
			
			/* foreach ($province_list as $v) {
				$childs = Service('Region')->getChildList($v['region_code']);
				$region_ids = array_keys($childs);
				$city = array();
				foreach ($city_list as $v2) {
					if (in_array($v2['region_code'], $region_ids)) {
						$city[] = array(
								'id'=>$v2['region_code'],
								'name'=>$v2['region_name'],
						);
					}
				}
				$province[] = array(
						'id'=>$v['region_code'],
						'name'=>$v['region_name'],
						'city'=>$city,
				);
				$city_data['province'] = $province;
			} */
			$province_list = Service('Region')->getProvinceList();
			$province_ids = array_keys($province_list);
			$region_list = Service('Region')->getRegionArray(implode(',', $province_ids));
			foreach ($region_list as $k => $v) {
				$city = array();
				foreach ($v['region_list'] as $k2 => $v2) {
					$city[] = array(
							'id'=>$k2,
							'name'=>$v2['region_name'],
					);
				}
				$province[] = array(
						'id'=>$k,
						'name'=>$v['region_name'],
						'city'=>$city,
				);
			}
			$city_data['province'] = $province;
			
			$this->assign('city_data', json_encode($city_data));
		}
		
		$city = session('city') ? session('city') : '440100';
		$city_choice = M('region')->where(array('region_code'=>$city))->find();
		$this->assign('city_choice', $city_choice);
		
		//统计访问量
		$params = array(
				'user_id'=>session('userid'),
				'region_code'=>$city,
				'shop_id'=>I('store_id'),
		);
		try {
			if (session('userid')) {
				$this->statisticsService()->createUserAsk($params); //用户访问量
			}else {
				$this->statisticsService()->createTouristAsk($params); //游客访问量
			}
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		try {
			$this->statisticsService()->createWebAsk($params); //PC访问量
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		try {
			$this->statisticsService()->createTotalAsk($params); //总访问量
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		
		//搜索类型
		$search_type = in_array(I('search_type'), array(1,2,3,4,5)) ? I('search_type') : 1;
		switch ($search_type) {
			case 1: $search_type_text = '搜产品';break;
			case 2: $search_type_text = '搜店铺';break;
			case 3: $search_type_text = '搜文章';break;
			case 4: $search_type_text = '搜品牌';break;
			case 5: $search_type_text = '搜楼盘';break;
		}
		$this->assign('search_type', $search_type);
		$this->assign('search_type_text', $search_type_text);
		
		//热门关键词
		$keywords = $this->goodsKeywordsService()->getPagerList();
		$this->assign('keyword_list', $keywords['list']);
		
		//导航栏
		$params = array(
				'is_show'=>1,
				'distributor_id'=>0,
				'type'=>0,
		);
		$nav = $this->navService()->getPagerList($params);
		/*$material_url = array(
				'name'=>'图库',
				'url'=>DK_DOMAIN.'/material',
				'opennew'=>1,
		);
		$nav['list'][] = $material_url;*/
		$this->assign('nav_list', $nav['list']);
		
		//购物车数量
		$params = array(
				'user_id'=>session('userid')
		);
		$cart_num = $this->cartService()->getCartNumber($params);
		$this->assign('cart_num', $cart_num);
		
		//广告
		$params = array(
				'distributor_id'=>0
		);
		$this->ad_list = $this->adService()->infoAllList($params);
		$this->assign('ad_list', $this->ad_list);
		
		//商品分类
		$map['is_show'] = 1;
		$datas_cat = $this->goodsCatService()->getAllList($map);
		$this->cat_list = $datas_cat['list'];
		$this->assign('cat_list', $this->cat_list);
		
		//友情链接
		$friendlink_datas = $this->friendLinkService()->infoPagerList();
		$this->assign('friendlink_list', $friendlink_datas['list']);
		
		//cookie
		$this->assign('pc_top', cookie('pc_top'));
		$this->assign('pc_goods_list_top', cookie('pc_goods_list_top'));
		$this->assign('pc_goods_list_bottom', cookie('pc_goods_list_bottom'));
		
		//来源url
		if (!IS_POST) {
			session('from_url', $_SERVER['HTTP_REFERER']);
		}
		
		//是否申请了分销员
		if($this->user['user_id']>0){
			$apply_agent_info=D('Saleman','Service')->userGetInfo($this->user['user_id']);
			if(!empty($apply_agent_info)){
				$this->assign('apply_agent_info',$apply_agent_info);
			}
		}
		
		$this->assign('question_cat_list', $this->questionService()->catList());
		$this->assign('manual_flag', session('manual_flag') == 1 ? 1 : 0);
    }
    
	protected function pageAndSize(&$p, &$size){
		$get = I('get.');
		$p2 = $get['p'];
		$size2 = $get['size'];
		if($p2 > 0){
			$p = $p2;
		}
		if($size2 > 0){
			$size = $size2;
		}
	}
	
    protected function error($message='',$jumpUrl='',$ajax=false) {
		parent::error($message, $jumpUrl, $ajax);
		exit;
    }

    protected function success($message='',$jumpUrl='',$ajax=false) {
		parent::success($message, $jumpUrl, $ajax);
		exit;
    }

	protected function renderPartial($templateFile='',$content='',$prefix=''){
		layout(false);
		return $this->fetch($templateFile,$content,$prefix);
	}
	
    protected function display($templateFile='',$charset='',$contentType='',$content='',$prefix='') {
		if(IS_AJAX){
			if($_GET['is_trp'] == 1){
				$ret = array(
					'status'=>2,
					'info'=>$this->renderPartial('_'.$templateFile),
					'pager'=>$this->get('pager'),
					'wrap_id'=>$this->get('x_wrap_id'),
					'pager_id'=>$this->get('x_pager_id'),
				);
			} else {
				$ret = array(
					'status'=>2,
					'info'=>$this->renderPartial($templateFile, $content,$prefix),
					'url'=>U('', I('get.')),
				);
			}
			$this->ajaxReturn($ret);
		} else {
			$this->view->display($templateFile,$charset,$contentType,$content,$prefix);
			exit;
		}
    }
	
	protected function specAdTypeToLink(&$ad){
		switch($ad['ad_type']){
			case 1:{ //注册页面
				$ad['ad_value'] = U('index/site/reg');
				break;
			}
		}
		return $ad;
	}
	
    protected function _empty(){
        $this->redirect('index/index/index');
    }
	
	protected function userService(){
		return D('User', 'Service');
	}
	
	protected function configService(){
		return D('Config', 'Service');
	}
	
	protected function navService() {
		return D('Nav', 'Service');
	}
	
	protected function adService(){
		return D('Ad', 'Service');
	}
	
	protected function friendLinkService(){
		return D('FriendLink', 'Service');
	}
	
	protected function goodsKeywordsService() {
		return D('GoodsKeywords', 'Service');
	}

	protected function goodsCatService(){
		return D('GoodsCat', 'Service');
	}
	
	protected function cartService(){
		return D('Cart', 'Service');
	}
	
	protected function statisticsService(){
		return D('Statistics', 'Service');
	}
	
	protected function questionService(){
		return D('Question', 'Service');
	}
}
