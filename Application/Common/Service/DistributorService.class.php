<?php
namespace Common\Service;
use Common\Basic\Status;
use Common\Basic\MessageConst;

class DistributorService{
	public function getFieldData($map,$field='distributor_id,distributor_name,distributor_image'){
		return $this->distributorInfoDao()->getFieldRecord($map,$field);
	}
	
	public function getInfo($id){
		if ($id < 1) return false;
		$info = $this->distributorInfoDao()->getRecord($id);
		return $this->outputForInfo($info);
	}
	
	public function createOrModify($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		$lnglat = explode(',', $data['lnglat']);
		$data['longitude'] = $lnglat[0];
		$data['latitude'] = $lnglat[1];
		$data['is_self_distributor'] = $params['is_self_distributor'] ? $params['is_self_distributor'] : 0;
		$data['is_edit_sales'] = $params['is_edit_sales'] ? $params['is_edit_sales'] : 0;
		
		if (!$this->distributorInfoDao()->create($data)){
			 throw new \Exception($this->distributorInfoDao()->getError());
		}
		
		M()->startTrans();
		
		if ($data['distributor_id'] > 0){
			$result = $this->distributorInfoDao()->save();
			if ($result === false){
				M()->rollback();
				throw new \Exception('修改失败');
			}
			
			//改变下面设计师的地址
			$designer_map=array('distributor_id'=>$data['distributor_id']);
			$designer_data=array('region_code'=>$data['region_code']);
			$this->designerInfoDao()->saveRecord($designer_data,$designer_map);
			
			//修改商品经纬度
			$map = array('distributor_id'=>$data['distributor_id']);
			$data = array(
					'lng'=>$data['longitude'],
					'lat'=>$data['latitude']
			);
			$result = $this->distributorGoodsDao()->where($map)->save($data);
			if ($result === false){
				M()->rollback();
				throw new \Exception('修改失败');
			}
			
			$distributor_id = $data['distributor_id'];
		} else {
			$distributor_id = $this->distributorInfoDao()->add();
			if ($distributor_id < 1){
				M()->rollback();
				throw new \Exception('添加失败');
			}
			
			//关联商家入驻ID
			$map = array('merchant_id'=>$params['merchant_id']);
			$data = array('distributor_id'=>$distributor_id);
			$result = $this->merchantDao()->where($map)->save($data);
			if ($result === false){
				M()->rollback();
				throw new \Exception('添加失败');
			}
			
			/* 把后台登录的信息通过邮箱或者短信发送给店铺负责人 */
			/* $data = array(
					'Title'=>'支付通知',
					'MsgType'=>Status::MsgTypeDistributorAccount,
					'DistributorId'=>$distributor_id,
			);
			try {
				$this->smsLogic()->smsLogByTemplate(MessageConst::SmsTpOnDistributorAccount, $data['mobile'], $data);
			} catch (\Exception $e) {
				M()->rollback();
				throw new \Exception($e->getMessage());
			} */
		}
		
		M()->commit();
		
		return true;
	}
	
	public function delete($distributor_id){
		//逻辑删除店铺
		$map = array('distributor_id'=>$distributor_id);
		$data = array('is_delete'=>1);
		if ($this->distributorInfoDao()->where($map)->save($data) === false) {
			throw new \Exception('删除失败');
		}
		return true;
	}
	
	public function getPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = $params['map'] ? $params['map'] : array();
		$map['is_delete'] = 0;
		if (!empty($params['keyword'])) {
			$map['distributor_name'] = array('like', '%'.trim($params['keyword']).'%');
		}
		if (!empty($params['status'])) {
			$map['status'] = $params['status'];
		}
		if ($params['is_self_distributor'] != '') {
			$map['is_self_distributor'] = $params['is_self_distributor'];
		}
		if (!empty($params['start_time'])) {
			$map['add_time'][] = array('egt', strtotime($params['start_time']));
		}
		if (!empty($params['end_time'])) {
			$map['add_time'][] = array('elt', strtotime($params['end_time']) + 86400);
		}
		if ($params['distributor_type'] != '') {
			$map['distributor_type'] = $params['distributor_type'];
		}
		if ($params['is_show'] != '') {
			$map['is_show'] = $params['is_show'];
		}
		if ($params['distributor_id'] != '') {
			$map['distributor_id'] = $params['distributor_id'];
		}
		//筛选有提现记录的店铺
		if ($params['_needCashApply'] == 1) {
			$apply_list = $this->distributorCashApplyDao()->distinct(true)->field('distributor_id')->select();
			if (empty($apply_list)) {
				return array();
			}
			foreach ($apply_list as $v) {
				$distributor_ids[] = $v['distributor_id'];
			}
			$map['distributor_id'] = array('in', $distributor_ids);
		}
		//品牌商筛选
		if ($params['brands_id']) {
			$map['brands_id'] = $params['brands_id'];
		}
		
		$count = $this->distributorInfoDao()->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$field = 'a.*';
			if ($params['distance']) {
				$field .= ', '.$params['distance'].' AS distance';
			}
			$orderby = empty($params['orderby']) ? 'distributor_id DESC' : $params['orderby'];
			$list = $this->distributorInfoDao()->searchRecords($map, $orderby, $params['page'], $params['pagesize'], $field);
			foreach ($list as $vo){
				$a_ids[$vo['area_id']] = $vo['area_id'];
			}
			$areas = $this->areaDao()->getRecords($a_ids);
		}
		
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
			'area_list'=>$areas,
		);
	}
	
	public function getAllList($map, $orderby){
		$map['is_delete'] = 0;
		$orderby = empty($orderby) ? 'distributor_id DESC' : $orderby;
		$list = $this->distributorInfoDao()->searchAllRecords($map, $orderby);
		return $this->outputForList($list);
	}
	
	public function isShow($distributor_id){
		$info = $this->distributorInfoDao()->find($distributor_id);
		if (empty($info)) throw new \Exception('店铺不存在');
		$is_show = $info['is_show'] == 1 ? 2 : 1;
		$result = $this->distributorInfoDao()->where(array('distributor_id'=>$distributor_id))->save(array('is_show'=>$is_show));
		if ($result === false) throw new \Exception('设置失败');
	}
	
	public function fine($params) {
		//发送短信
		$distributor_info = $this->distributorInfoDao()->find($params['distributor_id']);
		if (empty($distributor_info)) throw new \Exception('店铺不存在');
		
		$data = array(
				'Title'=>'支付通知',
				'MsgType'=>Status::MsgTypePayOrder,
				'OrderId'=>$params['order_id'],
		);
		try {
			$this->smsLogic()->smsLogByTemplate(MessageConst::SmsTpOnOrderPay, $distributor_info['mobile'], $data);
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage());
		}
	}
	
	private function outputForList($list) {
		if (!empty($list)) {
			foreach ($list as $k=> $v) {
				if ($v['region_code']) {
					$list[$k]['region_name'] = $this->regionDao()->getRegionName($v['region_code']);
				}
				
				//购买会员数
				$users = $this->orderInfoDao()->distinct(true)->field('user_id')->where(array('distributor_id'=>$v['distributor_id']))->select();
				if ($users) {
					$user_ids = array();
					foreach ($users as $v) {
						$user_ids[] = $v['user_id'];
					}
					$map = array(
							'user_id'=>array('in', $user_ids)
					);
					$list[$k]['buyusers'] = $this->userInfoDao()->where($map)->count();
				}
				
				//设计师列表
				$designer_list = $this->designerInfoDao()->getFieldRecord(array('distributor_id'=>$v['distributor_id']), 'designer_id, designer_name, designer_image, tel, region_code');
				$list[$k]['designer_list'] = $designer_list;
				$list[$k]['designer_count'] = count($designer_list);
				
				//案例列表
				//$case_list = $this->designerCaseDao()->getFieldRecord(array('distributor_id'=>$v['distributor_id']));
				$map = array('distributor_id'=>$v['distributor_id']);
				$case_list = $this->designerCaseDao()->searchRecords($map, 'case_id DESC', 1, 4);
				foreach ($case_list as $k2 => $v2) {
					$case_list[$k2]['designer_name'] = $designer_list[$v2['designer_id']]['designer_name'];
					$case_list[$k2]['designer_image'] = $designer_list[$v2['designer_id']]['designer_image'];
				}
				$list[$k]['case_list'] = $case_list;
				
				//处理距离
				if ($v['distance'] < 1) {
					$list[$k]['distance'] = round($v['distance'] / 1000, 1).'米';
				}else {
					$list[$k]['distance'] = round($v['distance'], 1).'公里';
				}
				
				//隐藏电话
				$list[$k]['distributor_tel_hide'] = substr($v['distributor_tel'], 0, 3).'****'.substr($v['distributor_tel'], -4, 4);
			}
		}
		
		return $list;
	}
	
	private function outputForInfo($info) {
		if (!empty($info)) {
			if ($info['region_code']) {
				$info['region_name'] = $this->regionDao()->getRegionName($info['region_code']);
			}
		}
		
		return $info;
	}
	
	private function distributorInfoDao(){
		return D('Common/Distributor/Info');
	}
	
	private function designerCaseDao(){
		return D('Common/Designer/DesignerCase');
	}
	
	private function regionDao(){
		return D('Region');
	}
	
	private function designerInfoDao(){
		return D('Common/Designer/DesignerInfo');
	}
	
	private function userInfoDao(){
		return D('Common/User/UserInfo');
	}
	
	private function orderInfoDao(){
		return D('Common/Order/OrderInfo');
	}
	
	private function distributorGoodsDao() {
		return D('Common/Distributor/Goods');
	}
	
	private function merchantDao() {
		return D('Common/Merchant/Merchant');
	}
	
	private function areaDao(){
		return D('Common/Area/Area');
	}
	
	private function adminInfoDao(){
		return D('Common/Admin/AdminInfo');
	}
	
	private function distributorCashApplyDao(){
		return D('Common/Distributor/CashApply');
	}
}