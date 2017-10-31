<?php
namespace Common\Service\Distributor;
use Common\Basic\Status;

class DistributorRankService{
	public function findInfo($id){
		return $this->distributorRankDao()->findRecord($id);
	}
	
	public function getInfo($map){
		return $this->distributorRankDao()->getRecord($map);
	}
	
	public function getField($map, $field, $bool){
		return $this->distributorRankDao()->getFieldRecord($map, $field, $bool);
	}
	
	public function getFieldData($map, $field, $bool){
		return $this->distributorRankDao()->getFieldRecord($map, $field, $bool);
	}
	
	public function createOrModify($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		$distributorRankDao = $this->distributorRankDao();
		if (!$distributorRankDao->create($data)){
			throw new \Exception($distributorRankDao->getError());
		}
		if ($params['rank_id'] > 0){
			$result = $distributorRankDao->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $distributorRankDao->add();
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function getPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		if(!empty($params['map'])){
			$map = array_merge($map, $params['map']);
		}
		
		$distributorRankDao = $this->distributorRankDao();
		$count = $distributorRankDao->searchRecordsCount($map);
		
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'discount ASC' : $params['orderby'];
			$list = $distributorRankDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		
		return array(
			'list'=>$this->_outputForList($list),
			'count'=>$count,
		);
	}
	
	public function getAllList($params){
		$map = array();
		if(!empty($params['map'])){
			$map = array_merge($map, $params['map']);
		}
		
		$orderby = empty($params['orderby']) ? 'discount ASC' : $params['orderby'];
		$list = $this->distributorRankDao()->searchAllRecords($map, $orderby);
		
		return $this->_outputForList($list);
	}
	
	public function getFieldList($params){
		$map = array();
		if(!empty($params['map'])){
			$map = array_merge($map, $params['map']);
		}
		
		$orderby = empty($params['orderby']) ? 'discount ASC' : $params['orderby'];
		$list = $this->distributorRankDao()->getFieldRecord($map);
		
		return $this->_outputForList($list);
	}
	
	private function _outputForList($list) {
		if (!empty($list)) {
			
		}
		
		return $list;
	}
	
	private function _outputForInfo($info) {
		if (!empty($info)) {
			
		}
		
		return $info;
	}
	
	private function distributorRankDao(){
		return D('Common/Distributor/DistributorRank');
	}
}

