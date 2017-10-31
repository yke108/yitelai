<?php
namespace Common\Service;

class GoodsService{
	protected $GoodsDao;
	
	public function __construct(){
		$this->GoodsDao = D('Common/Goods/Goods');
	}
	
	public function getInfo($id){
		if ($id < 1) return false;
		$info = $this->GoodsDao->getRecord($id);
		return $this->outputForInfo($info);
	}
	
	public function viewCount($id){
		if ($id < 1) return false;
		return  $this->GoodsDao->where(array('goods_id'=>$id))->setInc('view_count');
	}
	
	public function createOrModify($params){
		// 接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		$data['delivery_time'] = intval($data['delivery_time']);
		$data['repair_time'] = intval($data['repair_time']);
		//商品相册
		$data['goods_gallery'] = trim($data['goods_gallery'], '#');
		//商品设置
		$data['is_on_sale'] = $params['is_on_sale'] ? $params['is_on_sale'] : 0;
		$data['is_self_sale'] = $params['is_self_sale'] ? $params['is_self_sale'] : 0;
		$data['is_custom'] = $params['is_custom'] ? $params['is_custom'] : 0;
		if ($data['is_custom'] == 0) {
			$data['min_price'] = 0;
			$data['max_price'] = 0;
			$data['pay_type'] = 0;
		}
		//场景和标签
		$data['scene'] = $params['scene'] ? implode(',', $params['scene']) : '';
		$data['label'] = $params['label'] ? implode(',', $params['label']) : '';
		//服务承诺
		$data['service_promise'] = $params['service_promise'] ? implode(',', $params['service_promise']) : '';
		
		M()->startTrans();
		
		if ($data['goods_id'] > 0){
			$info = $this->GoodsDao->getRecord($data['goods_id']);
			if (empty($info)) throw new \Exception('修改失败');
			
			//如果分类改变了，就删除原来的属性值
			if ($info['cat_id'] != $data['cat_id']) {
				$map = array(
						'goods_id'=>$data['goods_id'],
				);
				$result = M('goods_spec_values')->where($map)->delete();
				if ($result === false){
					M()->rollback();
					throw new \Exception('修改失败');
				}
			}
			
			if (!$this->GoodsDao->create($data)){
				throw new \Exception($this->GoodsDao->getError());
			}
			
			$result = $this->GoodsDao->save();
			if ($result === false){
				M()->rollback();
				throw new \Exception('修改失败');
			}
			
			$goods_id = $params['goods_id'];
		} else {
			if (!$this->GoodsDao->create($data)){
				throw new \Exception($this->GoodsDao->getError());
			}
			
			$goods_id = $this->GoodsDao->add();
			if ($goods_id < 1){
				M()->rollback();
				throw new \Exception('添加失败');
			}
		}
		
		//处理商品属性
		$spec_value_ids = array();
		foreach ($params['goods_spec'] as $k => $v) {
			$map = array(
					'goods_id'=>$goods_id,
					'spec_id'=>$k,
			);
			$goods_spec = M('goods_spec_values')->where($map)->find();
			if ($goods_spec) {
				$result = M('goods_spec_values')->where($map)->save(array('spec_value'=>$v));
				if ($result === false){
					M()->rollback();
					throw new \Exception('修改失败');
				}
				$spec_value_ids[] = $goods_spec['goods_spec_value_id'];
			}else {
				$data = array(
						'goods_id'=>$goods_id,
						'spec_id'=>$k,
						'spec_value'=>trim($v)
				);
				$spec_value_id = M('goods_spec_values')->add($data);
				if ($spec_value_id === false){
					M()->rollback();
					throw new \Exception('修改失败');
				}
				$spec_value_ids[] = $spec_value_id;
			}
		}
		if ($spec_value_ids) {
			$data = array(
					'spec_value_ids'=>implode(',', $spec_value_ids)
			);
			$result = $this->GoodsDao->where(array('goods_id'=>$goods_id))->save($data);
			if ($result === false){
				M()->rollback();
				throw new \Exception('修改失败');
			}
		}
		
		//检查规格项
		foreach ($params['sku_name'] as $v) {
			if (empty($v)) {
				M()->rollback();
				throw new \Exception('规格项不能为空');
			}
		}
		
		if (count($params['sku_name']) != count(array_unique($params['sku_name']))) {
			M()->rollback();
			throw new \Exception('规格项不能重名');
		}
		
		//编辑sku
		if ($params['skus']) {
			foreach ($params['skus'] as $k => $v) {
				//更新sku_name
				$map = array('sku_id'=>$k);
				$sku = M('goods_sku')->where($map)->find();
				$map = array('sku_name'=>$sku['sku_name']);
				$data = array('sku_name'=>$v['sku_name']);
				$result = $this->goodsSkuDao()->where($map)->save($data);
				if ($result === false){
					M()->rollback();
					throw new \Exception('编辑sku失败');
				}
				
				//sku_value是否存在，存在就编辑，不存在就添加
				$map = array('goods_id'=>$goods_id, 'sku_name'=>$v['sku_name']);
				$sku_list = M('goods_sku')->where($map)->select();
				$arr = explode(',', $v['sku_value']);
				$sku_value_arr = array();
				foreach ($arr as $v2) {
					$sku_value_arr[] = trim($v2);
				}
				
				//规格值不能重名
				if (count($sku_value_arr) != count(array_unique($sku_value_arr))) {
					M()->rollback();
					throw new \Exception('规格值不能重名');
				}
				
				foreach ($sku_value_arr as $k2 => $v2) {
					if ($sku_list[$k2]) {
						$map = array('sku_id'=>$sku_list[$k2]['sku_id']);
						$data = array('sku_value'=>trim($v2));
						$result = M('goods_sku')->where($map)->save($data);
						if ($result === false){
							M()->rollback();
							throw new \Exception('编辑sku失败');
						}
					}else {
						$data = array(
								'goods_id'=>$goods_id,
								'sku_name'=>$v['sku_name'],
								'sku_value'=>$v2,
								'sort'=>$sku_list[0]['sort']
						);
						$result = M('goods_sku')->add($data);
						if ($result === false){
							M()->rollback();
							throw new \Exception('编辑sku失败');
						}
					}
				}
			}
		}
		
		//添加sku
		if ($params['sku_value']) {
			$dataList = array();
			foreach ($params['sku_value'] as $k => $v) {
				if (empty($v)) {
					M()->rollback();
					throw new \Exception('规格值不能为空');
				}
				
				$sku_value = explode(',', $v);
				foreach ($sku_value as $k2 => $v2) {
					$dataList[] = array(
							'goods_id'=>$goods_id,
							'sku_name'=>trim($params['sku_name'][$k]),
							'sku_value'=>trim($v2),
							'sort'=>$k
					);
				}
			}
			$result = $this->goodsSkuDao()->addAll($dataList);
			if ($result === false){
				M()->rollback();
				throw new \Exception('添加sku失败');
			}
		}
		
		//编辑货品
		$product_num = 0;
		$product_ids = array();
		if ($params['products']) {
			foreach ($params['products'] as $k => $v) {
				if (isset($v['product_image']) && empty($v['product_image'])) {
					M()->rollback();
					throw new \Exception('货品图片不能为空');
				}
				if (empty($v['product_price'])) {
					M()->rollback();
					throw new \Exception('优惠不能为空');
				}
				if (empty($v['market_price'])) {
					M()->rollback();
					throw new \Exception('原价不能为空');
				}
				if (empty($v['stock_price'])) {
					M()->rollback();
					throw new \Exception('进货价不能为空');
				}
				//优惠价不能小于进货价
				if ($v['product_price'] < $v['stock_price']) {
					M()->rollback();
					throw new \Exception('货品的优惠价不能小于进货价');
				}
				if (empty($v['product_weight'])) {
					M()->rollback();
					throw new \Exception('货品重量不能为空');
				}
				if (empty($v['stock_number'])) {
					M()->rollback();
					throw new \Exception('库存数量不能为空');
				}
				
				//判断货品是否存在
				$map = array(
						'product_id'=>$k,
						'goods_id'=>$goods_id
				);
				$product = $this->goodsProductDao()->findRecord($map);
				if (!$product) {
					M()->rollback();
					throw new \Exception('货品不存在');
				}
				
				$map = array('product_id'=>$k);
				unset($v['product_items_name']);
				$product_image = $v['product_image'] ? $v['product_image'] : $product_image;
				$v['product_image'] = $product_image;
				$result = $this->GoodsProductDao()->where($map)->save($v);
				if ($result === false){
					M()->rollback();
					throw new \Exception('编辑货品失败');
				}
				
				$product_num += $v['stock_number'];
				$product_ids[] = $k;
			}
		}
		
		//删除原来的货品
		if ($product_ids) {
			$map = array(
					'goods_id'=>$goods_id,
					'product_id'=>array('not in', $product_ids),
			);
			$result = $this->GoodsProductDao()->where($map)->delete();
			if ($result === false){
				M()->rollback();
				throw new \Exception('删除货品失败');
			}
		}
		
		//添加货品
		if ($params['product_items']) {
			$product_image = '';
			$dataList = array();
			foreach ($params['product_items'] as $k => $v) {
				if (empty($v)) {
					M()->rollback();
					throw new \Exception('货品规格不能为空');
				}
				/* if (empty($params['product_image'][$v])) {
					M()->rollback();
					throw new \Exception('货品图片不能为空');
				} */
				if (empty($params['product_price'][$k])) {
					M()->rollback();
					throw new \Exception('优惠价不能为空');
				}
				if (empty($params['market_price'][$k])) {
					M()->rollback();
					throw new \Exception('原价不能为空');
				}
				if (empty($params['stock_price'][$k])) {
					M()->rollback();
					throw new \Exception('进货价不能为空');
				}
				//优惠价不能小于进货价
				if ($params['product_price'][$k] < $params['stock_price'][$k]) {
					M()->rollback();
					throw new \Exception('货品的优惠价不能小于进货价');
				}
				if (empty($params['product_weight'][$k])) {
					M()->rollback();
					throw new \Exception('货品重量不能为空');
				}
				if (empty($params['stock_number'][$k])) {
					M()->rollback();
					throw new \Exception('库存数量不能为空');
				}
				
				//product_items
				$map = array(
						'sku_value'=>array('in', trim($v)),
						'goods_id'=>$goods_id
				);
				$sku_ids = $this->goodsSkuDao()->field('sku_id')->order('sort ASC')->where($map)->select();
				$product_items = '';
				foreach ($sku_ids as $v2) {
					$product_items .= $v2['sku_id'].',';
				}
				$product_items = trim($product_items, ',');
				
				//product_image
				$product_image = $params['product_image'][$v] ? $params['product_image'][$v] : $product_image;
				
				$dataList[] = array(
						'goods_id'=>$goods_id,
						'product_items'=>$product_items,
						'product_image'=>$product_image,
						'product_price'=>$params['product_price'][$k],
						'market_price'=>$params['market_price'][$k],
						'stock_price'=>$params['stock_price'][$k],
						'product_weight'=>$params['product_weight'][$k],
						'stock_number'=>$params['stock_number'][$k],
						//'notify_number'=>$params['notify_number'][$k],
						'add_time'=>NOW_TIME,
						'update_time'=>NOW_TIME
				);
				
				$product_num += $v['stock_number'];
			}
			
			$result = $this->GoodsProductDao()->addAll($dataList);
			if ($result === false){
				M()->rollback();
				throw new \Exception('添加货品失败');
			}
		}
		
		//修改商品表货品数量和价格
		$min_product = $this->GoodsProductDao()->where(array('goods_id'=>$goods_id))->order('product_price ASC')->find();
		$max_product = $this->GoodsProductDao()->where(array('goods_id'=>$goods_id))->order('product_price DESC')->find();
		$data = array(
				'product_num'=>$product_num,
				'min_product_price'=>$min_product['product_price'],
				'max_product_price'=>$max_product['product_price'],
		);
		$result = $this->GoodsDao->where(array('goods_id'=>$goods_id))->save($data);
		if ($result === false){
			M()->rollback();
			throw new \Exception('修改商品货品数量失败');
		}
		
		M()->commit();
		
		return true;
	}
	
	public function delete($id){
		M()->startTrans();
		//删除货品
		$res = $this->GoodsProductDao()->delRecords(array('goods_id'=>$id));
		if ($res === false) {
			M()->rollback();
			throw new \Exception('删除货品失败');
		}
		
		$result = $this->GoodsDao->delRecord($id);
		if ($result === false){
			M()->rollback();
			throw new \Exception('删除失败');
		}
		M()->commit();
		return true;
	}
	
	public function delall($goods_ids){
		M()->startTrans();
		//删除货品
		$map['goods_id'] = array('in',$goods_ids);
		$res = $this->GoodsProductDao()->delRecords($map);
		if ($res === false) {
			M()->rollback();
			throw new \Exception('删除货品失败');
		}
		
		$result = $this->GoodsDao->delRecords($map);
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
		if (!empty($params['keyword'])) {
			$map['goods_name'] = array('like', '%'.$params['keyword'].'%');
		}
		if (!empty($params['brand_id'])) {
			$map['brand_id'] = $params['brand_id'];
		}
		if ($params['_distributor_id']){
			$map_cl = array(
					'distributor_id'=>$params['_distributor_id'],
			);
			$cl = $this->goodsCatDistributorDao()->where($map_cl)->getField('cat_id, cat_id as b');
			if ($params['cat_id'] > 0){
				$clist = $this->GoodsCatService()->getCatChilds($params['cat_id']);
				$cl = array_intersect($cl, $clist);
			}
			empty($cl) && $cl = array(-1);
			$map['cat_id'] = array('in', $cl);
		} elseif (!empty($params['cat_id'])) {
			$clist = $this->GoodsCatService()->getCatChilds($params['cat_id']);
			$map['cat_id'] = array('in', $clist);
		}
		if (isset($params['is_on_sale'])) {
			$map['is_on_sale'] = $params['is_on_sale'];
		}
		if (isset($params['is_self_sale'])) {
			$map['is_self_sale'] = $params['is_self_sale'];
		}
		if (isset($params['is_custom'])) {
			$map['is_custom'] = $params['is_custom'];
		}
		
		$count = $this->GoodsDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'goods_id DESC' : $params['orderby'];
			$list = $this->GoodsDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	public function goodsList($params){
		if ($params['pagesize'] < 1) $params['pagesize'] = 4;
		$map = array();
		$params['cat_id'] > 0 && $map['cat_id'] = $params['cat_id'];
		$l = $this->GoodsDao->searchRecords($map, '', 1, $params['pagesize']);
		return $this->outputForList($l);
	}
	
	public function getDefaultProduct($goods_id){
		if ($goods_id < 1) return false;
		$map = array('goods_id'=>$goods_id);
		$orderBy = 'product_price ASC';
		return $this->GoodsProductDao()->searchRecord($map, $orderBy);
	}
	
	private function outputForList($list) {
		if (empty($list)) {
			return $list;
		}
		//获取商品分类和品牌
		foreach ($list as $v) {
			if ($v['cat_id']) {
				$cat_ids[] = $v['cat_id'];
			}
			if ($v['brand_id']) {
				$brand_ids[] = $v['brand_id'];
			}
		}
		$goods_cats = $this->GoodsCatDao()->searchRecordsField($cat_ids);
		$goods_brands = $this->GoodsBrandDao()->searchRecordsField($brand_ids);
		foreach ($list as $k => $v) {
			$list[$k]['cat_name'] = $goods_cats[$v['cat_id']];
			$brand = $goods_brands[$v['brand_id']];
			$list[$k]['brand_name'] = $brand['brand_name'];
			
			//商品相册
			if ($v['goods_gallery']) {
				$list[$k]['goods_gallery'] = explode('#', trim($v['goods_gallery'],'#'));
			}
			
			//默认货品
			$list[$k]['product'] = $this->getDefaultProduct($v['goods_id']);
			
			//货品列表
			$map = array('goods_id'=>$v['goods_id']);
			$product_list = $this->GoodsProductDao()->searchAllRecords($map);
		}
		return $list;
	}
	
	private function outputForInfo($info) {
		//获取商品分类和品牌
		$goods_cat = $this->GoodsCatDao()->getRecord($info['cat_id']);
		$goods_brand = $this->GoodsBrandDao()->getRecord($info['brand_id']);
		$info['cat_name'] = $goods_cat['cat_name'];
		$info['brand_name'] = $goods_brand['brand_name'];
		
		//商品相册
		if (!empty($info['goods_gallery'])) {
			$info['goods_gallery'] = explode('#', trim($info['goods_gallery'],'#'));
		}
		
		//默认货品
		$info['product'] = $this->getDefaultProduct($info['goods_id']);
		
		//货品列表
		$map = array('goods_id'=>$info['goods_id']);
		$product_list = $this->GoodsProductDao()->searchAllRecords($map);
		$info['product_list'] = $product_list;
		
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
		
		//商品属性
		$info['spec_list'] = M('goods_spec_values')->alias('a')->field('a.*, b.spec_name')
							->join('LEFT JOIN __GOODS_SPEC__ b ON b.spec_id=a.spec_id')
							->where(array('goods_id'=>$info['goods_id']))->select();
		
		//服务承诺
		$info['service_promise_arr'] = $info['service_promise'] ? explode(',', $info['service_promise']) : '';
		return $info;
	}

	public function distributorGoodListService($where = array(),$params = array(), $orderby = array()){
		$filed = array('record_id','goods_id','collect_count','distributor_id');
		$distributorGoods = D('DistributorGoods');
		$count = $distributorGoods->where($where)->field($filed)->count();;
		$_list = array();
		if($count > 0){
			$data = $distributorGoods->where($where)->field($filed)->order($orderby)->page($params['page'], $params['pagesize'])->select();
			foreach($data as $key => $val){
				$_t = $val;
				$goodsFind = D('Goods')->field(array('goods_name','goods_image'))->where(array('goods_id' => $val['goods_id']))->find();
				$distributorInfo = D('DistributorInfo')->where(array('distributor_id' => $val['distributor_id']))->field(array('distributor_name'))->find();
				$_t['distributor_name'] = $distributorInfo['distributor_name'];
				$_t['goods_name'] = $goodsFind['goods_name'];
				if($goodsFind['goods_image']){
					$_t['goods_image'] = domain_name_url.'/upload/'.$goodsFind['goods_image'];
				} else {
					$_t['goods_image'] = domain_name_url.'public/main/images/user_default_img.jpg';
				}
				$_list[] = $_t;
			}
		}
		return array(
			'list' => $_list,
			'count'=> $count,
		);
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
				'goods_id'=>$params['goods_id']
		);
		if (!$this->GoodsDao->validate($rules)->create($data)){
			throw new \Exception($this->GoodsDao->getError());
		}
		if ($data['seckill_end'] <= NOW_TIME) {
			throw new \Exception('结束时间不能小于当前时间');
		}
		$result = $this->GoodsDao->save();
		if ($result === false){
			M()->rollback();
			throw new \Exception('设置秒杀时间失败');
		}
		
		//秒杀价格
		$total_price = 0;
		foreach ($params['seckill_price'] as $price) {
			$total_price += $price;
		}
		if ($total_price == 0) {
			throw new \Exception('至少设置一个货品的秒杀价格大于零');
		}
		$map = array('goods_id'=>$params['goods_id']);
		$product_list = $this->GoodsProductDao()->searchAllRecords($map);
		foreach ($product_list as $product) {
			$data = array(
					'seckill_price'=>$params['seckill_price'][$product['product_id']],
					'product_id'=>$product['product_id']
			);
			if (!$this->GoodsProductDao()->create($data)){
				throw new \Exception($this->GoodsDao->getError());
			}
			$result = $this->GoodsProductDao()->save();
			if ($result === false){
				M()->rollback();
				throw new \Exception('设置秒杀价格失败');
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
	
	public function up($goods_ids) {
		$map['goods_id'] = array('in', $goods_ids);
		$data = array('is_on_sale'=>1);
		$result = $this->GoodsDao->where($map)->save($data);
		if ($result === false){
			throw new \Exception($this->GoodsDao->getError());
		}
		return true;
	}
	
	public function down($goods_ids) {
		$map['goods_id'] = array('in', $goods_ids);
		$data = array('is_on_sale'=>0);
		$result = $this->GoodsDao->where($map)->save($data);
		if ($result === false){
			throw new \Exception($this->GoodsDao->getError());
		}
		return true;
	}
	
	public function get_filter_attr($cat_id, $get) {
		$filter_attr_str = isset($get['filter_attr']) ? htmlspecialchars(trim($get['filter_attr'])) : '0';
		$filter_attr_str = trim(urldecode($filter_attr_str));
		$filter_attr_str = preg_match('/^[\d\.]+$/',$filter_attr_str) ? $filter_attr_str : '';
		$filter_attr = empty($filter_attr_str) ? '' : explode('.', $filter_attr_str);
		
		$cat = $this->GoodsCatDao()->find($cat_id);
		$cat_filter_attr = $this->goodsSpecDao()->field('spec_id, spec_name')->where(array('type_id'=>$cat['type_id']))->select();
		
		$all_attr_list = array();
		foreach ($cat_filter_attr AS $key => $value)
		{
			$map = array(
					'spec_id'=>$value['spec_id'],
					'spec_value'=>array('neq', ''),
					//'goods_spec_value_id'=>array('not in', $filter_attr)
			);
			$attr_list = $this->goodsSpecValuesDao()->field('goods_spec_value_id, goods_id, spec_id, spec_value')->where($map)->group('spec_value')->select();
			
			if (empty($attr_list)) {
				continue;
			}
			
			$temp_arrt_url_arr = array();
			for ($i = 0; $i < count($cat_filter_attr); $i++)
			{
				$temp_arrt_url_arr[$i] = !empty($filter_attr[$i]) ? $filter_attr[$i] : 0;
			}
			
			$temp_arrt_url_arr[$key] = 0;
			$temp_arrt_url = implode('.', $temp_arrt_url_arr);
			$all_attr_list[$key]['spec_id'] = $value['spec_id'];
			$all_attr_list[$key]['attr_value'] = $value['spec_name'];
			$url = U('mall/category/index', array('cat_id'=>$cat['cat_id'], 'brand_id'=>$get['brand_id'], 'region_code'=>$get['region_code'], 'filter_attr'=>$temp_arrt_url));
			$all_attr_list[$key]['url'] = $url;
			//$all_attr_list[$key]['selected'] = empty($filter_attr[$key]) ? 1 : 0;
			$all_attr_list[$key]['selected'] = 0;
			
			foreach ($attr_list as $k => $v)
			{
				$temp_key = $k + 1;
				$temp_arrt_url_arr[$key] = $v['goods_spec_value_id'];       //为url中代表当前筛选属性的位置变量赋值,并生成以‘.’分隔的筛选属性字符串
				$temp_arrt_url = implode('.', $temp_arrt_url_arr);
				
				$all_attr_list[$key]['attr_list'][$temp_key]['goods_spec_value_id'] = $v['goods_spec_value_id'];
				$all_attr_list[$key]['attr_list'][$temp_key]['attr_value'] = $v['spec_value'];
				$url = U('mall/category/index', array('cat_id'=>$cat['cat_id'], 'brand_id'=>$get['brand_id'], 'region_code'=>$get['region_code'], 'filter_attr'=>$temp_arrt_url));
				$all_attr_list[$key]['attr_list'][$temp_key]['url'] = $url;
			
				if (!empty($filter_attr[$key]) AND $filter_attr[$key] == $v['goods_spec_value_id'])
				{
					$all_attr_list[$key]['attr_list'][$temp_key]['selected'] = 1;
					$all_attr_list[$key]['selected'] = 1;
				}
				else
				{
					$all_attr_list[$key]['attr_list'][$temp_key]['selected'] = 0;
				}
			}
		}
		//_p($all_attr_list);
		return $all_attr_list;
	}
	
	private function GoodsProductDao() {
		return D('Common/Goods/GoodsProduct');
	}
	
	private function GoodsCatDao() {
		return D('Common/Goods/GoodsCat');
	}
	
	private function GoodsBrandDao() {
		return D('Common/Goods/GoodsBrand');
	}
	
	private function GoodsCatService() {
		return D('GoodsCat', 'Service');
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
	
	private function goodsSpecValuesDao() {
		return D('Common/Goods/GoodsSpecValues');
	}
	
	private function goodsSkuDao() {
		return D('Common/Goods/GoodsSku');
	}
	
	private function goodsCatDistributorDao() {
		return D('Common/Goods/GoodsCatDistributor');
	}
}//end HelpService!甜品