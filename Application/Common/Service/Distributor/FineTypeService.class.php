<?php
namespace Common\Service\Distributor;

class FineTypeService{
	public function __construct(){
		
	}
	
	public function getInfo($id){
		return $this->distributorFineTypeDao()->getRecord($id);
	}
	
	public function findInfo($map){
		return $this->distributorFineTypeDao()->findRecord($map);
	}
	
	public function createOrModify($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		if (!$this->distributorFineTypeDao()->create($data)) throw new \Exception($this->distributorFineTypeDao()->getError());
		
		if ($data['type_id'] > 0){
			$result = $this->distributorFineTypeDao()->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $this->distributorFineTypeDao()->add();
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function delete($type_id){
		$result = $this->distributorFineTypeDao()->delRecord($type_id);
		if ($result === false) throw new \Exception('删除失败');
		return true;
	}
	
	public function getAllList($map){
		return $this->distributorFineTypeDao()->searchAllRecords($map, $orderby = 'type_id DESC');
	}
	
	private function outputForList($list) {
		if (!empty($list)) {
			foreach ($list as $k => $v) {
				
			}
		}
		
		return $list;
	}
	
	private function outputForInfo($info) {
		if (!empty($info)) {
			
		}
		
		return $info;
	}
	
	private function distributorFineTypeDao() {
		return D('Common/Distributor/FineType');
	}
}//end HelpService!甜品