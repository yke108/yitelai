<?php
namespace Common\Service\Information;

class PointService{
	public function getPoint($id){
		if ($id < 1) return false;
		return $this->adminInfoDao()->getRecord($id);
	}
	
	public function pointPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		if(!empty($params['map'])){
			$map=array_merge($map,$params['map']);
		}
		
		$pointLogDao = $this->pointLogDao();
		$count = $pointLogDao->searchRecordsCount($map);
		
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'log_id desc' : $params['orderby'];
			$list = $pointLogDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
		);
	}
	
	private function outputForList($list) {
		if (empty($list)) {
			return $list;
		}
		
		foreach ($list as $k => $v) {
			$user_ids[] = $v['user_id'];
			$ref_user_ids[] = $v['ref_user_id'];
		}
		$users = $this->UserInfoDao()->getUsersByIds($user_ids);
		$ref_users = $this->UserInfoDao()->getUsersByIds($ref_user_ids);
		
		foreach ($list as $k => $v) {
			$list[$k]['mobile'] = $users[$v['user_id']]['mobile'];
			$list[$k]['ref_user'] = $ref_users[$v['ref_user_id']]['nick_name'];
		}
		
		return $list;
	}
	
	public function addUserPoint($params){
		if(empty($params['user_id']) || empty($params['point']) || empty($params['type'])){throw new \Exception('缺少参数');}
		$result = $this->PointLogic()->add($params);
		if ($result === false) {
			throw new \Exception('获取积分失败');
		}
		return true;
	}
	
	//人工添加积分
	public function pointPlus($params){
		if(empty($params['user_id']) || empty($params['point'])  || empty($params['admin_id'])){throw new \Exception('缺少参数');}
		$this->PointLogic()->plus($params);
	}
	
	//人工减少积分
	public function pointReduct($params){
		if(empty($params['user_id']) || empty($params['point']) || empty($params['admin_id'])){throw new \Exception('缺少参数');}
		$this->PointLogic()->reduct($params);
	}
	
	public function pointDelete($params){
		if ($params['log_id'] < 1) throw new \Exception('缺少参数');
		M()->startTrans();
		$result = $this->pointLogDao()->deleteRecord($params);
		if ($result === false){
			M()->rollback();
			throw new \Exception('删除失败');
		}
		
		M()->commit();
		return true;
	}
	
	private function PointLogic(){
		return D('Information\Point','Logic');
	}
	
	private function pointLogDao(){
		return D('Common/Information/Point/Log');
	}
	
	private function UserInfoDao(){
		return D('Common/Information/User/Info');
	}
	
}