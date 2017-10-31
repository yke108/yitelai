<?php
namespace Common\Model;
use Common\Basic\Status;

class OrderInfo{
    function orderStatusLabel($status){
    	$l = array(
			Status::OrderStatusNone => '未确认',
			Status::OrderStatusOnWay => '进行中',
			Status::OrderStatusSuccess => '已完成',
			Status::OrderStatusCancel => '已取消',
			Status::OrderStatusOnBack => '退货中',
			Status::OrderStatusOver => '已结束',
    	);
		return $l[$status];
    }
	
    function payStatusLabel($status){
    	$l = array(	
			Status::PayStatusNone => '未付款',
			Status::PayStatusPaid => '已付款',
			Status::PayStatusCOD => '货到付款',
			Status::PayStatusRepaying => '退款中',
			Status::PayStatusRepaid => '已退款',
    	);
		return $l[$status];
    }
	
    function ShippingStatusLabel($status){
    	$l = array(
			Status::ShippingStatusNone => '未发货',
			Status::ShippingStatusPrepare => '待发货',
			Status::ShippingStatusDelivering => '已发货',
			Status::ShippingStatusReceived => '已收货',
			Status::ShippingStatusBacking => '退货中',
			Status::ShippingStatusBacked => '已退货',
    	);
		
		if($status!=null){
			return $l[$status];
		}else{
			return $l;
		}
    }
	
	static function statusLabel($order_status, $pay_status, $shipping_status, $confirm_type = 0){
		switch($order_status){
			case Status::OrderStatusNone:
			{
				$status = self::payStatusLabel($pay_status);
				if($pay_status != Status::PayStatusNone){
					$status  .= '，待发货';
				}
				break;
			}
			
			case Status::OrderStatusOnWay:
			//case Status::OrderStatusOnBack:
			{
				$status  = self::payStatusLabel($pay_status).'，';
				$status .= self::shippingStatusLabel($shipping_status);
				break;
			}
			
			case Status::OrderStatusSuccess:
			case Status::OrderStatusCancel:
			case Status::OrderStatusOnBack:
			case Status::OrderStatusOver:
			{
				$status = self::orderStatusLabel($order_status);
				break;
			}
		}
		if ($confirm_type == 1) {
			$status .= '，系统自动收货';
		}
		return $status;
	}
	
	static function stockDeductAfterSaled($order_id = 0){
		if($order_id < 1) return false;
		//扣除库存
		$map = array(
				'order_id'=>$order_id,
		);
		$gl = M('OrderGoods')->where($map)->select();
		foreach($gl as $vo){
			/*$data = array(
					'goods_id'=>$vo['goods_id'],
					'sale_count'=>array('exp', 'sale_count+'.$vo['goods_number']),
			);
			$res = M('GoodsInfo')->data($data)->save();
			if($res === false){
				return false;
			}*/

			// $data = array(
			// 		'goods_id'=>$vo['goods_id'],
			// 		'goods_number'=>array('exp', 'goods_number-'.$vo['goods_number']),
			// 		//'sale_count'=>array('exp', 'sale_count+'.$vo['goods_number']),
			// );
			// $res = M('Goods')->data($data)->save();
			if($vo['product_id']){
				$data = array(
						'product_id'=>$vo['product_id'],
						'product_number'=>array('exp', 'product_number-'.$vo['goods_number']),
						//'sale_count'=>array('exp', 'sale_count+'.$vo['goods_number']),
				);
				$res = M('Products')->data($data)->save();
				if($res === false){
					return false;
				}
			}
		}
		return true;
	}
	
	static function stockDeductAfterSaledInsure($order_id = 0){
		if($order_id < 1) return false;
		//扣除库存
		$map = array(
				'order_id'=>$order_id,
		);
		$gl = M('InsureOrder')->where($map)->select();
		foreach($gl as $vo){
			/*$data = array(
					'goods_id'=>$vo['goods_id'],
					'sale_count'=>array('exp', 'sale_count+'.$vo['goods_number']),
			);
			$res = M('GoodsInfo')->data($data)->save();
			if($res === false){
				return false;
			}*/

			$data = array(
					'goods_id'=>$vo['goods_id'],
					'goods_number'=>array('exp', 'goods_number-'.$vo['buy_count']),
					//'sale_count'=>array('exp', 'sale_count+'.$vo['goods_number']),
			);
			$res = M('InsureGoods')->data($data)->save();
			
		}
		return true;
	}
}