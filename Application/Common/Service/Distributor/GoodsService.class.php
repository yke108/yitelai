<?php
namespace Common\Service\Distributor;

use Common\Basic\Genre;
class GoodsService{
	public $discount;
	
	public function __construct(){
		
	}
	
	public function getInfo($id){
		if ($id < 1) return false;
		$info = $this->distributorGoodsDao()->getRecord($id);
		return $this->outputForInfo($info);
	}
	
	public function findInfo($map){
		if ($map['record_id'] < 1) return false;
		if ($map['a.distributor_id'] < 1) return false;
		$info = $this->distributorGoodsDao()->findRecord($map);
		return $this->outputForInfo($info);
	}
	
	public function findInfoGoods($map){
		if ($map['a.goods_id'] < 1) return false;
		if ($map['a.distributor_id'] < 1) return false;
		
		$info = $this->distributorGoodsDao()->findRecord($map);
		//return $this->outputForInfo($info);
		return $info;
	}
	
	public function viewCount($id){
		if ($id < 1) return false;
		return  $this->distributorGoodsDao()->where(array('record_id'=>$id))->setInc('view_count');
	}
	
	public function createOrModify($params){
		// 接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		//商品设置
		$data['is_hot'] = $params['is_hot'] ? 1 : 0;
		$data['is_new'] = $params['is_new'] ? 1 : 0;
		$data['is_recommend'] = $params['is_recommend'] ? 1 : 0;
		$data['is_privilege'] = $params['is_privilege'] ? $params['is_privilege'] : 0;
		//场景和标签
		$data['distributor_label'] = $params['distributor_label'] ? implode(',', $params['distributor_label']) : '';
		//商品服务
		$data['service_id'] = $params['service_id'] ? implode(',', $params['service_id']) : '';
		//总销量
		$data['total_sale_count'] = $data['sale_count'] + $data['default_sale_count'];
		
		M()->startTrans();
		
		if (!$this->distributorGoodsDao()->create($data)){
			 throw new \Exception($this->distributorGoodsDao()->getError());
		}
		if ($data['record_id'] > 0){
			$result = $this->distributorGoodsDao()->save();
			if ($result === false){
				M()->rollback();
				throw new \Exception('修改失败');
			}
			$record_id = $data['record_id'];
		} else {
			$record_id = $this->distributorGoodsDao()->add();
			if ($record_id < 1){
				M()->rollback();
				throw new \Exception('添加失败');
			}
		}
		
		//编辑货品
		$product_num = 0;
		foreach ($params['products'] as $k => $v) {
			$map = array('id'=>$k);
			//分销商货品
			$distributor_product = $this->distributorGoodsProductDao()->getRecord($k);
			//判断货品库存是否大于平台货品库存的25%
			$stock_number = floor($distributor_product['stock_number'] * 0.25);
			if ($v['stock_num'] > $stock_number) {
				M()->rollback();
				throw new \Exception('货品'.$distributor_product['product_name'].'的库存不能超过'.$stock_number);
			}
			//优惠价不能小于进货价
			if ($v['product_price'] < $distributor_product['stock_price']) {
				M()->rollback();
				throw new \Exception('货品'.$distributor_product['product_name'].'的优惠价不能小于进货价');
			}
			$result = $this->distributorGoodsProductDao()->where($map)->save($v);
			if ($result === false){
				M()->rollback();
				throw new \Exception('编辑货品失败');
			}
			$product_num += $stock_number;
		}
		
		$min_product = $this->distributorGoodsProductDao()->where(array('record_id'=>$record_id))->order('product_price ASC')->find();
		$max_product = $this->distributorGoodsProductDao()->where(array('record_id'=>$record_id))->order('product_price DESC')->find();
		$data = array(
				'product_num'=>$product_num,
				'min_product_price'=>$min_product['product_price'],
				'max_product_price'=>$max_product['product_price'],
		);
		$result = $this->distributorGoodsDao()->where(array('record_id'=>$record_id))->save($data);
		if ($result === false){
			M()->rollback();
			throw new \Exception('编辑货品失败');
		}
		
		M()->commit();
	}
	
	public function delete($id){
		M()->startTrans();
		
		//删除sku
		/* $res = $this->distributorGoodsSkuDao()->delRecords(array('record_id'=>$id));
		if ($res === false) {
			M()->rollback();
			throw new \Exception('删除sku失败');
		} */
		
		//删除货品
		$res = $this->distributorGoodsProductDao()->delRecords(array('record_id'=>$id));
		if ($res === false) {
			M()->rollback();
			throw new \Exception('删除货品失败');
		}
		
		//删除商品
		$result = $this->distributorGoodsDao()->delRecord($id);
		if ($result === false){
			M()->rollback();
			throw new \Exception('删除失败');
		}
		M()->commit();
		return true;
	}
	
	public function delall($record_ids, $distributor_id){
		M()->startTrans();
		
		//删除sku
		/* $res = $this->distributorGoodsSkuDao()->delRecords(array('record_id'=>$record_ids));
		if ($res === false) {
			M()->rollback();
			throw new \Exception('删除sku失败');
		} */
		
		//删除货品
		$map = array(
				'record_id'=>array('in',$record_ids)
		);
		$res = $this->distributorGoodsProductDao()->delRecords($map);
		if ($res === false) {
			M()->rollback();
			throw new \Exception('删除货品失败');
		}
		
		$map = array(
				'record_id'=>array('in',$record_ids),
				'distributor_id'=>$distributor_id
		);
		$result = $this->distributorGoodsDao()->delRecords($map);
		if ($result === false){
			M()->rollback();
			throw new \Exception('删除失败');
		}
		M()->commit();
		return true;
	}
	
	public function getPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = $params['map'] ? $params['map'] : array();
		//$map['c.status'] = 2;
		$map['c.is_delete'] = 0;
		if (!empty($params['keyword'])) {
			$map['goods_name'] = array('like', '%'.trim($params['keyword']).'%');
		}
		if (!empty($params['cat_id'])) {
			$clist = $this->goodsCatService()->getCatChilds($params['cat_id']);
			$map['a.cat_id'] = array('in', $clist);
		}
		if (!empty($params['self_cat_id'])) {
			$clist = $this->distributorGoodsCatService()->getCatChilds($params['self_cat_id']);
			$map['self_cat_id'] = array('in', $clist);
		}
		if (!empty($params['distributor_id'])) {
			$map['a.distributor_id'] = $params['distributor_id'];
		}
		if (!empty($params['label_id'])) {
			$map['distributor_label'] = array('like', '%'.$params['label_id'].'%');
		}
		if (!empty($params['scene_id'])) {
			$map['scene'] = array('like', '%'.$params['scene_id'].'%');
		}
		//商品设置
		if (!empty($params['is_new'])) {
			$map['a.is_new'] = $params['is_new'];
		}
		if (!empty($params['is_hot'])) {
			$map['a.is_hot'] = $params['is_hot'];
		}
		if (!empty($params['is_recommend'])) {
			$map['a.is_recommend'] = $params['is_recommend'];
		}
		if (!empty($params['is_privilege'])) {
			$map['a.is_privilege'] = $params['is_privilege'];
		}
		//筛选
		if (!empty($params['brand_id'])) {
			$map['brand_id'] = array('in', $params['brand_id']);
		}
		if (!empty($params['region_code'])) {
			$map['region_code'] = array('in', $params['region_code']);
		}
		if (!empty($params['goods_spec_value_id'])) {
			$map['spec_value_ids'] = array('like', trim($params['goods_spec_value_id'], ','));
		}
		if (isset($params['is_custom'])) {
			$map['b.is_custom'] = $params['is_custom'];
		}
		//_p($map);
		
		$count = $this->distributorGoodsDao()->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$field = 'b.*, a.*';
			if ($params['distance']) {
				$field .= ', '.$params['distance'].' AS distance';
				//$params['orderby'] = 'distance ASC';
			}
			if ($params['orderby'] != 'rand()') {
				$orderby = empty($params['orderby']) ? 'a.sort_order DESC, record_id DESC' : $params['orderby'];
			}
			$list = $this->distributorGoodsDao()->searchRecords($map, $orderby, $params['page'], $params['pagesize'], $field);
		}
		
		return array(
				'list'=>$this->outputForList($list),
				'count'=>$count,
		);
	}
	
	public function getAllList($distributor_id){
		$map = array('a.distributor_id'=>$distributor_id);
		//$map['c.status'] = 2;
		$map['c.is_delete'] = 0;
		return $this->distributorGoodsDao()->searchAllRecords($map);
	}
	
	public function goodsAllList($map){
		return $this->distributorGoodsDao()->searchAllRecords($map);
	}
	
	public function goodsList($params){
		if ($params['pagesize'] < 1) $params['pagesize'] = 4;
		$map = array();
		$params['map'] > 0 && $map = $params['map'];
		$l = $this->distributorGoodsDao()->searchRecords($map, '', 1, $params['pagesize']);
		return $this->outputForList($l);
	}
	
	private function outputForList($list) {
		if (!empty($list)) {
			//获取商品分类和品牌
			foreach ($list as $v) {
				$cat_ids[] = $v['cat_id'];
				if ($v['self_cat_id']) {
					$self_cat_ids[] = $v['self_cat_id'];
				}
				if ($v['brand_id']) {
					$brand_ids[] = $v['brand_id'];
				}
			}
			$map = array(
					'cat_id'=>array('in', $cat_ids)
			);
			$goods_cats = $this->goodsCatDao()->searchRecordsField($map);
			$map = array(
					'brand_id'=>array('in', $brand_ids)
			);
			$goods_brands = $this->goodsBrandDao()->searchRecordsField($map);
			//获取商品自定义分组
			$map = array(
					'self_cat_id'=>array('in', $self_cat_ids)
			);
			$self_goods_cats = $this->distributorGoodsCatDao()->searchRecordsField($map);
			foreach ($list as $k => $v) {
				$list[$k]['cat_name'] = $goods_cats[$v['cat_id']];
				$list[$k]['brand'] = $goods_brands[$v['brand_id']];
				$list[$k]['self_cat_name'] = $self_goods_cats[$v['self_cat_id']];
			
				//商品相册
				if ($v['goods_gallery']) {
					$list[$k]['goods_gallery'] = explode('#', trim($v['goods_gallery'],'#'));
				}
					
				//货品列表
				/* $map = array('record_id'=>$v['record_id']);
				 $product_list = $this->distributorGoodsProductDao()->searchAllRecords($map);
				$list[$k]['product_list'] = $product_list; */
					
				//默认货品
				$product = $this->getDefaultProduct($v['record_id']);
				$list[$k]['product'] = $product;
					
				//分销商
				$distributor = $this->distributorInfoDao()->getRecord($v['distributor_id']);
				$distributor['region'] = $this->regionDao()->getRegionNameDistrict($distributor['region_code']);
				$list[$k]['distributor'] = $distributor;
					
				//是否秒杀商品
				if ( $v['seckill_start'] <= NOW_TIME && $v['seckill_end'] >= NOW_TIME && $v['seckill_status'] == 1 && $v['total_seckill_num'] > 0 ) {
					$list[$k]['is_seckill'] = 1;
				}else {
					$list[$k]['is_seckill'] = 0;
				}
				
				//处理距离
				if ($v['distance'] < 1) {
					$list[$k]['distance'] = round($v['distance'] / 1000, 1).'米';
				}else {
					$list[$k]['distance'] = round($v['distance'], 1).'公里';
				}
			}
		}
		
		return $list;
	}
	
	private function outputForInfo($info) {
		if (empty($info)) {
			return $info;
		}
		
		//获取商品分类和品牌
		$goods_cat = $this->goodsCatDao()->getRecord($info['cat_id']);
		$goods_brand = $this->goodsBrandDao()->getRecord($info['brand_id']);
		$info['cat_name'] = $goods_cat['cat_name'];
		$info['brand_name'] = $goods_brand['brand_name'];
		
		//商品相册
		if ($info['goods_gallery']) {
			$info['goods_gallery'] = explode('#', trim($info['goods_gallery'],'#'));
		}
		
		//默认货品
		$info['product'] = $this->getDefaultProduct($info['record_id']);
		
		//货品列表
		/* $map = array('record_id'=>$info['record_id']);
		$product_list = $this->distributorGoodsProductDao()->searchAllRecords($map);
		$new_product_list = array();
		if (!empty($product_list)) {
			foreach ($product_list as $v) {
				$v['is_default'] = $v['id'] == $info['product']['id'] ? 1 : 0;
				$new_product_list[$v['id']] = $v;
			}
		}
		$info['product_list'] = $new_product_list; */
		
		//商品场景和标签
		if ($info['scene']) {
			$info['scene_ids'] = explode(',', $info['scene']);
			$map = array(
					'scene_id'=>array('in', $info['scene'])
			);
			$scene_list = $this->goodsSceneDao()->searchAllRecords($map);
			$info['scene_list'] = $scene_list;
		}
		if ($info['label']) {
			$info['label_ids'] = explode(',', $info['label']);
			$map = array(
					'label_id'=>array('in', $info['label'])
			);
			$label_list = $this->goodsLabelDao()->searchAllRecords($map);
			$info['label_list'] = $label_list;
		}
		
		//自定义标签
		if ($info['distributor_label']) {
			$info['distributor_label_ids'] = explode(',', $info['distributor_label']);
			$map = array(
					'label_id'=>array('in', $info['distributor_label'])
			);
			$label_list = $this->goodsLabelDao()->searchAllRecords($map);
			$info['distributor_label_list'] = $label_list;
		}
		
		//商品属性
		$info['spec_list'] = M('goods_spec_values')->alias('a')->field('a.*, b.spec_name')
							->join('LEFT JOIN __GOODS_SPEC__ b ON b.spec_id=a.spec_id')
							->where(array('goods_id'=>$info['goods_id']))->select();
		
		//商品服务
		$info['service_id_arr'] = $info['service_id'] ? explode(',', $info['service_id']) : '';
		
		//服务承诺
		$info['service_promise_arr'] = $info['service_promise'] ? explode(',', $info['service_promise']) : '';
		
		//是否秒杀商品
		if ( $info['seckill_start'] <= NOW_TIME && $info['seckill_end'] >= NOW_TIME && $info['seckill_status'] == 1 && $info['total_seckill_num'] > 0 ) {
			$info['is_seckill'] = 1;
		}else {
			$info['is_seckill'] = 0;
		}
		
		return $info;
	}
	
	private function getDefaultProduct($record_id){
		if ($record_id < 1) return false;
		$map = array('record_id'=>$record_id);
		$orderBy = 'a.is_seckill DESC, a.product_price ASC';
		return $this->distributorGoodsProductDao()->searchRecord($map, $orderBy);
	}
	
	//复制商品
	public function copy($goods_id, $distributor_id){
		M()->startTrans();
		
		$map['goods_id'] = $goods_id;
		//判断是否有复制权限
		$distributor = $this->distributorInfoDao()->getRecord($distributor_id);
		if ($distributor['brand_ids']) {
			$map['brand_id'] = array('in', $distributor['brand_ids']);
		}
		$goods_info = $this->goodsDao()->findRecord($map);
		if (empty($goods_info)) throw new \Exception('商品不存在');
		
		$data = array(
				'distributor_id'=>$distributor_id,
				'goods_id'=>$goods_info['goods_id'],
				'lng'=>$distributor['longitude'],
				'lat'=>$distributor['latitude'],
				'add_time'=>NOW_TIME,
				'update_time'=>NOW_TIME,
				'cat_id'=>$goods_info['cat_id'],
				//'product_num'=>$goods_info['product_num'],
				'service_id'=>$goods_info['service_id'],
				'min_product_price'=>$goods_info['min_product_price'],
				'max_product_price'=>$goods_info['max_product_price'],
		);
		$record_id = $this->distributorGoodsDao()->add($data);
		if (!$record_id) {
			M()->rollback();
			throw new \Exception($this->distributorGoodsDao()->getError());
		}
		
		//复制sku
		/* $res = $this->copySku($record_id, $goods_info['goods_id']);
		if (!$res) {
			M()->rollback();
			throw new \Exception('复制sku失败');
		} */
		
		//进货价折扣
		$discount = 100;
		$distribution = $this->distributorDistributionDao()->getRecord($goods_info['distributor_distribution_id']);
		$distributor = $this->distributorInfoDao()->getRecord($distributor_id);
		if ($distribution && $distributor['rank_id'] > 0) {
			$distribution_info = unserialize($distribution['distribution_info']);
			foreach ($distribution_info as $v) {
				if ($v['rank_id'] == $distributor['rank_id']) {
					$discount = $v['discount'];
					break;
				}
			}
		}
		
		//复制货品
		$res = $this->copyProduct($record_id, $goods_info['goods_id'], $discount);
		if (!$res) {
			M()->rollback();
			throw new \Exception('复制货品失败');
		}
		
		M()->commit();
		
		return true;
	}
	
	//复制货品
	private function copyProduct($record_id, $goods_id, $discount){
		$map = array('goods_id'=>$goods_id);
		$product_list = $this->goodsProductDao()->searchAllRecords($map);
		if (empty($product_list)) {
			throw new \Exception('货品不存在');
		}
		
		foreach ($product_list as $k => $v) {
			//查找新的sku_id
			/* $sku = $this->goodsSkuDao()->alias('a')->field('b.*')
					->join('LEFT JOIN __DISTRIBUTOR_GOODS_SKU__ b ON b.sku_value=a.sku_value')
					->where(array('a.sku_id'=>array('in',$v['product_items'])))->select();
			$product_items = '';
			foreach ($sku as $v2) {
				$product_items .= $v2['sku_id'].',';
			}
			$product_items = trim($product_items, ','); */
			
			$dataList[] = array(
					'record_id'=>$record_id,
					//'product_items'=>$product_items,
					'product_id'=>$v['product_id'],
					'product_price'=>$v['product_price'],
					//'market_price'=>$v['market_price'],
					'add_time'=>NOW_TIME,
					'update_time'=>NOW_TIME,
					//'stock_num'=>$v['stock_num'],
					//'notify_num'=>$v['notify_num'],
					'platform_stock_price'=>$v['stock_price'],
					'stock_price'=>$v['stock_price'] * $discount / 100,
					'discount'=>$discount
			);
		}
		$res = $this->distributorGoodsProductDao()->addAll($dataList);
		if (!$res) {
			return false;
		}
		return true;
	}
	
	//复制sku
	private function copySku($record_id, $goods_id){
		$map = array('goods_id'=>$goods_id);
		$sku_list = $this->goodsSkuDao()->selectAllRecords($map);
		if (empty($sku_list)) {
			throw new \Exception('sku不存在');
		}
		
		foreach ($sku_list as $k => $v) {
			$dataList[] = array(
					'record_id'=>$record_id,
					'sku_name'=>$v['sku_name'],
					'sku_value'=>$v['sku_value']
			);
		}
		$res = $this->distributorGoodsSkuDao()->addAll($dataList);
		if (!$res) {
			return false;
		}
		return true;
	}
	
	public function copys($goods_ids, $distributor_id){
		$map['goods_id'] = array('in', explode(',', $goods_ids));
		$goods_list = $this->goodsDao()->searchAllRecords($map);
		
		$distributor = $this->distributorInfoDao()->getRecord($distributor_id);
		
		M()->startTrans();
		
		foreach ($goods_list as $k => $v) {
			$data = array(
					'distributor_id'=>$distributor_id,
					'goods_id'=>$v['goods_id'],
					'lng'=>$distributor['longitude'],
					'lat'=>$distributor['latitude'],
					'add_time'=>NOW_TIME,
					'update_time'=>NOW_TIME,
					'cat_id'=>$v['cat_id'],
					'product_num'=>$v['product_num'],
					'min_product_price'=>$v['min_product_price'],
					'max_product_price'=>$v['max_product_price'],
			);
			$record_id = $this->distributorGoodsDao()->add($data);
			if (!$record_id) {
				M()->rollback();
				throw new \Exception($this->distributorGoodsDao()->getError());
			}
			
			//判断是否有复制权限
			$map['goods_id'] = $v['goods_id'];
			if ($distributor['brand_ids']) {
				$map['brand_id'] = array('in', $distributor['brand_ids']);
			}
			$goods_info = $this->goodsDao()->findRecord($map);
			if (empty($goods_info)) throw new \Exception('商品不存在');
			
			//进货价折扣
			$discount = 100;
			$distribution = $this->distributorDistributionDao()->getRecord($goods_info['distributor_distribution_id']);
			if ($distribution && $distributor['rank_id'] > 0) {
				$distribution_info = unserialize($distribution['distribution_info']);
				foreach ($distribution_info as $v) {
					if ($v['rank_id'] == $distributor['rank_id']) {
						$discount = $v['discount'];
						break;
					}
				}
			}
			
			$res = $this->copyProduct($record_id, $v['goods_id'], $discount);
			if (!$res) {
				M()->rollback();
				throw new \Exception('复制货品失败');
			}
			
			/* $res = $this->copySku($record_id, $v['goods_id']);
			if (!$res) {
				M()->rollback();
				throw new \Exception('复制sku失败');
			} */
		}
		
		M()->commit();
		
		return true;
	}
	
	public function seckill($params){
		M()->startTrans();
		//秒杀时间
		$rules = array(
				array('seckill_start','require','开始时间不能为空'),
				array('seckill_end','require','结束时间不能为空'),
				//array('seckill_end','checkSeckillEnd','结束时间不能小于当前时间',0,'function'),
		);
		$data = array(
				'seckill_start'=>strtotime($params['seckill_start']),
				'seckill_end'=>strtotime($params['seckill_end']),
				'record_id'=>$params['record_id'],
				'distributor_id'=>$params['distributor_id']
		);
		if (!$this->distributorGoodsDao()->validate($rules)->create($data)){
			throw new \Exception($this->distributorGoodsDao()->getError());
		}
		if ($data['seckill_end'] <= NOW_TIME) {
			throw new \Exception('结束时间不能小于当前时间');
		}
		$result = $this->distributorGoodsDao()->save();
		if ($result === false){
			M()->rollback();
			throw new \Exception('秒杀时间设置失败');
		}
		
		//秒杀价格
		$total_price = 0;
		foreach ($params['seckill_price'] as $price) {
			$total_price += $price;
		}
		if ($total_price == 0) {
			throw new \Exception('至少设置一个货品的秒杀价格大于零');
		}
		$map = array('record_id'=>$params['record_id']);
		$product_list = $this->distributorGoodsProductDao()->searchAllRecords($map);
		foreach ($product_list as $product) {
			$data = array(
					'seckill_price'=>$params['seckill_price'][$product['id']],
					'id'=>$product['id']
			);
			if (!$this->distributorGoodsProductDao()->create($data)){
				throw new \Exception($this->distributorGoodsProductDao()->getError());
			}
			$result = $this->distributorGoodsProductDao()->save();
			if ($result === false){
				M()->rollback();
				throw new \Exception('秒杀价格设置失败');
			}
		}
		M()->commit();
		return true;
	}
	
	private function checkSeckillEnd($seckill_end) {
		$seckill_end = strtotime($seckill_end);
		if ($seckill_end <= NOW_TIME) {
			return false;
		}else {
			return true;
		}
	}
	
	//获取活动的属性列表
	public function getGoodsProductList($map){
		$d_product=$this->distributorGoodsProductDao()->searchRecords($map);
		return $d_product;
	}
	
	public function share($params){
		if (empty($params['user_id']) || empty($params['record_id'])) throw new \Exception('缺少参数');
		
		$info = $this->getInfo($params['record_id']);
		if (empty($info)) throw new \Exception('商品不存在');
		
		M()->startTrans();
		
		//分享记录
		$data = array(
				'user_id'=>$params['user_id'],
				'id_value'=>$info['record_id'],
				'collect_type'=>Genre::CollectTypeShareGoods,
				'system'=>$params['system'],
				'browser'=>$params['browser'],
				'version'=>$params['version'],
				'add_time'=>NOW_TIME,
		);
		$result = $this->collectDao()->addRecord($data);
		if ($result === false) {
			M()->rollback();
			throw new \Exception('分享失败');
		}
		
		//分享统计
		$result = $this->distributorGoodsDao()->where(array('record_id'=>$info['record_id']))->setInc('share_count');
		if ($result === false) {
			M()->rollback();
			throw new \Exception('分享失败');
		}
		
		M()->commit();
	}
	
	private function distributorGoodsDao() {
		return D('Common/Distributor/Goods');
	}
	
	private function goodsDao() {
		return D('Common/Goods/Goods');
	}
	
	private function goodsProductDao() {
		return D('Common/Goods/GoodsProduct');
	}
	
	private function goodsCatDao() {
		return D('Common/Goods/GoodsCat');
	}
	
	private function goodsBrandDao() {
		return D('Common/Goods/GoodsBrand');
	}

	private function distributorGoodsCatDao() {
		return D('Common/Distributor/GoodsCat');
	}
	
	private function distributorGoodsProductDao() {
		return D('Common/Distributor/GoodsProduct');
	}
	
	private function goodsCatService() {
		return D('GoodsCat', 'Service');
	}
	
	private function distributorGoodsCatService() {
		return D('Distributor\GoodsCat', 'Service');
	}
	
	private function distributorInfoDao(){
		return D('Common/Distributor/Info');
	}
	
	private function goodsSceneDao() {
		return D('Common/Goods/GoodsScene');
	}
	
	private function goodsLabelDao() {
		return D('Common/Goods/GoodsLabel');
	}
	
	private function goodsSpecDao() {
		return D('Common/Goods/GoodsSpec');
	}
	
	private function regionDao(){
		return D('Region');
	}
	
	private function collectDao(){
		return D('Collect');
	}
	
	private function goodsSkuDao(){
		return D('Common/Goods/GoodsSku');
	}
	
	private function distributorGoodsSkuDao(){
		return D('Common/Distributor/GoodsSku');
	}
	
	private function distributorRankDao(){
		return D('Common/Distributor/DistributorRank');
	}
	
	private function distributorDistributionDao(){
		return D('Common/Distributor/Distribution');
	}
}//end HelpService!甜品