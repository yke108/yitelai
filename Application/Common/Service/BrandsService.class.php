<?php
namespace Common\Service;
use Common\Basic\Status;
use Common\Basic\MessageConst;

class BrandsService{
	public function getFieldData($map,$field='brands_id,brand_name,brand_image'){
		return $this->brandInfoDao()->getFieldRecord($map,$field);
	}
	
	public function getInfo($id){
		if ($id < 1) return false;
		$info = $this->brandInfoDao()->getRecord($id);
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
		
		if (!$this->brandInfoDao()->create($data)){
			 throw new \Exception($this->brandInfoDao()->getError());
		}
		
		M()->startTrans();
		
		if ($data['brands_id'] > 0){
			$result = $this->brandInfoDao()->save();
			if ($result === false){
				M()->rollback();
				throw new \Exception('修改失败');
			}
		} else {
			$brands_id = $this->brandInfoDao()->add();
			if ($brands_id < 1){
				M()->rollback();
				throw new \Exception('添加失败');
			}
		}
		
		M()->commit();
		
		return true;
	}
	
	public function delete($brands_id){
		//逻辑删除店铺
		$map = array('brands_id'=>$brands_id);
		$data = array('is_delete'=>1);
		if ($this->brandInfoDao()->where($map)->save($data) === false) {
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
			$map['brand_name'] = array('like', '%'.trim($params['keyword']).'%');
		}
		if (!empty($params['status'])) {
			$map['status'] = $params['status'];
		}
		if (!empty($params['start_time'])) {
			$map['add_time'][] = array('egt', strtotime($params['start_time']));
		}
		if (!empty($params['end_time'])) {
			$map['add_time'][] = array('elt', strtotime($params['end_time']) + 86400);
		}
		if ($params['is_show'] != '') {
			$map['is_show'] = $params['is_show'];
		}
		if ($params['brands_id'] != '') {
			$map['brands_id'] = $params['brands_id'];
		}
		//筛选有提现记录的店铺
		if ($params['_needCashApply'] == 1) {
			$apply_list = $this->distributorCashApplyDao()->distinct(true)->field('brands_id')->select();
			if (empty($apply_list)) {
				return array();
			}
			foreach ($apply_list as $v) {
				$brands_ids[] = $v['brands_id'];
			}
			$map['brands_id'] = array('in', $brands_ids);
		}
		
		$count = $this->brandInfoDao()->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$field = 'a.*';
			if ($params['distance']) {
				$field .= ', '.$params['distance'].' AS distance';
			}
			$orderby = empty($params['orderby']) ? 'brands_id DESC' : $params['orderby'];
			$list = $this->brandInfoDao()->searchRecords($map, $orderby, $params['page'], $params['pagesize'], $field);
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
		$orderby = empty($orderby) ? 'brands_id DESC' : $orderby;
		$list = $this->brandInfoDao()->searchAllRecords($map, $orderby);
		return $this->outputForList($list);
	}
	
	public function isShow($brands_id){
		$info = $this->brandInfoDao()->find($brands_id);
		if (empty($info)) throw new \Exception('店铺不存在');
		$is_show = $info['is_show'] == 1 ? 2 : 1;
		$result = $this->brandInfoDao()->where(array('brands_id'=>$brands_id))->save(array('is_show'=>$is_show));
		if ($result === false) throw new \Exception('设置失败');
	}
	
	private function outputForList($list) {
		if (!empty($list)) {
			foreach ($list as $k=> $v) {
				if ($v['region_code']) {
					$list[$k]['region_name'] = $this->regionDao()->getRegionName($v['region_code']);
				}
				
				//购买会员数
				$users = $this->orderInfoDao()->distinct(true)->field('user_id')->where(array('brands_id'=>$v['brands_id']))->select();
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
				$designer_list = $this->designerInfoDao()->getFieldRecord(array('brands_id'=>$v['brands_id']), 'designer_id, designer_name, designer_image, tel, region_code');
				$list[$k]['designer_list'] = $designer_list;
				$list[$k]['designer_count'] = count($designer_list);
				
				//案例列表
				//$case_list = $this->designerCaseDao()->getFieldRecord(array('brands_id'=>$v['brands_id']));
				$map = array('brands_id'=>$v['brands_id']);
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
	
	private function brandInfoDao(){
		return D('Common/Brand/Info');
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