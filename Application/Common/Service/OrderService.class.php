<?php
namespace Common\Service;

use Think\Model;
use Common\Basic\Status;
use Common\Model\OrderInfo;
use Common\Model\Shipping;
use Common\Basic\MessageConst;

class OrderService{
	const OrderStatusNone = 0;
	const OrderStatusPaid = 1;
	const OrderStatusShipping = 2;
	private $system_config;
	
	public function __construct(){
		$this->system_config = $this->configService()->findConfigs('system');
	}
	
	public function getOrderList($params, $page, $pagesize){
		//$this->userCheck($map);
		
		$map = $params['map'] ? $params['map'] : array();
		if (!empty($params['order_id'])) {
			$map['order_id'] = array('like', '%'.trim($params['order_id']).'%');
		}
		if (!empty($params['consignee'])) {
			$map['consignee'] = array('like', '%'.trim($params['consignee']).'%');
		}
		if (!empty($params['mobile'])) {
			$map['mobile'] = array('like', '%'.trim($params['mobile']).'%');
		}
		if (!empty($params['custom_order_status'])) {
			$map['custom_order_status'] = $params['custom_order_status'];
		}
		if(!empty($params['start_time'])){
			$map['add_time'][] = array('egt', strtotime($params['start_time']));
		}
		if(!empty($params['end_time'])){
			$map['add_time'][] = array('elt', strtotime($params['end_time']) + 86400);
		}
		if (!empty($params['user_id'])) {
			$map['user_id'] = $params['user_id'];
		}
		if (!empty($params['distributor_id'])) {
			$map['distributor_id'] = $params['distributor_id'];
		}
		if ($params['is_commission']==1) {
			$map['is_commission'] = 1;
		}
		if($params['order_type']){
			if($params['order_type'] == 'all'){
				unset($map['order_type']);
			}else {
				$map['order_type'] = $params['order_type'];
			}
		}
		if ($params['keyword']) {
			$map_user['nick_name|real_name|mobile'] = array('like', '%'.trim($params['keyword']).'%');
			$user_list = $this->userInfoDao()->where($map_user)->getField('user_id, nick_name, real_name, mobile');
			if ($user_list) {
				$user_ids = array_keys($user_list);
				$where['user_id'] = array('in', $user_ids);
				$where['order_id'] = array('like', '%'.trim($params['keyword']).'%');
				$where['_logic'] = 'or';
				$map['_complex'] = $where;
			}else {
				$map['order_id'] = array('like', '%'.trim($params['keyword']).'%');
			}
		}
		
		$orderInfoDao = $this->orderInfoDao();
		$count = $orderInfoDao->where($map)->count();
		if ($count) {
			$list = $orderInfoDao->where($map)->order('order_id DESC')->page($page,$pagesize)->select();
			foreach($list as $key=>$val){
				$order_ids[$val['order_id']]=$val['order_id'];
			}
			if($params['_needCommision']==1){//需要分利字段
				if(!empty($order_ids)){
					$commision_map=array('ref_id'=>array('in',$order_ids),'change_type'=>11,'ref_user_id'=>$val['user_id']);
					$commision_list=$this->userAccountDao()->getFieldRecord($commision_map,'ref_id,amount_change');
				}
				foreach($list as $key=>$val){
					$list[$key]['commision']=$commision_list[$val['order_id']];
				}
			}
			if($params['_needStockPrice']==1){
				foreach($order_ids as $key=>$val){
					$order_goods_map=array('order_id'=>$val);		
					$d_products_map=array('og.order_id'=>$val);
					$stock_price=$this->distributorGoodsProductDao()->getStockPriceAmount($d_products_map);
					$stock_price_list[$key]=$stock_price;
				}
				$all_order_id = $orderInfoDao->where($map)->getField("order_id",true);
				
				$d_products_map=array('og.order_id'=>array('in',$all_order_id));
				$stock_amount=$this->distributorGoodsProductDao()->getStockPriceAmount($d_products_map);
			}
		}
		
		return array(
				'list'=>$this->outputForList($list),
				'count'=>$count,
				'stock_price_list'=>$stock_price_list,
				'stock_amount'=>$stock_amount,
		);
	}
	
	public function selectDistinctUserid($map) {
		return $this->orderInfoDao()->searchDistinctUserid($map);
	}
	
	public function orderStatistics($map){
		$orderInfoDao = $this->orderInfoDao();
		$count = $orderInfoDao->where($map)->count();
		$list = array();
		if ($count) {
			$field = 'FROM_UNIXTIME(add_time,"%Y/%m/%d") shj, sum(order_amount) as amount, COUNT(*) as num, add_time';
			$list = $orderInfoDao->field($field)->where($map)->group('shj')->order('order_id DESC')->select();
			$map['pay_status'] = Status::PayStatusPaid;
			$pay_list = $orderInfoDao->field($field)->where($map)->group('shj')->order('order_id DESC')->select();
		}
		
		return array(
				'list'=>$list,
				'pay_list'=>$pay_list,
		);
	}
	
	private function outputForList($list) {
		if (!empty($list)) {
			$user_ids = $distributor_ids = array();
			foreach ($list as $v) {
				$user_ids[] = $v['user_id'];
				$distributor_ids[] = $v['distributor_id'];
			}
			$users = $this->userInfoDao()->getUsersByIds($user_ids);
			$distributors = $this->distributorInfoDao()->getFieldRecord(array('distributor_id'=>array('in', $distributor_ids)), 'distributor_id, distributor_name');
			
			foreach ($list as $k => $v) {
				//订单状态
				if ($v['order_type'] == Status::OrderTypeCustom) {
					$list[$k]['status'] = Status::$customOrderStatusList[$v['custom_order_status']];
				}else {
					$list[$k]['status'] = OrderInfo::statusLabel($v['order_status'], $v['pay_status'], $v['shipping_status'], $v['confirm_type']);
				}
				$list[$k]['nick_name'] = $users[$v['user_id']]['nick_name'];
				$list[$k]['distributor_name'] = $distributors[$v['distributor_id']];
				
				/* 支付状态 */
				$list[$k]['pay_status_label'] = Status::$payStatusList[$v['pay_status']];
				$list[$k]['custom_pay_status_label'] = Status::$customPayStatusList[$v['custom_pay_status']]; //定制订单
				
				//已过7天的订单不可退换货
				$return_deadline = $this->system_config['return_deadline'];
				$return_deadline_time = NOW_TIME - $return_deadline * 86400;
				$list[$k]['can_back'] = ($v['confirm_time'] >= $return_deadline_time) ? 1 : 0;
				
				//订单商品
				$commission = $stock_price = 0;
				$gmap = array(
						'order_id'=>$v['order_id']
				);
				$order_goods = $this->orderGoodsDao()->where($gmap)->select();
				$order_goods_ids = array();
				foreach ($order_goods as $k2 => $v2) {
					if ($v2['extension_code']) {
						$order_goods[$k2]['extension_code'] = $v2['extension_code'] ? unserialize($v2['extension_code']) : array();
						$order_goods[$k2]['distribution'] = $v2['distribution'] ? unserialize($v2['distribution']) : array();
					}
					
					//可否申请维修
					$can_repair = 0;
					$repair_time = $v['confirm_time'] + $v2['repair_time'] * 86400;
					if ( ($repair_time > NOW_TIME) && ($v2['repair_status'] == 0) ) {
						$can_repair = 1;
					}
					$order_goods[$k2]['can_repair'] = $can_repair;
					
					//佣金
					if ($v2['distribution']) {
						$distribution = unserialize($v2['distribution']);
						$commission += $v2['goods_price'] * $distribution['distributor_ratio'] / 100;
						$order_goods[$k2]['distribution'] = $distribution;
						$order_goods[$k2]['commission'] = $v2['goods_price'] * $v2['goods_number'] * $distribution['distributor_ratio'] / 100;
					}else {
						$order_goods[$k2]['commission'] = 0;
					}
					
					//进货价
					$stock_price += $v2['goods_price'] * $v2['goods_number'];
					
					$order_goods_ids[] = $v2['id'];
				}
				$list[$k]['order_goods'] = $order_goods;
				$list[$k]['commission'] = $commission;
				$list[$k]['stock_price'] = $stock_price;
				
				//评论数
				$comment_count = 0;
				if ($order_goods_ids) {
					$map = array('order_goods_id'=>array('in', $order_goods_ids));
					$comment_count = $this->goodsCommentDao()->where($map)->count();
				}
				$list[$k]['comment_count'] = $comment_count;
				
				//是否秒杀订单
				$is_seckill = 0;
				foreach ($order_goods as $v2) {
					if ($v2['is_seckill'] == 1) {
						$is_seckill = 1;
						break;
					}
				}
				$list[$k]['is_seckill'] = $is_seckill;
				
				//商品总数
				$total_goods_number = 0;
				foreach ($order_goods as $key => $goods) {
					$total_goods_number += $goods['goods_number'];
				}
				$list[$k]['total_goods_number'] = $total_goods_number;
				
				//查看物流
				if ($v['shipping_no']) {
					$callbackurl = DK_DOMAIN.U('user/order/shipping');
					$ship_url = 'http://m.kuaidi100.com/index_all.html?type='.$v['shipping_code'].'&postid='.$v['shipping_no'].'&callbackurl='.$callbackurl;
					$list[$k]['ship_url'] = $ship_url;
					$pc_ship_url = 'http://www.kuaidi100.com/chaxun?com='.$v['shipping_code'].'&nu='.$v['shipping_no'];
					$list[$k]['pc_ship_url'] = $pc_ship_url;
				}
				
				//分销商
				$distributor = $this->distributorInfoDao()->getRecord($v['distributor_id']);
				$list[$k]['distributor'] = $distributor;
				
				//分销商设置
				$distributor_config = $this->distributorConfigService()->findConfigs('system', $distributor['distributor_id']);
				$list[$k]['distributor_config'] = $distributor_config;
			}
		}
		
		return $list;
	}
	
	private function outputForInfo($info) {
		if (!empty($info)) {
			//订单状态
			if ($info['order_type'] == Status::OrderTypeCustom) {
				$info['status'] = Status::$customOrderStatusList[$info['custom_order_status']];
			}else {
				$info['status'] = OrderInfo::statusLabel($info['order_status'], $info['pay_status'], $info['shipping_status']);
			}
			
			/* 支付状态 */
			$info['pay_status_label'] = Status::$payStatusList[$info['pay_status']];
			$info['custom_pay_status_label'] = Status::$payStatusList[$info['pay_status']]; //定制订单
			
			//订单商品
			$gmap = array(
					'order_id'=>$info['order_id']
			);
			$order_goods = $this->orderGoodsDao()->where($gmap)->select();
			$total_goods_number = 0;
			foreach ($order_goods as $k => $v) {
				if ($v['extension_code']) {
					$order_goods[$k]['extension_code'] = unserialize($v['extension_code']);
				}
					
				$total_goods_number += $v['goods_number'];
				//发货时间
				$distributor_goods = $this->distributorGoodsDao()->getRecord($v['goods_id']);
				$order_goods[$k]['delivery_time'] = $distributor_goods['delivery_time'];
					
				//赠送积分
				$distribution_points = $commission = 0;
				if ($v['distribution']) {
					$distribution = unserialize($v['distribution']);
					$distribution_points = round($v['goods_price'] * $v['goods_number'] * $distribution['user_ratio'] / 100);
					$order_goods[$k]['distribution'] = $distribution;
					$commission = $v['goods_price'] * $v['goods_number'] * $distribution['distributor_ratio'] / 100;
				}
				$order_goods[$k]['distribution_points'] = $distribution_points;
				$order_goods[$k]['commission'] = $commission;
			}
			$info['total_goods_number'] = $total_goods_number;
			$info['order_goods'] = $order_goods;
			
			//查看物流
			if ($info['shipping_code']) {
				$callbackurl = DK_DOMAIN.U('user/order/info', array('id'=>$info['order_id']));
				$ship_url = 'http://m.kuaidi100.com/index_all.html?type='.$info['shipping_code'].'&postid='.$info['shipping_no'].'&callbackurl='.$callbackurl;
				$info['ship_url'] = $ship_url;
				$pc_ship_url = 'http://www.kuaidi100.com/chaxun?com='.$info['shipping_code'].'&nu='.$info['shipping_no'];
				$info['pc_ship_url'] = $pc_ship_url;
			}
			
			//分销商
			$distributor = $this->distributorInfoDao()->getRecord($info['distributor_id']);
			$info['distributor'] = $distributor;
		}
		
		return $info;
	}
	
	public function getOrderInfo($params) {
		$map = array('order_id'=>$params['order_id']);
		if (isset($params['user_id'])) {
			$map['user_id'] = $params['user_id'];
		}
		if (isset($params['distributor_id'])) {
			$map['distributor_id'] = $params['distributor_id'];
		}
		if(!empty($params['map'])){
			$map=array_merge($map,$params['map']);
		}
		$info = $this->orderInfoDao()->where($map)->find();
		return $this->outputForInfo($info);
	}
	
	public function findOrderInfo($map){
		$info = $this->orderInfoDao()->where($map)->find();
		return $this->outputForInfo($info);
	}
	
	public function getGeneralOrderInfo($params) {
		$orderInfoDao = $this->orderInfoDao();
	
		$map = array(
				'general_order_id'=>$params['general_order_id']
		);
		if (isset($params['user_id'])) {
			$map['user_id'] = $params['user_id'];
		}
		$list = $orderInfoDao->where($map)->select();
		if (empty($list)) {
			return $list;
		}
		
		foreach ($list as $k => $v) {
			$orders[] = $this->outputForInfo($v);
			//订单总金额
			$total_order_amount += $v['order_amount'];
			//订单总积分抵扣
			$total_points_money += $v['points_money'];
			//订单总运费
			$total_shipping_fee += $v['shipping_fee'];
			//商品服务费用
			$total_service_money += $v['service_money'];
		}
		$info['orders'] = $orders;
		$info['total_order_amount'] = $total_order_amount;
		$info['total_points_money'] = $total_points_money;
		$info['total_shipping_fee'] = $total_shipping_fee;
		$info['total_service_money'] = $total_service_money;
		$info['general_order_id']=$params['general_order_id'];
		
		return $info;
	}
	
	public function getOrderGoods($params) {
		$info = $this->orderGoodsDao()->find($params['id']);
		
		$map = array(
				'order_id'=>$info['order_id'],
				'user_id'=>$params['user_id']
		);
		$order_info = $this->orderInfoDao()->where($map)->find();
		if (empty($order_info)) {
			throw new \Exception('订单商品不存在');
		}
		
		return array(
				'info'=>$info,
				'order'=>$order_info
		);
	}
	
	public function orderGoodsList($params) {
		//订单
		$map = array('order_id'=>$params['order_id']);
		if ($params['user_id']) {
			$map['user_id'] = $params['user_id'];
		}
		$order_info = $this->orderInfoDao()->where($map)->find();
		if (empty($order_info)) {
			throw new \Exception('订单不存在');
		}
		
		//订单商品
		$map = array('order_id'=>$params['order_id']);
		return $this->orderGoodsDao()->where($map)->select();
	}
	
	public function getBackOrderGoods($params) {
		$info = $this->orderGoodsDao()->find($params['id']);
	
		$map = array(
				'order_id'=>$info['order_id'],
				'user_id'=>$params['user_id']
		);
		$order_info = $this->orderInfoDao()->where($map)->find();
		if (empty($order_info)) {
			throw new \Exception('订单商品不存在');
		}
	
		//7天后不可退换货
		$days = (NOW_TIME - $order_info['confirm_time']) / 86400;
		if ($days > $this->system_config['back_days']) {
			throw new \Exception('订单商品已过了天退货期限');
		}
	
		return $info;
	}
	
	public function getOrderCount($params){
		//$this->userCheck($params);
		
		//全部
		$map = array('order_type'=>array('in', array(Status::OrderTypeNormal, Status::OrderTypeGroup)));
		if ($params['user_id']) {
			$map['user_id'] = $params['user_id'];
		}
		if ($params['distributor_id']) {
			$map['distributor_id'] = $params['distributor_id'];
		}
		$all = $this->orderInfoDao()->where($map)->count();
		
		//已取消
		$map = array(
				'order_type'=>array('in', array(Status::OrderTypeNormal, Status::OrderTypeGroup)),
				'order_status'=>Status::OrderStatusCancel,
		);
		if ($params['user_id']) {
			$map['user_id'] = $params['user_id'];
		}
		if ($params['distributor_id']) {
			$map['distributor_id'] = $params['distributor_id'];
		}
		$cancel = $this->orderInfoDao()->where($map)->count();
		
		//待付款
		$map = array(
				'order_type'=>array('in', array(Status::OrderTypeNormal, Status::OrderTypeGroup)),
				'pay_status'=>Status::PayStatusNone,
				'order_status'=>Status::OrderStatusNone,
		);
		if ($params['user_id']) {
			$map['user_id'] = $params['user_id'];
		}
		if ($params['distributor_id']) {
			$map['distributor_id'] = $params['distributor_id'];
		}
		$nopay = $this->orderInfoDao()->where($map)->count();
		
		//待发货
		$map = array(
				'order_type'=>array('in', array(Status::OrderTypeNormal, Status::OrderTypeGroup)),
				'pay_status'=>Status::PayStatusPaid,
				'shipping_status'=>Status::ShippingStatusNone,
		);
		if ($params['user_id']) {
			$map['user_id'] = $params['user_id'];
		}
		if ($params['distributor_id']) {
			$map['distributor_id'] = $params['distributor_id'];
		}
		$wait = $this->orderInfoDao()->where($map)->count();
		
		//已发货
		$map = array(
				'order_type'=>array('in', array(Status::OrderTypeNormal, Status::OrderTypeGroup)),
				'shipping_status'=>Status::ShippingStatusDelivering,
		);
		if ($params['user_id']) {
			$map['user_id'] = $params['user_id'];
		}
		if ($params['distributor_id']) {
			$map['distributor_id'] = $params['distributor_id'];
		}
		$shipping = $this->orderInfoDao()->where($map)->count();
		
		//已收货
		$map = array(
				'order_type'=>array('in', array(Status::OrderTypeNormal, Status::OrderTypeGroup)),
				'shipping_status'=>Status::ShippingStatusReceived,
		);
		if ($params['user_id']) {
			$map['user_id'] = $params['user_id'];
		}
		if ($params['distributor_id']) {
			$map['distributor_id'] = $params['distributor_id'];
		}
		$nopj = $this->orderInfoDao()->where($map)->count();
		
		//退换货
		$map = array(
				'order_type'=>array('in', array(Status::OrderTypeNormal, Status::OrderTypeGroup)),
				'order_status'=>Status::OrderStatusOnBack
		);
		if ($params['user_id']) {
			$map['user_id'] = $params['user_id'];
		}
		if ($params['distributor_id']) {
			$map['distributor_id'] = $params['distributor_id'];
		}
		$back = $this->orderInfoDao()->where($map)->count();
		
		//退换货
		$map = array(
				'order_type'=>array('in', array(Status::OrderTypeNormal, Status::OrderTypeGroup)),
				'shipping_status'=>Status::ShippingStatusReceived,
		);
		if ($params['user_id']) {
			$map['user_id'] = $params['user_id'];
		}
		if ($params['distributor_id']) {
			$map['distributor_id'] = $params['distributor_id'];
		}
		$finish = $this->orderInfoDao()->where($map)->count();
		
		//已评价
		$map = array(
				'order_type'=>array('in', array(Status::OrderTypeNormal, Status::OrderTypeGroup)),
				'comment_status'=>Status::CommentStatusYes,
		);
		if ($params['user_id']) {
			$map['user_id'] = $params['user_id'];
		}
		if ($params['distributor_id']) {
			$map['distributor_id'] = $params['distributor_id'];
		}
		$comment = $this->orderInfoDao()->where($map)->count();
		
		$uon = array(
				'all_count'=>$all,
				'cancel'=>$cancel,
				'nopay'=>$nopay,
				'wait'=>$wait,
				'shipping'=>$shipping,
				'nopj'=>$nopj,
				'back'=>$back,
				'finish'=>$finish,
				'comment'=>$comment,
		);
		return $uon;
	}
	
	public function getCustomOrderCount($map){
		//$this->userCheck($params);
		$map['order_type'] = Status::OrderTypeCustom;
		
		//全部
		$all = $this->orderInfoDao()->where($map)->count();
		
		//待设计、测量
		$map['custom_order_status'] = Status::CustomOrderStatusDesign;
		$design = $this->orderInfoDao()->where($map)->count();
		
		//生产待审
		$map['custom_order_status'] = Status::CustomOrderStatusPendingCheck;
		$pendingcheck = $this->orderInfoDao()->where($map)->count();
		
		//成本待审
		$map['custom_order_status'] = Status::CustomOrderStatusCheckPass;
		$checkpass = $this->orderInfoDao()->where($map)->count();
		
		//待生产
		$map['custom_order_status'] = Status::CustomOrderStatusPendingProduce;
		$pendingproduce = $this->orderInfoDao()->where($map)->count();
		
		//生产中
		$map['custom_order_status'] = Status::CustomOrderStatusProducing;
		$producing = $this->orderInfoDao()->where($map)->count();
		
		//已生产
		$map['custom_order_status'] = Status::CustomOrderStatusProduced;
		$produced = $this->orderInfoDao()->where($map)->count();
		
		//已入库
		$map['custom_order_status'] = Status::CustomOrderStatusStorage;
		$storage = $this->orderInfoDao()->where($map)->count();
		
		//已发货
		$map['custom_order_status'] = Status::CustomOrderStatusShipped;
		$shipped = $this->orderInfoDao()->where($map)->count();
		
		//已安装
		$map['custom_order_status'] = Status::CustomOrderStatusInstalled;
		$installed = $this->orderInfoDao()->where($map)->count();
		
		//已完成
		$map['custom_order_status'] = Status::CustomOrderStatusFinish;
		$finish = $this->orderInfoDao()->where($map)->count();
		
		//已评论
		$map['custom_order_status'] = Status::CustomOrderStatusComment;
		$comment = $this->orderInfoDao()->where($map)->count();
		
		//回访记录
		$visit = 0;
		
		//保养记录
		$keep = 0;
		
		//缺补单
		$patch = 0;
		
		//已取消
		$map['custom_order_status'] = Status::CustomOrderStatusCancel;
		$cancel = $this->orderInfoDao()->where($map)->count();
		
		$uon = array(
				'all'=>$all,
				'design'=>$design, //待设计、测量
				'pendingcheck'=>$pendingcheck, //生产待审
				'checkpass'=>$checkpass, //成本待审
				'pendingproduce'=>$pendingproduce, //待生产
				'producing'=>$producing, //生产中
				'produced'=>$produced, //已生产
				'storage'=>$storage, //已入库
				'shipped'=>$shipped, //已发货
				'installed'=>$installed, //已安装
				'finish'=>$finish, //已完成
				'comment'=>$comment, //已评论
				'visit'=>$visit, //回访记录
				'keep'=>$keep, //保养记录
				'patch'=>$patch, //缺补单
				'cancel'=>$cancel, //已取消
		);
		return $uon;
	}
	
	public function getOrdersCount($map){
		return $this->orderInfoDao()->where($map)->count();
	}
	
	public function getOrderAmount($params){
		$map = $params['map'] ? $params['map'] : array();
		if (!empty($params['order_id'])) {
			$map['order_id'] = array('like', '%'.$params['order_id'].'%');
		}
		if(!empty($params['start_time'])){
			$map['add_time'][] = array('egt', strtotime($params['start_time']));
		}
		if(!empty($params['end_time'])){
			$map['add_time'][] = array('elt', strtotime($params['end_time']) + 86400);
		}
		if (!empty($params['user_id'])) {
			$map['user_id'] = $params['user_id'];
		}
		if (!empty($params['distributor_id'])) {
			$map['distributor_id'] = $params['distributor_id'];
		}
		if (isset($params['order_type'])) {
			$map['order_type'] = $params['order_type'];
		}
		return $this->orderInfoDao()->where($map)->sum('order_amount');
	}
	
	public function getStockAmount($params){
		$map = $params['map'] ? $params['map'] : array();
		if (!empty($params['order_id'])) {
			$map['order_id'] = array('like', '%'.$params['order_id'].'%');
		}
		if(!empty($params['start_time'])){
			$map['add_time'][] = array('egt', strtotime($params['start_time']));
		}
		if(!empty($params['end_time'])){
			$map['add_time'][] = array('elt', strtotime($params['end_time']) + 86400);
		}
		if (!empty($params['user_id'])) {
			$map['user_id'] = $params['user_id'];
		}
		if (!empty($params['distributor_id'])) {
			$map['distributor_id'] = $params['distributor_id'];
		}
		if (isset($params['order_type'])) {
			$map['order_type'] = $params['order_type'];
		}
		return $this->orderInfoDao()->where($map)->sum('stock_amount');
	}
	
	public function getOrderGoodsAmount($params){
		$map = $params['map'] ? $params['map'] : array();
		$map['order_type']=0;
		if (!empty($params['order_id'])) {
			$map['order_id'] = array('like', '%'.$params['order_id'].'%');
		}
		if(!empty($params['start_time'])){
			$map['add_time'][] = array('egt', strtotime($params['start_time']));
		}
		if(!empty($params['end_time'])){
			$map['add_time'][] = array('elt', strtotime($params['end_time']) + 86400);
		}
		if (!empty($params['user_id'])) {
			$map['user_id'] = $params['user_id'];
		}
		if (!empty($params['distributor_id'])) {
			$map['distributor_id'] = $params['distributor_id'];
		}
		return $this->orderInfoDao()->where($map)->sum('goods_amount');
	}
	
	//后台订单统计
	public  function getAdminOrderCount($params){
		//待发货
		$map = array(
				'pay_status'=>Status::PayStatusPaid,
				'shipping_status'=>Status::ShippingStatusNone,
				'distributor_id'=>$params['distributor_id']
		);
		$wait = $this->orderInfoDao()->where($map)->count();
		
		//平台待发货
		$map = array(
				'pay_status'=>Status::PayStatusPaid,
				'shipping_status'=>Status::ShippingStatusNone,
		);
		$platform_wait = $this->orderInfoDao()->where($map)->count();
		
		return array(
				'wait'=>$wait,
				'platform_wait'=>$platform_wait,
		);
	}
	
	public function createOrder($params){
		$this->userCheck($params['user']['user_id']);
		
		//检查购物车
		$l = $this->cartDao()->searchSelectedList($params['user']['user_id'], $params['act_type']);
		if(empty($l)) throw new \Exception('购物车为空');
		
		$total_goods_amount = 0;
		foreach ($l as $v) {
			$total_goods_amount += $v['cart_price'] * $v['cart_number'];
		}
		
		$list = $this->outputCartList($l, $params['user']['rank']['discount']); //分单
		
		//检查收货地址
		$address = $this->userAddressService()->findDefaultAddress($params['user']['user_id']);
		if (empty($address)) {
			M()->rollback();
			throw new \Exception('请填写收货地址');
		}
		$region = $this->regionDao()->getDistrictOfProvince($address['region_code']);
		
		//不能大于可用积分
		if ($params['pay_points'] > 0 && $params['pay_points'] > $params['user']['pay_points']) {
			M()->rollback();
			throw new \Exception('最多可使用'.$params['user']['pay_points'].'积分');
		}
		
		//总订单号
		$general_order_id = date('ymdHis').rand(1000,9999);
		
		M()->startTrans();
		
		$total_order_amount = 0;
		$pay_type = 0; //支付方式
		foreach ($list as $distributor_id => $goods_list){
			//统计金额, 保存订单商品
			$ori_amount = $goods_amount = $stock_amount = $total_number = $goods_weight = $service_amount = $shipping_fee = $pay_points = $points_money = 0;
			
			$order_id = date('ymdHis').rand(1000,9999); //订单号
			$custom_order_id = date('ymdHis').rand(1000,9999); //定制订单号
			
			//订单商品（分定制商品和普通商品）
			$data = $data_custom = array();
			foreach ($goods_list as $vo) {
				$ori_amount += $vo['ProductPrice'] * $vo['CartNumber'];
				$goods_amount += $vo['GoodsPrice'] * $vo['CartNumber'];
				$total_number += $vo['CartNumber'];
				$goods_weight += $vo['GoodsWeight'] * $vo['CartNumber'];
				$stock_amount += $vo['StockPrice'] * $vo['CartNumber']; //进货价
				
				//商品服务费用
				$service_amount += $vo['GoodsService']['price'];
				
				//货品
				$product = $this->goodsProductDao()->getRecord($vo['goods_id']);
				
				if ($vo['IsCustom'] == 1) {
					$data_custom[] = array(
							'order_id'=>$custom_order_id,
							'goods_id'=>$vo['RecordId'],
							'product_id'=>$vo['ProductId'],
							'goods_name'=>$vo['GoodsName'],
							'product_name'=>$vo['ProductName'],
							'goods_sn'=>$vo['GoodsSn'],
							'product_sn'=>$vo['ProductSn'],
							'goods_number'=>$vo['CartNumber'],
							'ori_price'=>$vo['ProductPrice'],
							'market_price'=>$vo['MarketPrice'],
							'goods_price'=>$vo['GoodsPrice'],
							'stock_price'=>$vo['StockPrice'],
							'goods_img'=>$vo['GoodsImage'],
							'distribution'=>$vo['Distribution'],
							'is_seckill'=>$vo['IsSeckill'],
							'service_id'=>$vo['GoodsService']['service_id'] ? $vo['GoodsService']['service_id'] : 0,
							'service_name'=>$vo['GoodsService']['name'] ? $vo['GoodsService']['name'] : '',
							'service_price'=>$vo['GoodsService']['price'] ? $vo['GoodsService']['price'] : 0,
							'extension_code'=>serialize(array('sku'=>$vo['Sku'])),
							'delivery_time'=>$vo['DeliveryTime'],
							'repair_time'=>$vo['RepairTime'],
					);
				}else {
					$data[] = array(
							'order_id'=>$order_id,
							'goods_id'=>$vo['RecordId'],
							'product_id'=>$vo['ProductId'],
							'goods_name'=>$vo['GoodsName'],
							'product_name'=>$vo['ProductName'],
							'goods_sn'=>$vo['GoodsSn'],
							'product_sn'=>$vo['ProductSn'],
							'goods_number'=>$vo['CartNumber'],
							'ori_price'=>$vo['ProductPrice'],
							'market_price'=>$vo['MarketPrice'],
							'goods_price'=>$vo['GoodsPrice'],
							'stock_price'=>$vo['StockPrice'],
							'goods_img'=>$vo['GoodsImage'],
							'distribution'=>$vo['Distribution'],
							'is_seckill'=>$vo['IsSeckill'],
							'service_id'=>$vo['GoodsService']['service_id'] ? $vo['GoodsService']['service_id'] : 0,
							'service_name'=>$vo['GoodsService']['name'] ? $vo['GoodsService']['name'] : '',
							'service_price'=>$vo['GoodsService']['price'] ? $vo['GoodsService']['price'] : 0,
							'extension_code'=>serialize(array('sku'=>$vo['Sku'])),
							'delivery_time'=>$vo['DeliveryTime'],
							'repair_time'=>$vo['RepairTime'],
					);
				}
				
				//减少购物车统计数量
				$distributor_goods = $this->distributorGoodsDao()->getRecord($vo['RecordId']);
				if ($distributor_goods['cart_count'] >= $vo['CartNumber'] ) {
					$result = $this->distributorGoodsDao()->where(array('record_id'=>$vo['RecordId']))->setDec('cart_count', $vo['CartNumber']);
					if($result === false) {
						M()->rollback();
						throw new \Exception('添加失败');
					}
				}
				
				//只要有分期付款的商品，订单付款方式就为分期付款
				if ($vo['PayType'] == 1) {
					$pay_type = 1;
				}
			}
			
			//运费计算
			$result = Shipping::fee($distributor_id, key($region), $goods_amount, $total_number, $goods_weight);
			$shipping_fee = $result['shipping_fee'];
			
			//积分计算
			$total_amount_points = ($goods_amount + $shipping_fee) * $params['point_exchange'];
			if ($params['pay_points'] > 0 && $params['pay_points'] > $total_amount_points) { //不能大于订单总金额
				M()->rollback();
				throw new \Exception('最多可使用'.$total_amount_points.'积分');
			}
			$points_money = $params['pay_points'] / $params['point_exchange'];
			
			//订单总金额
			$order_amount = $goods_amount + $shipping_fee - $points_money + $service_amount;
			
			//生成订单（分定制订单和普通订单）
			if ($data) {
				//插入商品
				if($this->orderGoodsDao()->addAll($data) < 1) {
					M()->rollback();
					throw new \Exception('订单提交失败');
				}
				$data_order = array(
						'order_id'=>$order_id,
						'general_order_id'=>$general_order_id,
						'distributor_id'=>$distributor_id,
						'invoice_title'=>$params['invoice_title'],
						'buyer_note'=>$params['buyer_note'],
						'user_id'=>$params['user']['user_id'],
						'consignee'=>$address['consignee'],
						'district'=>$address['region_code'],
						'address'=>$address['zone'].$address['address'],
						'mobile'=>$address['mobile'],
						'ori_amount'=>$ori_amount,
						'goods_amount'=>$goods_amount,
						'discount'=>$params['user']['rank']['discount'],
						'shipping_fee'=>$shipping_fee,
						'order_amount'=>$order_amount,
						'stock_amount'=>$stock_amount,
						'pay_points'=>$params['pay_points'],
						'points_money'=>$points_money,
						'service_money'=>$service_amount,
						'region_code'=>$params['region_code'],
						'order_type'=>Status::OrderTypeNormal, //订单类型
						'add_time'=>NOW_TIME,
						'pay_type'=>$pay_type, //支付方式（0全款，1分期）
				);
				$total_order_amount += $data_order['order_amount'];
				if($this->orderInfoDao()->add($data_order) === false) {
					M()->rollback();
					throw new \Exception('订单提交失败');
				}
				
				//订单日志
				$data = array(
						'order_id' => $custom_order_id,
						'ref_id' => $params['user']['user_id'],
						'ref_type' => Status::OrderLogRefTypeUser,
						'log_type' => Status::OrderLogTypeCreate,
						'content' => Status::$orderLogTypeList[Status::OrderLogTypeCreate],
						'add_time' => NOW_TIME,
				);
				if ($this->orderLogDao()->add($data) === false) {
					M()->rollback();
					throw new \Exception('系统错误');
				}
			}
			
			if ($data_custom) {
				//插入商品
				if($this->orderGoodsDao()->addAll($data_custom) < 1) {
					M()->rollback();
					throw new \Exception('订单提交失败');
				}
				$data_order = array(
						'order_id'=>$custom_order_id,
						'general_order_id'=>$general_order_id,
						'distributor_id'=>$distributor_id,
						'invoice_title'=>$params['invoice_title'],
						'buyer_note'=>$params['buyer_note'],
						'user_id'=>$params['user']['user_id'],
						'consignee'=>$address['consignee'],
						'district'=>$address['region_code'],
						'address'=>$address['zone'].$address['address'],
						'mobile'=>$address['mobile'],
						'ori_amount'=>$ori_amount,
						'goods_amount'=>$goods_amount,
						'discount'=>$params['user']['rank']['discount'],
						'shipping_fee'=>$shipping_fee,
						'order_amount'=>$order_amount,
						'stock_amount'=>$stock_amount,
						'pay_points'=>$params['pay_points'],
						'points_money'=>$points_money,
						'service_money'=>$service_amount,
						'region_code'=>$params['region_code'],
						'order_type'=>Status::OrderTypeCustom, //订单类型
						'add_time'=>NOW_TIME,
						'pay_type'=>$pay_type, //支付方式（0全款，1分期）
				);
				$total_order_amount += $data_order['order_amount'];
				if($this->orderInfoDao()->add($data_order) === false) {
					M()->rollback();
					throw new \Exception('订单提交失败');
				}
				
				//订单日志
				$data = array(
						'order_id' => $custom_order_id,
						'ref_id' => $params['user']['user_id'],
						'ref_type' => Status::OrderLogRefTypeUser,
						'log_type' => Status::OrderLogTypeCreate,
						'content' => Status::$orderLogTypeList[Status::OrderLogTypeCreate],
						'add_time' => NOW_TIME,
				);
				if ($this->orderLogDao()->add($data) === false) {
					M()->rollback();
					throw new \Exception('系统错误');
				}
			}
		}
		
		//扣积分
		if ($params['pay_points'] > 0) {
			$PointLogic = $this->PointLogic();
			$params_point = array(
					'user_id'=>$params['user']['user_id'],
					'point_old'=>$params['user']['pay_points'],
					'point'=>$params['pay_points'],
					'type'=>$PointLogic::PointTypePayOrder,
					//'ref_user_id'=>$params['user']['user_id'],
					'ref_id'=>$params['user']['user_id']
			);
			$result = $PointLogic->reduce($params_point);
			if ($result === false) {
				M()->rollback();
				throw new \Exception('订单提交失败');
			}
		}
		
		//删除购物车商品
		$res = $this->cartDao()->deleteCheckedOnes($params['user']['user_id'], $params['act_type']);
		if(!$res) {
			M()->rollback();
			throw new \Exception('订单提交失败');
		}
		
		M()->commit();
		
		return array(
				'general_order_id'=>$general_order_id,
				'total_order_amount'=>$total_order_amount
		);
		//使用余额支付订单
		/* $p = array(
			'user_id'=>$params['user']['user_id'],
			'order_id'=>$order_id,
			'order_amount'=>$data['order_amount'],
			'pay_id'=>$params['pay_id'],
			'shipping_fee'=>$shipping_fee,
		);
		return $this->_payOrder($p); */
	}
	
	//生成团购订单
	//只需要传user_id,还有发起团购活动id
	public function createTeamOrder($params){
		$order_type=1;
		$this->userCheck($params);
		$team_post_id=$params['team_post_id'];
		
		$team_post_info=$this->activityService()->getTeamPost($team_post_id);
		if(empty($team_post_info)){
			throw new \Exception('找不到相关团购活动');
		}
		
		//判断团购人数是否已经达到最大
		$current_joined_num=$team_post_info['joined_num']+1;
		if($current_joined_num>$team_post_info['member_limit']){
			throw new \Exception('该团购名额已满');
		}
		
		//活动是否已结束
		if($team_post_info['end_time']<time()){
			throw new \Exception('该团购已经结束');
		}
		
		//判断该用户是否已经参与该团购
		$order_check_map=array('user_id'=>$params['user_id'],'order_type'=>1,'team_post_id'=>$params['team_post_id']);
		$team_order_info=$this->findOrderInfo($order_check_map);
		if(!empty($team_order_info)){
			throw new \Exception('抱歉，您已经参加了该团购');
		}
		
		
		$distributor_goods = $this->distributorGoodsDao()->getRecord($team_post_info['goods_id']);
		$product_info=$this->distributorGoodsProductDao()->getRecord($team_post_info['product_id']);
		$goods_list=array();
		$goods_list[]=$distributor_goods;
		
		
		
		//检查收货地址
		$address = $this->userAddressService()->findDefaultAddress($params['user_id']);
		if (empty($address)) {
			throw new \Exception('请设置默认收货地址');
		}
		$region = $this->regionDao()->getDistrictOfProvince($address['region_code']);
		
		
		//总订单号
		$general_order_id = date('ymdHis').rand(1000,9999);
		
		M()->startTrans();
		
		
		//统计金额, 保存订单商品
		$goods_amount = $total_number = $goods_weight = $service_amount = $shipping_fee = $pay_points = $points_money = 0;
		$order_id = date('ymdHis').rand(1000,9999); //订单号
		
		//订单商品
		foreach ($goods_list as $vo) {
			$goods_amount = $team_post_info['price'] * 1;
			$total_number = 1;
			$goods_weight += $vo['GoodsWeight'] * 1;
			
			//商品服务费用
			$service_amount += $vo['GoodsService']['price'];
				
			$product = $this->goodsProductDao()->getRecord($vo['goods_id']);
			//$process_amount += $product['process_cost'] * $vo['CartNumber']; //加工费
			$data = array(
					'order_id'=>$order_id,
					'goods_id'=>$team_post_info['goods_id'],
					'product_id'=>$team_post_info['product_id'],
					'goods_name'=>$vo['goods_name'],
					'product_name'=>$product_info['product_name'],
					'goods_sn'=>$vo['goods_sn'],
					'goods_number'=>1,
					'goods_price'=>$team_post_info['price'],
					'market_price'=>$vo['market_price'],
					'goods_img'=>$vo['goods_image'],
					'distribution'=>'',
					
			);
			if($this->orderGoodsDao()->add($data) < 1) {
				M()->rollback();
				throw new \Exception('订单提交失败');
			}
				
			//修改销量
			/* $p = array(
					'record_id'=>$team_post_info['goods_id'],
					'goods_id'=>$vo['goods_id'],
					'goods_number'=>1,
			);
			if($this->setSale($p) === false) {
				M()->rollback();
				throw new \Exception('订单提交失败');
			} */
		}
		
		//运费计算
		//$result = Shipping::fee($distributor_id, key($region), $goods_amount, $total_number, $goods_weight);
//			$shipping_fee = $result['shipping_fee'];


        //生成订单
        $data = array(
            'order_id' => $order_id,
            'general_order_id' => $general_order_id,
            'distributor_id' => $team_post_info['distributor_id'],
            'invoice_title' => '',
            'buyer_note' => '',
            'user_id' => $params['user_id'],
            'consignee' => $address['consignee'],
            'district' => $address['region_code'],
            'address' => $address['zone'] . $address['address'],
            'mobile' => $address['mobile'],
            'goods_amount' => $goods_amount,
            //'shipping_fee'=>$shipping_fee,
            //'order_amount'=>$goods_amount + $shipping_fee - $points_money + $service_amount,
            'order_amount' => $goods_amount + $service_amount,
            //'process_amount'=>$process_amount,
            'pay_points' => 0,
            'points_money' => 0,
            'service_money' => $service_amount,
            'add_time' => NOW_TIME,
            'order_type' => 1,
            'team_post_id' => $params['team_post_id'],
        );
        if ($this->orderInfoDao()->add($data) === false) {
            M()->rollback();
            throw new \Exception('订单提交失败');
        }


        M()->commit();

        return array(
            'general_order_id' => $general_order_id
        );
        //使用余额支付订单
        /* $p = array(
            'user_id'=>$params['user']['user_id'],
            'order_id'=>$order_id,
            'order_amount'=>$data['order_amount'],
            'pay_id'=>$params['pay_id'],
            'shipping_fee'=>$shipping_fee,
        );
        return $this->_payOrder($p); */
    }

    //成团之后把拼团的成员订单的order_type设置为1
    public function changeTeamOrderType($post_id)
    {
        $team_post_info = $this->activityService()->getTeamPost($post_id);

        if (empty($team_post_info)) {
            return false;
        }


        $map = array('order_type' => 1, 'team_post_id' => $post_id);
        $order_save_data = array('order_type' => 0);
        return $result = $this->orderInfoDao()->where($map)->save($order_save_data);


    }
	
    public function payOrder($params)
    {
        $this->userCheck($params);
        $info = $this->orderCheck($params);
        $params['order_amount'] = $info['order_amount'];
        return $this->_payOrder($params);
    }
    
    //支付定制订单
    public function payCustomOrder($params)
    {
    	//检查用户
    	$this->userCheck($params);
    	
    	//检查订单
    	$orderInfoDao = $this->orderInfoDao();
    	$map = array(
    			'order_id' => $params['order_id']
    	);
    	if (isset($params['user_id'])) {
    		$map['user_id'] = $params['user_id'];
    	}
    	$order_info = $orderInfoDao->where($map)->find();
    	if (empty($order_info)) {
    		throw new \Exception('订单不存在');
    	} elseif ($order_info['custom_order_status'] != Status::CustomOrderStatusPendingProduce) {
    		throw new \Exception('订单不可支付');
    	}
    	
    	//支付订单
    	$params['order_amount'] = $order_info['custom_amount'] + $order_info['service_money'];
    	if ($params['pay_id'] == 1) {
    		$params['order_id'] = 'C'.$params['order_id'];
    		$params['order_amount'] = $order_info['custom_amount'] - $order_info['order_amount'];
    		return $this->payWithUserMoney($params);
    	} elseif ($params['pay_id'] == 2) {
    		$user = $this->userInfoDao()->getRecord($params['user_id']);
    		$wxconf = $this->configService()->findWeixinConfigs();
    		$unifiedOrder = new \Common\Payment\WeixinPay\AppPay($wxconf['js_app_id'], $wxconf['mchid'], $wxconf['key']);
    		$unifiedOrder->setParameter("openid", $user['openid']);//商品描述
    		$unifiedOrder->setParameter("body", "支付定制订单");//商品描述
    		$unifiedOrder->setParameter("out_trade_no", 'C'.$params['order_id']);//订单号
    		$unifiedOrder->setParameter("total_fee", ($params['order_amount'] * 100));//总金额
    		$unifiedOrder->setParameter("notify_url", DK_DOMAIN . '/wap/weixin.php');//通知地址
    		$unifiedOrder->setParameter("trade_type", "JSAPI");//交易类型
    		$jsApiParameters = $unifiedOrder->createTradeDataForJS();
    		return array(
    				'order_id' => $params['order_id'],
    				'js_api_params' => $jsApiParameters,
    		);
    	}
    }
    
    //支付支付单
    public function payPayment($params)
    {
    	//检查用户
    	$this->userCheck($params);
    	
    	//检查支付单
    	$paymentDao = $this->paymentDao();
    	$map = array(
    			'payment_id' => $params['payment_id']
    	);
    	if (isset($params['user_id'])) {
    		$map['user_id'] = $params['user_id'];
    	}
    	$payment_info = $paymentDao->where($map)->find();
    	if (empty($payment_info)) {
    		throw new \Exception('支付单不存在');
    	} elseif ($payment_info['pay_status'] == 1) {
    		throw new \Exception('支付单已支付');
    	}
    	
    	//支付订单
    	if ($params['pay_id'] == 1) {
    		$user = $this->userInfoDao()->getRecord($payment_info['user_id']);
    		if (empty($user) || $user['user_money'] < $payment_info['pay_amount']) throw new \Exception('余额不足');
    		
    		M()->startTrans();
    		
    		//扣款
    		if ($this->userInfoDao()->depleteMoney($params['user_id'], $payment_info['pay_amount']) < 1) {
    			M()->rollback();
    			throw new \Exception('订单支付失败');
    		}
    		
    		//扣款日志
    		$params['money'] = $payment_info['pay_amount'];
    		if ($this->userAccountDao()->payOrderByUserMoney($params) < 1) {
    			M()->rollback();
    			throw new \Exception('订单支付失败');
    		}
    		
    		M()->commit();
    		
    		$params = array(
    				'payment_id'=>$payment_info['payment_id'],
    				'money_paid'=>$params['money_paid'],
    				'pay_id'=>$params['pay_id'],
    		);
    		return $this->paymentSuccess($params);
    	} elseif ($params['pay_id'] == 2) {
    		$user = $this->userInfoDao()->getRecord($params['user_id']);
    		$wxconf = $this->configService()->findWeixinConfigs();
    		$unifiedOrder = new \Common\Payment\WeixinPay\AppPay($wxconf['js_app_id'], $wxconf['mchid'], $wxconf['key']);
    		$unifiedOrder->setParameter("openid", $user['openid']);//商品描述
    		$unifiedOrder->setParameter("body", "支付定制订单");//商品描述
    		$unifiedOrder->setParameter("out_trade_no", $payment_info['payment_id']);//支付单号
    		$unifiedOrder->setParameter("total_fee", ($payment_info['pay_amount'] * 100));//总金额
    		$unifiedOrder->setParameter("notify_url", DK_DOMAIN . '/wap/weixin.php');//通知地址
    		$unifiedOrder->setParameter("trade_type", "JSAPI");//交易类型
    		$jsApiParameters = $unifiedOrder->createTradeDataForJS();
    		return array(
    				'order_id' => $params['order_id'],
    				'js_api_params' => $jsApiParameters,
    		);
    	}
    }
	
    public function payOrderNow($params)
    {
        $this->userCheck($params);
        $map = array(
            'general_order_id' => $params['general_order_id'],
            'user_id' => $params['user_id']
        );
        $list = $this->orderInfoDao()->where($map)->select();
		
        M()->startTrans();
        foreach ($list as $v) {
            $params['order_id'] = $v['order_id'];
            $params['order_amount'] = $v['order_amount'];
            $params['pay_points'] = $v['pay_points'];
            $params['order_type'] = $v['order_type'];
            $params['team_post_id'] = $v['team_post_id'];

            $this->orderCheck($params);
            $result = $this->_payOrder($params);
            if ($result === false) {
                M()->rollback();
                throw new \Exception('支付失败');
            }
        }
        M()->commit();
        return true;
    }

    private function _payOrder($params)
    {
        if ($params['pay_id'] == 1) {
            return $this->payWithUserMoney($params);
        } elseif ($params['pay_id'] == 2) {
            $user = $this->userInfoDao()->getRecord($params['user_id']);
            $wxconf = $this->configService()->findWeixinConfigs();
            $unifiedOrder = new \Common\Payment\WeixinPay\AppPay($wxconf['js_app_id'], $wxconf['mchid'], $wxconf['key']);
            $unifiedOrder->setParameter("openid", $user['openid']);//商品描述
            $unifiedOrder->setParameter("body", "支付商城商品");//商品描述
            $unifiedOrder->setParameter("out_trade_no", $params['order_id']);//订单号
            $unifiedOrder->setParameter("total_fee", ($params['order_amount'] * 100));//总金额
            $unifiedOrder->setParameter("notify_url", DK_DOMAIN . '/wap/weixin.php');//通知地址
            $unifiedOrder->setParameter("trade_type", "JSAPI");//交易类型
            //var_dump($unifiedOrder);
            $jsApiParameters = $unifiedOrder->createTradeDataForJS();
            //var_dump($jsApiParameters);exit;
            return array(
                'order_id' => $params['order_id'],
                'js_api_params' => $jsApiParameters,
            );
        }
    }

    private function payWithUserMoney($params)
    {
        $user = $this->userInfoDao()->getRecord($params['user_id']);
        if (empty($user) || $user['user_money'] < $params['order_amount']) throw new \Exception('余额不足');
		
        M()->startTrans();
		
        //扣款
        if ($this->userInfoDao()->depleteMoney($params['user_id'], $params['order_amount']) < 1) {
            M()->rollback();
            throw new \Exception('订单支付失败');
        }
		
        //扣款日志
        $params['money'] = $params['order_amount'];
        if ($this->userAccountDao()->payOrderByUserMoney($params) < 1) {
            M()->rollback();
            throw new \Exception('订单支付失败');
        }
		
        M()->commit();
		
        $params['user_money'] = $user['user_money'];
        return $this->dealAfterPay($params);
    }

    //支付之后处理的程序
    public function dealAfterPay($params)
    {
    	$order_id = str_replace('C', '', $params['order_id']);
    	$order_info = $this->orderInfoDao()->getRecord($order_id);
    	if (empty($order_info)) throw new \Exception('订单不存在');
    	
        //事务处理
        M()->startTrans();
		
        //修改订单
        if ($this->setPaid($params) === false) {
            M()->rollback();
            throw new \Exception('订单支付失败');
        }
        
        //修改库存
        if ($this->dealStockNum($params) === false) {
            M()->rollback();
            throw new \Exception('订单支付失败');
        }
		
        //判断如果是团购的，就修改团购的信息
        if ($order_info['order_type'] == 1) {
            $post_team_params = array('user_id' => $params['user_id'], 'post_id' => $params['team_post_id']);
            $post_team_result = $this->ActivityService()->changePostTeam($post_team_params);
        }
        
        //支付统计
        $data = array(
        		'order_id'=>$order_info['order_id'],
        		'user_id'=>$order_info['user_id'],
        		'region_code'=>$order_info['region_code'],
        		'shop_id'=>$order_info['distributor_id'],
        );
        if (!$this->payBuyersAskDao()->create($data)){
        	throw new \Exception($this->payBuyersAskDao()->getError());
        }
        try {
        	$this->payBuyersAskDao()->add();
        } catch (\Exception $e) {
        	M()->rollback();
        	throw new \Exception($e->getMessage());
        }
		
        if (strpos($params['order_id'], 'C') !== false) {
        	//订单日志
        	$data = array(
        			'order_id' => $order_info['order_id'],
        			'ref_id' => $order_info['user_id'],
        			'ref_type' => Status::OrderLogRefTypeUser,
        			'log_type' => Status::OrderLogTypePayAll,
        			'content' => Status::$orderLogTypeList[Status::OrderLogTypePayAll],
        			'add_time' => NOW_TIME,
        	);
        	if ($this->orderLogDao()->add($data) === false) {
        		M()->rollback();
        		throw new \Exception('系统错误');
        	}
        }else {
        	//订单日志
        	$data = array(
        			'order_id' => $order_info['order_id'],
        			'ref_id' => $order_info['user_id'],
        			'ref_type' => Status::OrderLogRefTypeUser,
        			'log_type' => Status::OrderLogTypePay,
        			'content' => Status::$orderLogTypeList[Status::OrderLogTypePay],
        			'add_time' => NOW_TIME,
        	);
        	if ($this->orderLogDao()->add($data) === false) {
        		M()->rollback();
        		throw new \Exception('系统错误');
        	}
        }
        
        //发送短信
        $user_info = $this->userInfoDao()->getRecord($params['user_id']);
        $data = array(
            	'PayName' => $this->payNameLabel($params['pay_id']),
	            'UserId' => $user_info['user_id'],
        		'UserName' => $user_info['real_name'] ? $user_info['real_name'] : $user_info['nick_name'],
	            'Title' => '支付通知',
	            'MsgType' => Status::MsgTypePayOrder,
	            'OrderId' => $order_info['order_id'],
        );
        try {
            $this->smsLogic()->smsLogByTemplate(MessageConst::SmsTpOnOrderPay, $user_info['mobile'], $data);
        } catch (\Exception $e) {
            M()->rollback();
            throw new \Exception($e->getMessage());
        }
		
        M()->commit();
		
        return array(
            'OrderId' => $params['order_id'],
            'OrderAmount' => $params['order_amount'],
            'Paid' => 1,
        );
    }

    public function cancelByUser($user, $order_id)
    {
        $map = array(
            'order_id' => $order_id,
            'user_id' => $user['user_id'],
        );
        $info = M('order_info')->where($map)->find();
        if (empty($info)) throw new \Exception('订单不存在');
        $data = array(
            'order_status' => Status::OrderStatusCancel,
        );
        if ($this->orderInfoDao()->where($map)->save($data) < 1) throw new \Exception('订单取消失败');
        return true;
    }

    public function cancelByDistributor($params)
    {
        $map = array(
            'order_id' => $params['order_id'],
            'distributor_id' => $params['distributor_id'],
        );
        $info = $this->orderInfoDao()->where($map)->find();
        if (empty($info)) throw new \Exception('订单不存在');
        if ($info['order_status'] > 0 || $info['pay_status'] > 0 || $info['shipping_status'] > 0) {
            throw new \Exception('交易进行中，不能取消');
        }
		
        M()->startTrans();
		
        //修改订单状态
        $data = array(
            'order_status' => Status::OrderStatusCancel,
        );
        if ($this->orderInfoDao()->where($map)->save($data) < 1) {
            M()->rollback();
            throw new \Exception('系统错误');
        }
		
        //订单日志
        $data = array(
            'order_id' => $info['order_id'],
            'ref_id' => $params['admin_id'],
            'ref_type' => Status::OrderLogRefTypeDistributor,
            'log_type' => Status::OrderLogTypeCancel,
            'content' => Status::$orderLogTypeList[Status::OrderLogTypeCancel],
            'add_time' => NOW_TIME,
        );
        if ($this->orderLogDao()->add($data) === false) {
            M()->rollback();
            throw new \Exception('系统错误');
        }
		
        M()->commit();
    }
	
    public function ReceiveAuto($order_id)
    {
        $data = array(
            'shipping_status' => Status::ShippingStatusReceived,
            'confirm_time' => NOW_TIME,
            'confirm_type' => 1,
            'order_status' => Status::OrderStatusSuccess,
        );
        if ($this->orderInfoDao()->where(array('order_id' => $order_id))->save($data) < 1) {
            throw new \Exception('确认收货失败');
        }
    }
	
    public function ReceiveByUser($user, $order_id)
    {
        //订单
        $map = array(
            'order_id' => $order_id,
            'user_id' => $user['user_id'],
        );
        $info = M('order_info')->where($map)->find();
        if (empty($info)) throw new \Exception('订单不存在');
        if ($info['confirm_time'] > 0 || $info['pay_time'] < 1) throw new \Exception('操作失败');
		
        M()->startTrans();
		
        //标志订单已收货
        $data = array(
	            'shipping_status' => Status::ShippingStatusReceived,
	            'confirm_time' => NOW_TIME,
	            'order_status' => Status::OrderStatusSuccess,
        		'custom_order_status' => Status::CustomOrderStatusReceived,
        );
        if ($this->orderInfoDao()->where($map)->save($data) < 1) {
            M()->rollback();
            throw new \Exception('确认收货失败');
        }
		
        //处理分利
        /* $params = array(
                'user_id'=>$user['user_id'],
                'order_id'=>$info['order_id'],
                'add_time'=>$info['add_time'] //处理生日购买赠送积分用
        );
        if($this->dealCommission($params) === false) {
            M()->rollback();
            throw new \Exception('确认收货失败');
        } */
		
        //赠送抽奖机会
        $lottery = $this->lotteryInfoDao()->getRecordForUser();
        if ($lottery && $lottery['start_time'] < NOW_TIME && $lottery['end_time'] > NOW_TIME) {
            $data = array(
                'user_id' => $info['user_id'],
                'lottery_id' => $lottery['lottery_id'],
                'chance_type' => 3,
                'chance_brief' => '购物抽奖',
            );
            $result = $this->lotteryLogDao()->addRecord($data);
            if ($result === false) {
                M()->rollback();
                throw new \Exception('操作失败');
            }
        }
        
        //订单日志
        $data = array(
        		'order_id' => $order_id,
        		'ref_id' => $info['user_id'],
        		'ref_type' => Status::OrderLogRefTypeUser,
        		'log_type' => Status::OrderLogTypeReceived,
        		'content' => Status::$orderLogTypeList[Status::OrderLogTypeReceived],
        		'add_time' => NOW_TIME,
        );
        if ($this->orderLogDao()->add($data) === false) {
        	M()->rollback();
        	throw new \Exception('系统错误');
        }
		
        M()->commit();
		
        return true;
    }
	
    public function paySuccessByWeixin($params)
    {
        $params['pay_id'] = 2;
        if ($params['general_order_id']) {
            $general_order = $this->getGeneralOrderInfo($params);
            foreach ($general_order as $order) {
                $params['order_id'] = $order['order_id'];
                $this->setPaid($params);
            }
        } else {
            $this->setPaid($params);
        }
    }
    
    //处理支付支付单成功
    public function paymentSuccess($params)
    {
    	//修改支付单状态
    	$map = array('payment_id'=>$params['payment_id']);
    	$data = array(
    			'money_paid'=>$params['money_paid'],
    			'pay_id'=>$params['pay_id'],
    			'pay_name'=>$this->payNameLabel($params['pay_id']),
    			'pay_status'=>1,
    			'pay_time'=>NOW_TIME,
    	);
    	if ($this->paymentDao()->where($map)->save($data) === false) {
    		return false;
    	}
    	
    	//修改订单状态（订单是否全部支付完成）
    	$payall = 1;
    	$payment_info = $this->getPaymentInfo($params['payment_id']);
    	$payment_list = $this->getPaymentList($payment_info['order_id']);
    	foreach ($payment_list as $v) {
    		if ($v['pay_status'] == 0) {
    			$payall = 0;
    		}
    	}
    	if ($payall == 1) {
    		$map = array('order_id'=>$payment_info['order_id']);
    		$data = array(
    				'pay_id'=>$params['pay_id'],
    				'pay_name'=>$this->payNameLabel($params['pay_id']),
    				'custom_pay_status'=>Status::CustomPayStatusPaidAll,
    				'pay_time'=>NOW_TIME,
    		);
    		if ($this->orderInfoDao()->where($map)->save($data) === false) {
    			return false;
    		}
    	}
    	
    	return true;
    }
	
    private function setPaid($params)
    {
    	$map = array(
    			'order_id' => str_replace('C', '', $params['order_id']),
    	);
        
        if (strpos($params['order_id'], 'C') !== false) {
        	$data['custom_pay_status'] = Status::CustomPayStatusPaidAll;
        	$data = array(
        			'custom_pay_status' => Status::CustomPayStatusPaidAll,
        			'custom_pay_id' => $params['pay_id'],
        			'custom_pay_time' => NOW_TIME,
        			'money_paid'=>array('exp', 'money_paid'+$params['order_amount']),
        	);
        }else {
        	$data = array(
        			'order_status' => Status::OrderStatusOnWay,
        			'pay_status' => self::OrderStatusPaid,
        			'pay_id' => $params['pay_id'],
        			'pay_name' => $this->payNameLabel($params['pay_id']),
        			'pay_time' => NOW_TIME,
        			'custom_pay_status' => Status::CustomPayStatusPaid,
        			'money_paid'=>array('exp', 'money_paid'+$params['order_amount']),
        	);
        }
        if ($this->orderInfoDao()->where($map)->save($data) < 1) return false;
        return true;
    }
	
    private function dealStockNum($params)
    {
        $map = array(
            'order_id' => $params['order_id'],
        );
        $order_goods = $this->orderGoodsDao()->where($map)->select();
        foreach ($order_goods as $v) {
            $map = array(
                'id' => $v['product_id'],
            );
            //是否秒杀商品
            if ($v['is_seckill'] == 1) {
                $res = $this->distributorGoodsProductDao()->where($map)->setDec('seckill_num', $v['goods_number']);
                if (!$res) {
                    return false;
                }

                //减去总秒杀数量
                $map = array(
                    'record_id' => $v['goods_id'],
                );
                $res = $this->distributorGoodsDao()->where($map)->setDec('total_seckill_num', $v['goods_number']);
                if (!$res) {
                    return false;
                }

                //秒杀记录
                $map = array(
                    'record_id' => $v['goods_id'],
                    'seckill_start' => array('lgt', NOW_TIME),
                    'seckill_end' => array('egt', NOW_TIME),
                    'status' => 1
                );
                $seckill = $this->distributorSeckillGoodsDao($map)->find();
                if ($seckill) {
                    $data = array(
                        'seckill_id' => $seckill['seckill_id'],
                        'seckill_num' => $v['goods_number'],
                        'user_id' => $params['user_id'],
                        'add_time' => NOW_TIME
                    );
                    $res = $this->seckillLogDao()->add($data);
                    if (!$res) {
                        return false;
                    }
                }
            } else {
                $res = $this->distributorGoodsProductDao()->where($map)->setDec('stock_num', $v['goods_number']);
                if (!$res) {
                    return false;
                }
            }
            
            //统计分销商商品销量
            $map = array(
            		'record_id' => $v['goods_id'],
            );
            if ($this->distributorGoodsDao()->where($map)->setInc('sale_count', $v['goods_number']) < 1) return false;
            if ($this->distributorGoodsDao()->where($map)->setInc('total_sale_count', $v['goods_number']) < 1) return false;
            
            //统计商品销量
            $distributor_goods = $this->distributorGoodsDao()->getRecord($v['goods_id']);
            $map = array(
            		'goods_id' => $distributor_goods['goods_id'],
            );
            if ($this->goodsInfoDao()->where($map)->setInc('sale_count', $v['goods_number']) < 1) return false;
        }
        return true;
    }

    private function setSale($params)
    {
        //统计分销商商品销量
        $map = array(
            'record_id' => $params['record_id'],
        );
        if ($this->distributorGoodsDao()->where($map)->setInc('sale_count', $params['goods_number']) < 1) return false;
        if ($this->distributorGoodsDao()->where($map)->setInc('total_sale_count', $params['goods_number']) < 1) return false;
        //统计商品销量
        $map = array(
            'goods_id' => $params['goods_id'],
        );
        if ($this->goodsInfoDao()->where($map)->setInc('sale_count', $params['goods_number']) < 1) return false;
        return true;
    }

    private function setRank($params)
    {
        $user = $this->userInfoDao()->getRecord($params['user_id']);
        //自己累积等级积分
        $res = $this->userInfoDao()->increaseRankPoints($user['user_id'], $params['rank_points']);
        if ($res === false) {
            return false;
        }
		
        //处理升级
        $rank_points = $user['rank_points'] + $params['rank_points'];
        $rank_id = 0;
        $rank_list = $this->userRankService()->getAllList();
        if ($rank_list) {
            foreach ($rank_list as $rank) {
                if ($rank_points >= $rank['min_points']) {
                    $rank_id = $rank['rank_id'];
                }
            }
            $map = array('user_id' => $user['user_id']);
            if ($this->userInfoDao()->where($map)->save(array('rank_id' => $rank_id)) === false) {
                return false;
            }
        }
		
        return true;
    }

    public function dealCommission($order_id)
    {
        //订单
        $order_info = $this->orderInfoDao()->find($order_id);
        //会员
        $user = $this->userInfoDao()->getRecord($order_info['user_id']);

        //订单商品
        $user_pay_points = $distributorman_money = $salesman_pay_points = 0;
        $order_goods = $this->orderGoodsDao()->where(array('order_id' => $order_info['order_id']))->select();
        foreach ($order_goods as $v) {
            if (in_array($v['back_status'], array(0, 2, 4))) {
                $distribution = unserialize($v['distribution']);
                //普通会员积分
                if ($distribution['user_id'] > 0 && $distribution['user_ratio']) {
                    $user = $this->userInfoDao()->getRecord($distribution['user_id']);
                    $user_pay_points += round($v['goods_price'] * $v['goods_number'] * $distribution['user_ratio'] / 100);
                }
                //分销员分利
                if ($distribution['distributor_id'] > 0 && $distribution['distributor_ratio']) {
                    $distributorman = $this->userInfoDao()->getRecord($distribution['distributor_id']);
                    $distributorman_money += $v['goods_price'] * $v['goods_number'] * $distribution['distributor_ratio'] / 100;
                }
                //业务员积分
                if ($distribution['salesman_id'] > 0 && $distribution['salesman_ratio']) {
                    $salesman = $this->userInfoDao()->getRecord($distribution['salesman_id']);
                    $salesman_pay_points += round($v['goods_price'] * $v['goods_number'] * $distribution['salesman_ratio'] / 100);
                }
            }
        }

        M()->startTrans();

        //普通会员获得积分
        if ($user_pay_points > 0) {
            $PointLogic = $this->PointLogic();
            //生日购买加倍
            if (date('m/d', strtotime($user['birthday'])) == date('m/d', $order_info['add_time'])) {
                $point_config = $this->configService()->findConfigs('point');
                $user_pay_points *= $point_config['birthday'];
                $type = $PointLogic::PointTypeBirthday;
            } else {
                $type = $PointLogic::PointTypeBuyGoods;
            }
            $params = array(
                'user_id' => $user['user_id'],
                'point_old' => $user['pay_points'],
                'point' => $user_pay_points,
                'type' => $type,
                //'ref_user_id'=>$user['user_id'],
                'ref_id' => $order_info['order_id']
            );
            $result = $PointLogic->add($params);
            if ($result === false) {
                M()->rollback();
                throw new \Exception('普通会员获得消费积分失败');
            }
        }

        //分销员获得分利
        if ($distributorman_money > 0) {
            $res = $this->userInfoDao()->increaseUserMoney($distributorman['user_id'], $distributorman_money);
            if ($res === false) {
                M()->rollback();
                throw new \Exception('分销员获得分利失败');
            }
            $res = $this->userInfoDao()->increaseCommissionMoney($distributorman['user_id'], $distributorman_money);
            if ($res === false) {
                M()->rollback();
                throw new \Exception('分销员获得分利失败');
            }
            //分利日志
            $params_account = array(
                'distributor_id' => $order_info['distributor_id'],
                'user_id' => $distributorman['user_id'],
                'commission_money' => $distributorman['commission_money'],
                'money' => $distributorman_money,
                'ref_user_id' => $user['user_id'],
                'order_id' => $order_info['order_id'],
            );
            $res = $this->userAccountDao()->getCommissionMoney($params_account);
            if ($res === false) {
                M()->rollback();
                throw new \Exception('分销员获得分利日志失败');
            }
        }

        //业务员获得积分
        if ($salesman_pay_points > 0) {
            $PointLogic = $this->PointLogic();
            $params = array(
                'user_id' => $salesman['user_id'],
                'point_old' => $salesman['pay_points'],
                'point' => $salesman_pay_points,
                'type' => $PointLogic::PointTypeJuniorBuyGoods,
                'ref_user_id' => $user['user_id'],
                'ref_id' => $order_info['order_id']
            );
            $result = $PointLogic->add($params);
            if ($result === false) {
                M()->rollback();
                throw new \Exception('业务员获得积分失败');
            }
        }

        //更改订单分利状态
        $result = $this->orderInfoDao()->where(array('order_id' => $order_info['order_id']))->save(array('is_commission' => 1));
        if ($result === false) {
            M()->rollback();
            throw new \Exception('更改订单分利状态失败');
        }

        M()->commit();

        return true;
    }

    private function createUserRankLog($params)
    {
        $data = array(
            'user_id' => $params['user']['user_id'],
            'parent_id' => $params['user']['parent_id'],
            'rank_id' => $params['user']['user_rank'],
            'order_id' => $params['order_id'],
            'order_amount' => $params['order_amount'],
            'process_amount' => $params['process_amount'],
            'add_time' => NOW_TIME
        );
        $res = M('user_rank_log')->add($data);
        if (!$res) {
            return false;
        }

        return true;
    }

    private function userCheck(&$params)
    {
        $params['user_id'] = intval($params['user_id']);
        if ($params['user_id'] < 1) {
            throw new \Exception('缺少用户参数');
        }
    }

    private function orderCheck(&$params)
    {
        $orderInfoDao = $this->orderInfoDao();
        $map = array(
            'order_id' => $params['order_id']
        );
        if (isset($params['user_id'])) {
            $map['user_id'] = $params['user_id'];
        }
        $info = $orderInfoDao->where($map)->find();
        if (empty($info)) {
            throw new \Exception('订单不存在');
        } elseif ($info['pay_status'] == 1) {
            throw new \Exception('订单已支付');
        }

        //判断库存
        $order_goods = $this->orderGoodsDao()->where(array('order_id' => $params['order_id']))->select();
        foreach ($order_goods as $v) {
            $distributor_product = $this->distributorGoodsProductDao()->find($v['product_id']);
            if ($v['is_seckill'] == 1) {
                if ($distributor_product['seckill_num'] < $v['goods_number']) {
                    throw new \Exception('库存不足');
                }
            } else {
                if ($distributor_product['stock_num'] < $v['goods_number']) {
                    throw new \Exception('库存不足');
                }
            }
        }

        return $info;
    }

    private function payNameLabel($pay_id)
    {
        $l = array(
            '1' => '余额支付',
            '2' => '微信支付',
            '3' => '支付宝支付',
        );
        return $l[$pay_id];
    }

    private function outputCartList($l, $discount = 0)
    {
        if (empty($l)) {
            return $l;
        }

        $user = $this->userInfoDao()->getRecord($l[0]['user_id']);

        if ($user['user_type'] == 1) { //普通用户
            if ($user['parent_id']) {
                $map = array(
                    'user_id' => $user['parent_id'],
                    'user_type' => 2
                );
                $distributorman = $this->userInfoDao()->findUser($map);
                if ($distributorman['parent_id']) {
                    $map = array(
                        'user_id' => $distributorman['parent_id'],
                        'user_type' => 3
                    );
                    $salesman = $this->userInfoDao()->findUser($map);
                }
            }
        } elseif ($user['user_type'] == 2) { //分销员
            if ($user['parent_id']) {
                $map = array(
                    'user_id' => $user['parent_id'],
                    'user_type' => 3
                );
                $salesman = $this->userInfoDao()->findUser($map);
            }
        } elseif ($user['user_type'] == 3) { //业务员

        }
        $list = array();
        foreach ($l as $vo) {
            //分销商货品
            $distributor_product = $this->distributorGoodsProductDao()->getRecord($vo['goods_id']);
            //平台货品
            $product = $this->goodsProductDao()->getRecord($distributor_product['product_id']);
            //分销商商品
            $distributor_goods = $this->distributorGoodsDao()->getRecord($distributor_product['record_id']);
            //秒杀
            $is_seckill = $vo['is_seckill'];
            if ($distributor_goods['seckill_start'] > NOW_TIME || $distributor_goods['seckill_end'] < NOW_TIME && $distributor_goods['seckill_status'] != 1) {
                $map = array('id' => $vo['id']);
                $data = array(
                    'cart_price' => $distributor_product['product_price'],
                    'is_seckill' => 0
                );
                if ($this->cartDao()->where($map)->save($data)) {
                    $vo['cart_price'] = $distributor_product['product_price'];
                }
                $is_seckill = 0;
            }
            //分销商
            $distributor = $this->distributorInfoDao()->getRecord($distributor_goods['distributor_id']);
            //商品
            $goods = $this->goodsInfoDao()->getRecord($distributor_goods['goods_id']);
            //分成方案
            $distribution_ratio = array();
            if ($goods['distribution_id'] > 0) {
                $distribution = $this->goodsDistributionDao()->getRecord($goods['distribution_id']);
                $distribution_ratio = array(
                    'user_id' => $user['user_id'],
                    'distributor_id' => $distributorman ? $distributorman['user_id'] : 0,
                    'salesman_id' => $salesman ? $salesman['user_id'] : 0,
                    'salesman_ratio' => $distribution['salesman_ratio'],
                    'distributor_ratio' => $distribution['distributor_ratio'],
                    'user_ratio' => $distribution['user_ratio']
                );
            }
            //商品服务
            $goods_service = array();
            if ($vo['service_id'] > 0) {
                $goods_service = $this->goodsServiceDao()->getRecord($vo['service_id']);
            }
            //最终价格
            if ($is_seckill == 1) {
                $GoodsPrice = $distributor_product['seckill_price'];
            } elseif ($discount > 0) {
                $GoodsPrice = $distributor_product['product_price'] * $discount / 100;
            } else {
                $GoodsPrice = $distributor_product['product_price'];
            }
            //sku
            $sku = $this->goodsSkuDao()->where(array('sku_id' => array('in', $distributor_product['product_items'])))->select();
            $list[$distributor_goods['distributor_id']][] = array(
                'CartId' => $vo['id'],
                'CartNumber' => $vo['cart_number'],
                'CartPrice' => $vo['cart_price'],
                'ServiceId' => $vo['service_id'],
                'IsChecked' => $vo['is_checked'],
                'IsSeckill' => $vo['is_seckill'],
				//商品服务
                'GoodsService' => $goods_service,
				//店铺商品
                'RecordId' => $distributor_goods['record_id'],
                'GoodsSn' => $distributor_goods['goods_sn'],
                'GoodsName' => $distributor_goods['goods_name'],
                'Distribution' => $distribution_ratio ? serialize($distribution_ratio) : '',
				//店铺货品
                'ProductId' => $distributor_product['id'],
                'ProductName' => $distributor_product['product_name'],
                'GoodsPrice' => $GoodsPrice,
                'ProductPrice' => $distributor_product['product_price'],
                'MarketPrice' => $distributor_product['market_price'],
                'GoodsWeight' => $distributor_product['product_weight'],
                'GoodsNumber' => $distributor_product['stock_num'],
                'GoodsImage' => $distributor_product['product_image'],
				//平台商品
                'GoodsId' => $goods['goods_id'],
                'DeliveryTime' => $goods['delivery_time'],
                'RepairTime' => $goods['repair_time'],
            	'IsCustom' => $goods['is_custom'], //是否定制商品
            	'ProductSn' => $goods['product_sn'], //商品货号
            	'PayType' => $goods['pay_type'], //支付方式（0全款，1分期）
            	//平台货品
            	'StockPrice' => $product['stock_price'], //进货价
				//sku
                'Sku' => $sku
            );
        }
        return $list;
    }

    //发货
    public function orderSend($params)
    {
    	if (empty($params['shipping_id'])) throw new \Exception('请选择物流公司');
    	if (empty($params['kd_no'])) throw new \Exception('请填写快递单号');
		
        $map = array(
            'shipping_id' => $params['shipping_id'],
        );
        $shipping_info = M('ShippingInfo')->where($map)->find();
        if (empty($shipping_info)) throw new \Exception('物流公司不存在');
		
        M()->startTrans();
		
        //修改订单状态
        $map = array(
        		'order_id' => $params['order_id'],
        );
        $data = array(
	            'order_status' => Status::OrderStatusOnWay,
	            'shipping_status' => Status::ShippingStatusDelivering,
	            'shipping_id' => $shipping_info['shipping_id'],
	            'shipping_code' => $shipping_info['shipping_code'],
	            'shipping_name' => $shipping_info['shipping_name'],
	            'shipping_no' => $params['kd_no'],
	            'shipping_time' => NOW_TIME,
        );
        $res = $this->orderInfoDao()->where($map)->save($data);
        if ($res < 1) {
            M()->rollback();
            throw new \Exception('操作失败');
        }
        
        //订单日志
        $data = array(
        		'order_id' => $params['order_id'],
        		'ref_id' => $params['admin_id'],
        		'ref_type' => Status::OrderLogRefTypeDistributor,
        		'log_type' => Status::OrderLogTypeShipping,
        		'content' => Status::$orderLogTypeList[Status::OrderLogTypeShipping],
        		'add_time' => NOW_TIME,
        );
        if ($this->orderLogDao()->add($data) === false) {
        	M()->rollback();
        	throw new \Exception('系统错误');
        }
		
        //发送短信
        $order_info = $this->orderInfoDao()->find($params['order_id']);
        $user_info = $this->userInfoDao()->getRecord($order_info['user_id']);
        $data = array(
            'UserName' => $user_info['real_name'] ? $user_info['real_name'] : $user_info['nick_name'],
            'UserId' => $user_info['user_id'],
            'Title' => '发货通知',
            'MsgType' => Status::MsgTypeShipping,
            'OrderId' => $order_info['order_id'],
        );
        try {
            $this->smsLogic()->smsLogByTemplate(MessageConst::SmsTpOnOrderSend, $user_info['mobile'], $data);
        } catch (\Exception $e) {
            M()->rollback();
            throw new \Exception($e->getMessage());
        }
		
        M()->commit();
    }

    //拼团失败退款
    public function TeamReturnMoney($post_id)
    {
        $pay_map = array('team_post_id' => $post_id, 'pay_status' => 1);
        $pay_ordr_params = array('map' => $pay_map, 'order_type' => 1);
        $pay_order_result = $this->getOrderList($pay_ordr_params);
        $order_list = $pay_order_result['list'];

        if (!empty($order_list)) {
            M()->startTrans();
            foreach ($order_list as $key => $val) {
                $user_money = $this->userInfoDao()->getFieldRecord(array('user_id' => $val['user_id']), 'user_money');

                //用户加上退回金额
                $order_bool = $this->userInfoDao()->increaseUserMoney($val['user_id'], $val['order_amount']);
                if ($order_bool == false) {
                    M()->rollback();
                    throw new \Exception('退还用户金额失败');
                    break;
                }

                //添加退款记录
                $back_params = array('user_id' => $val['user_id'], 'user_money' => $user_money, 'money' => $val['order_amount'], 'back_id' => $val['order_id']);
                $account_bool = $this->userAccountDao()->OrderTeamBack($back_params);
                if ($account_bool == false) {
                    M()->rollback();
                    throw new \Exception('添加退款记录失败');
                    break;
                }

                //退款订单的支付状态设置为已退款
                $order_save_data = array('order_id' => $val['order_id'], 'pay_status' => Status::PayStatusRepaid);
                $order_status_bool = $this->orderInfoDao()->save($order_save_data);
                if ($order_status_bool == false) {
                    M()->rollback();
                    throw new \Exception($val['order_id'] . '退款订单支付状态修改失败');
                    break;
                }

            }
            //修改拼团的退款状态
            $team_post_data = array('post_id' => $post_id, 'is_return' => 1);
            $team_post_bool = $this->teamBuyingPostDao()->saveRecord($team_post_data);
            if ($team_post_bool == false) {
                M()->rollback();
                throw new \Exception('拼团退款状态失败');
                break;
            }
            M()->commit();
        }
        return true;
    }

    //前时期   订单数量和总金额
    public function orderTotalFrontList($startTime = '', $endTime = '', $region_code = '', $distributor_id = ''){
        $where = array();
        if ($region_code) {
            $where['region_code'] = $region_code;
        }
        if ($distributor_id) {
            $where['distributor_id'] = $distributor_id;
        }
		$frontStartEntTime = frontStartEntTime($startTime, $endTime);
		$where['pay_time'] = array('between', $frontStartEntTime['starTime'] . ',' . $frontStartEntTime['endTime']);
		$where['pay_status'] = 1;
        $field = array('order_id', 'order_amount');
        $count = 0;
        $data = $this->orderInfoDao()->orderTotalList($where, $field);
        $order_amount = 0;
        foreach ($data as $key => $val) {
            $order_amount += $val['order_amount'];
        }
        $count = count($data);
        return array(
            "count" => $count,
            "order_amount" => $order_amount,
        );
    }

	public function orderUserFrontCount($startTime = '', $endTime = '', $region_code = '', $distributor_id = ''){
		$where = array();
		if ($region_code) {
			$where['region_code'] = $region_code;
		}
		if ($distributor_id) {
			$where['distributor_id'] = $distributor_id;
		}
		$frontStartEntTime = frontStartEntTime($startTime, $endTime);
		$where['pay_time'] = array('between', $frontStartEntTime['starTime'] . ',' . $frontStartEntTime['endTime']);
		$where['pay_status'] = 1;
		$count = $this->orderInfoDao()->orderUserCount($where);
		return array(
			"count" => $count,
		);
	}

	public function orderTotalSaleList($distributor_id = ''){
		$where = array();
		if ($distributor_id) {
			$where['distributor_id'] = $distributor_id;
		}
		$where['pay_status'] = 1;
		$field = array('order_id', 'order_amount');
		$count = 0;
		$data = $this->orderInfoDao()->orderTotalList($where, $field);
		$order_amount = 0;
		foreach ($data as $key => $val) {
			$order_amount += $val['order_amount'];
		}
		$count = count($data);
		return array(
			"count" => $count,
			"order_amount" => $order_amount,
		);
	}

    //后时期   订单数量和总金额
    public function orderTotalTotList($startTime = '', $endTime = '', $region_code = '', $distributor_id = ''){

        $where = array();
        if ($region_code) {
            $where['region_code'] = $region_code;
        }
        if ($distributor_id) {
            $where['distributor_id'] = $distributor_id;
        }
		$where['pay_status'] = 1;
		$toStartEntTime = toStartEntTime($startTime, $endTime);
		$where['pay_time'] = array('between', $toStartEntTime['starTime'] . ',' . $toStartEntTime['endTime']);
        $field = array('order_id', 'order_amount');
        $count = 0;
        $data = $this->orderInfoDao()->orderTotalList($where, $field);
        $order_amount = 0;
        foreach ($data as $key => $val) {
            $order_amount += $val['order_amount'];
        }
        $count = count($data);
        return array(
            "count" => $count,
            "order_amount" => $order_amount,
        );
    }

	public function orderUserToCount($startTime = '', $endTime = '', $region_code = '', $distributor_id = ''){
		$where = array();
		if ($region_code) {
			$where['region_code'] = $region_code;
		}
		if ($distributor_id) {
			$where['distributor_id'] = $distributor_id;
		}
		$toStartEntTime = toStartEntTime($startTime, $endTime);
		$where['pay_time'] = array('between', $toStartEntTime['starTime'] . ',' . $toStartEntTime['endTime']);
		$where['pay_status'] = 1;
		$count = $this->orderInfoDao()->orderUserCount($where);
		return array(
			"count" => $count,
		);
	}

	public function appOrderTotal($where = array()){
		return $this->orderInfoDao()->searchRecordsCount($where);
	}

	//前日期 订单统计
	public function analysisOrderFrontTotal($pay_status = '', $shipping_status = '', $content = '', $start_price = '', $end_price = '', $front_start_time = '', $front_end_time = '', $region_code = '', $shop_id = '', $order_status = ''){
		$where = array();
		if($pay_status){
			if($pay_status == 99){
				$where['pay_status'] = 0;
			} else {
				$where['pay_status'] = array('in', $pay_status);
			}
		}
		if($order_status){
			if($order_status == 99){
				$where['order_status'] = 0;
			} else {
				$where['order_status'] = array('in', $order_status);
			}
		}
		if($shipping_status){
			if($pay_status == 99){
				$where['shipping_status'] = 0;
			} else {
				$where['shipping_status'] = array('in', $shipping_status);
			}
		}
		if($content){
			$where[] = array(
				'order_id' => array('like','%'.$content.'%'),
				'general_order_id' => array('like','%'.$content.'%'),
				'_logic' => 'or'
			);
		}
		if($start_price && $end_price){
			$where['order_amount'] = array('between', $start_price . ',' . $end_price);
		}
		if($region_code){
			$where['region_code'] = $region_code;
		}
		if($shop_id){
			$where['distributor_id'] = $shop_id;
		}
		$frontStartEntTime = frontStartEntTime($front_start_time, $front_end_time);
		$where['pay_time'] = array('between', $frontStartEntTime['starTime'] . ',' . $frontStartEntTime['endTime']);
		return $this->orderInfoDao()->analysisOrderTotal($where);
	}

	//后日期 订单统计
	public function analysisOrderToTotal($pay_status = '', $shipping_status = '', $content = '', $start_price = '', $end_price = '', $to_start_time = '', $to_end_time = '', $region_code = '', $shop_id = '', $order_status = ''){
		$where = array();
		if($pay_status){
			if($pay_status == 99){
				$where['pay_status'] = 0;
			} else {
				$where['pay_status'] = array('in', $pay_status);
			}
		}
		if($order_status){
			if($order_status == 99){
				$where['order_status'] = 0;
			} else {
				$where['order_status'] = array('in', $order_status);
			}
		}
		if($shipping_status){
			if($pay_status == 99){
				$where['shipping_status'] = 0;
			} else {
				$where['shipping_status'] = array('in', $shipping_status);
			}
		}
		if($content){
			$where[] = array(
				'order_id' => array('like','%'.$content.'%'),
				'general_order_id' => array('like','%'.$content.'%'),
				'_logic' => 'or'
			);
		}
		if($start_price && $end_price){
			$where['order_amount'] = array('between', $start_price . ',' . $end_price);
		}
		if($region_code){
			$where['region_code'] = $region_code;
		}
		if($shop_id){
			$where['distributor_id'] = $shop_id;
		}
		$toStartEntTime = toStartEntTime($to_start_time, $to_end_time);
		$where['pay_time'] = array('between', $toStartEntTime['starTime'] . ',' . $toStartEntTime['endTime']);
		return $this->orderInfoDao()->analysisOrderTotal($where);
	}

	public function orderTypeList($content = '', $start_price = '', $end_price = '', $to_start_time = '', $to_end_time = '', $region_code = '', $shop_id = '', $page, $pagesize)
	{
		$where = array();
		if($content){
			$where[] = array(
				'order_id' => array('like','%'.$content.'%'),
				'general_order_id' => array('like','%'.$content.'%'),
				'_logic' => 'or'
			);
		}
		if($start_price && $end_price){
			$where['order_amount'] = array('between', $start_price . ',' . $end_price);
		}
		if($region_code){
			$where['region_code'] = $region_code;
		}
		if($shop_id){
			$where['distributor_id'] = $shop_id;
		}
		if($to_start_time && $to_end_time){
			$toStartEntTime = toStartEntTime($to_start_time, $to_end_time);
			$where['pay_time'] = array('between', $toStartEntTime['starTime'] . ',' . $toStartEntTime['endTime']);
		}
		$field = array('order_id','user_id','order_amount','add_time','pay_id','consignee');
		$orderBy = array();
		$count = $this->orderInfoDao()->where($where)->count();
		$data = $this->orderInfoDao()->searchOrderList($where, $field, $orderBy, $page, $pagesize);
		$_list = array();
		foreach ($data as $key => $val) {
			$_t = $val;
			$userFind = D('UserInfo')->where(array('user_id' => $val['user_id']))->field(array('nick_name','real_name','user_img','headimgurl'))->find();
			if($userFind['nick_name']){
				$_t['user_name'] = $userFind['nick_name'];
			} else {
				$_t['user_name'] = $userFind['real_name'];
			}
			if($userFind['user_img']){
				$_t['user_img'] = domain_name_url.'/upload/'.$userFind['user_img'];
			} else {
				if($userFind['headimgurl']){
					$_t['user_img'] = $userFind['headimgurl'];
				} else {
					$_t['user_img'] = domain_name_url.'public/main/images/user_default_img.jpg';
				}
			}
			$_t['add_time'] = date('Y-m-d', $val['add_time']);
			$_t['detailUrl'] = U('/Analysis/Order/orderdetail', array('order_id' => $val['order_id']));
			$_list[] = $_t;
		}
		return array(
			'list' => $_list,
			'count' => $count,
		);
	}

	public function orderFindService($order_id){
		$where = array();
		$where['order_id'] = $order_id;
		$field = array();
		$orderFind = $this->orderInfoDao()->orderFind($where, $field);
		$DistributorInfoFind = D('DistributorInfo')->where(array('distributor_id' => $orderFind['distributor_id']))->field(array('distributor_name'))->find();
		$userFind = D('UserInfo')->where(array('user_id' => $orderFind['user_id']))->field(array('nick_name','real_name','user_img','headimgurl'))->find();
		$orderFind['distributor_name'] = $DistributorInfoFind['distributor_name'];
		$orderGoods = D('OrderGoods')->where(array('order_id' => $order_id))->field(array('goods_id','goods_name'))->select();
		$_goods_id = '';
		$_goods_name_string = '';
		foreach($orderGoods as $key => $val){
			$_goods_id.=$val['goods_id'].',';
			$_goods_name_string.=$val['goods_name'].' ';
		}

		$goodsData = D('Goods')->where(array('goods_id' => array('in',$_goods_id)))->field(array('cat_id','brand_id'))->select();
		$brand_string = '';
		$cat_string = '';
		foreach($goodsData as $k => $v){
			$GoodsBrand = D('GoodsBrand')->where(array('brand_id' => $v['brand_id']))->field(array('brand_name'))->find();
			$brand_string.=$GoodsBrand['brand_name'].' ';
			$GoodsCat = D('GoodsCat')->where(array('cat_id' => $v['cat_id']))->field(array('cat_name'))->find();
			$cat_string.=$GoodsCat['cat_name'].' ';
		}
		$orderFind['goods_name'] = $_goods_name_string;
		$orderFind['brand_name'] = $brand_string;
		$orderFind['cat_name'] = $cat_string;
		if($userFind['nick_name']){
			$orderFind['user_name'] = $userFind['nick_name'];
		} else {
			$orderFind['user_name'] = $userFind['real_name'];
		}
		if($userFind['user_img']){
			$orderFind['user_img'] = domain_name_url.'/upload/'.$userFind['user_img'];
		} else {
			if($userFind['headimgurl']){
				$orderFind['user_img'] = $userFind['headimgurl'];
			} else {
				$orderFind['user_img'] = domain_name_url.'public/main/images/user_default_img.jpg';
			}
		}
		return $orderFind;
	}

	public function appOrderTotalAmount($where = array())
	{
		$data = $this->orderInfoDao()->orderTotalList($where, array('order_amount'));
		$total_amount = 0;
		foreach($data as $key => $val){
			$total_amount+=$val['order_amount'];
		}
		return $total_amount;
	}

    public function getShippingStatus()
    {
        return $this->orderInfoDao()->ShippingStatusLabel();
    }

	public function statisticsOrderList($where = array(),$params)
	{
		$field = array('order_id','user_id','order_amount','add_time');
		$count = $this->orderInfoDao()->analysisOrderTotal($where);
		$data = $this->orderInfoDao()->searchOrderList($where, $field, $orderBy = array(), $params['page'], $params['pagesize']);
		$_list = array();
		foreach($data as $key => $val){
			$_t = $val;
			$userInfoFind = $this->userInfoDao()->userInfoFind(array('user_id' => $val['user_id']), array('nick_name','real_name','user_img','headimgurl'));
			if($userInfoFind['nick_name']){
				$_t['user_name'] = $userInfoFind['nick_name'];
			} else {
				$_t['user_name'] = $userInfoFind['real_name'];
			}
			if($userInfoFind['user_img']){
				$_t['user_img'] = domain_name_url.'/upload/'.$userInfoFind['user_img'];
			} else {
				if($userInfoFind['headimgurl']){
					$_t['user_img'] = $userInfoFind['headimgurl'];
				} else {
					$_t['user_img'] = domain_name_url.'public/main/images/user_default_img.jpg';
				}
			}
			$_t['add_time'] = date('Y-m-d H:i', $val['add_time']);
			$_t['detailUrl'] = U('Statistics/Order/detail', array('order_id' => $val['order_id']));
			$_list[]  = $_t;
		}
		return array(
			'list' => $_list,
			'count'=> $count,
		);
	}

	public function orderInfoService($order_id)
	{
		$where = array();
		$field = array('order_id','distributor_id','user_id','order_amount','add_time');
		$where['order_id'] = $order_id;
		$orderFind = $this->orderInfoDao()->orderFind($where, $field);
		$orderFind['add_time'] = date('Y-m-d H:i', $orderFind['add_time']);
		return $orderFind;
	}

	public function orderGoodsListService($order_id)
	{
		$where = array();
		$field = array('goods_id','goods_name','goods_img','goods_price','goods_number');
		$where['order_id'] = $order_id;
		$data = $this->orderGoodsDao()->orderGoodsList($where, $field);
		$_list = array();
		$_brand_name = '';
		foreach($data as $key => $val){
			$_t = $val;
			$goodsFind = $this->goodsInfoDao()->goodsFind($val['goods_id'], array('cat_id','brand_id'));
			$goodsBrand = D('GoodsBrand')->where(array('brand_id' => $goodsFind['brand_id']))->field(array('brand_name'))->find();
			$_brand_name.= $goodsBrand['brand_name'].' ';
			$goodsCat = D('GoodsCat')->where(array('cat_id' =>  $goodsFind['cat_id']))->field(array('cat_name'))->find();
			$_t['cat_name'] = $goodsCat['cat_name'];
			if($val['goods_img']){
				$_t['goods_img'] = domain_name_url.'/upload/'.$val['goods_img'];
			} else {
				$_t['goods_img'] = domain_name_url.'public/main/images/user_default_img.jpg';
			}
			$_list[]  = $_t;
		}
		return array(
			'list' => $_list,
			'brand_name' => $_brand_name,
		);
	}
	
	//上传图纸、材料明细
	public function drawing($params) {
		$params_order = array('order_id'=>$params['id']);
		$order_info = $this->getOrderInfo($params_order);
		if (!in_array($order_info['custom_order_status'], array(Status::CustomOrderStatusDesign, Status::CustomOrderStatusPendingCheck, Status::CustomOrderStatusCheckNoPass))) throw new \Exception('权限不够，无法操作');
		
		//判断理由是否为空
		$is_cancel = 0;
		if ($params['is_cancel'] == 1) {
			$is_cancel = 1;
			if (empty($params['cancel_reason'])) throw new \Exception('请填写取消理由');
			
			//修改订单状态
			$data = array(
					'order_id'=>$order_info['order_id'],
					'custom_order_status'=>Status::CustomOrderStatusCancel,
					'cancel_reason'=>$params['cancel_reason'],
			);
			if ($this->orderInfoDao()->saveRecord($data) === false) {
				M()->rollback();
				throw new \Exception('系统错误');
			}
			return true;
		}
		
		M()->startTrans();
		
		//处理图纸
		$is_check = ($params['is_check'] == 1) ? 1 : 0;
		try {
			$this->deal_files($params, $is_check);
		} catch (\Exception $e) {
			M()->rollback();
			throw new \Exception($e->getMessage());
		}
		
		//处理材料明细
		try {
			$result = $this->deal_goods($params, $is_check);
		} catch (\Exception $e) {
			M()->rollback();
			throw new \Exception($e->getMessage());
		}
		
		//处理分期付款
		$params['custom_amount'] = $result['user_price'];
		try {
			$this->deal_payments($params, $order_info);
		} catch (\Exception $e) {
			M()->rollback();
			throw new \Exception($e->getMessage());
		}
		
		//修改订单状态
		$data = array(
				'order_id'=>$order_info['order_id'],
				'inner_order_id'=>$params['inner_order_id'],
				'custom_amount'=>$result['user_price'],
				'deduction_amount'=>$params['deduction_amount'], //抵扣金额
				'pay_type'=>$params['pay_type'], //付款方式
				'show_type'=>$params['show_type'], //显示方式
		);
		if ($is_check == 1) {
			$data['custom_order_status'] = Status::CustomOrderStatusPendingCheck;
		}
		if ($this->orderInfoDao()->saveRecord($data) === false) {
			M()->rollback();
			throw new \Exception('系统错误');
		}
		
		//订单日志
		$data = array(
				'order_id' => $order_info['order_id'],
				'ref_id' => $params['admin_id'],
				'ref_type' => Status::OrderLogRefTypeDistributor,
				'log_type' => Status::OrderLogTypeDrawing,
				'content' => Status::$orderLogTypeList[Status::OrderLogTypeDrawing],
				'add_time' => NOW_TIME,
		);
		if ($this->orderLogDao()->add($data) === false) {
			M()->rollback();
			throw new \Exception('系统错误');
		}
		
		M()->commit();
	}
	
	//审核资料
	public function check_drawing($params) {
		$params_order = array('order_id'=>$params['id']);
		$order_info = $this->getOrderInfo($params_order);
		if ($order_info['custom_order_status'] != Status::CustomOrderStatusPendingCheck) throw new \Exception('权限不够，无法操作');
		
		if (empty($params['custom_order_status'])) throw new \Exception('请选择审核状态');
		if (!in_array($params['custom_order_status'], array(Status::CustomOrderStatusCheckNoPass, Status::CustomOrderStatusCheckPass))) {
			throw new \Exception('审核状态不正确');
		}
		
		//判断审核理由是否为空
		if ($params['custom_order_status'] == Status::CustomOrderStatusCheckNoPass && empty($params['nopass_reason'])) throw new \Exception('请填写审核不通过理由');
		
		M()->startTrans();
		
		//处理材料明细
		try {
			$result = $this->deal_goods($params);
		} catch (\Exception $e) {
			M()->rollback();
			throw new \Exception($e->getMessage());
		}
		
		//检查出厂价
		if ($params['custom_order_status'] == Status::CustomOrderStatusCheckPass && $result['floor_price'] <= 0) {
			throw new \Exception('出厂价不能为空');
		}
		
		//修改订单状态
		$data = array(
				'order_id'=>$params['id'],
				'custom_order_status'=>$params['custom_order_status'],
				'nopass_reason'=>$params['nopass_reason'],
				'floor_price'=>$result['floor_price'],
		);
		if ($this->orderInfoDao()->saveRecord($data) === false) {
			M()->rollback();
			throw new \Exception('系统错误');
		}
		
		//订单日志
		$content = Status::$orderLogTypeList[Status::OrderLogTypeCheckDrawing];
		$content .= ($params['custom_order_status'] == Status::CustomOrderStatusCheckNoPass) ? '，审核不通过' : '，审核通过';
		$data = array(
				'order_id' => $params['id'],
				'ref_id' => $params['admin_id'],
				'ref_type' => Status::OrderLogRefTypePlatform,
				'log_type' => Status::OrderLogTypeCheckDrawing,
				'content' => $content,
				'add_time' => NOW_TIME,
		);
		if ($this->orderLogDao()->add($data) === false) {
			M()->rollback();
			throw new \Exception('系统错误');
		}
		
		M()->commit();
	}
	
	private function deal_files($params, $is_check = 1) {
		//新文件
		$dataList = array();
		if ($params['path']) {
			foreach ($params['path'] as $k => $v) {
				if ($is_check == 1) {
					if (empty($v)) throw new \Exception('请上传图纸');
					if (empty($params['file_name'][$k])) throw new \Exception('请填写图纸名称');
					if (empty($params['file_size'][$k])) throw new \Exception('请填写图纸数量');
				}
				
				if ($v) {
					$dataList[] = array(
							'order_id'=>$params['id'],
							'upload_path'=>$v,
							'file_name'=>$params['file_name'][$k],
							'file_size'=>$params['file_size'][$k],
							'remark'=>$params['remark'][$k],
					);
				}
			}
		}
		if (empty($dataList) && empty($params['files']) && $is_check == 1) throw new \Exception('请上传图纸');
		
		//更新文件
		$map = array('order_id'=>$params['id']);
		$file_list = $this->orderFileDao()->searchFieldRecords($map);
		if ($file_list) {
			if ($params['files']) {
				foreach ($params['files'] as $k => $v) {
					if ($is_check == 1) {
						if (empty($v['upload_path'])) throw new \Exception('请上传图纸');
						if (empty($v['file_name'])) throw new \Exception('请填写图纸名称');
						if (empty($v['file_size'])) throw new \Exception('请填写图纸数量');
					}
					
					$data = array(
							'file_id'=>$k,
							'upload_path'=>$v['upload_path'],
							'file_name'=>$v['file_name'],
							'file_size'=>$v['file_size'],
							'remark'=>$v['remark'],
					);
					if ($this->orderFileDao()->saveRecord($data) === false) {
						throw new \Exception('系统错误');
					}
				}
		
				//删除文件
				$del_file_ids = array();
				$file_ids = array_keys($params['files']);
				foreach ($file_list as $v) {
					if (!in_array($v['file_id'], $file_ids)) {
						$del_file_ids[] = $v['file_id'];
					}
				}
				if ($del_file_ids) {
					$map = array('file_id'=>array('in', $del_file_ids));
					if ($this->orderFileDao()->where($map)->delete() === false) {
						throw new \Exception('系统错误');
					}
				}
			}else {
				if ($this->orderFileDao()->where($map)->delete() === false) {
					throw new \Exception('系统错误');
				}
			}
		}
		
		//添加文件
		if ($dataList) {
			if ($this->orderFileDao()->addAll($dataList) === false) {
				throw new \Exception('系统错误');
			}
		}
	}
	
	private function deal_goods($params, $is_check = 1) {
		//出厂价
		$user_price = $floor_price = 0;
		
		//更新
		$map = array('order_id'=>$params['id']);
		$detail_list = $this->orderDetailDao()->searchFieldRecords($map);
		if ($params['goods']) {
			foreach ($params['goods'] as $k => $v) {
				if ($is_check == 1) {
					if (empty($v['picture'])) throw new \Exception('图片不能为空');
					if (empty($v['goods_name'])) throw new \Exception('材料名称不能为空');
					if (empty($v['color'])) throw new \Exception('颜色不能为空');
					if (intval($v['width']) == 0) throw new \Exception('总宽度不能为空');
					if (intval($v['depth']) == 0) throw new \Exception('总深度不能为空');
					if (intval($v['height']) == 0) throw new \Exception('总高度不能为空');
					//if (intval($v['area']) == 0) throw new \Exception('立面面积不能为空');
					if (intval($v['goods_number']) == 0) throw new \Exception('数量不能为空');
					if (intval($v['door_number']) == 0) throw new \Exception('趟门数量不能为空');
				}
				
				$detail_info = $this->getDetailInfo($k);
				
				$data = array(
						'detail_id'=>$k,
						'picture'=>$v['picture'],
						'goods_name'=>$v['goods_name'],
						'color'=>$v['color'],
						'width'=>$v['width'],
						'depth'=>$v['depth'],
						'height'=>$v['height'],
						'area'=>$v['width'] * $v['height'],
						'goods_number'=>$v['goods_number'],
						'door_number'=>$v['door_number'],
						//'goods_price'=>$v['goods_price'] ? $v['goods_price'] : 0,
						'remark'=>$v['remark'],
						'update_id'=>$params['admin_id'],
				);
				//店铺才有权限修改客户价
				if ($params['sys_id'] == Status::SysIdDistributor) {
					$data['user_price'] = $v['user_price'] ? $v['user_price'] : 0;
				}
				//品牌商才有权限修改成本价
				if ($params['sys_id'] == Status::SysIdBrand) {
					$data['goods_price'] = $v['goods_price'] ? $v['goods_price'] : 0;
				}
				if ($this->orderDetailDao()->saveRecord($data) === false) {
					throw new \Exception('系统错误');
				}
				
				//材料明细日志
				$content = '管理员'.$params['admin_name'].'修改了';
				$is_log = 0;
				if ($data['goods_name'] != $detail_info['goods_name']) {
					$content .= '材料名称：'.$data['goods_name'].'（原名称：'.$detail_info['goods_name'].'），';
					$is_log = 1;
				}
				if ($data['color'] != $detail_info['color']) {
					$content .= '颜色：'.$data['goods_name'].'（原颜色：'.$detail_info['goods_name'].'），';
					$is_log = 1;
				}
				if ($data['width'] != $detail_info['width']) {
					$content .= '总宽度：'.$data['width'].'（原总宽度：'.$detail_info['width'].'），';
					$is_log = 1;
				}
				if ($data['depth'] != $detail_info['depth']) {
					$content .= '总深度：'.$data['depth'].'（原总深度：'.$detail_info['depth'].'），';
					$is_log = 1;
				}
				if ($data['area'] != $detail_info['area']) {
					$content .= '立面面积：'.$data['area'].'（原立面面积：'.$detail_info['area'].'），';
					$is_log = 1;
				}
				if ($data['goods_number'] != $detail_info['goods_number']) {
					$content .= '数量：'.$data['goods_number'].'（原数量：'.$detail_info['goods_number'].'），';
					$is_log = 1;
				}
				if ($data['door_number'] != $detail_info['door_number']) {
					$content .= '趟门数量：'.$data['door_number'].'（原趟门数量：'.$detail_info['door_number'].'），';
					$is_log = 1;
				}
				if ($params['sys_id'] == Status::SysIdBrand) {
					if (intval($data['goods_price']) != intval($detail_info['goods_price'])) {
						$content .= '成本价：'.$data['goods_price'].'（原成本价：'.$detail_info['goods_price'].'），';
						$is_log = 1;
					}
				}
				if ($data['remark'] != $detail_info['remark']) {
					$old_remark = $detail_info['remark'] ? $detail_info['remark'] : '无备注';
					$new_remark = $data['remark'] ? $data['remark'] : '无备注';
					$content .= '备注：'.$new_remark.'（原备注：'.$old_remark.'）';
					$is_log = 1;
				}
				if ($is_log == 1) {
					$dataLog = array(
							'detail_id'=>$detail_info['detail_id'],
							'admin_id'=>$params['admin_id'],
							'content'=>trim($content,'，'),
							'add_time'=>NOW_TIME,
					);
					if ($this->orderDetailLogDao()->add($dataLog) === false) {
						throw new \Exception('系统错误');
					}
				}
				
				$floor_price += $v['goods_price'] * $v['goods_number'];
				$user_price += $v['user_price'] * $v['goods_number'];
			}
		
			//删除材料明细
			$del_detail_ids = array();
			$detail_ids = array_keys($params['goods']);
			foreach ($detail_list as $v) {
				if (!in_array($v['detail_id'], $detail_ids)) {
					$del_detail_ids[] = $v['detail_id'];
				}
			}
			if ($del_detail_ids) {
				$map = array('detail_id'=>array('in', $del_detail_ids));
				if ($this->orderDetailDao()->where($map)->delete() === false) {
					throw new \Exception('系统错误');
				}
			}
		}
		
		//添加
		$dataList = array();
		if ($params['goods_name']) {
			foreach ($params['goods_name'] as $k => $v) {
				if ($is_check == 1) {
					if (empty($params['picture'][$k])) throw new \Exception('图片不能为空');
					if (empty($v)) throw new \Exception('材料名称不能为空');
					if (empty($params['color'][$k])) throw new \Exception('颜色不能为空');
					if (intval($params['width'][$k]) == 0) throw new \Exception('总宽度不能为空');
					if (intval($params['depth'][$k]) == 0) throw new \Exception('总深度不能为空');
					if (intval($params['height'][$k]) == 0) throw new \Exception('总高度不能为空');
					//if (intval($params['area'][$k]) == 0) throw new \Exception('立面面积不能为空');
					if (intval($params['goods_number'][$k]) == 0) throw new \Exception('数量不能为空');
					if (intval($params['door_number'][$k]) == 0) throw new \Exception('趟门数量不能为空');
				}
				
				$data = array(
						'order_id'=>$params['id'],
						'picture'=>$params['picture'][$k],
						'goods_name'=>$v,
						'color'=>$params['color'][$k],
						'width'=>$params['width'][$k],
						'depth'=>$params['depth'][$k],
						'height'=>$params['height'][$k],
						'area'=>$params['width'][$k] * $params['height'][$k],
						'goods_number'=>$params['goods_number'][$k],
						'door_number'=>$params['door_number'][$k],
						//'goods_price'=>$params['goods_price'][$k] ? $params['goods_price'][$k] : 0,
						'remark'=>$params['remark'][$k],
						'add_id'=>$params['admin_id'],
				);
				//店铺才有权限修改客户价
				if ($params['sys_id'] == Status::SysIdDistributor) {
					$data['user_price'] = $params['user_price'][$k] ? $params['user_price'][$k] : 0;
				}
				//品牌商才有权限修改成本价
				if ($params['sys_id'] == Status::SysIdBrand) {
					$data['goods_price'] = $params['goods_price'][$k] ? $params['goods_price'][$k] : 0;
				}
				$detail_id = $this->orderDetailDao()->add($data);
				if ($detail_id === false) {
					throw new \Exception('系统错误');
				}
					
				//材料明细日志
				$remark = $data['remark'] ? $data['remark'] : '无备注';
				$content = '管理员'.$params['admin_name'].'添加材料明细，'.
						'图片：'.$data['picture'].'，'.
						'材料名称：'.$v.'，'.
						'颜色：'.$data['color'].'，'.
						'总宽度：'.$data['width'].'，'.
						'总深度：'.$data['depth'].'，'.
						'总高度：'.$data['height'].'，'.
						'立面面积：'.$data['area'].'，'.
						'数量：'.$data['goods_number'].'，'.
						'趟门数量：'.$data['door_number'].'，'.
						//'成本价：'.$data['goods_price'].'，'.
				'备注：'.$remark;
				$dataLog = array(
						'detail_id'=>$detail_id,
						'admin_id'=>$params['admin_id'],
						'content'=>$content,
						'add_time'=>NOW_TIME,
				);
				if ($this->orderDetailLogDao()->add($dataLog) === false) {
					throw new \Exception('系统错误');
				}
				
				$floor_price += $params['goods_price'][$k] * $params['goods_number'][$k];
				$user_price += $params['user_price'][$k] * $params['goods_number'][$k];
			}
		}
		
		//计算出厂价
		$map = array('order_id'=>$params['id']);
		$data = array(
				'floor_price'=>$floor_price,
				'custom_amount'=>$user_price,
		);
		if ($this->orderInfoDao()->where($map)->save($data) === false) {
			throw new \Exception('系统错误');
		}
		
		return array('floor_price'=>$floor_price, 'user_price'=>$user_price);
	}
	
	private function deal_payments($params, $order_info) {
		//总价必须大于订金
		if ($params['custom_amount'] <= $order_info['order_amount']) throw new \Exception('订单金额必须大于预付金额（付款相关）');
		
		$total_payment = 0;
		
		//更新
		$payment_list = $this->getPaymentList($order_info['order_id']);
		if ($payment_list) {
			$submit_payment_ids = array();
			if ($params['payments']) {
				foreach ($params['payments'] as $k => $v) {
					$data = array(
							'payment_id'=>$k,
							'pay_amount'=>$v['pay_amount'],
							'remark'=>$v['remark'],
					);
					if ($this->paymentDao()->saveRecord($data) === false) {
						throw new \Exception('系统错误');
					}
					$submit_payment_ids[] = $k;
					$total_payment += $v['pay_amount'];
				}
		
				//删除
				$del_payment_ids = array();
				foreach ($payment_list as $v) {
					if (!in_array($v['payment_id'], $submit_payment_ids)) {
						$del_payment_ids[] = $v['payment_id'];
					}
				}
				if ($del_payment_ids) {
					if ($this->paymentDao()->where(array('payment_id'=>array('in', $del_payment_ids)))->delete() === false) {
						throw new \Exception('系统错误');
					}
				}
			}else { //删除全部
				if ($this->paymentDao()->where(array('order_id'=>$order_info['order_id']))->delete() === false) {
					throw new \Exception('系统错误');
				}
			}
		}
		
		//添加
		$data_payment = array();
		foreach ($params['pay_amount'] as $k => $v) {
			$payment_id = date('ymdHis').rand(1000,9999);
			if ($v > 0) {
				$data_payment[] = array(
						'payment_id'=>$payment_id,
						'order_id'=>$order_info['order_id'],
						'distributor_id'=>$order_info['distributor_id'],
						'user_id'=>$order_info['user_id'],
						'pay_amount'=>$v,
				);
			}
			$total_payment += $v;
		}
		if ($data_payment) {
			if ($this->paymentDao()->addAll($data_payment) === false) {
				throw new \Exception('系统错误');
			}
		}
		
		if ($total_payment != ($params['custom_amount'] - $order_info['order_amount'] - $params['deduction_amount'])) {
			throw new \Exception('分期金额总和必须等于未付金额（付款相关）');
		}
	}
	
	//图纸
	public function getFileList($order_id) {
		$map = array('order_id'=>$order_id);
		$list = $this->orderFileDao()->searchAllRecords($map);
		foreach ($list as $k => $v) {
			$list[$k]['file_url'] = picurl($v['upload_path']);
		}
		return $list;
	}
	
	//材料明细
	public function getDetailList($order_id) {
		$map = array('order_id'=>$order_id);
		$list = $this->orderDetailDao()->searchAllRecords($map);
		$admin_ids = array();
		foreach ($list as $v) {
			$admin_ids[] = $v['add_id'];
			$admin_ids[] = $v['update_id'];
		}
		if ($admin_ids) {
			$admins = $this->adminInfoDao()->getRecords($admin_ids);
			foreach ($list as $k => $v) {
				$list[$k]['admin_add'] = $admins[$v['add_id']]['admin_name'];
				$list[$k]['admin_update'] = $admins[$v['update_id']]['admin_name'];
			}
		}
		
		return $list;
	}
	
	public function getDetailInfo($detail_id) {
		return $this->orderDetailDao()->getRecord($detail_id);
	}
	
	//材料明细日志
	public function getDetailLogList($detail_id) {
		$map = array('detail_id'=>$detail_id);
		return $this->orderDetailLogDao()->searchAllRecords($map);
	}
	
	//订单日志
	public function getLogList($order_id) {
		$map = array('order_id'=>$order_id);
		return $this->orderLogDao()->searchAllRecords($map);
	}
	
	//分期付款
	public function getPaymentList($order_id) {
		$map = array('order_id'=>$order_id);
		$list = $this->paymentDao()->searchAllRecords($map);
		foreach ($list as $k => $v) {
			$list[$k]['pay_status_label'] = Status::$payStatusList[$v['pay_status']];
		}
		return $list;
	}
	
	public function getPaymentInfo($payment_id) {
		return $this->paymentDao()->getRecord($payment_id);
	}
	
	public function findPaymentInfo($map) {
		return $this->paymentDao()->findRecord($map);
	}
	
	//生产报价
	public function offer($params) {
		$params_order = array('order_id'=>$params['id']);
		$order_info = $this->getOrderInfo($params_order);
		if ($order_info['custom_order_status'] != Status::CustomOrderStatusCheckPass) throw new \Exception('权限不够，无法操作');
		
		M()->startTrans();
		
		//处理材料明细
		try {
			$result = $this->deal_goods($params);
		} catch (\Exception $e) {
			M()->rollback();
			throw new \Exception($e->getMessage());
		}
		
		//检查出厂价
		if ($result['floor_price'] <= 0) {
			throw new \Exception('出厂价不能为空');
		}
		
		//修改订单状态
		$data = array(
				'order_id'=>$params['id'],
				'custom_order_status'=>Status::CustomOrderStatusConfirmed,
		);
		if ($this->orderInfoDao()->saveRecord($data) === false) {
			M()->rollback();
			throw new \Exception('系统错误');
		}
		
		//订单日志
		$data = array(
				'order_id' => $params['id'],
				'ref_id' => $params['admin_id'],
				'ref_type' => Status::OrderLogRefTypePlatform,
				'log_type' => Status::OrderLogTypeOffer,
				'content' => Status::$orderLogTypeList[Status::OrderLogTypeOffer],
				'add_time' => NOW_TIME,
		);
		if ($this->orderLogDao()->add($data) === false) {
			M()->rollback();
			throw new \Exception('系统错误');
		}
		
		M()->commit();
	}
	
	//确认生产报价
	public function confirm_price($params) {
		$params_order = array('order_id'=>$params['id']);
		$order_info = $this->getOrderInfo($params_order);
		if ($order_info['custom_order_status'] != Status::CustomOrderStatusConfirmed) {
			throw new \Exception('权限不够，无法操作');
		}
		if (!in_array($params['custom_order_status'], array(Status::CustomOrderStatusPendingProduce, Status::CustomOrderStatusCancel))) {
			throw new \Exception('权限不够，无法操作');
		}
		
		M()->startTrans();
		
		//修改订单状态
		$data = array(
				'order_id'=>$params['id'],
				'custom_order_status'=>$params['custom_order_status'],
		);
		if ($this->orderInfoDao()->saveRecord($data) === false) {
			M()->rollback();
			throw new \Exception('系统错误');
		}
		
		M()->commit();
	}
	
	//申请特批
	public function delay_pay($params) {
		$params_order = array('order_id'=>$params['id']);
		$order_info = $this->getOrderInfo($params_order);
		if ($order_info['custom_order_status'] != Status::CustomOrderStatusPendingProduce) throw new \Exception('权限不够，无法操作');
		
		if (empty($params['delay_reason'])) throw new \Exception('请输入申请特批原因');
		
		M()->startTrans();
		
		//修改订单状态
		$data = array(
				'order_id'=>$params['id'],
				'delay_pay'=>Status::DelayPayApply,
				'delay_reason'=>$params['delay_reason'],
		);
		if ($this->orderInfoDao()->saveRecord($data) === false) {
			M()->rollback();
			throw new \Exception('系统错误');
		}
		
		M()->commit();
	}
	
	//审核申请特批
	public function confirm_delay_pay($params) {
		$params_order = array('order_id'=>$params['id']);
		$order_info = $this->getOrderInfo($params_order);
		if ($order_info['custom_order_status'] != Status::CustomOrderStatusPendingProduce) throw new \Exception('权限不够，无法操作');
		
		if (!in_array($params['delay_pay'], array(Status::DelayPayAgree, Status::DelayPayDisagree))) throw new \Exception('请选择是否通过审核');
		
		M()->startTrans();
		
		//修改订单状态
		$data = array(
				'order_id'=>$params['id'],
				'delay_pay'=>$params['delay_pay'],
		);
		if ($this->orderInfoDao()->saveRecord($data) === false) {
			M()->rollback();
			throw new \Exception('系统错误');
		}
		
		M()->commit();
	}
	
	//确认生产
	public function confirm_produce($params) {
		$params_order = array('order_id'=>$params['id']);
		$order_info = $this->getOrderInfo($params_order);
		if ($order_info['custom_order_status'] != Status::CustomOrderStatusPendingProduce && $order_info['delay_pay'] != Status::DelayPayAgree) {
			throw new \Exception('权限不够，无法操作');
		}
		
		M()->startTrans();
		
		//修改订单状态
		$data = array(
				'order_id'=>$params['id'],
				'custom_order_status'=>Status::CustomOrderStatusProducing,
		);
		if ($this->orderInfoDao()->saveRecord($data) === false) {
			M()->rollback();
			throw new \Exception('系统错误');
		}
		
		//订单日志
		$data = array(
				'order_id' => $params['id'],
				'ref_id' => $params['admin_id'],
				'ref_type' => Status::OrderLogRefTypePlatform,
				'log_type' => Status::OrderLogTypeProduced,
				'content' => Status::$orderLogTypeList[Status::CustomOrderStatusProducing],
				'add_time' => NOW_TIME,
		);
		if ($this->orderLogDao()->add($data) === false) {
			M()->rollback();
			throw new \Exception('系统错误');
		}
		
		M()->commit();
	}
	
	//确认生产完成
	public function confirm_produced($params) {
		$params_order = array('order_id'=>$params['id']);
		$order_info = $this->getOrderInfo($params_order);
		if ($order_info['custom_order_status'] != Status::CustomOrderStatusProducing && $order_info['delay_pay'] != Status::DelayPayAgree) {
			throw new \Exception('权限不够，无法操作');
		}
	
		M()->startTrans();
	
		//修改订单状态
		$data = array(
				'order_id'=>$params['id'],
				'custom_order_status'=>Status::CustomOrderStatusProduced,
		);
		if ($this->orderInfoDao()->saveRecord($data) === false) {
			M()->rollback();
			throw new \Exception('系统错误');
		}
	
		//订单日志
		$data = array(
				'order_id' => $params['id'],
				'ref_id' => $params['admin_id'],
				'ref_type' => Status::OrderLogRefTypePlatform,
				'log_type' => Status::OrderLogTypeProduced,
				'content' => Status::$orderLogTypeList[Status::CustomOrderStatusProduced],
				'add_time' => NOW_TIME,
		);
		if ($this->orderLogDao()->add($data) === false) {
			M()->rollback();
			throw new \Exception('系统错误');
		}
	
		M()->commit();
	}
	
	//确认入库
	public function confirm_storage($params) {
		$params_order = array('order_id'=>$params['id']);
		$order_info = $this->getOrderInfo($params_order);
		if ($order_info['custom_order_status'] != Status::CustomOrderStatusProduced) throw new \Exception('权限不够，无法操作');
		
		M()->startTrans();
		
		//修改订单状态
		$data = array(
				'order_id'=>$params['id'],
				'custom_order_status'=>Status::CustomOrderStatusStorage,
		);
		if ($this->orderInfoDao()->saveRecord($data) === false) {
			M()->rollback();
			throw new \Exception('系统错误');
		}
		
		//订单日志
		$data = array(
				'order_id' => $params['id'],
				'ref_id' => $params['admin_id'],
				'ref_type' => Status::OrderLogRefTypePlatform,
				'log_type' => Status::OrderLogTypeStorage,
				'content' => Status::$orderLogTypeList[Status::OrderLogTypeStorage],
				'add_time' => NOW_TIME,
		);
		if ($this->orderLogDao()->add($data) === false) {
			M()->rollback();
			throw new \Exception('系统错误');
		}
		
		M()->commit();
	}
	
	//确认发货
	public function confirm_shipped($params) {
		$params_order = array('order_id'=>$params['id']);
		$order_info = $this->getOrderInfo($params_order);
		if ($order_info['custom_order_status'] != Status::CustomOrderStatusStorage) throw new \Exception('权限不够，无法操作');
		
		if (empty($params['shipping_id'])) throw new \Exception('请选择物流公司');
		if (empty($params['kd_no'])) throw new \Exception('请填写物流单号');
		
		$map = array(
				'shipping_id' => $params['shipping_id'],
		);
		$shipping_info = M('ShippingInfo')->where($map)->find();
		if (empty($shipping_info)) throw new \Exception('物流公司不存在');
		
		M()->startTrans();
		
		//修改订单状态
		$data = array(
				'order_id'=>$params['id'],
				'shipping_id' => $shipping_info['shipping_id'],
				'shipping_code' => $shipping_info['shipping_code'],
				'shipping_name' => $shipping_info['shipping_name'],
				'shipping_no' => $params['kd_no'],
				'shipping_time' => NOW_TIME,
				'custom_order_status'=>Status::CustomOrderStatusShipped,
		);
		if ($this->orderInfoDao()->saveRecord($data) === false) {
			M()->rollback();
			throw new \Exception('系统错误');
		}
		
		//订单日志
		$data = array(
				'order_id' => $params['id'],
				'ref_id' => $params['admin_id'],
				'ref_type' => Status::OrderLogRefTypeDistributor,
				'log_type' => Status::OrderLogTypeShipped,
				'content' => Status::$orderLogTypeList[Status::OrderLogTypeShipped],
				'add_time' => NOW_TIME,
		);
		if ($this->orderLogDao()->add($data) === false) {
			M()->rollback();
			throw new \Exception('系统错误');
		}
		
		M()->commit();
	}
	
	public function edit_shipped($params) {
		$params_order = array('order_id'=>$params['id']);
		$order_info = $this->getOrderInfo($params_order);
		if ($order_info['custom_order_status'] != Status::CustomOrderStatusShipped) throw new \Exception('权限不够，无法操作');
		
		if (empty($params['shipping_id'])) throw new \Exception('请选择物流公司');
		if (empty($params['kd_no'])) throw new \Exception('请填写物流单号');
		
		$map = array(
				'shipping_id' => $params['shipping_id'],
		);
		$shipping_info = M('ShippingInfo')->where($map)->find();
		if (empty($shipping_info)) throw new \Exception('物流公司不存在');
		
		M()->startTrans();
		
		//修改订单状态
		$data = array(
				'order_id'=>$params['id'],
				'shipping_id' => $shipping_info['shipping_id'],
				'shipping_code' => $shipping_info['shipping_code'],
				'shipping_name' => $shipping_info['shipping_name'],
				'shipping_no' => $params['kd_no'],
				'shipping_time' => NOW_TIME,
		);
		if ($this->orderInfoDao()->saveRecord($data) === false) {
			M()->rollback();
			throw new \Exception('系统错误');
		}
		
		M()->commit();
	}
	
	//指派安装人员
	public function confirm_installer($params) {
		$params_order = array('order_id'=>$params['id']);
		$order_info = $this->getOrderInfo($params_order);
		if ($order_info['custom_order_status'] != Status::CustomOrderStatusReceived) throw new \Exception('权限不够，无法操作');
		
		if (empty($params['admin_ids'])) throw new \Exception('请选择安装人员');
		
		M()->startTrans();
		
		//修改订单状态
		$data = array(
				'order_id'=>$params['id'],
				'admin_ids'=>implode(',', $params['admin_ids']),
		);
		if ($this->orderInfoDao()->saveRecord($data) === false) {
			M()->rollback();
			throw new \Exception('系统错误');
		}
		
		M()->commit();
	}
	
	//安装凭证
	public function confirm_installed($params) {
		$params_order = array('order_id'=>$params['id']);
		$order_info = $this->getOrderInfo($params_order);
		$admin_ids = explode(',', $order_info['admin_ids']);
		if ($order_info['custom_order_status'] != Status::CustomOrderStatusReceived) {
			throw new \Exception('权限不够，无法操作');
		}elseif (!in_array($params['admin_id'], $admin_ids) && $params['is_admin'] != 1) {
			throw new \Exception('权限不够，无法操作');
		}
		
		if (empty($params['install_remark'])) throw new \Exception('请填写备注内容');
		if (empty($params['install_certi'])) throw new \Exception('请上传安装凭证');
		
		M()->startTrans();
		
		//修改订单状态
		$data = array(
				'order_id'=>$params['id'],
				'custom_order_status'=>Status::CustomOrderStatusInstalled,
				'install_remark'=>$params['install_remark'],
				'install_certi'=>$params['install_certi'],
		);
		if ($this->orderInfoDao()->saveRecord($data) === false) {
			M()->rollback();
			throw new \Exception('系统错误');
		}
		
		//订单日志
		$data = array(
				'order_id' => $params['id'],
				'ref_id' => $params['admin_id'],
				'ref_type' => Status::OrderLogRefTypeDistributor,
				'log_type' => Status::OrderLogTypeInstalled,
				'content' => Status::$orderLogTypeList[Status::OrderLogTypeInstalled],
				'add_time' => NOW_TIME,
		);
		if ($this->orderLogDao()->add($data) === false) {
			M()->rollback();
			throw new \Exception('系统错误');
		}
		
		M()->commit();
	}
	
    private function userAccountDao()
    {
        return D('Common/User/UserAccount');
    }
	
    private function userInfoDao()
    {
        return D('Common/User/UserInfo');
    }

    private function goodsInfoDao()
    {
        return D('Common/Goods/Goods');
    }

    private function goodsServiceDao()
    {
        return D('Common/Goods/GoodsService');
    }

    private function goodsDistributionDao()
    {
        return D('Common/Goods/GoodsDistribution');
    }

    private function goodsProductDao()
    {
        return D('Common/Goods/GoodsProduct');
    }

    private function distributorGoodsProductDao()
    {
        return D('Common/Distributor/GoodsProduct');
    }

    private function distributorGoodsDao()
    {
        return D('Common/Distributor/Goods');
    }

    private function distributorInfoDao()
    {
        return D('Common/Distributor/Info');
    }

    private function distributorSeckillGoodsDao()
    {
        return D('Common/Distributor/SeckillGoods');
    }

    private function cartDao()
    {
        return D('Cart');
    }

    private function orderInfoDao()
    {
        return D('Common/Order/OrderInfo');
    }

    private function orderGoodsDao()
    {
        return D('Common/Order/OrderGoods');
    }

    private function orderLogDao()
    {
        return D('Common/Order/OrderLog');
    }

    private function regionDao()
    {
        return D('Region');
    }

    private function userAddressService()
    {
        return D('UserAddress', 'Service');
    }

    private function userRankService()
    {
        return D('UserRank', 'Service');
    }

    private function userRankDao()
    {
        return D('Common/User/UserRank');
    }

    private function configService()
    {
        return D('Config', 'Service');
    }

    private function PointLogic()
    {
        return D('Point', 'Logic');
    }

    private function activityService()
    {
        return D('Activity', 'Service');
    }

    private function teamBuyingPostDao()
    {
        return D('Common/Activity/TeamBuyingPost');
    }

    private function lotteryInfoDao()
    {
        return D('Common/Lottery/Info');
    }

    private function lotteryLogDao()
    {
        return D('Common/Lottery/Log');
    }

    private function goodsSkuDao()
    {
        return D('Common/Goods/GoodsSku');
    }

    private function seckillLogDao()
    {
        return D('Common/Distributor/SeckillLog');
    }

    private function goodsCommentDao()
    {
        return D('Common/Goods/GoodsComment');
    }
    
    private function payBuyersAskDao()
    {
    	return D('Common/Statistics/PayBuyersAsk');
    }

    private function smsLogic()
    {
        return D('Sms', 'Logic');
    }

    private function distributorConfigService()
    {
        return D('Distributor\Config', 'Service');
    }
    
    private function orderFileDao()
    {
    	return D('Common/Order/OrderFile');
    }
    
    private function orderDetailDao()
    {
    	return D('Common/Order/OrderDetail');
    }
    
    private function orderDetailLogDao()
    {
    	return D('Common/Order/OrderDetailLog');
    }
    
    private function paymentDao()
    {
    	return D('Common/Order/Payment');
    }
    
    private function adminInfoDao()
    {
    	return D('Common/Admin/AdminInfo');
    }
}