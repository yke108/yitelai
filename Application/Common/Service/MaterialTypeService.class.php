<?php
namespace Common\Service;

class MaterialTypeService{
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->materialType()->getRecord($id);
	}
	
	public function createOrModify($params){
		// 接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = $v;
		}
		if (!$this->materialType()->create($data)){
			 throw new \Exception($this->materialType()->getError());
		}
		if ($params['type_id'] > 0){
			$result = $this->materialType()->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $this->materialType()->add();
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function delete($id){
		$result = $this->materialType()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function getPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		$count = $this->materialType()->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order ASC, type_id DESC' : $params['orderby'];
			$list = $this->materialType()->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	public function getAllList($map = array()){
		return $this->materialType()->searchAllRecords($map);
	}
	
	public function getOptionList($map = array()){
		$list = $this->materialType()->searchAllRecords($map);
		$spec_list = $this->materialSpecDao()->selectAllRecords();
		$new_list = array();
		foreach ($list as $k => $v) {
			$new_spec_list = array();
			foreach ($spec_list as $k2 => $v2) {
				if ($v2['type_id'] == $k) {
					$new_spec_list[] = $v2;
				}
			}
			$new_list[$k] = $new_spec_list;
		}
		return $new_list;
	}
	
	private function outputForList($list) {
		if (!empty($list)) {
			foreach ($list as $k => $v) {
				$specs = '';
				$goods_spec = M('material_spec')->where(array('type_id'=>$v['type_id']))->select();
				if ($goods_spec) {
					foreach ($goods_spec as $v2) {
						$specs .= $v2['spec_name'].',';
					}
					$specs = trim($specs, ',');
				}
				$list[$k]['specs'] = $specs;
			}
		}
		
		return $list;
	}
	
	private function materialType() {
		return D('Common/Material/Type');
	}
	
	private function materialSpecDao() {
		return D('Common/Material/Spec');
	}
}//end HelpService!甜品