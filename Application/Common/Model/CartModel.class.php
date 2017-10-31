<?php
namespace Common\Model;
use Think\Model;

class CartModel extends Model{
	// tableName属性来改变默认的规则
	protected $tableName = 'cart_info';
	protected $pk = 'cart_id';
	
	public function increaseNumber($id, $number){
		$map = array(
			'id'=>$id,
		);
		$data = array(
			'cart_number'=>array('exp', 'cart_number+'.$number),
		);
		return $this->where($map)->save($data);
	}
	
	public function getRecord($id){
		return $this->find($id);
	}
	
	public function findRecord($map){
		return $this->where($map)->find();
	}
	
	public function findByUAG($user_id, $goods_id, $act_type){
		$map = array(
			'user_id'=>$user_id,
			'goods_id'=>$goods_id,
			'act_type'=>$act_type,
		);
		return $this->where($map)->find();
	}
	
	public function searchSelectedList($user_id, $act_type = 0){
		$map = array(
			'is_checked'=>1,
			'user_id'=>$user_id,
			//'act_type'=>$act_type,
		);
		return $this->alias('a')->field('a.*')
				->join('JOIN __DISTRIBUTOR_GOODS_PRODUCT__ b ON b.id=a.goods_id')
				->where($map)->select();
	}
	
	public function searchAllList($user_id, $act_type = 0){
		$map = array(
				'user_id'=>$user_id,
				//'act_type'=>$act_type,
		);
		return $this->alias('a')->field('a.*')
				->join('JOIN __DISTRIBUTOR_GOODS_PRODUCT__ b ON b.id=a.goods_id')
				//->join('JOIN __DISTRIBUTOR_GOODS__ c ON c.record_id=b.record_id')
				->where($map)->select();
	}
	
	public function searchCount($map){
		return $this->alias('a')->field('a.*')
				->join('JOIN __DISTRIBUTOR_GOODS_PRODUCT__ b ON b.id=a.goods_id')
				//->join('JOIN __DISTRIBUTOR_GOODS__ c ON c.record_id=b.record_id')
				->where($map)->sum('cart_number');
	}
	
	//获取购物车总价
	public function searchPriceAmount($map){
		return $this->alias('a')->field('a.*')
				->join('JOIN __DISTRIBUTOR_GOODS_PRODUCT__ b ON b.id=a.goods_id')
				//->join('JOIN __DISTRIBUTOR_GOODS__ c ON c.record_id=b.record_id')
				->where($map)->sum('cart_number*cart_price');
	}
	
	public function searchAllCount($user_id){
		$map = array(
				'user_id'=>$user_id,
		);
		return $this->where($map)->sum('cart_number');
	}
	
	Public function goodsCheck($user_id, $cart_ids){
		$map = array(
			'user_id'=>$user_id,
		);
		$data = array(
			'is_checked'=>0,
		);
		if($this->where($map)->save($data) === false) return false;
		$map = array(
			'user_id'=>$user_id,
			'id'=>array('in', $cart_ids),
		);
		$data = array(
			'is_checked'=>1,
		);
		if($this->where($map)->save($data) === false) return false;
		return true;
	}
	
	public function deleteCheckedOnes($user_id, $act_type = 0){
		$map = array(
			'user_id'=>$user_id,
			'is_checked'=>1,
			//'act_type'=>$act_type,
		);
		return $this->where($map)->delete();
	}
	
	public function deleteCartGoods($map){
		return $this->where($map)->delete();
	}
}