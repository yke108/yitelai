<?php
namespace Common\Service;
use Common\Basic\Status;

class WxLogService{
	
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->wxLogDao()->getRecord($id);
	}
	
	public function createOrModify($params){
		//参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		$wxLogDao = $this->wxLogDao();
		if (!$wxLogDao->create($data)){
			 throw new \Exception($wxLogDao->getError());
		}
		if ($params['log_id'] > 0){
			$result = $wxLogDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $wxLogDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
		return $result;
	}
	
	public function createNewsLog($params){
		$map_news['news_id'] = array('in', $params['checkid']);
		$orderby = 'sort_order DESC';
		$news_list = $this->wxNewsDao()->searchAllRecords($map_news, $orderby);
		$data = array();
		foreach ($news_list as $v) {
			//$url = DK_DOMAIN.'/wap/index.php/article/index/info/id/'.$v['news_id'];
			$data[] = array(
					'title'=>urlencode($v['title']),
					'description'=>urlencode($v['description']),
					'url'=>urlencode($v['url']),
					'picurl'=>urlencode(picurl($v['picture'])),
			);
		}
		
		$map_user = array();
		if ($params['group_id'] == 9999) {
			$map_user['district_id'] = 0;
		}elseif ($params['group_id'] >= 3574) {
			$map_user['district_id'] = $params['group_id'];
		}elseif ($params['group_id'] >= 1) {
			$map_user['group_id'] = $params['group_id'];
		}
		if ($params['type_id'] >= 1) {
			$map_user['type_id'] = $params['type_id'];
		}
		$total = $this->userGroupDao()->searchRecordsCount($map_user);
		
		$datas = array(
				'msg_type' => Status::MsgTypeNews,
				'group_id' => $params['group_id'],
				'type_id' => $params['type_id'],
				'data' => serialize($data),
				'total' => $total,
				'add_time' => NOW_TIME,
				'admin_id' => $params['admin_id'],
		);
		$result = $this->wxLogDao()->addRecord($datas);
		if ($result < 1){
			throw new \Exception('添加失败');
		}
	}
	
	public function createTextLog($params){
		if ($params['group_id']) {
			$map_group['group_id'] = $params['group_id'];
			$total = $this->userGroupDao()->searchRecordsCount($map_group);
		}else {
			$total = $this->userInfoDao()->searchRecordsCount();
		}
	
		$datas = array(
				'msg_type' => Status::MsgTypeText,
				'group_id' => $params['group_id'],
				'type_id' => $params['type_id'],
				'data' => $params['content'],
				'total' => $total,
				'add_time' => NOW_TIME,
				'admin_id' => $params['admin_id'],
		);
		$result = $this->wxLogDao()->addRecord($datas);
		if ($result < 1){
			throw new \Exception('添加失败');
		}
	}
	
	public function logDelete($id){
		$result = $this->wxLogDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function logCancel($id){
		$data = array(
				'log_id'=>$id,
				'status'=>Status::LogStatusCancel
		);
		$result = $this->wxLogDao()->saveRecord($data);
		if ($result === false) throw new \Exception('取消失败');
	}
	
	public function getPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		if(!empty($params['start_time'])){
			$map['add_time'][] = array('egt', strtotime($params['start_time']));
		}
		if(!empty($params['end_time'])){
			$map['add_time'][] = array('elt', strtotime($params['end_time']) + 86400);
		}
		
		$wxLogDao = $this->wxLogDao();
		$count = $wxLogDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'log_id DESC' : $params['orderby'];
			$list = $wxLogDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$this->outPutForList($list),
			'count'=>$count,
		);
	}
	
	public function send($params){
		$text = $this->wxLogDao()->getRecord($params['log_id']);
		$data = array(
				//'touser' => 'oh842t4CMxE_65VKPfJ3V6PfprCs',
				'msgtype' => 'text',
				'text' => array('content'=>urlencode($text['content']))
		);
	}
	
	private function outPutForList($list) {
		if (!empty($list)) {
			$groups = $this->userGroupDao()->searchFieldRecords();
			$districts = $this->districtDao()->searchFieldRecords();
			
			foreach ($list as $k => $v) {
				if ($v['group_id'] >= 3574) {
					$list[$k]['group_name'] = $districts[$v['group_id']];
				}else {
					$list[$k]['group_name'] = $groups[$v['group_id']]['group_name'];
				}
			}
		}
		
		return $list;
	}
	
	//调用model
	private function wxLogDao(){
		return D('Common/Weixin/WxLog');
	}
	
	private function wxNewsDao(){
		return D('Common/Weixin/WxNews');
	}
	
	private function userGroupDao(){
		return D('Common/User/Group');
	}
	
	private function userInfoDao(){
		return D('Common/User/UserInfo');
	}
	
	private function districtDao(){
		return D('Common/Region/District');
	}
}//end FeedbackService!