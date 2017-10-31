<?php
namespace Common\Service;
use Common\Service\Interfaces\MessageServiceInterface;

class MessageService{
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
		
        $list = $this->messageDao()->searchMessages($map, $orderBy, $start, $limit);
        return $this->outputForList($list, $user_id, $reg_time);
    }
	
	public function searchMessagesCount($map){
		if ($map['type'] == 0) {
			unset($map['user_id']);
		}
		//$map['add_time'] = array('lt', NOW_TIME);
		return $this->messageDao()->searchMessagesCount($map);
    }
    
    private function outputForList($list, $user_id, $reg_time) {
    	if (!empty($list)) {
    		foreach ($list as $v) {
    			$msg_ids[] = $v['msg_id'];
    		}
    		$map = array(
    				'msg_id'=>array('in', $msg_ids),
    				'user_id'=>$user_id
    		);
    		$message_status = $this->messageStatusDao()->where($map)->getField('id, msg_id');
    		
    		foreach ($list as $k => $v) {
    			//判断是否已读
    			if (in_array($v['msg_id'], $message_status) || $v['add_time'] < $reg_time) {
    				$list[$k]['is_read'] = 1;
    			}else {
    				$list[$k]['is_read'] = 0;
    			}
    			//简介
    			$list[$k]['description'] = strip_tags($v['content']);
    		}
    	}
    	
    	return $list;
    }
    
    public function getMessageInfo($map){
    	$map['add_time'] = array('lt', NOW_TIME);
    	return $this->messageDao()->getMessageInfo($map);
    }
    
    public function searchMessageInfo($map, $orderby = 'add_time DESC'){
    	$map['add_time'] = array('lt', NOW_TIME);
    	return $this->messageDao()->where($map)->order($orderby)->find();
    }
	
	public function addMessage($user_id, &$data){
		$data['user_id'] = $user_id;
		$data['add_time'] < 1 && $data['add_time'] = time();
		return $this->messageDao()->add($data);
	}
	
	public function addAllMessage($user_ids, &$data){
		if(empty($user_ids)){throw new \Exception('缺少参数');}
		$users=$this->userService()->getUsers($user_ids);
		$data['user_id'] = $user_id;
		$data['add_time'] < 1 && $data['add_time'] = time();
		foreach($user_ids as $key=>$val){
			$data['user_id']=$val;
			$data['user_name']=$users[$val]['nick_name'];
			$datas[]=$data;
		}
		
		return $this->messageDao()->addAll($datas);
	}
	
	
	
	public function setRead($params){
		$map = array(
				'msg_id'=>$params['msg_id'],
				'user_id'=>$params['user_id']
		);
		$info = M('message_status')->where($map)->find();
		if ($info) {
			return true;
		}
		
		M()->startTrans();
		
		$data = array(
				'msg_id'=>$params['msg_id'],
				'user_id'=>$params['user_id']
		);
		$result = $this->messageStatusDao()->add($data);
		if ($result === false) {
			M()->rollback();
			return false;
		}
		
		$result = $this->messageDao()->where(array('msg_id'=>$params['msg_id']))->setInc('read_count');
		if ($result === false) {
			M()->rollback();
			return false;
		}
		
		M()->commit();
	}
	
	private function messageDao(){
		return D('Message');
	}
	
	private function messageStatusDao(){
		return D('MessageStatus');
	}
	
	private function userService(){
		return D('User','Service');
	}
	
}