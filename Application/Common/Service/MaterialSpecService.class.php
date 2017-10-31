<?php
namespace Common\Service;

class MaterialSpecService{
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->materialSpecService()->getRecord($id);
	}
	
	public function createOrModify($params){
		// 接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = $v;
		}
		if (!$this->materialSpecService()->create($data)){
			 throw new \Exception($this->materialSpecService()->getError());
		}
		if ($params['spec_id'] > 0){
			$result = $this->materialSpecService()->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $this->materialSpecService()->add();
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function delete($id){
		$result = $this->materialSpecService()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function getPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		if (isset($params['type_id'])) {
			$map['type_id'] = $params['type_id'];
		}
		
		$count = $this->materialSpecService()->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order ASC, spec_id DESC' : $params['orderby'];
			$list = $this->materialSpecService()->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	public function getAllList($map = array()){
		return $this->materialSpecService()->searchAllRecords($map);
	}
	
	private function outputForList($list) {
		if (!empty($list)) {
			
		}
		
		return $list;
	}
	
	private function materialSpecService() {
		return D('Common/Material/Spec');
	}
}//end HelpService!甜品