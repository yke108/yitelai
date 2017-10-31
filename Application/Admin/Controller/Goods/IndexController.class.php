<?php
namespace Admin\Controller\Goods;
use Admin\Controller\FController;
use Common\Basic\Pager;

class IndexController extends FController {
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'goods',
			'ac'=>'goods_index_index',
		);
		$this->sbset($set);
    }
    
    //修正商品表货品数量和价格
    public function testAction(){
    	$goods_list = M('goods')->select();
    	foreach ($goods_list as $goods) {
    		$product_num = M('goods_product')->where(array('goods_id'=>$goods['goods_id']))->sum('stock_number');
    		$min_product = M('goods_product')->where(array('goods_id'=>$goods['goods_id']))->order('product_price ASC')->find();
    		$max_product = M('goods_product')->where(array('goods_id'=>$goods['goods_id']))->order('product_price DESC')->find();
    		$data = array(
    				'product_num'=>$product_num,
    				'min_product_price'=>$min_product['product_price'] ? $min_product['product_price'] : 0,
    				'max_product_price'=>$max_product['product_price'] ? $max_product['product_price'] : 0,
    		);
    		$res = M('goods')->where(array('goods_id'=>$goods['goods_id']))->save($data);
    		_p($res, false);
    	}
    }
    
    public function indexAction(){
    	session('back_url', __SELF__);
    	
		$this->listDisplay();
    }
	
    public function singleselAction(){
    	layout('Layout/sel');
    	$this->listDisplay();
    }
    
	private function listDisplay($map = array()){
		$get = I('get.');
		$this->assign('get', $get);
		
		//分类列表
		$categorys = $this->GoodsCatService()->getOptionList($get['cat_id']);
		$this->assign('categorys', $categorys);
		
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
		if ($get['is_on_sale'] != '') {
			$params['is_on_sale'] = $get['is_on_sale'];
		}
		if ($get['is_self_sale'] != '') {
			$params['is_self_sale'] = $get['is_self_sale'];
		}
		if ($get['is_custom'] != '') {
			$params['is_custom'] = $get['is_custom'];
		}
		
		$datas = $this->goodsService()->getPagerList($params);
		foreach($datas['list'] as $key=>$val){
			$url = U("mall/goods/preview",array("id"=>$val[goods_id]));
			$url = str_replace("/gajadmin", "", $url);
			$datas['list'][$key]['url'] = $url;
		}
		$this->assign('list', $datas['list']);
		$pager = new Pager($datas['count'], $this->pagesize);
		$this->assign('pager', $pager->show());
		
		$this->display('index');
	}
	
	public function addAction(){
		if(IS_POST){			
			$post = I('post.');
			try {
				$this->goodsService()->createOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功', U('index'));
		}
		
		$this->editPublic();
		
		$this->display('edit');
	}
	
	public function editAction($goods_id = 0){
		$info = $this->goodsService()->getInfo($goods_id);
		if(empty($info)) $this->error('商品不存在');
		$this->assign('info', $info);
		
		if(IS_POST){
			$post = I('post.');
			try {
				$this->goodsService()->createOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('编辑成功', session('back_url'));
		}
		
		$this->editPublic($info['cat_id']);
		
		//获取分类的场景和标签
		$cat = $this->GoodsCatService()->getInfo($info['cat_id']);
		$map = array(
				'scene_id'=>array('in', $cat['scene_ids'])
		);
		$scene_list = $this->goodsSceneService()->getAllList($map);
		$this->assign('scene_list', $scene_list);
		
		$map = array(
				'label_id'=>array('in', $cat['label_ids'])
		);
		$label_list = $this->goodsLabelService()->getAllList($map);
		$this->assign('label_list', $label_list);
		
		//商品规格
		$goods_sku = M('goods_sku')->where(array('goods_id'=>$goods_id))->order('sku_id ASC')->select();
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
					'sku_id'=>$v2['sku_id'],
					'sku_name'=>$k,
					'sku_value'=>trim($sku_value, ',')
			);
		}
		$this->assign('sku_list', $sku_list);
		
		//商品属性
		$spec_value_list = array();
		foreach ($info['spec_list'] as $v) {
			$spec_value_list[$v['spec_id']] = $v;
		}
		if ($cat['type_id']) {
			$params = array(
					'type_id'=>$cat['type_id']
			);
			$datas = $this->goodsSpecService()->getPagerList($params);
			$spec_list = array();
			foreach ($datas['list'] as $v) {
				$spec_value = $spec_value_list[$v['spec_id']]['spec_value'];
				if ($v['spec_type'] == 1) {
					$spec_values_tmp = explode("\n", $v['spec_values']);
					$spec_values = array();
					foreach ($spec_values_tmp as $v2) {
						$is_selected = 0;
						if (trim($v2) == $spec_value) {
							$is_selected = 1;
						}
						$spec_values[] = array(
								'spec_value'=>trim($v2),
								'is_selected'=>$is_selected
						);
					}
					$v['spec_values'] = $spec_values;
				}else {
					$v['spec_value'] = $spec_value;
				}
				$spec_list[] = $v;
			}
			$this->assign('spec_list', $spec_list);
		}
		
		//货品列表
		$params = array(
				'goods_id'=>$goods_id,
				'orderby'=>'product_items ASC'
		);
		$datas = $this->goodsProductService()->getAllList($params);
		$product_list = array();
		$str = '';
		foreach ($datas['list'] as $v) {
			$product_items = explode(',', $v['product_items']);
			if ($str != $product_items[0]) {
				$v['is_image'] = 1;
			}else {
				$v['is_image'] = 0;
			}
			$product_list[] = $v;
			$str = $product_items;
		}
		$this->assign('product_list', $product_list);
		
		$this->display();
	}
	
	public function delAction($goods_id = 0){
		try {
			$this->goodsService()->delete($goods_id);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功');
	}
	
	//批量删除
	public function delallAction(){
		$goods_ids = explode(',', I('goods_ids'));
		try {
			$this->goodsService()->delall($goods_ids);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('操作成功');
	}
	
	//批量上架
	public function upAction(){
		$goods_ids = explode(',', I('goods_ids'));
		try {
			$this->goodsService()->up($goods_ids);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('操作成功', session('back_url'));
	}
	
	//批量下架
	public function downAction(){
		$goods_ids = explode(',', I('goods_ids'));
		try {
			$this->goodsService()->down($goods_ids);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('操作成功', session('back_url'));
	}
	
	private function editPublic($cat_id) {
		//分类列表
		$categorys = $this->GoodsCatService()->getOptionList($cat_id);
		$this->assign('categorys', $categorys);
		
		//品牌列表
		$brand_list = $this->goodsBrandService()->getAllList();
		$this->assign('brand_list', $brand_list);
		
		//全部分类
		$all_cats = $this->GoodsCatService()->selectAllList();
		foreach ($all_cats as $v) {
			$cat_type_list[$v['cat_id']] = $v['type_id'];
		}
		$this->assign('cat_type_list', $cat_type_list);
		
		//全部场景和标签
		$scene_list = $this->goodsSceneService()->getAllList();
		$label_list = $this->goodsLabelService()->getAllList();
		$all_scene_list = $all_label_list = array();
		foreach ($all_cats as $cat) {
			foreach ($scene_list as $k => $v) {
				$scene = explode(',', $cat['scene']);
				if (in_array($k, $scene)) {
					$all_scene_list[$cat['cat_id']][] = array(
							'scene_id'=>$k,
							'name'=>$v
					);
				}
			}
			foreach ($label_list as $k => $v) {
				$label = explode(',', $cat['label']);
				if (in_array($k, $label)) {
					$all_label_list[$cat['cat_id']][] = array(
							'label_id'=>$k,
							'name'=>$v
					);
				}
			}
		}
		$this->assign('all_scene_list', $all_scene_list);
		$this->assign('all_label_list', $all_label_list);
		
		//商品类型
		$all_type_list = $this->goodsTypeService()->getOptionList();
		foreach ($all_type_list as $k => $v) {
			if (is_array($v)) {
				foreach ($v as $k2 => $v2) {
					if ($v2['spec_type'] == 1) {
						$spec_values = explode("\n", $v2['spec_values']);
						$options = '';
						foreach ($spec_values as $v3) {
							$options .= '<option value="'.$v3.'">'.$v3.'</option>';
						}
						$all_type_list[$k][$k2]['spec_values'] = $options;
					}
				}
			}
		}
		$this->assign('all_type_list', $all_type_list);
		
		//商品服务
		$map = array('distributor_id'=>0);
		$service_list = $this->goodsServiceService()->getAllList($map);
		$this->assign('service_list', $service_list);
		
		//分成方案
		$distribution_list = $this->goodsDistributionService()->getAllList();
		$this->assign('distribution_list', $distribution_list);
		
		//进货价方案
		$distributor_distribution_list = $this->distributorDistributionService()->getAllList();
		$this->assign('distributor_distribution_list', $distributor_distribution_list);
	}
	
	public function skuAction() {
		layout('Layout/sel');
		$get = I('get.');
		
		if (IS_POST) {
			$post = I('post.');
			
			//规格组合
			$sku_value_arr = array();
			foreach ($post['sku_value'] as $k => $v) {
				if (!empty($v)) {
					$v_arr = explode(',', $v);
					$arr = array();
					foreach ($v_arr as $v2) {
						$arr[] = trim($v2);
					}
					
					//规格值不能重名
					if (count($arr) != count(array_unique($arr))) {
						M()->rollback();
						throw new \Exception('规格值不能重名');
					}
					
					$sku_value_arr[] = $arr;
					
					//更新sku_value
					if ($post['sku_id'][$k]) {
						$sku = M('goods_sku')->where(array('sku_id'=>$post['sku_id'][$k]))->find();
						$sku_list = M('goods_sku')->where(array('goods_id'=>$sku['goods_id'], 'sku_name'=>$sku['sku_name']))->select();
						foreach ($sku_list as $k2 => $v2) {
							$result = M('goods_sku')->where(array('sku_id'=>$v2['sku_id']))->save(array('sku_value'=>$arr[$k2]));
							if ($result === false) {
								$this->error('编辑sku失败');
							}
						}
					}
				}
			}
			$product_list = $this->getArrSet($sku_value_arr);
			
			//货品组合
			$new_product_list = array();
			$haystack = '';
			foreach ($product_list as $v) {
				$sku_value = implode(',', $v);
				
				//货品是否存在
				$product = array();
				if ($post['goodsid']) {
					$map = array('sku_value'=>array('in', $sku_value), 'goods_id'=>$post['goodsid']);
					$skus = M('goods_sku')->field('sku_id, sku_value')->where($map)->order('sort ASC')->select();
					if ($skus) {
						$sku_ids = array();
						$product_items_name = '';
						foreach ($skus as $v2) {
							$sku_ids[] = $v2['sku_id'];
							$product_items_name .= $v2['sku_value'].',';
						}
						$product_items_name = trim($product_items_name, ',');
						$product_items = implode(',', $sku_ids);
						$product = M('goods_product')->where(array('product_items'=>$product_items, 'goods_id'=>$post['goodsid']))->find();
					}
				}
				
				//判断是否有货品图片
				$is_sku_image = 0;
				if ($v[0] != $haystack) {
					$is_sku_image = 1;
				}
				$haystack = $v[0];
				
				if ($product) {
					$product['is_image'] = $is_sku_image;
					$product['product_items_name'] = $product_items_name;
				}else {
					$product = array(
							'sku_value'=>$sku_value,
							'is_sku_image'=>$is_sku_image
					);
				}
				$new_product_list[] = $product;
			}
			$this->assign('product_list', $new_product_list);
			
			$_product_list = $this->renderPartial('_product_list');
			$data = array('product_list'=>$_product_list);
			$this->ajaxReturn($data);
		}
		
		$this->assign('get', $get);
		$this->assign('sku_value_id', $get['sku_value_id']);
		
		if ($get['val']) {
			$get_vals = explode(',', $get['val']);
			$list = array();
			foreach ($get_vals as $v) {
				$list[] = trim($v);
			}
			$this->assign('list', $list);
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
	
	private function goodsService() {
		return D('Goods', 'Service');
	}
	
	private function GoodsCatService() {
		return D('GoodsCat', 'Service');
	}
	
	private function goodsBrandService() {
		return D('GoodsBrand', 'Service');
	}
	
	private function goodsProductService() {
		return D('GoodsProduct', 'Service');
	}
	
	private function goodsSceneService() {
		return D('GoodsScene', 'Service');
	}
	
	private function goodsLabelService() {
		return D('GoodsLabel', 'Service');
	}
	
	private function goodsTypeService() {
		return D('GoodsType', 'Service');
	}
	
	private function goodsSpecService() {
		return D('GoodsSpec', 'Service');
	}
	
	private function goodsServiceService() {
		return D('GoodsService', 'Service');
	}
	
	private function goodsDistributionService() {
		return D('GoodsDistribution', 'Service');
	}
	
	private function distributorGoodsService() {
		return D('Distributor\Goods', 'Service');
	}
	
	private function distributorDistributionService() {
		return D('Distributor\Distribution', 'Service');
	}
}