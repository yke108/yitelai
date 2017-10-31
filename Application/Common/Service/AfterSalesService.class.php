<?php
namespace Common\Service;
use Common\Basic\Status;

class AfterSalesService{
	private $afterSalesDao;
	
	static $status_show = array(
			0=>'未审核',
			1=>'审核通过',
			2=>'审核不通过',
			3=>'退货中',
			4=>'退货不通过',
			5=>'退货通过',
			6=>'已退款',
	);
	
	public function __construct(){
		$this->afterSalesDao = D('AfterSales');
	}
	
	public function getInfo($id){
		if ($id < 1) return false;
		$info = $this->afterSalesDao->getRecord($id);
		return $this->outputForInfo($info);
	}
	
	public function findInfo($map){
		$info = $this->afterSalesDao->findRecord($map);
		return $this->outputForInfo($info);
	}
	
	public function getInfoByItemId($id){
		if ($id < 1) return false;
		$map = array('item_id'=>$id);
		$info = $this->afterSalesDao->findRecord($map);
		return $this->outputForInfo($info);
	}
	
	public function createAfterSale($params){
		//如果超过退货期限不能退货
		$system_config = $this->configService()->findConfigs('system');
		$return_deadline = $system_config['return_deadline'];
		$map = array(
				'order_id'=>$params['order_id'],
				'user_id'=>$params['user_id']
		);
		$order_info = $this->orderInfoDao()->where($map)->find();
		$confirm_days = ceil((NOW_TIME - $order_info['confirm_time']) / 86400);
		if ($confirm_days > $return_deadline) {
			throw new \Exception('已超过退货期限');
		}
		
		// 接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		//退货单号
		$data['id'] = date('ymdHis').rand(1000,9999);
		$data['region_code'] = $order_info['region_code'];
		$data['add_time'] = NOW_TIME;
		
		if (!$this->afterSalesDao->create($data)){
			 throw new \Exception($this->afterSalesDao->getError());
		}
		
		M()->startTrans();
		
		$result = $this->afterSalesDao->add();
		if (!$result){
			M()->rollback();
			throw new \Exception('申请售后失败');
		}
		
		//更新退货状态
		$map = array('id'=>$data['item_id']);
		$data = array('back_status'=>1);
		if ($this->orderGoodsDao()->where($map)->save($data) === false) {
			M()->rollback();
			throw new \Exception('更新退货状态失败');
		}
		
		//更新订单状态
		$map = array('order_id'=>$order_info['order_id']);
		$data = array(
				'order_status'=>Status::OrderStatusOnBack,
				'shipping_status'=>Status::ShippingStatusBacking
		);
		if ($this->orderInfoDao()->where($map)->save($data) === false) {
			M()->rollback();
			throw new \Exception('更新退货状态失败');
		}
		
		M()->commit();
		
		return true;
	}
	
	public function createRepair($params){
		//如果超过维修期限不能退货
		$repair_time = $params['confirm_time'] + $params['repair_time'] * 86400;
		if ($repair_time < NOW_TIME) throw new \Exception('商品已过了维修期限');
		
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		//维修单号
		$data['id'] = date('ymdHis').rand(1000,9999);
		$data['add_time'] = NOW_TIME;
		if (!$this->afterSalesDao->create($data)){
			throw new \Exception($this->afterSalesDao->getError());
		}
		
		M()->startTrans();
		
		//添加维修记录
		$result = $this->afterSalesDao->add();
		if (!$result){
			M()->rollback();
			throw new \Exception('申请维修失败');
		}
		
		//更新维修状态
		$map = array('id'=>$data['item_id']);
		$data = array('repair_status'=>1);
		if ($this->orderGoodsDao()->where($map)->save($data) === false) {
			M()->rollback();
			throw new \Exception('申请维修失败');
		}
		
		//更新订单状态
		$map = array('order_id'=>$params['order_id']);
		$data = array(
				'order_status'=>Status::OrderStatusOnRepair,
		);
		if ($this->orderInfoDao()->where($map)->save($data) === false) {
			M()->rollback();
			throw new \Exception('申请维修失败');
		}
		
		M()->commit();
		
		return true;
	}
	
	public function modifyAfterSale($params){
		$shipping = M('shipping_code')->find($params['shipping_id']);
		if (!$shipping){
			throw new \Exception('快递公司不存在');
		}
		
		// 接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		$data['shipping_id'] = $shipping['id'];
		$data['shipping_code'] = $shipping['code'];
		$data['shipping_name'] = $shipping['name'];
		$data['update_time'] = NOW_TIME;
		if (!$this->afterSalesDao->create($data)){
			throw new \Exception($this->afterSalesDao->getError());
		}
		
		M()->startTrans();
		
		$result = $this->afterSalesDao->save();
		if (!$result){
			M()->rollback();
			throw new \Exception('操作失败');
		}
		
		M()->commit();
		
		return true;
	}
	
	public function backCheck($params){
		// 接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		$data['update_time'] = NOW_TIME;
		if (!$this->afterSalesDao->create($data)){
			throw new \Exception($this->afterSalesDao->getError());
		}
		
		M()->startTrans();
		
		$result = $this->afterSalesDao->save();
		if (!$result){
			M()->rollback();
			throw new \Exception('审核失败');
		}
		
		if ($data['status'] == 1) {
			$back_status = 3; //审核通过
		}else {
			$back_status = 2; //审核不通过
		}
		
		//更新订单商品状态
		$res = $this->orderGoodsDao()->where(array('id'=>$params['info']['item']['id']))->save(array('back_status'=>$back_status));
		if (!$res){
			M()->rollback();
			throw new \Exception('更新订单商品状态失败');
		}
		
		//如果审核不通过标志订单为已结束
		if ($back_status == 2) {
			$map = array('order_id'=>$params['info']['item']['order_id']);
			$data = array(
					'order_status'=>Status::OrderStatusOver
			);
			$res = $this->orderInfoDao()->where($map)->save($data);
			if ($res === false){
				M()->rollback();
				throw new \Exception('更新订单失败');
			}
			
			//如果没有未申请退换货的商品，设订单状态为已收结束
			/* $map = array(
					'order_id'=>$params['info']['item']['order_id'],
					'back_status'=>array('elt', 1)
			);
			$order_goods = $this->orderGoodsDao()->where($map)->find();
			if (!$order_goods) {
				$map = array('order_id'=>$params['info']['item']['order_id']);
				$data = array(
						//'shipping_status'=>Status::ShippingStatusBacked,
						//'pay_status'=>Status::PayStatusRepaid,
						'order_status'=>Status::OrderStatusOver
				);
				$res = $this->orderInfoDao()->where($map)->save($data);
				if ($res === false){
					M()->rollback();
					throw new \Exception('更新订单失败');
				}
			} */
		}
		
		M()->commit();
		
		return true;
	}
	
	public function checkLogistics($params){
		// 接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		$data['update_time'] = NOW_TIME;
		$data['status'] = $params['back_status'];
		
		if (!$this->afterSalesDao->create($data)){
			throw new \Exception($this->afterSalesDao->getError());
		}
	
		M()->startTrans();
	
		$result = $this->afterSalesDao->save();
		if (!$result){
			M()->rollback();
			throw new \Exception('审核失败');
		}
		
		//更新订单商品状态
		$res = $this->orderGoodsDao()->where(array('id'=>$params['info']['item']['id']))->save(array('back_status'=>$params['back_status']));
		if (!$res){
			M()->rollback();
			throw new \Exception('更新订单商品状态失败');
		}
		
		//如果审核不通过标志订单为已结束
		if ($params['back_status'] == 4) {
			$map = array('order_id'=>$params['info']['item']['order_id']);
			$data = array(
					'order_status'=>Status::OrderStatusOver
			);
			$res = $this->orderInfoDao()->where($map)->save($data);
			if (!$res){
				M()->rollback();
				throw new \Exception('更新订单失败');
			}
			
			//如果没有未申请退换货的商品，设订单状态为已收结束
			/* $map = array(
					'order_id'=>$params['info']['item']['order_id'],
					'back_status'=>array('elt', 1)
			);
			$order_goods = $this->orderGoodsDao()->where($map)->find();
			if (!$order_goods) {
				$map = array('order_id'=>$params['info']['item']['order_id']);
				$data = array(
						//'shipping_status'=>Status::ShippingStatusBacked,
						//'pay_status'=>Status::PayStatusRepaid,
						'order_status'=>Status::OrderStatusOver
				);
				$res = $this->orderInfoDao()->where($map)->save($data);
				if ($res === false){
					M()->rollback();
					throw new \Exception('更新订单失败');
				}
			} */
		}
		
		M()->commit();
	
		return true;
	}
	
	public function backMoney($params){
		// 接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		//检查提交的数据
		if ($params['back_money'] > $params['info']['item']['goods_price'] * $params['info']['amount']) {
			throw new \Exception('退款金额不能大于订单商品金额');
		}
		
		$data['update_time'] = NOW_TIME;
		$data['status'] = $params['back_status'];
		
		if (!$this->afterSalesDao->create($data)){
			throw new \Exception($this->afterSalesDao->getError());
		}
	
		M()->startTrans();
	
		$result = $this->afterSalesDao->save();
		if (!$result){
			M()->rollback();
			throw new \Exception('审核失败');
		}
		
		//退款到余额
		$res = $this->userInfoDao()->increaseUserMoney($params['user']['user_id'], $params['back_money']);
		if (!$res){
			M()->rollback();
			throw new \Exception('退款失败');
		}
			
		//退款日志
		$params_account = array(
				'user_id'=>$params['user']['user_id'],
				'user_money'=>$params['user']['user_money'],
				'money'=>$params['back_money'],
				'order_id'=>$params['info']['order_id'],
		);
		$res = $this->userAccountDao()->backOrder($params_account);
		if (!$res){
			M()->rollback();
			throw new \Exception('生成退款日志失败');
		}
		
		//更新订单商品状态
		$res = $this->orderGoodsDao()->where(array('id'=>$params['info']['item']['id']))->save(array('back_status'=>$params['back_status']));
		if (!$res){
			M()->rollback();
			throw new \Exception('更新订单商品状态失败');
		}
		
		$map = array('order_id'=>$params['info']['item']['order_id']);
		$data = array(
				'shipping_status'=>Status::ShippingStatusBacked,
				'pay_status'=>Status::PayStatusRepaid,
				'order_status'=>Status::OrderStatusOver
		);
		$res = $this->orderInfoDao()->where($map)->save($data);
		if (!$res){
			M()->rollback();
			throw new \Exception('更新订单失败');
		}
		
		//如果没有未申请退换货的商品，设订单状态为已收结束
		/* $map = array(
				'order_id'=>$params['info']['item']['order_id'],
				'back_status'=>array('elt', 1)
		);
		$order_goods = $this->orderGoodsDao()->where($map)->find();
		if (!$order_goods) {
			$map = array('order_id'=>$params['info']['item']['order_id']);
			$data = array(
					//'shipping_status'=>Status::ShippingStatusBacked,
					//'pay_status'=>Status::PayStatusRepaid,
					'order_status'=>Status::OrderStatusOver
			);
			$res = $this->orderInfoDao()->where($map)->save($data);
			if ($res === false){
				M()->rollback();
				throw new \Exception('更新订单失败');
			}
		} */
		
		M()->commit();
		
		return true;
	}
	
	public function delete($id){
		$result = $this->afterSalesDao->delRecord($id);
		if ($result === false){
			throw new \Exception('删除失败');
		}
		return true;
	}
	
	public function getAdminCount($map){
		$map['status'] = 0;
		$nocheck = $this->afterSalesDao->searchRecordsCount($map);
		$map['status'] = 5;
		$backmoney = $this->afterSalesDao->searchRecordsCount($map);
		return array(
				'nocheck'=>$nocheck,
				'backmoney'=>$backmoney,
		);
	}
	
	public function getDistributorCount($map){
		return $this->afterSalesDao->searchRecordsCount($map);
	}
	
	public function getPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = $params['map'] ? $params['map'] : array();
		if (!empty($params['type'])) {
			$map['type'] = $params['type'];
		}
		if (!empty($params['id'])) {
			$map['id'] = array('like', '%'.$params['id'].'%');
		}
		if (!empty($params['order_id'])) {
			$map['order_id'] = array('like', '%'.$params['order_id'].'%');
		}
		if (!empty($params['start_time'])) {
			$map['add_time'][] = array('egt', strtotime($params['start_time']));
		}
		if (!empty($params['end_time'])) {
			$map['add_time'][] = array('elt', strtotime($params['end_time']) + 86400);
		}
		if (!empty($params['distributor_id'])) {
			$map['distributor_id'] = $params['distributor_id'];
		}
		if (!empty($params['user_id'])) {
			$map['user_id'] = $params['user_id'];
		}
		
		$count = $this->afterSalesDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'id DESC' : $params['orderby'];
			$list = $this->afterSalesDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
			$total_back_money = $this->afterSalesDao->where($map)->sum('back_money');
		}
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
			'total_back_money'=>$total_back_money,
		);
	}
	
	private function outputForList($list) {
		if (empty($list)) {
			return $list;
		}
		
		foreach ($list as $v) {
			$user_ids[] = $v['user_id'];
		}
		$users = $this->userInfoDao()->getUsersByIds($user_ids);
		
		foreach ($list as $k => $v) {
			$list[$k]['nick_name'] = $users[$v['user_id']]['nick_name'];
			//订单商品
			$list[$k]['item'] = $this->orderGoodsDao()->find($v['item_id']);
			//店铺
			$list[$k]['distributor'] = $this->distributorInfoDao()->find($v['distributor_id']);
			//收货地址
			$config = $this->distributorConfigService()->findConfig('back_address', 'system', $v['distributor_id']);
			$list[$k]['back_address'] = $config['cval'];
			$list[$k]['pc_ship_url'] = 'http://www.kuaidi100.com/chaxun?com='.$v['shipping_code'].'&nu='.$v['shipping_no'];
			//状态
			$list[$k]['status_show'] = self::$status_show[$v['status']];
		}
		
		return $list;
	}
	
	private function outputForInfo($info) {
		if (empty($info)) {
			return $info;
		}

		$info['status_show'] = self::$status_show[$info['status']];
		if ($info['pictures']) {
			$info['pictures'] = explode('#', $info['pictures']);
		}
		
		if ($info['shipping_code'] && $info['shipping_no']) {
			$callbackurl = DK_DOMAIN.U('user/order/backview', array('id'=>$info['id']));
			$ship_url = 'http://m.kuaidi100.com/index_all.html?type='.$info['shipping_code'].'&postid='.$info['shipping_no'].'&callbackurl='.$callbackurl;
			$info['ship_url'] = $ship_url;
		}
		
		$item = $this->orderGoodsDao()->find($info['item_id']);
		$info['item'] = $item;
		
		//店铺
		$info['distributor'] = $this->distributorInfoDao()->find($info['distributor_id']);
		//收货地址
		$config = $this->distributorConfigService()->findConfig('back_address', 'system', $info['distributor_id']);
		$info['back_address'] = $config['cval'];
		
		return $info;
	}

	//前时期   退款数量和退款金额
	public function afterSalesFrontList($startTime = '', $endTime = '', $region_code = '', $distributor_id = ''){
		$where = array();
		if ($startTime && $endTime) {
			$frontStarTime = strtotime(date('Y-m-d H:i:s', (strtotime($startTime . ' 00:00:01'))));
			$frontEndTime = strtotime(date('Y-m-d H:i:s', (strtotime($endTime . ' 23:59:59'))));
		} else {
			$frontStarTime = strtotime(date('Y-m-d H:i:s', (strtotime(date('Y-m-d 00:00:01')) - 86400)));
			$frontEndTime = strtotime(date('Y-m-d H:i:s', (strtotime(date('Y-m-d 23:59:59')) - 86400)));
		}
		if ($region_code) {
			$where['region_code'] = $region_code;
		}
		if ($distributor_id) {
			$where['distributor_id'] = $distributor_id;
		}
		$where['update_time'] = array('between', $frontStarTime . ',' . $frontEndTime);
		$where['status'] = 5;
		$field = array('id', 'back_money');
		$count = 0;
		$orderBy = array();
		$data = $this->afterSalesDao->where($where)->field($field)->order($orderBy)->select();
		$back_money = 0;
		foreach ($data as $key => $val) {
			$back_money += $val['back_money'];
		}
		$count = count($data);
		return array(
			"count" => $count,
			"back_money" => $back_money,
		);
	}

	//后时期   退款数量和退款金额
	public function afterSalesToList($startTime = '', $endTime = '', $region_code = '', $distributor_id = ''){

		$where = array();
		if ($startTime && $endTime) {
			$toStarTime = strtotime(date('Y-m-d H:i:s', (strtotime($startTime . ' 00:00:01'))));
			$toEndTime = strtotime(date('Y-m-d H:i:s', (strtotime($endTime . ' 23:59:59'))));
		} else {
			$toStarTime = strtotime(date('Y-m-d H:i:s', strtotime(date('Y-m-d 00:00:01'))));
			$toEndTime = strtotime(date('Y-m-d H:i:s', strtotime(date('Y-m-d 23:59:59'))));
		}
		if ($region_code) {
			$where['region_code'] = $region_code;
		}
		if ($distributor_id) {
			$where['distributor_id'] = $distributor_id;
		}
		$where['update_time'] = array('between', $toStarTime . ',' . $toEndTime);
		$where['status'] = 5;
		$field = array('id', 'back_money');
		$count = 0;
		$orderBy = array();
		$data = $this->afterSalesDao->where($where)->field($field)->order($orderBy)->select();
		$back_money = 0;
		foreach ($data as $key => $val) {
			$back_money += $val['back_money'];
		}
		$count = count($data);
		return array(
			"count" => $count,
			"back_money" => $back_money,
		);
	}

	public function afterSalesSaleList($distributor_id = ''){

		$where = array();
		if ($distributor_id) {
			$where['distributor_id'] = $distributor_id;
		}
		$where['status'] = 5;
		$field = array('id', 'back_money');
		$count = 0;
		$orderBy = array();
		$data = $this->afterSalesDao->where($where)->field($field)->order($orderBy)->select();
		$back_money = 0;
		foreach ($data as $key => $val) {
			$back_money += $val['back_money'];
		}
		$count = count($data);
		return array(
			"count" => $count,
			"back_money" => $back_money,
		);
	}

	private function orderGoodsDao(){
		return D('OrderGoods');
	}
	
	private function orderInfoDao(){
		return D('OrderInfo');
	}
	
	private function userInfoDao(){
		return D('Common/User/UserInfo');
	}
	
	private function userAccountDao(){
		return D('Common/User/UserAccount');
	}
	
	private function distributorInfoDao(){
		return D('Common/Distributor/Info');
	}
	
	private function configService(){
		return D('Config', 'Service');
	}
	
	private function distributorConfigService(){
		return D('Distributor\Config', 'Service');
	}
}