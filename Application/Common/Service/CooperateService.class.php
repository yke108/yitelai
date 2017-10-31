<?php
namespace Common\Service;
use Common\Basic\Status;

class CooperateService{
	public function findInfo($id){
		return $this->cooperateDao()->findRecord($id);
	}
	
	public function getInfo($map){
		return $this->cooperateDao()->getRecord($map);
	}
	
	public function getField($map, $field, $bool){
		return $this->cooperateDao()->getFieldRecord($map, $field, $bool);
	}
	
	public function createOrModify($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		$data['add_time']=time();
		$cooperateDao = $this->cooperateDao();
		if (!$cooperateDao->create($data)){
			throw new \Exception($cooperateDao->getError());
		}
		if ($params['cooperate_id'] > 0){
			$result = $cooperateDao->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $cooperateDao->add();
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function getPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		
		!empty($params['keyword']) && $map['_string']="consignee like '%{$params[keyword]}%' or tel like '%{$params[keyword]}%'";
		
		if (!empty($params['start_time'])) {
			$map['add_time'][] = array('egt', strtotime($params['start_time']));
		}
		if (!empty($params['end_time'])) {
			$map['add_time'][] = array('elt', strtotime($params['end_time']) + 86400);
		}
		
		if(!empty($params['map'])){
			$map = array_merge($map, $params['map']);
		}
		
		$cooperateDao = $this->cooperateDao();
		$count = $cooperateDao->searchRecordCount($map);
		
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'add_time desc' : $params['orderby'];
			$list = $cooperateDao->searchRecord($map, $orderby, $params['page'], $params['pagesize']);
			foreach($list as $key=>$val){
				if(!empty($val['region_code'])){	
				$region_codes[]=$val['region_code'];
				}
				$val['user_id']!='' && $user_ids[$val['user_id']]=$val['user_id'];
			}
			if(!empty($region_codes)){
				$regions=$this->RegionDao()->getAllProvinceCity($region_codes);
			}
			if(!empty($user_ids)){
				$users=$this->userInfoDao()->getUsers($user_ids);
			}
		}
		
		return array(
			'list'=>$list,
			'count'=>$count,
			'regions'=>$regions,
			'users'=>$users,
		);
	}
	
	
	private function cooperateDao(){
		return D('Common/Cooperate');
	}
	
	private function RegionDao(){
		return D('Common/Region');
	}
	
	private function userInfoDao(){
		return D('Common/User/UserInfo');
	}
	
}

