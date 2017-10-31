<?php
namespace Common\Service;
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
			$map = array_merge($map,$params['map']);
		}
		if($params['keyword']){
			$where['mobile|nick_name|real_name'] = array('like', '%'.trim($params['keyword']).'%');
			$user_list = $this->userInfoDao()->where($where)->select();
			if (empty($user_list)) {
				return array();
			}
			$user_ids = array();
			foreach ($user_list as $v) {
				$user_ids[] = $v['user_id'];
			}
			$map['a.user_id'] = array('in', $user_ids);
		}
		if(!empty($params['user_id'])){
			$map['a.user_id'] = $params['user_id'];
		}
		if (!empty($params['start_time'])) {
			$map['a.add_time'][] = array('egt', strtotime($params['start_time']));
		}
		if (!empty($params['end_time'])) {
			$map['a.add_time'][] = array('elt', strtotime($params['end_time']) + 86400);
		}
		if ($params['start_point']) {
			$map['point_change'][] = array('egt', $params['start_point']);
		}
		if ($params['end_point']) {
			$map['point_change'][] = array('elt', $params['end_point']);
		}
		if ($params['change_type'] == 1) {
			$map['point_change'] = array('gt', 0);
		}
		if ($params['change_type'] == 2) {
			$map['point_change'] = array('lt', 0);
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
		if (!empty($list)) {
			foreach ($list as $k => $v) {
				$user_ids[] = $v['user_id'];
				$ref_user_ids[] = $v['ref_user_id'];
			}
			$users = $this->userInfoDao()->getUsersByIds($user_ids);
			$ref_users = $this->userInfoDao()->getUsersByIds($ref_user_ids);
			
			foreach ($list as $k => $v) {
				$list[$k]['user_name'] = $users[$v['user_id']]['real_name'] ? $users[$v['user_id']]['real_name'] : $users[$v['user_id']]['nick_name'];
				$list[$k]['avatar'] = $users[$v['user_id']]['user_img'] ? picurl( $users[$v['user_id']]['user_img'], 'b90') : $users[$v['user_id']]['headimgurl'];
				$list[$k]['mobile'] = $users[$v['user_id']]['mobile'];
				$list[$k]['ref_user'] = $ref_users[$v['ref_user_id']]['nick_name'];
			}
		}
		
		return $list;
	}
	
	public function selectDistinctUserid() {
		return $this->pointLogDao()->searchDistinctUserid();
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
		return D('Point','Logic');
	}
	
	private function pointLogDao(){
		return D('Common/Point/PointLog');
	}
	
	private function userInfoDao(){
		return D('Common/User/UserInfo');
	}
	
}