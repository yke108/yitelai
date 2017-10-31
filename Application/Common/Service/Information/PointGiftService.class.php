<?php
namespace Common\Service\Information;
use Common\Basic\Pager;

class PointGiftService{
	
	const shipping_wait=0;//等待发货
	const shipping_send=1;//已发货
	const shipping_take_delivery=2;//已收货
	
	
	public function getInfo($id){
		if ($id < 1) return false;
		$info=$this->PointGiftDao()->getRecord($id);
		if(!empty($info)){
			$info['goods_gallery']=$info['gallery']!=''?explode('#',trim($info['gallery'],'#')):array();
		}
		return $info;
	}
	
	public function infoCreateOrModify($params){
		
		//参数
		$data = array(
			'name'=>$params['name'],
			'point'=>$params['point'],
			'start_time'=>$params['start_time'],
			'end_time'=>$params['end_time'],
			'goods_introduce'=>$params['goods_introduce'],
			'sort_order'=>$params['sort_order'],
			'is_close'=>$params['is_close'],
			'stock'=>$params['stock'],
		);
		
		$params['picture']!='' && $data['picture']=$params['picture'];
		$params['gallery']!='' && $data['gallery']=$params['gallery'];
		
		if($params['id']==''){
			$data['add_time']=time();
		}else{
			$data['id']=$params['id'];
		}
		
		$PointGiftDao = $this->PointGiftDao();
		if (!$PointGiftDao->validate($rules)->create($data)){
			 throw new \Exception($PointGiftDao->getError());
		}
		
		if ($params['id'] > 0){
			$result = $PointGiftDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $PointGiftDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}	
	public function infoDelete($id){
		if(empty($id)){throw new \Exception('缺少参数');}
		$info=$this->getInfo($id);
		if(empty($info)){throw new \Exception('查询不到记录');}
		$map=array('id'=>$id);
		$result = $this->PointGiftDao()->deleteRecord($map);
		if ($result === false) throw new \Exception('删除失败');
	}
	//分页查询
	public function infoPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		if(!empty($params['map'])){
			$map=array_merge($map,$params['map']);
		}
		
		if($params['is_going']==1){
			$map['is_close']=0;
			$map['start_time']=array('lt',time());
			$map['end_time']=array('gt',time());
		}
		
		$PointGiftDao = $this->PointGiftDao();
		$count = $PointGiftDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? ' id DESC' : $params['orderby'];
			$list = $PointGiftDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
			foreach($list as $key=>$val){
				$list[$key]['goods_gallery']=$val['gallery']!=''?explode('#',trim($val['gallery'],'#')):array();
			}
		}
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
		);
	}
	
	private function PointGiftDao(){
		return D('Common/Information/Point/Gift');
	}
	
	
	public function getOrderInfo($id){
		if ($id < 1) return false;
		$info=$this->pointGiftOrderDao()->getRecord($id);
		$info['region_name'] = $this->regionDao()->getRegionName($info['region_code']);
		return $info;
	}
	
	//生成积分兑换订单
	public function orderCreateOrModify($params){
		
		if(empty($params['user_id']) || empty($params['gift_id'])){throw new \Exception('缺少参数');}
		$address_map=array('user_id'=>$params['user_id'],'address_id'=>$params['address_id']);
		//查询用户默认地址
		$address_info=$this->userAddressDao()->findRecord($address_map);
		if(empty($address_info)){
			throw new \Exception('请选择地址',10000);
		}
		
		
		//查询积分商品
		$gift_info=$this->getInfo($params['gift_id']);
		if(empty($gift_info)){
			throw new \Exception('积分商品不存在');
		}
		
		//商品库存
		if($gift_info['stock']<=0){
			throw new \Exception('库存不足,积分商品兑换失败');
		}
		
		//用户是否存在
		$user_info=$this->userDao()->getRecord($params['user_id']);
		if(empty($user_info)){
			throw new \Exception('用户不存在');
		}
		
		//查询用户是否足够兑换积分
		if($user_info['pay_points']<$gift_info['point']){
			throw new \Exception('积分不足，兑换商品失败');
		}
		
		
		$time_str=(string)time();
		$order_sn='P'.$time_str[0].($time_str[1]+5).substr($time_str,2).rand(1000,9999);
		//参数
		$data = array(
			'order_sn'=>$order_sn,
			'user_id'=>$params['user_id'],
			'shipping_status'=>0,
			'region_code'=>$address_info['region_code'],
			'address'=>$address_info['address'],
			'consignee'=>$address_info['consignee'],
			'zipcode'=>$address_info['zipcode'],
			'mobile'=>$address_info['mobile'],
			'shipping_id'=>0,
			'shipping_code'=>'', 
			'shipping_name'=>'',
			'shipping_sn'=>'',
			'shipping_time'=>'',
			'add_time'=>time(),
			'gift_id'=>$params['gift_id'],
			'recommend_points'=>$gift_info['point'],
		);
		
		//生成积分订单
		M()->startTrans();
		$order_id = $this->pointGiftOrderDao()->addRecord($data);
		if ($order_id < 1){
			M()->rollback();
			throw new \Exception('兑换商品失败');
		}
		
		//生成积分订单商品记录
		$gift_order_goods=array(
								'gift_id'=>$gift_info['id'],
								'order_id'=>$order_id,
								'gift_name'=>$gift_info['name'],
								'gift_img'=>$gift_info['picture'],
								'price'=>0,
								'recommend_points'=>$gift_info['point'],
							);
		$gift_order_goods_id=$this->pointGiftOrderGoodsDao()->addRecord($gift_order_goods);
		if($gift_order_goods_id==false){
			M()->rollback();
			throw new \Exception('兑换商品失败');
		}
		
		//减去商品库存
		$stock_map=array('id'=>$params['gift_id']);
		$stock_result=$this->PointGiftDao()->where($stock_map)->setDec('stock',1);
		if($stock_result==false){
			M()->rollback();
			throw new \Exception('兑换积分商品失败');
		}
		
		//添加扣除记录
		$point_log_params=array('user_id'=>$params['user_id'],'point_old'=>$user_info['pay_points'],'point'=>-$gift_info['point'],'type'=>9);
		$point_log=$this->PointLogic()->add($point_log_params);
		if($point_log==false){
			M()->rollback();
			throw new \Exception('积分兑换商品记录失败');
		}
		
		M()->commit();
		
			
	}	
	
	//快递发货
	public function shipping($params){
		if(empty($params['shipping_id']) || empty($params['order_id']) || empty($params['shipping_sn'])){throw new \Exception('缺少参数');}
		$map=array('id'=>$params['order_id'],'shipping_status'=>self::shipping_wait);
		$order_info=$this->pointGiftOrderDao()->findRecord($map);
		if(empty($order_info)){throw new \Exception('兑换商品订单不存在');}
		
		$shipping_info=$this->shippingInfoDao()->getRecord($params['shipping_id']);
		if(empty($shipping_info)){
			throw new \Exception('快递不存在');
		}
		
		$send_time=time();
		
		$data=array(
					'shipping_status'=>self::shipping_send,
					'shipping_id'=>$params['shipping_id'],
					'shipping_code'=>$shipping_info['shipping_code'],
					'shipping_name'=>$shipping_info['shipping_name'],
					'shipping_sn'=>$params['shipping_sn'],
					'shipping_time'=>$send_time,
					'id'=>$params['order_id'],
					);
		
		$result=$this->pointGiftOrderDao()->saveRecord($data);
		if($result==false){
			throw new \Exception('兑换商品订单发货失败');
		}
		return $send_time;
	} 
	
	//确认收货
	public function delivery(){
		if(empty($params['id'])){throw new \Exception('缺少参数');}
		$map=array('id'=>$params['id'],'shipping_status'=>self::shipping_send);
		$order_info=$this->pointGiftOrderDao()->findRecord($map);
		if(empty($order_info)){throw new \Exception('兑换商品订单不存在');}
		
		$data=array(
					'shipping_status'=>self::shipping_take_delivery,
					'delivery_time'=>time(),
					'id'=>$params['id'],
					);
		
		$result=$this->pointGiftOrderDao()->saveRecord($data);
		if($result==false){
			throw new \Exception('兑换商品订单确认收货失败');
		}
	} 
	
	public function orderDelete($id){
		if(empty($id)){throw new \Exception('缺少参数');}
		$info=$this->getInfo($id);
		if(empty($info)){throw new \Exception('查询不到记录');}
		$map=array('id'=>$id);
		$result = $this->pointGiftOrderDao()->deleteRecord($map);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	//分页查询
	public function orderPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		
		if(!empty($params['map'])){
			$map=array_merge($map,$params['map']);
		}
		
		if($params['is_going']==1){
			$map['is_close']=0;
			$map['start_time']=array('lt',time());
			$map['end_time']=array('gt',time());
		}
		
		$pointGiftOrderDao = $this->pointGiftOrderDao();
		$count = $pointGiftOrderDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? ' id DESC' : $params['orderby'];
			$list = $pointGiftOrderDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
			foreach($list as $key=>$val){
				$user_id[]=$val['user_id'];
				$gift_goods[]=$val['gift_id'];
			}
			if(!empty($gift_goods)){
				$gift_list=$this->PointGiftDao()->getFieldRecord(array('id'=>array('in',$gift_goods)),'id,name,picture,point');
			}
			if(!empty($user_id)){
				$user_list=$this->userDao()->getFieldRecord(array('user_id'=>array('in',$user_id)),'user_id,nick_name');
			}
			foreach($list as $key=>$val){
				$list[$key]['gift_name']=$gift_list[$val['gift_id']]['name'];
				$list[$key]['nick_name']=$user_list[$val['user_id']];
				$list[$key]['order_goods']=$gift_list[$val['gift_id']];
			}
			
		}
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
		);
	}
	
	//分页查询
	public function orderGoodsPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		
		if(!empty($params['map'])){
			$map=array_merge($map,$params['map']);
		}
		
		
		$pointGiftOrderGoodsDao = $this->pointGiftOrderGoodsDao();
		$count = $pointGiftOrderGoodsDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? ' id DESC' : $params['orderby'];
			$list = $pointGiftOrderGoodsDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
			
		}
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
		);
	}
	
	private function pointGiftOrderDao(){
		return D('Common/Information/Point/GiftOrder');
	}
	
	private function pointGiftOrderGoodsDao(){
		return D('Common/Information/Point/GiftOrderGoods');
	}
	
	private function shippingInfoDao(){
		return D('Common/Shipping/Info');
	}
	
	private function userAddressDao(){
		return D('Common/Information/User/Address');
	}
	private function userDao(){
		return D('Common/Information/User/Info');
	}
	
	private function PointLogic(){
		return D('Point','Logic');
	}
	
	private function regionDao(){
		return D('Region');
	}
}//end HelpService!