<?php
namespace Distributor\Controller\Goods;
use Distributor\Controller\FController;
use Common\Basic\Pager;

class IndexController extends FController {
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'goods',
			'ac'=>'goods_index_index',
		);
		$this->sbset($set);
		
		$this->assign('is_edit_sales', $this->distributor_info['is_edit_sales']);
    }
    
    //修正店铺商品数量和价格
    public function testAction(){
    	$goods_list = M('distributor_goods')->select();
    	foreach ($goods_list as $goods) {
    		$product_num = M('distributor_goods_product')->where(array('record_id'=>$goods['record_id']))->sum('stock_num');
    		$min_product = M('distributor_goods_product')->where(array('record_id'=>$goods['record_id']))->order('product_price ASC')->find();
    		$max_product = M('distributor_goods_product')->where(array('record_id'=>$goods['record_id']))->order('product_price DESC')->find();
    		$data = array(
    				'product_num'=>$product_num,
    				'min_product_price'=>$min_product['product_price'] ? $min_product['product_price'] : 0,
    				'max_product_price'=>$max_product['product_price'] ? $max_product['product_price'] : 0,
    		);
    		$res = M('distributor_goods')->where(array('record_id'=>$goods['record_id']))->save($data);
    		_p($res, false);
    	}
    }
    
    public function indexAction(){
    	session('back_url', __SELF__);
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	//平台分类
    	$categorys = $this->goodsCatService()->getDisOptionList($this->org_id, $get['cat_id']);
    	$this->assign('platform_categorys', $categorys);
		
    	//自定义分组
    	$map = array('distributor_id'=>$this->org_id);
    	$categorys = $this->distributorGoodsCatService()->getOptionList($map, $get['self_cat_id']);
    	$this->assign('distributor_categorys', $categorys);
    	
    	//商品列表
    	$params = array(
    			'distributor_id'=>$this->org_id,
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    	);
    	if (!empty($get['keyword'])) {
    		$params['keyword'] = $get['keyword'];
    	}
    	if (!empty($get['cat_id'])) {
    		$params['cat_id'] = $get['cat_id'];
    	}
    	if (!empty($get['self_cat_id'])) {
    		$params['self_cat_id'] = $get['self_cat_id'];
    	}
    	if ($get['is_custom'] != '') {
    		$params['is_custom'] = $get['is_custom'];
    	}
    	$datas = $this->distributorGoodsService()->getPagerList($params);
    	foreach($datas['list'] as $key=>$val){
    		$url=U("mall/goods/preview",array("id"=>$val[goods_id]));
    		$url=str_replace("/distributor","",$url);
    		$datas['list'][$key]['url']=$url;
    	}
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $this->pagesize);
    	$this->assign('pager', $pager->show());
    	
    	$this->display();
    }

    public function platformAction(){
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	//平台分类列表
    	$categorys = $this->goodsCatService()->getDisOptionList($this->org_id, $get['cat_id']);
    	$this->assign('categorys', $categorys);
    	
    	//品牌筛选列表
    	//只能增加指定的品牌的商品
    	/* if ($this->distributor_info['brand_ids']) {
    		$map['brand_id'] = array('in', $this->distributor_info['brand_ids']);
    	} */
    	$brand_ids = $this->distributor_info['brand_ids'] ? $this->distributor_info['brand_ids'] : array();
    	if ($brand_ids) {
    		$map['brand_id'] = array('in', $brand_ids);
    		$brand_list = $this->goodsBrandService()->getAllList($map);
    		$this->assign('brand_list', $brand_list);
    	}
    	
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'_distributor_id'=>$this->org_id,
    	);
    	if (!empty($get['keyword'])) {
    		$params['keyword'] = $get['keyword'];
    	}
    	if (!empty($get['cat_id'])) {
    		$params['cat_id'] = $get['cat_id'];
    	}
    	if ($get['is_custom'] != '') {
    		$params['is_custom'] = $get['is_custom'];
    	}
    	
    	$map['is_on_sale'] = 1; //商品必须上架
    	$map['product_num'] = array('gt', 0); //商品必须有货品
    	//过滤已添加的商品
    	$all_goods = $this->distributorGoodsService()->getAllList($this->org_id);
    	if ($all_goods) {
    		foreach ($all_goods as $goods) {
    			$goods_ids[] = $goods['goods_id'];
    		}
    		$map['goods_id'] = array('not in', $goods_ids);
    	}
    	//是否调用直营商品
    	$distributor_info = $this->distributorService()->getInfo($this->org_id);
    	if ($distributor_info['is_self_distributor'] == 0) {
    		$map['is_self_sale'] = array('neq', 1);
    	}
    	if ($get['brand_id']) {
    		$map['brand_id'] = $get['brand_id'];
    	}
    	$params['map'] = $map;
    	$datas = $this->goodsService()->getPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $this->pagesize);
    	$this->assign('pager', $pager->show());
    	
    	$set = array(
    			'in'=>'goods',
    			'ac'=>'goods_index_platform',
    	);
    	$this->sbset($set)->display();
    }
    
    public function infoAction($goods_id = 0){
    	$info = $this->goodsService()->getInfo($goods_id);
    	if(empty($info)){
    		$this->error('数据不存在');
    	}
    	$this->assign('info', $info);
    
    	$params = array(
    			'goods_id'=>$goods_id
    	);
    	$datas = $this->goodsProductService()->getAllList($params);
    	$this->assign('product_list', $datas['list']);
    
    	$this->display();
    }
    
	public function editAction($record_id = 0){
		$info = $this->distributorGoodsService()->getInfo($record_id);
		if(empty($info)) $this->error('商品不存在');
		$this->assign('info', $info);
		
		if(IS_POST){
			$post = I('post.');
			try {
				$this->distributorGoodsService()->createOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('编辑成功', session('back_url'));
		}
		
		$this->editPublic($info['self_cat_id']);
		
		//货品列表
		$params = array(
				'record_id'=>$record_id,
				'orderby'=>'product_items ASC'
		);
		$product_list = $this->distributorGoodsProductService()->getAllList($params);
		$this->assign('product_list', $product_list);
		
		//商品规格
		$goods_sku = M('goods_sku')->where(array('goods_id'=>$info['goods_id']))->order('sku_id ASC')->select();
		$list = array();
		foreach ($goods_sku as $v) {
			$list[$v['sku_name']][] = $v;
		}
		$sku_list = array();
		foreach ($list as $k => $v) {
			$sku_value = '';
			foreach ($v as $v2) {
				$sku_value .= $v2['sku_value'].',';
			}
			$sku_list[] = array(
					'sku_name'=>$k,
					'sku_value'=>trim($sku_value, ',')
			);
		}
		$this->assign('sku_list', $sku_list);
		
		$this->display();
	}
	
	private function editPublic($cat_id) {
		//自定义分类列表
		$categorys = $this->distributorGoodsCatService()->getOptionList($cat_id);
		$this->assign('categorys', $categorys);
		
		//分类树
		$map = array('distributor_id'=>$this->org_id);
		$cat_list = $this->distributorGoodsCatService()->getTreeList($map);
		$this->assign('cat_list', $cat_list);
		
		//商品服务
		$map = array(
				'distributor_id'=>array('in', array(0, $this->org_id))
		);
		$service_list = $this->goodsServiceService()->getAllList($map);
		$this->assign('service_list', $service_list);
		
		$map = array(
				'distributor_id'=>$this->org_id
		);
		$label_list = $this->goodsLabelService()->getAllList($map);
		$this->assign('label_list', $label_list);
	}
	
	public function delAction($record_id = 0){
		try {
			$this->distributorGoodsService()->delete($record_id);
			$this->success('删除成功');
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	public function addAction($goods_id){
		try {
			$this->distributorGoodsService()->copy($goods_id, $this->org_id);
			$this->success('添加成功');
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	public function addAllAction(){
		$goods_ids = I('goods_ids');
		try {
			$this->distributorGoodsService()->copys($goods_ids, $this->org_id);
			$this->success('添加成功', U('platform'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	//批量上架
	public function upAction(){
		$goods_ids = I('goods_ids');
		try {
			$this->distributorGoodsService()->copys($goods_ids, $this->org_id);
			$this->success('添加成功', U('platform'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	//批量下架
	public function downAction(){
		
	}
	
	public function delAllAction(){
		$record_ids = I('record_ids');
		try {
			$this->distributorGoodsService()->delAll($record_ids, $this->org_id);
			$this->success('删除成功', U('platform'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	public function seckillAction($record_id = 0) {
		$goods_info = $this->distributorGoodsService()->getInfo($record_id);
		if(empty($goods_info)){
			$this->error('数据不存在');
		}
		$this->assign('goods_info', $goods_info);
	
		//货品列表
		$params = array(
				'map'=>array('record_id'=>$record_id)
		);
		$products = $this->distributorGoodsProductService()->getAllList($params);
		$this->assign('product_list', $products['list']);
		
		if (IS_POST) {
			$post = I('post.');
			$post['distributor_id'] = $this->org_id;
			try {
				$this->distributorGoodsService()->seckill($post);
				$this->success('设置成功', U('index'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		
		$this->display();
	}
	
	public function productAction($goods_id = 0){
		$get = I('get.');
		$this->assign('get', $get);
		
		//商品信息
		$info = $this->GoodsService()->getInfo($goods_id);
		if (empty($info)) {
			$this->error('数据不存在');
		}
		$this->assign('info', $info);
		
		$params = array(
				'page'=>intval(I('p')) ? intval(I('p')) : 1,
				'pagesize'=>$this->pagesize,
				'map'=>array('goods_id'=>$goods_id),
		);
		$datas = $this->goodsProductService()->getPagerList($params);
		$this->assign('list', $datas['list']);
		$pager = new Pager($datas['count'], $this->pagesize);
		$this->assign('pager', $pager->show());
		
		//进货价折扣
		$discount = 100;
		$distribution = $this->distributorDistributionService()->getInfo($info['distributor_distribution_id']);
		if ($distribution && $this->distributor_info['rank_id'] > 0) {
			foreach ($distribution['distribution_info'] as $v) {
				if ($v['rank_id'] == $this->distributor_info['rank_id']) {
					$discount = $v['discount'];
					break;
				}
			}
		}
		$this->assign('discount', $discount);
	
		$this->display();
	}
	
	public function skuAction() {
		layout('Layout/sel');
		$get = I('get.');
	
		if (IS_POST) {
			$post = I('post.');
				
			$sku_value = array();
			foreach ($post['sku_value'] as $v) {
				$sku_value[] = explode(',', $v);
			}
				
			//规格值不能为空
			foreach ($sku_value as $v) {
				foreach ($v as $v2) {
					if (empty($v2)) {
						$this->error('规格值不能为空');
					}
				}
			}
				
			$product_list = $this->getArrSet($sku_value);
				
			$new_product_list = array();
			foreach ($product_list as $v) {
				$new_product_list[] = implode(',', $v);
			}
			$this->assign('product_list', $new_product_list);
				
			$_product_list = $this->renderPartial('_product_list');
			$data = array('sku_value'=>$sku_value, 'product_list'=>$_product_list);
			$this->ajaxReturn($data);
		}
	
		$this->assign('sku_value_id', $get['sku_value_id']);
		if ($get['val']) {
			$val_arr = explode(',', $get['val']);
			$this->assign('list', $val_arr);
		}
	
		$this->display();
	}
	
	public function getArrSetAction() {
	
	
		/*************TEST**************/
		$arr=array(
				array('红','绿','蓝'),
				array('X','L','M'),
				array('春','夏','秋','冬')
		);
	
		$_total_arr = $this->getArrSet($arr);
		_p($_total_arr);
	}
	
	/*
	 Author:GaZeon
	Date:2016-6-20
	Function:getArrSet
	Param:$arrs 二维数组
	getArrSet(array(array(),...))
	数组不重复排列集合
	*/
	private function getArrSet($arrs,$_current_index=-1)
	{
		//总数组
		static $_total_arr;
		//总数组下标计数
		static $_total_arr_index;
		//输入的数组长度
		static $_total_count;
		//临时拼凑数组
		static $_temp_arr;
	
		//进入输入数组的第一层，清空静态数组，并初始化输入数组长度
		if($_current_index<0)
		{
			$_total_arr=array();
			$_total_arr_index=0;
			$_temp_arr=array();
			$_total_count=count($arrs)-1;
			$this->getArrSet($arrs,0);
		}
		else
		{
			//循环第$_current_index层数组
			foreach($arrs[$_current_index] as $v)
			{
				//如果当前的循环的数组少于输入数组长度
				if($_current_index<$_total_count)
				{
					//将当前数组循环出的值放入临时数组
					$_temp_arr[$_current_index]=$v;
					//继续循环下一个数组
					$this->getArrSet($arrs,$_current_index+1);
	
				}
				//如果当前的循环的数组等于输入数组长度(这个数组就是最后的数组)
				else if($_current_index==$_total_count)
				{
					//将当前数组循环出的值放入临时数组
					$_temp_arr[$_current_index]=$v;
					//将临时数组加入总数组
					$_total_arr[$_total_arr_index]=$_temp_arr;
					//总数组下标计数+1
					$_total_arr_index++;
				}
	
			}
		}
	
		return $_total_arr;
	}
	
	private function distributorGoodsService() {
		return D('Distributor\Goods', 'Service');
	}
	
	private function distributorGoodsCatService() {
		return D('Distributor\GoodsCat', 'Service');
	}
	
	private function distributorGoodsProductService() {
		return D('Distributor\GoodsProduct', 'Service');
	}
	
	private function goodsCatService() {
		return D('GoodsCat', 'Service');
	}
	
	private function goodsBrandService() {
		return D('GoodsBrand', 'Service');
	}
	
	private function goodsService() {
		return D('Goods', 'Service');
	}
	
	private function goodsProductService() {
		return D('GoodsProduct', 'Service');
	}
	
	private function goodsServiceService() {
		return D('GoodsService', 'Service');
	}
	
	private function goodsLabelService() {
		return D('GoodsLabel', 'Service');
	}
	
	private function distributorDistributionService() {
		return D('Distributor\Distribution', 'Service');
	}
}