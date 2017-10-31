<?php
namespace Auto\Controller;
use Think\Controller;
use Common\Basic\Status;

class DealCommissionController extends Controller {
    public function indexAction(){
		header("Content-type: text/html; charset=utf-8");
		
		$system_config = $this->configService()->findConfigs('system');
		
		//处理自动收货
		$auto_confirm = $system_config['auto_confirm'];
		$auto_confirm_time = NOW_TIME - $auto_confirm * 86400;
		$map = array(
				'is_commission'=>0,
				'shipping_status'=>Status::ShippingStatusDelivering,
				'shipping_time'=>array('elt', $auto_confirm_time)
		);
		$params['map'] = $map;
		$datas = $this->orderService()->getOrderList($params);
		if (!empty($datas['list'])) {
			foreach ($datas['list'] as $v) {
				try{
					$result = $this->orderService()->ReceiveAuto($v['order_id']);
				}catch(\Exception $e){
					die($e->getMessage);
				}
			}
			die('执行自动收货成功');
		}
		
		//处理分利
		$return_deadline = $system_config['return_deadline'];
		$return_deadline_time = NOW_TIME - $return_deadline * 86400;
		
		$map = array(
				'is_commission'=>0,
				//'shipping_status'=>Status::ShippingStatusReceived,
				'confirm_time'=>array('elt', $return_deadline_time),
				'order_status'=>array('egt', Status::OrderStatusSuccess),
		);
		$params['map'] = $map;
		$datas = $this->orderService()->getOrderList($params);
		if (!empty($datas['list'])) {
			foreach ($datas['list'] as $v) {
				try{
					$result = $this->orderService()->dealCommission($v['order_id']);
				}catch(\Exception $e){
					die($e->getMessage);
				}
			}
			die('执行分利成功');
		}
    }
	
	private function orderService(){
		return D('Order','Service');
	}
	
	private function configService(){
		return D('Config','Service');
	}
}