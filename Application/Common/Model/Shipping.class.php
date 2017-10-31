<?php
namespace Common\Model;
use Common\Basic\Genre;

class Shipping{
	const NumberInvolid = 1;
	const WeightInvolid = 1;
	const ShippingAreaNotExist = 2;
	const StepValueUnsetted = 3;
	
	static function fee($distributor_id, $region_id, $amount, $number, $weight = 0){
		if($number <= 0){
			//return self::NumberInvolid;
			return array(
					'shipping_fee'=>0,
			);
		}
		
		$map = array(
			'distributor_id'=>$distributor_id,
			'region_code_list'=>array('like', '%'.$region_id.'%'),
			'status'=>1,
		);
		$info = M('ShippingArea')->where($map)->find();
		if(empty($info)){
			//return self::ShippingAreaNotExist;
			return array(
					'shipping_fee'=>0,
			);
		}
		
		if($info['free_shipping'] > 0 && $amount >= $info['free_shipping']){
			return array(
				'shipping_fee'=>0,
			);
		}
		
		if($info['fee_mode'] == 1){//按件计费
			$fee_value = $number;
		} else {//按重量计费
			if($weight < 0) {
				//return self::WeightInvolid;
				return array(
						'shipping_fee'=>0,
				);
			}
			$fee_value = $weight;
		}
		
		$total_fee = $info['item_fee'];
		$wgt = $fee_value - $info['item_value'];
		if($wgt > 0){
			if($info['step_value'] <= 0) return self::StepValueUnsetted;
			$total_fee += ceil($wgt * 1.0 / $info['step_value']) * $info['step_fee'];
		}
		$shipping_fee =  $total_fee;
		return array(
			'shipping_fee'=>$shipping_fee,
		);
	}
}