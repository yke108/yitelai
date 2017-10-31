<?php
namespace Common\Service;
use Common\Basic\Status;

class UserRankService{
	public function findInfo($id){
		return $this->UserRankDao()->findRecord($id);
	}
	
	public function getInfo($map){
		return $this->UserRankDao()->getRecord($map);
	}
	
	public function getField($map, $field, $bool){
		return $this->UserRankDao()->getFieldRecord($map, $field, $bool);
	}
	
	public function getFieldData($map, $field, $bool){
		return $this->UserRankDao()->getFieldRecord($map, $field, $bool);
	}
	
	public function createOrModify($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		$userRankDao = $this->userRankDao();
		if (!$userRankDao->create($data)){
			throw new \Exception($userRankDao->getError());
		}
		if ($params['rank_id'] > 0){
			$result = $userRankDao->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $userRankDao->add();
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
		
		$UserRankDao = $this->UserRankDao();
		$count = $UserRankDao->searchRecordsCount($map);
		
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'min_points ASC' : $params['orderby'];
			$list = $UserRankDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
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
		
		$orderby = empty($params['orderby']) ? 'min_points ASC' : $params['orderby'];
		$list = $this->UserRankDao()->searchRecords($map, $orderby);
		
		return $this->_outputForList($list);
	}
	
	private function _outputForList($list) {
		if (empty($list)) {
			return $list;
		}
		
		
		
		return $list;
	}
	
	private function _outputForInfo($info) {
		if (empty($info)) {
			return $info;
		}
		
		
		
		return $info;
	}
	
	private function UserRankDao(){
		return D('Common/User/UserRank');
	}
}

