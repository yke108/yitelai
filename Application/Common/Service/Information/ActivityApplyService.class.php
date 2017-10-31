<?php
namespace Common\Service\Information;

class ActivityApplyService{
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->activityApplyDao()->getRecord($id);
	}
	
	public function infoCreateOrModify($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		if($params['news_id'] > 0){
			$data['news_id'] = $params['news_id'];
		}
		$activityApplyDao = $this->activityApplyDao();
		if (!$activityApplyDao->create($data)){
			 throw new \Exception($activityApplyDao->getError());
		}
		if ($params['news_id'] > 0){
			$result = $activityApplyDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $activityApplyDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function infoDelete($id){
		$result = $this->activityApplyDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function infoRecommend($info){
		$is_recommend = $info['is_recommend'] == 0 ? 1 : 0;
		$result = $this->activityApplyDao()->where(array('news_id'=>$info['news_id']))->save(array('is_recommend'=>$is_recommend));
		if ($result === false) throw new \Exception('设置失败');
	}
	
	public function infoOpen($info){
		$is_open = $info['is_open'] == 0 ? 1 : 0;
		$result = $this->activityApplyDao()->where(array('news_id'=>$info['news_id']))->save(array('is_open'=>$is_open));
		if ($result === false) throw new \Exception('设置失败');
	}
	
	//分页查询
	public function infoPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		if (!empty($params['keyword'])) {
			$map['title'] = array('like', '%'.$params['keyword'].'%');
		}
		if (!empty($params['cat_id'])) {
			$clist = $this->catChilds($params['cat_id']);
			$map['cat_id'] = array('in', $clist);
		}
		if (isset($params['is_open'])) {
			$map['is_open'] = $params['is_open'];
		}
		if (isset($params['is_recommend'])) {
			$map['is_recommend'] = $params['is_recommend'];
		}
		
		$activityApplyDao = $this->activityApplyDao();
		$count = $activityApplyDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order desc' : $params['orderby'];
			$list = $activityApplyDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	public function infoAllList($params){
		$map = array();
		if (!empty($params['cat_id'])) {
			$map['cat_id'] = $params['cat_id'];
		}
		$activityApplyDao = $this->activityApplyDao();
		$orderby = empty($params['orderby']) ? 'sort_order desc' : $params['orderby'];
		return $activityApplyDao->searchAllRecords($map, $orderby);
	}
	
	private function outputForList($list){
		if (!empty($list)) {
			$user_ids = $activity_ids = array();
			foreach ($list as $v) {
				$user_ids[] = $v['user_id'];
				$activity_ids[] = $v['activity_id'];
			}
			$users = $this->userInfoDao()->getUsersByIds($user_ids);
			$activitys = $this->activityInfoDao()->getRecordsField($user_ids);
			
			foreach ($list as $k => $v) {
				$list[$k]['user_img'] = $users[$v['user_id']]['user_img'];
				$list[$k]['headimgurl'] = $users[$v['user_id']]['headimgurl'];
				$list[$k]['activity_title'] = $activitys[$v['activity_id']]['title'];
			}
		}
		
		return $list;
	}
	
	private function activityApplyDao(){
		return D('Common/Information/Activity/Apply');
	}
	
	private function activityInfoDao(){
		return D('Common/Information/Activity/Info');
	}
	
	private function userInfoDao(){
		return D('Common/User/UserInfo');
	}
}//end HelpService!甜品