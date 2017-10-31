<?php
namespace Common\Service;

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
		
		$activityApplyDao = $this->activityApplyDao();
		if (!$activityApplyDao->create($data)){
			 throw new \Exception($activityApplyDao->getError());
		}
		
		if ($params['apply_id'] > 0){
			$result = $activityApplyDao->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$map = array('user_id'=>$params['user_id'], 'activity_id'=>$params['activity_id']);
			$apply_info = $activityApplyDao->where($map)->find();
			if ($apply_info) throw new \Exception('您已经报名过');
			
			M()->startTrans();
			
			$result = $activityApplyDao->add();
			if ($result < 1){
				M()->rollback();
				throw new \Exception('添加失败');
			}
			
			//统计报名数
			if ($this->activityInfoDao()->where(array('activity_id'=>$params['activity_id']))->setInc('apply_count') === false) {
				M()->rollback();
				throw new \Exception('系统错误');
			}
			
			M()->commit();
		}
	}
	
	public function infoDelete($id){
		$result = $this->activityApplyDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function infoRecommend($info){
		$is_recommend = $info['is_recommend'] == 0 ? 1 : 0;
		$result = $this->activityApplyDao()->where(array('apply_id'=>$info['apply_id']))->save(array('is_recommend'=>$is_recommend));
		if ($result === false) throw new \Exception('设置失败');
	}
	
	public function infoOpen($info){
		$is_open = $info['is_open'] == 0 ? 1 : 0;
		$result = $this->activityApplyDao()->where(array('apply_id'=>$info['apply_id']))->save(array('is_open'=>$is_open));
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
		if (!empty($params['activity_id'])) {
			$map['activity_id'] = $params['activity_id'];
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
			$orderby = empty($params['orderby']) ? 'apply_id asc' : $params['orderby'];
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
			$user_ids = $apply_ids = array();
			foreach ($list as $v) {
				$user_ids[] = $v['user_id'];
				$apply_ids[] = $v['apply_id'];
			}
			$users = $this->userInfoDao()->getUsersByIds($user_ids);
			$activitys = $this->activityInfoDao()->getRecordsField($user_ids);
			foreach ($list as $k => $v) {
				$list[$k]['nick_name'] = $users[$v['user_id']]['nick_name'];
				$list[$k]['avatar'] = $users[$v['user_id']]['user_img'] ? picurl( $users[$v['user_id']]['user_img'], 'b90') : $users[$v['user_id']]['headimgurl'];
			}
		}
		
		return $list;
	}


	public function activityApplyPagerList($params){
		$map = array();
		$map['user_id'] = $params['user_id'];
		$activityApplyDao = $this->activityApplyDao();
		$count = $activityApplyDao->searchRecordsCount($map);
		$_list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'apply_id DESC' : $params['orderby'];
			$field = array();
			$data = $activityApplyDao->searchFieldRecords($map, $field, $orderby, $params['page'], $params['pagesize']);
			foreach ($data as $key => $val){
				$_t = $val;
				$_t['inputtime'] = date('Y-m-d H:i:s', $val['add_time']);
				$_t['detailUrl'] = U('user/history/activitydetail', array('apply_id' => $val['apply_id']));
				$activityFind = $this->activityInfoDao()->findFieldRecord(array('apply_id' => $val['apply_id']), array('title'));
				$_t['activity_title'] = $activityFind['title'];
				$_list[]  = $_t;
			}
		}
		return array(
			'list'=>$_list,
			'count'=>$count,
		);
	}
	
	private function activityApplyDao(){
		return D('Common/ActivityInfo/Apply');
	}
	
	private function activityInfoDao(){
		return D('Common/ActivityInfo/Info');
	}
	
	private function userInfoDao(){
		return D('Common/User/UserInfo');
	}
}//end HelpService!甜品