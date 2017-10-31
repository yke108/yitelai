<?php
namespace Common\Service;

class UserDemandService{
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->userDemandDao()->getRecord($id);
	}
	
	public function infoCreateOrModify($params){
		// 接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = $v;
		}
		
		$userDemandDao = $this->userDemandDao();
		if (!$userDemandDao->create($data)){
			 throw new \Exception($userDemandDao->getError());
		}
		if ($params['demand_id'] > 0){
			$result = $userDemandDao->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $userDemandDao->add();
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function infoDelete($id){
		$result = $this->userDemandDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	//分页查询
	public function infoPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		$userDemandDao = $this->userDemandDao();
		$count = $userDemandDao->searchRecordsCount($map);
		
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order DESC, demand_id DESC' : $params['orderby'];
			$list = $userDemandDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		
		return array(
			'list'=>$list,
			'count'=>$count,
		);
	}
	
	public function infoAllList($map){
		return $this->userDemandDao()->searchFieldRecords($map);;
	}
	
	public function isShow($demand_id){
		$info = $this->getInfo($demand_id);
		$is_show = $info['is_show'] == 0 ? 1 : 0;
		$result = $this->userDemandDao()->where(array('demand_id'=>$info['demand_id']))->save(array('is_show'=>$is_show));
		if ($result === false) throw new \Exception('设置失败');
	}
	
	private function userDemandDao(){
		return D('Common/User/UserDemand');
	}
}//end HelpService!