<?php
namespace Common\Service\Information;

class UserMsgService{
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->userMsgDao()->getRecord($id);
	}
	
	public function infoCreateOrModify($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		$userMsgDao = $this->userMsgDao();
		if (!$userMsgDao->create($data)){
			 throw new \Exception($userMsgDao->getError());
		}
		if ($params['msg_id'] > 0){
			$result = $userMsgDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $userMsgDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function searchMessages($map, $orderBy, $start, $limit){
		$user_id = $map['user_id'];
		$reg_time = $map['reg_time'];
		if ($map['type'] == 0) {
			unset($map['user_id']);
			unset($map['reg_time']);
		}elseif($map['type']==100 && $user_id>0){
			$map['user_id']=array('in',array(0,$user_id));
			unset($map['type']);
			unset($map['reg_time']);
		}
		
		$map['add_time'] = array('lt', NOW_TIME);
		$orderBy = 'msg_id DESC';
		
		$list = $this->userMsgDao()->searchRecords($map, $orderBy, $start, $limit);
		return $this->outputForList($list, $user_id, $reg_time);
	}
	
	public function searchMessagesCount($map){
		if ($map['type'] == 0) {
			unset($map['user_id']);
		}
		//$map['add_time'] = array('lt', NOW_TIME);
		return $this->userMsgDao()->searchRecordsCount($map);
	}
	
	public function getMessageInfo($map){
		$map['add_time'] = array('lt', NOW_TIME);
		return $this->userMsgDao()->findRecord($map);
	}
	
	public function addMessage($user_id, &$data){
		$data['user_id'] = $user_id;
		$data['add_time'] < 1 && $data['add_time'] = time();
		return $this->userMsgDao()->add($data);
	}
	
	public function addAllMessage($user_ids, &$data){
		if(empty($user_ids)){throw new \Exception('缺少参数');}
		$map = array(
				'user_id'=>array('in', $user_ids),
		);
		$users=$this->userInfoDao()->where($map)->getField('user_id, nick_name, user_img, mobile');
		$data['add_time'] < 1 && $data['add_time'] = time();
		foreach($user_ids as $key=>$val){
			$data['user_id']=$val;
			$datas[]=$data;
		}
		
		return $this->userMsgDao()->addAll($datas);
	}
	
	public function setRead($params){
		$map = array(
				'msg_id'=>$params['msg_id'],
				'user_id'=>$params['user_id']
		);
		$info = $this->userMsgStatusDao()->where($map)->find();
		if ($info) {
			return true;
		}
		$data = array(
				'msg_id'=>$params['msg_id'],
				'user_id'=>$params['user_id']
		);
		return $this->userMsgStatusDao()->add($data);
	}
	
	public function infoDelete($id){
		$map = array('msg_id'=>id);
		$result = $this->userMsgDao()->deleteRecord($map);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	//分页查询
	public function infoPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		if (!empty($params['keyword'])) {
			$map['title'] = array('like', '%'.$params['keyword'].'%');
		}
		if (!empty($params['start_time'])) {
			$map['add_time'][] = array('egt', strtotime($params['start_time']));
		}
		if (!empty($params['end_time'])) {
			$map['add_time'][] = array('elt', strtotime($params['end_time']) + 86400);
		}
		if (!empty($params['msg_type'])) {
			$map['msg_type'] = $params['msg_type'];
		}
		
		$userMsgDao = $this->userMsgDao();
		$count = $userMsgDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'msg_id DESC' : $params['orderby'];
			$list = $userMsgDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$this->outputForList($list, $params['user_id'], $params['reg_time']),
			'count'=>$count,
		);
	}
	
	public function infoAllList($map, $orderby){
		return $this->userMsgDao()->searchAllRecords($map, $orderby);
	}
	
	private function outputForList($list, $user_id, $reg_time){
		if (!empty($list)) {
			$msg_ids = $user_ids = array();
			foreach ($list as $v) {
				$msg_ids = $v['msg_id'];
				$user_ids[] = $v['user_id'];
			}
			//已读
			$map = array(
					'msg_id'=>array('in', $msg_ids),
					'user_id'=>$user_id,
			);
			$message_status = $this->userMsgStatusDao()->where($map)->getField('id, msg_id');
			//会员
			$users = $this->userInfoDao()->getUsersByIds($user_ids);
			
			foreach ($list as $k => $v) {
				//判断是否已读
				if (in_array($v['msg_id'], $message_status) || $v['add_time'] < $reg_time) {
					$list[$k]['is_read'] = 1;
				}else {
					$list[$k]['is_read'] = 0;
				}
				//会员
				$list[$k]['nick_name'] = $users[$v['user_id']]['nick_name'];
			}
		}
		
		return $list;
	}
	
	private function userMsgDao(){
		return D('Common/Information/User/Msg');
	}
	
	private function userInfoDao(){
		return D('Common/Information/User/Info');
	}
	
	private function userMsgStatusDao(){
		return D('Common/Information/User/MsgStatus');
	}
}//end HelpService!甜品