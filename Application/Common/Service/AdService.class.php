<?php
namespace Common\Service;
class AdService{
	// Cat
	public function getPosition($id){
		if ($id < 1) return false;
		return $this->adPositionDao()->getRecord($id);
	}
	
	public function findPosition($map){
		return $this->adPositionDao()->findRecord($map);
	}
	
	public function positionCreateOrModify($params){
		// 自动验证
		$rules = array(
			array('position_name', 'require', '名称是必须的'),
		);
		// 接收到的参数
		$data = array(
				'position_name'=>trim($params['position_name']),
				'distributor_id'=>$params['distributor_id'],
				'position_code'=>trim($params['position_code']),
				'position_type'=>intval($params['position_type']),
		);	
		if($params['position_id'] > 0){
			$data['position_id'] = $params['position_id'];
		}
		$adPositionDao = $this->adPositionDao();
		if (!$adPositionDao->validate($rules)->create($data)){
			 throw new \Exception($adPositionDao->getError());
		}
		if ($params['position_id'] > 0){
			$result = $adPositionDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $adPositionDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function positionDelete($id){
		$result = $this->adPositionDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function positionPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		if (isset($params['distributor_id'])) {
			$map['distributor_id'] = $params['distributor_id'];
		}
		if (isset($params['position_type'])) {
			$map['position_type'] = $params['position_type'];
		}
		
		$adPositionDao = $this->adPositionDao();
		$count = $adPositionDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'position_id desc' : $params['orderby'];
			$list = $adPositionDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
		);
	}
	
	public function positionAllList(){
		return $this->adPositionDao()->searchAllRecords();
	}
	
	private function adPositionDao(){
		//调用model
		return D('Common/Ad/AdPosition');
	}

	//news_info
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->adInfoDao()->getRecord($id);
	}
	
	public function infoCreateOrModify($params){
		// 自动验证
		$rules = array(
			array('ad_name', 'require', '标题是必须的'),
		);
		//参数
		$data = array(
			'position_code'=>trim($params['position_code']),
			'position_type'=>trim($params['position_type']),
			'ad_type'=>trim($params['ad_type']),		
			'ad_value'=>trim($params['ad_value']),
			'ad_name'=>trim($params['ad_name']),
			'ad_desc'=>trim($params['ad_desc']),
			'ad_time'=>trim($params['ad_time']),
			'ad_picture'=>trim($params['ad_picture']),/*---*/
			'is_for_pc'=>trim($params['is_for_pc']),
			'start_time'=>time(),/*----*/
			'end_time'=>time(),
			'sort_order'=>trim($params['sort_order']),
			'click_count'=>trim($params['click_count']),
			'enabled'=>trim($params['enabled']),		
		);
		if($params['ad_id'] > 0){
			$data['ad_id'] = $params['ad_id'];
		}
		$adInfoDao = $this->adInfoDao();
		if (!$adInfoDao->validate($rules)->create($data)){
			 throw new \Exception($adInfoDao->getError());
		}
		if ($params['ad_id'] > 0){
			$result = $adInfoDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $adInfoDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function infoDelete($id){
		$result = $this->adInfoDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	//分页查询
	public function infoPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = $params['map'] ? $params['map'] : array();
		if ($params['keyword']) {
			$map_adp['position_name'] = array('like', '%'.trim($params['keyword']).'%');
			$adps = $this->adPositionDao()->where($map_adp)->select();
			if (empty($adps)) {
				return array();
			}
			$position_codes = array();
			foreach ($adps as $v) {
				$position_codes[] = $v['position_code'];
			}
			$map['position_code'] = array('in', $position_codes);
		}
		if ($params['status'] == 1) { //正常
			$map['enabled'] = 1;
			$map['end_time'] = array('gt', NOW_TIME);
		}
		if ($params['status'] == 2) { //已过期
			$map['enabled'] = 1;
			$map['end_time'] = array('lt', NOW_TIME);
		}
		if ($params['status'] == 3) { //无效
			$map['enabled'] = 0;
		}
		if (!empty($params['start_time'])) {
			$map['start_time'][] = array('egt', strtotime($params['start_time']));
		}
		if (!empty($params['end_time'])) {
			$map['end_time'][] = array('elt', strtotime($params['end_time']) + 86400);
		}
		
		$adInfoDao = $this->adInfoDao();
		$count = $adInfoDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order ASC, ad_id DESC' : $params['orderby'];
			$list = $adInfoDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
		);
	}
	
	public function infoAllList($params){
		$map = array(
				'enabled'=>1,
				'start_time'=>array('elt', NOW_TIME),
				'end_time'=>array('egt', NOW_TIME)
		);
		if (isset($params['distributor_id'])) {
			$map['distributor_id'] = $params['distributor_id'];
		}
		
		$orderby = empty($params['orderby']) ? 'sort_order DESC' : $params['orderby'];
		$list = $this->adInfoDao()->searchAllRecords($map, $orderby);
		$new_list = array();
		foreach ($list as $v) {
			$new_list[$v['position_code']][] = $v;
		}
		
		return $new_list;
	}
	
	private function adInfoDao(){
		//返回model
		return D('Common/Ad/AdInfo');
	}
}//end HelpService!甜品