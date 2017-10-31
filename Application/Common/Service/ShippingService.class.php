<?php
namespace Common\Service;

class ShippingService{
	
	public function getShippingInfo($id){
		if ($id < 1) return false;
		$info = $this->shippingInfoDao()->getRecord($id);
		return $this->outputForInfo($info);
	}
	
	public function shippingCreateOrModify($params){
		// 接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		if (!$this->shippingInfoDao()->create($data)){
			 throw new \Exception($this->shippingInfoDao()->getError());
		}
		if ($data['shipping_id'] > 0){
			$result = $this->shippingInfoDao()->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $this->shippingInfoDao()->add();
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function shippingDelete($map){
		$result = $this->shippingInfoDao()->delRecord($map);
		if ($result === false){
			throw new \Exception('删除失败');
		}
		return true;
	}
	
	public function getPagerShippingList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array(
				'distributor_id'=>$params['distributor_id']
		);
		
		$count = $this->shippingInfoDao()->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'shipping_id DESC' : $params['orderby'];
			$list = $this->shippingInfoDao()->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	public function getShippingList($params){
		$map = array(
				'distributor_id'=>$params['distributor_id'],
		);
		if (isset($params['enabled'])) {
			$map['enabled'] = $params['enabled'];
		}
		
		$orderby = empty($params['orderby']) ? 'shipping_id DESC' : $params['orderby'];
		$list = $this->shippingInfoDao()->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		
		return $this->outputForList($list);
	}
	
	/**
	 * 配送区域
	 */
	public function getShippingAreaInfo($id){
		if ($id < 1) return false;
		$info = $this->shippingAreaDao()->getRecord($id);
		return $this->outputForInfo($info);
	}
	
	public function shippingAreaCreateOrModify($params){
		// 接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			if ($k == 'region_code_list') {
				$data[$k] = $v;
			}else {
				$data[$k] = trim($v);
			}
		}
		$data['region_code_list'] = implode(',', $data['region_code_list']);
		if (!$this->shippingAreaDao()->create($data)){
			throw new \Exception($this->shippingInfoDao()->getError());
		}
		if ($data['shipping_area_id'] > 0){
			$result = $this->shippingAreaDao()->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $this->shippingAreaDao()->add();
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function shippingAreaDelete($map){
		$result = $this->shippingAreaDao()->delRecord($map);
		if ($result === false){
			throw new \Exception('删除失败');
		}
		return true;
	}
	
	public function getPagerShippingAreaList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		if (isset($params['distributor_id'])) {
			$map['distributor_id'] = $params['distributor_id'];
		}
		
		$count = $this->shippingAreaDao()->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'shipping_area_id DESC' : $params['orderby'];
			$list = $this->shippingAreaDao()->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		
		return array(
				'list'=>$this->outputForList($list),
				'count'=>$count,
		);
	}
	
	public function getShippingAreaList($params){
		$map = array();
		if (isset($params['distributor_id'])) {
			$map['distributor_id'] = $params['distributor_id'];
		}
		
		$orderby = empty($params['orderby']) ? 'shipping_area_id DESC' : $params['orderby'];
		$list = $this->shippingAreaDao()->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		
		return $this->outputForList($list);
	}
	
	public function getAllCodeList($map = array()){
		return $this->shippingCodeDao()->searchAllRecordsField($map);
	}
	
	private function outputForList($list) {
		if (!empty($list)) {
			;
		}
		return $list;
	}
	
	private function outputForInfo($info) {
		if (!empty($info)) {
			;
		}
		return $info;
	}
	
	private function shippingInfoDao() {
		return D('Common/Shipping/Info');
	}
	
	private function shippingAreaDao() {
		return D('Common/Shipping/Area');
	}
	
	private function shippingCodeDao() {
		return D('Common/Shipping/Code');
	}
}//end HelpService!甜品