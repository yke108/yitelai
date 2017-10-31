<?php
namespace Main\Controller\Store;
use Main\Controller\MainController;
use Common\Basic\Genre;
use Common\Basic\Pager;

class GoodsController extends MainController {	
	public function _initialize(){
		parent::_initialize();
		
		$this->assign('page_title', '商品列表');
		
		$get = I('get.');
		$this->assign('get', $get);
		
		//分销商
		$distributor = $this->distributorService()->getInfo($get['store_id']);
		if (empty($distributor)) {
			$this->error('店铺不存在');
		}
		$distributor['region'] = $this->regionService()->getDistrictFullName($distributor['region_code']);
		$this->assign('distributor', $distributor);
		
		//微信端地址
		$store_url = DK_DOMAIN.U('store/index/index', array('store_id'=>$distributor['distributor_id']));
		$store_url = str_replace('index.php', 'wap/index.php', $store_url);
		$this->assign('store_url', $store_url);
		
		//导航菜单
		$params = array(
				'is_show'=>1,
				'distributor_id'=>$get['store_id']
		);
		$nav = $this->navService()->getPagerList($params);
		$this->assign('store_nav_list', $nav['list']);
		
		//分销商商品分组
		$map = array('distributor_id'=>$distributor['distributor_id']);
		$self_cat_list = $this->distributorGoodsCatService()->getAllList($map);
		$this->assign('self_cat_list', $self_cat_list);
		
		//商品标签
		$params = array(
				'distributor_id'=>$get['store_id'],
				'nav_show'=>1,
				'pagesize'=>6,
		);
		$datas = $this->goodsLabelService()->getPagerList($params);
		$this->assign('goodslabel_list', $datas['list']);
    }
	
    public function indexAction(){
    	$this->listDisplay();
    }
    
    public function newAction(){
    	$map = array('a.is_new'=>1);
    	$this->listDisplay($map);
    }
    
    public function hotAction(){
    	$map = array('a.is_hot'=>1);
    	$this->listDisplay($map);
    }
    
    public function recommendAction(){
    	$map = array('a.is_recommend'=>1);
    	$this->listDisplay($map);
    }
    
    private function listDisplay($map = array()){
    	$get = I('get.');
    	$get['sort'] = $get['sort'] ? $get['sort'] : 'down';
    	$this->assign('get', $get);
    	
    	//当前分类
    	$cat = $this->distributorGoodsCatService()->getCat($get['cat_id']);
    	$this->assign('cat', $cat);
    	
    	//店铺商品列表
    	$map['a.product_num'] = array('gt', 0);
    	$map['a.distributor_id'] = $get['store_id'];
    	$page = $get['p'] < 1 ? 1 : $get['p'];
    	$params = array(
    			'page'=>$page,
    			'pagesize'=>12,
    			'map'=>$map
    	);
    	if (!empty($get['cat_id'])) {
    		$params['self_cat_id'] = $get['cat_id'];
    	}
    	if (!empty($get['keyword'])) {
    		$params['keyword'] = $get['keyword'];
    	}
    	if (!empty($get['label_id'])) {
    		$params['label_id'] = $get['label_id'];
    	}
    	
    	//排序
    	switch ($get['order']) {
    		case 'view': $order_by = 'a.view_count'; $sort_view = ($get['sort'] == 'down') ? 'up' : 'down'; $this->assign('sort_view', $sort_view); break;
    		case 'sale': $order_by = 'a.total_sale_count'; $sort_sale = ($get['sort'] == 'down') ? 'up' : 'down'; $this->assign('sort_sale', $sort_sale); break;
    		case 'new': $order_by = 'a.is_new'; $sort_new = ($get['sort'] == 'down') ? 'up' : 'down'; $this->assign('sort_new', $sort_new); break;
    		case 'price': $order_by = 'a.min_product_price'; $sort_price = ($get['sort'] == 'down') ? 'up' : 'down'; $this->assign('sort_price', $sort_price); break;
    		case 'collect': $order_by = 'a.collect_count'; $sort_collect = ($get['sort'] == 'down') ? 'up' : 'down'; $this->assign('sort_collect', $sort_collect); break;
    	}
    	if (!empty($order_by)) {
    		$sort = ($get['sort'] == 'down') ? 'DESC' : 'ASC';
    		$params['orderby'] = $order_by.' '.$sort;
    	}
    	
    	$datas = $this->distributorGoodsService()->getPagerList($params);
    	if ($datas[count] > 0){
    		$this->assign('goods_list', $datas['list']);
    		$pager = new Pager($datas['count'], $params['pagesize']);
    		$this->assign('pages', $pager->show_pc());
    		
    		$total_page = ceil($datas['count'] / $params['pagesize']);
    		$this->assign('page', $page);
    		$this->assign('total_page', $total_page);
    		$this->assign('pages_turn', $pager->show_turn());
    	}
    	 
    	$this->display('index');
    }
    
    private function distributorService(){
    	return D('Distributor', 'Service');
    }
	
	private function distributorGoodsService(){
		return D('Distributor\Goods', 'Service');
	}
	
	private function distributorGoodsCatService(){
		return D('Distributor\GoodsCat', 'Service');
	}
	
	private function regionService(){
		return D('Region', 'Service');
	}
	
	private function goodsLabelService(){
		return D('GoodsLabel', 'Service');
	}
}