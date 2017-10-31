<?php
namespace Common\Service;
use Common\Model\Shipping;

class CartService{
	public function addCart($params){
		$this->userCheck($params);
		$id = intval($params['id']);
		if($id < 1) throw new \Exception('参数错误');
		
		$number = intval($params['number']);
		if ($number < 1) throw new \Exception('数量不正确');
		
		//检查货品是否存在
		$distributor_product = $this->distributorGoodsProductDao()->getRecord($params['id']);
		if(empty($distributor_product)) throw new \Exception('请选择规格');
		
		$params['act_type'] = 0;
		
		//如果是立即购买则清除其它立即购买商品
		if ($params['act_type'] == 1) {
			$map = array(
					'user_id'=>$params['user_id'],
					'act_type'=>$params['act_type'],
			);
			$result = $this->cartDao()->deleteCartGoods($map);
		}
		
		//分销商商品
		$distributor_goods = $this->distributorGoodsDao()->getRecord($distributor_product['record_id']);
		
		//店铺
		$distributor_info = $this->distributorInfoDao()->getRecord($distributor_goods['distributor_id']);
		if ($distributor_info['status'] == 3) {
			throw new \Exception('店铺已关闭，无法购买');
		}
		
		//使用商品服务
		$service_price = 0;
		/* if ($params['use_service'] > 0) {
			if ($distributor_goods['service_id'] == 0) {
				throw new \Exception('商品服务不存在');
			}
			$service_id = $distributor_goods['service_id'];
		} */
		$service_id = $params['service_id'] ? $params['service_id'] : 0;
		
		//秒杀价格
		$is_seckill = 0;
		if ($distributor_goods['seckill_start'] <= NOW_TIME && $distributor_goods['seckill_end'] >= NOW_TIME && $distributor_goods['seckill_status'] == 1 && $distributor_goods['total_seckill_num'] > 0 && $distributor_product['seckill_price'] > 0) {
			$cart_price = $distributor_product['seckill_price'];
			$is_seckill = 1;
		}else {
			$cart_price = $distributor_product['product_price'];
		}
		
		//检查库存
		$cart_goods = $this->cartDao()->findByUAG($params['user_id'], $id, $params['act_type']);
		$want_number = $number + intval($cart_goods['cart_number']);
		if ($is_seckill == 1) {
			if($want_number > $distributor_product['seckill_num']) throw new \Exception('库存不足');
		}else {
			if($want_number > $distributor_product['stock_num']) throw new \Exception('库存不足');
		}
		
		//处理折扣
		if ($params['discount'] > 0) {
			$cart_price = $cart_price * $params['discount'] / 100;
		}
		
		M()->startTrans();
		
		if($cart_goods){
			$result = $this->cartDao()->increaseNumber($cart_goods['id'], $number);
			if($result === false) {
				M()->rollback();
				throw new \Exception('更新失败');
			}
			
			//更新商品服务
			$data = array(
					'service_id'=>$service_id,
					'cart_price'=>$cart_price,
					'is_seckill'=>$is_seckill,
					'discount'=>$params['discount']
			);
			$res = $this->cartDao()->where(array('id'=>$cart_goods['id']))->save($data);
			if($res === false) {
				M()->rollback();
				throw new \Exception('操作失败');
			}
		} else {
			$data = array(
					'user_id'=>$params['user_id'],
					'goods_id'=>$distributor_product['id'],
					'cart_price'=>$cart_price,
					'cart_number'=>$number,
					'service_id'=>$service_id,
					'is_checked'=>1,
					'act_type'=>$params['act_type'],
					'is_seckill'=>$is_seckill,
					'discount'=>$params['discount']
			);
			$result = $this->cartDao()->add($data);
			if($result < 1) {
				M()->rollback();
				throw new \Exception('添加失败');
			}
		}
		
		//统计购物车数量
		$result = $this->distributorGoodsDao()->where(array('record_id'=>$distributor_goods['record_id']))->setInc('cart_count', $number);
		if($result === false) {
			M()->rollback();
			throw new \Exception('添加失败');
		}
		
		M()->commit();
	}
	
	public function updateCart($params){
		$this->userCheck($params);
		$cart_id = intval($params['cart_id']);
		if ($cart_id < 1) throw new \Exception('参数错误');
		$number = intval($params['number']);
		if ($number < 1) throw new \Exception('数量不正确');
		$cart_goods = $this->cartDao()->find($cart_id);
		if(empty($cart_goods)) throw new \Exception('更新失败');
		$goods = $this->goodsDao()->getRecord($cart_goods['goods_id']);
		if(empty($goods)) throw new \Exception('更新失败');
		$want_number = $number + intval($cart_goods['cart_number']);
		if ($want_number > $goods['goods_number']) throw new \Exception('库存不足');
		$result = $this->cartDao()->increaseNumber($cart_id, $number);
		if($result === false) throw new \Exception('更新失败');
	}
	
	public function delCart($params){
		$this->userCheck($params);
		$id = intval($params['cart_id']);
		$map = array(
				'id'=>$id,
				'user_id'=>$params['user_id']
		);
		if($this->cartDao()->deleteCartGoods($map) === false)
			throw new \Exception('删除失败');
	}
	
	public function delAllCart($params){
		$this->userCheck($params);
		$cart_ids = $params['cart_ids'];
		$map = array(
				'id'=>array('in', $cart_ids),
				'user_id'=>$params['user_id']
		);
		if($this->cartDao()->deleteCartGoods($map) === false) throw new \Exception('删除失败');
	}
	
	public function accountCart($params){
		$this->userCheck($params);
		$l = $this->cartDao()->searchSelectedList($params['user_id'], $params['act_type']);
		if(empty($l)) throw new \Exception('购物车为空');
		$total_amount = $goods_amount = $total_number = $total_shipping_fee = 0;
		
		$list = $this->outputList($l, $params['user']);
		
		$region = $this->regionDao()->getDistrictOfProvince($params['district']);
		foreach ($list as $distributor_id => $v){
			$amount = $number = $weight = 0;
			foreach ($v['goods_list'] as $vo) {
				$amount += $vo['GoodsPrice'] * $vo['CartNumber'];
				$number += $vo['CartNumber'];
				$weight += $vo['GoodsWeight'] * $vo['CartNumber'];
				
				$goods_amount += $vo['GoodsPrice'] * $vo['CartNumber'] + $vo['GoodsService']['price'];
				$total_number += $vo['CartNumber'];
			}
			//运费计算
			$result = Shipping::fee($distributor_id, key($region), $amount, $number, $weight);
			$list[$distributor_id]['shipping_fee'] = $result['shipping_fee'];
			$total_shipping_fee += $result['shipping_fee'];
		}
		$total_amount = $goods_amount + $total_shipping_fee;
		
		return array(
			'list'=>$list,
			'info'=>array(
				'GoodsAmount'=>sprintf("%.2f", $goods_amount),
				'TotalNumber'=>sprintf("%.2f", $total_number),
				'ShippingFee'=>sprintf("%.2f", $total_shipping_fee),
				'TotalAmount'=>sprintf("%.2f", $total_amount),
			),
		);
	}
	
	public function checkCart($params){
		$this->userCheck($params);
		if (!is_array($params['cart_id_list']) || count($params['cart_id_list']) < 1) throw new \Exception('参数错误');
		
		M()->startTrans();
		
		$result = $this->cartDao()->goodsCheck($params['user_id'], $params['cart_id_list']);
		if($result === false) {
			M()->rollback();
			throw new \Exception('操作失败');
		}
		
		//处理购物车数量
		foreach ($params['cart_id_list'] as $cart_id) {
			$map = array(
					'user_id'=>$params['user_id'],
					'id'=>$cart_id,
			);
			$cart_product = $this->cartDao()->findRecord($map);
			if (empty($cart_product)) {
				M()->rollback();
				throw new \Exception('购物车商品不存在');
			}
			
			//检查库存
			$product = $this->distributorGoodsProductDao()->getRecord($cart_product['goods_id']);
			//分销商商品
			$distributor_goods = $this->distributorGoodsDao()->getRecord($product['record_id']);
			//判断是否秒杀商品
			$is_seckill = 0;
			if ($distributor_goods['seckill_start'] <= NOW_TIME && $distributor_goods['seckill_end'] >= NOW_TIME && $distributor_goods['seckill_status'] == 1 && $distributor_goods['total_seckill_num'] > 0 && $product['seckill_price'] > 0) {
				$is_seckill = 1;
			}
			if ($is_seckill == 1) {
				if ($params['number'][$cart_id] > $product['seckill_num']) {
					M()->rollback();
					throw new \Exception('库存不足');
				}
			}else {
				if ($params['number'][$cart_id] > $product['stock_num']) {
					M()->rollback();
					throw new \Exception('库存不足');
				}
			}
				
			$map = array('id'=>$cart_id);
			$res = $this->cartDao()->where($map)->save(array('cart_number'=>$params['number'][$cart_id]));
			if($res === false) {
				M()->rollback();
				throw new \Exception('操作失败');
			}
		}
		
		M()->commit();
		
		return true;
	}
	
	public function listCart($params){
		$this->userCheck($params);
		$l = $this->cartDao()->searchAllList($params['user_id']);
		return $this->outputList($l, $params['user']);
	}
	
	public function getAllList($params){
		$this->userCheck($params);
		$l = $this->cartDao()->searchAllList($params['user_id']);
		foreach ($l as $k => $v) {
			$distributor_product = $this->distributorGoodsProductDao()->getRecord($v['goods_id']);
			$distributor_goods = $this->distributorGoodsDao()->getRecord($distributor_product['record_id']);
			$l[$k]['record_id'] = $distributor_goods['record_id'];
			$l[$k]['goods_name'] = $distributor_goods['goods_name'];
			$l[$k]['goods_image'] = $distributor_product['product_image'];
			$l[$k]['goods_price'] = $distributor_product['product_price'];
		}
		return $l;
	}
	
	public function getCartNumber($params){
		$map = array(
				'user_id'=>$params['user_id'],
				//'act_type'=>0,
		);
		return $this->cartDao()->searchCount($map);
	}
	
	private function outputList($l, $user = array()){
		if (empty($l)) {
			return $l;
		}
		
		//会员折扣
		$discount = $user['rank']['discount'] ? $user['rank']['discount'] : 0;
		
		$list = array();
		foreach ($l as $vo) {
			//分销商货品
			$distributor_product = $this->distributorGoodsProductDao()->getRecord($vo['goods_id']);
			if ($distributor_product) {
				//分销商商品
				$distributor_goods = $this->distributorGoodsDao()->getRecord($distributor_product['record_id']);
				if ($distributor_goods) {
					//商品
					$goods = $this->goodsDao()->find($distributor_goods['goods_id']);
					if ($goods) {
						//分成方案
						$distribution_points = 0;
						if ($goods['distribution_id'] > 0) {
							$goods_distribution = $this->goodsDistributionDao()->find($goods['distribution_id']);
							$user_ratio = $goods_distribution['user_ratio'];
							$distribution_points = round($vo['cart_price'] * $vo['cart_number'] * $user_ratio / 100);
						}
						//秒杀
						$is_seckill = $vo['is_seckill'];
						if ($distributor_goods['seckill_start'] > NOW_TIME || $distributor_goods['seckill_end'] < NOW_TIME  && $distributor_goods['seckill_status'] != 1) {
							$map = array('id'=>$vo['id']);
							$data = array(
									'cart_price'=>$distributor_product['product_price'],
									'is_seckill'=>0
							);
							$result = $this->cartDao()->where($map)->save($data);
							$is_seckill = 0;
						}
						//分销商
						$distributor = $this->distributorInfoDao()->getRecord($distributor_goods['distributor_id']);
						if ($distributor) {
							//商品服务
							$goods_service = array();
							if ($vo['service_id'] > 0) {
								$goods_service = $this->goodsServiceDao()->getRecord($vo['service_id']);
							}
							$list[$distributor_goods['distributor_id']]['distributor'] = $distributor;
							
							//最终价格
							if ($is_seckill == 1) {
								$GoodsPrice = $distributor_product['seckill_price'];
							}elseif ($discount > 0) {
								$GoodsPrice = $distributor_product['product_price'] * $discount / 100;
							}else {
								$GoodsPrice = $distributor_product['product_price'];
							}
							
							//sku
							if ($distributor_product['product_items']) {
								$sku = $this->goodsSkuDao()->where(array('sku_id'=>array('in',$distributor_product['product_items'])))->select();
								if ($sku) {
									$list[$distributor_goods['distributor_id']]['goods_list'][] = array(
											'CartId'=>$vo['id'],
											'CartNumber'=>$vo['cart_number'],
											'CartPrice'=>$vo['cart_price'],
											'ServiceId'=>$vo['service_id'],
											'IsChecked'=>$vo['is_checked'],
											'IsSeckill'=>$vo['is_seckill'],
											'Discount'=>$vo['discount'],
												
											'GoodsService'=>$goods_service,
												
											'GoodsId'=>$distributor_goods['record_id'],
											'GoodsSn'=>$distributor_goods['goods_sn'],
											'GoodsName'=>$distributor_goods['goods_name'],
											'DeliveryTime'=>$distributor_goods['delivery_time'],
											'RepairTime'=>$distributor_goods['repair_time'],
											'IsDistribution'=>$distributor_goods['is_distribution'],
												
											'ProductName'=>$distributor_product['product_name'],
											'MarketPrice'=>$distributor_product['market_price'],
											'GoodsPrice'=>$GoodsPrice,
											'OriginalPrice'=>$distributor_product['product_price'],
											'GoodsWeight'=>$distributor_product['product_weight'],
											'GoodsNumber'=>$distributor_product['stock_num'],
											'GoodsImage'=>$distributor_product['product_image'],
											'SeckillNumber'=>$distributor_product['seckill_num'],
												
											'DistributionPoints'=>$distribution_points,
												
											'Sku'=>$sku
									);
								}
							}
						}
					}
				}
			}
		}
		return $list;
	}
	
	//获取购物车总价
	public function getCartPriceAmount($map){
		return $this->cartDao()->searchPriceAmount($map);
	}
	
	private function userCheck(&$params){
		$params['user_id'] = intval($params['user_id']);
		if($params['user_id'] < 1){
			throw new \Exception('缺少用户参数',100);
		}
	}


	public function cartInfoList($where = array())
	{
		$map = array();
		if ($where['user_id']) {
			$map['user_id'] = $where['user_id'];
		}
		if($where['starTime'] && $where['endTime']){
			$addTime = toStartEntTime($where['starTime'], $where['endTime']);
			$map['add_time'] = array('between', $addTime['starTime'] . ',' . $addTime['endTime']);
		}
		$data = $this->cartInfoDao()->field(array('user_id','goods_id'))->where($map)->select();
		$_list = array();
		foreach($data as $key => $val){
			$_t = $val;
			$distributorGoodsFind = D('DistributorGoods')->where(array('goods_id' => $val['goods_id']))->field(array('goods_id'))->find();
			$goodsFind = D('Goods')->where(array('goods_id' => $distributorGoodsFind['goods_id']))->field(array('goods_name'))->find();
			$_t['goods_name'] = $goodsFind['goods_name'];
			$_list[] = $_t;
		}
		return $_list;
	}

	private function cartInfoDao(){
		return D('CartInfo');
	}


	private function cartDao(){
		return D('Cart');
	}
	
	private function orderInfoDao(){
		return D('OrderInfo');
	}
	
	private function orderGoodsDao(){
		return D('OrderGoods');
	}
	
	private function goodsDao(){
		return D('Common/Goods/Goods');
	}
	
	private function goodsProductDao(){
		return D('Common/Goods/GoodsProduct');
	}
	
	private function goodsServiceDao(){
		return D('Common/Goods/GoodsService');
	}
	
	private function distributorGoodsProductDao(){
		return D('Common/Distributor/GoodsProduct');
	}
	
	private function distributorGoodsDao(){
		return D('Common/Distributor/Goods');
	}
	
	private function distributorInfoDao(){
		return D('Common/Distributor/Info');
	}
	
	private function goodsDistributionDao(){
		return D('Common/Goods/GoodsDistribution');
	}
	
	private function regionDao(){
		return D('Region');
	}
	
	private function goodsSkuDao(){
		return D('Common/Goods/GoodsSku');
	}
}
