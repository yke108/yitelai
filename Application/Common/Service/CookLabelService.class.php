<?php
namespace Common\Service;

class CookLabelService{
	protected $cookLabel;
	
	public function __construct(){
		$this->cookLabel = D('Common/Cook/Label');
	}
	
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->cookLabel->getRecord($id);
	}
	
	public function findInfo($map){
		return $this->cookLabel->findRecord($map);
	}
	
	public function createOrModify($params){
		// 接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = $v;
		}
		
		if (!$this->cookLabel->create($data)){
			 throw new \Exception($this->cookLabel->getError());
		}
		
		if ($params['label_id'] > 0){
			$result = $this->cookLabel->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $this->cookLabel->add();
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function delete($id){
		$result = $this->cookLabel->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function del($map){
		$result = $this->cookLabel->where($map)->delete();
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function getPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		if (isset($params['distributor_id'])) {
			$map['distributor_id'] = $params['distributor_id'];
		}
		if (isset($params['nav_show'])) {
			$map['nav_show'] = $params['nav_show'];
		}
		
		$count = $this->cookLabel->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order ASC, label_id ASC' : $params['orderby'];
			$list = $this->cookLabel->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	public function getAllList($map = array()){
		return $this->cookLabel->searchAllRecords($map);
	}
	
	private function outputForList($list) {
		if (!empty($list)) {
			
		}
		return $list;
	}
}//end HelpService!甜品