<?php
namespace Common\Service\Distributor;

class ActivityService{
	public function getInfo($id){
		if ($id < 1) return false;
		$info = $this->distributorActivityDao()->getRecord($id);
		return $this->outputForInfo($info);
	}
	
	public function infoCreateOrModify($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		$data['start_time'] = strtotime($params['start_time']);
		$data['end_time'] = strtotime($params['end_time']);
		$data['cat_ids'] = $params['cat_ids'] ? implode(',', $params['cat_ids']) : '';
		$data['record_ids'] = $params['record_ids'] ? implode(',', $params['record_ids']) : '';
		
		$distributorActivityDao = $this->distributorActivityDao();
		if (!$distributorActivityDao->create($data)){
			 throw new \Exception($distributorActivityDao->getError());
		}
		
		M()->startTrans();
		
		if ($params['activity_id'] > 0){
			$result = $distributorActivityDao->save();
			if ($result === false){
				M()->rollback();
				throw new \Exception('修改失败');
			}
			
			$activity_id = $params['activity_id'];
		} else {
			$activity_id = $distributorActivityDao->add();
			if ($activity_id < 1){
				M()->rollback();
				throw new \Exception('添加失败');
			}
		}
		
		//编辑发起人
		if ($params['launchs']) {
			foreach ($params['launchs'] as $k => $v) {
				$data = array(
						'launch_id'=>$k,
						'launch_image'=>$v['launch_image'],
						'launch_name'=>$v['launch_name'],
						'update_time'=>NOW_TIME,
				);
				$result = $this->activityLaunchDao()->saveRecord($data);
				if ($result === false){
					M()->rollback();
					throw new \Exception('系统错误');
				}
			}
		}
		
		//添加发起人
		if ($params['launch_image']) {
			$dataList = array();
			foreach ($params['launch_image'] as $k => $v) {
				if (empty($v)) throw new \Exception('发起人头像不能为空');
				if (empty($params['launch_name'][$k])) throw new \Exception('发起人姓名不能为空');
				$dataList[] = array(
						'activity_id'=>$activity_id,
						'launch_image'=>$v,
						'launch_name'=>$params['launch_name'][$k],
						'add_time'=>NOW_TIME,
				);
			}
			$result = $this->activityLaunchDao()->addAll($dataList);
			if ($result === false){
				M()->rollback();
				throw new \Exception('系统错误');
			}
		}
		
		M()->commit();
	}
	
	public function infoDelete($id){
		$result = $this->distributorActivityDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function infoRecommend($activity_id){
		$info = $this->getInfo($activity_id);
		$is_recommend = $info['is_recommend'] == 0 ? 1 : 0;
		$result = $this->distributorActivityDao()->where(array('activity_id'=>$info['activity_id']))->save(array('is_recommend'=>$is_recommend));
		if ($result === false) throw new \Exception('设置失败');
	}
	
	public function infoOpen($activity_id){
		$info = $this->getInfo($activity_id);
		$is_open = $info['is_open'] == 0 ? 1 : 0;
		$result = $this->distributorActivityDao()->where(array('activity_id'=>$info['activity_id']))->save(array('is_open'=>$is_open));
		if ($result === false) throw new \Exception('设置失败');
	}
	
	public function readCount($activity_id){
		$this->distributorActivityDao()->where(array('activity_id'=>$activity_id))->setInc('read_count');
	}
	
	//分页查询
	public function infoPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = $params['map'] ? $params['map'] : array();
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
		
		$distributorActivityDao = $this->distributorActivityDao();
		$count = $distributorActivityDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order desc, activity_id desc' : $params['orderby'];
			$list = $distributorActivityDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
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
		$distributorActivityDao = $this->distributorActivityDao();
		$orderby = empty($params['orderby']) ? 'sort_order desc' : $params['orderby'];
		return $distributorActivityDao->searchAllRecords($map, $orderby);
	}
	
	private function outputForList($list){
		if (!empty($list)) {
			foreach ($list as $k => $v) {
				
			}
		}
		
		return $list;
	}
	
	private function outputForInfo($info){
		if (!empty($info)) {
			//是否过期
			$is_active = 0;
			if ($info['start_time'] <= NOW_TIME && $info['end_time'] >= NOW_TIME) {
				$is_active = 1;
			}
			$info['is_active'] = $is_active;
		}
		
		return $info;
	}
	
	//返回model
	private function distributorActivityDao(){
		return D('Common/Distributor/Activity');
	}
}//end HelpService!甜品