<?php
namespace Common\Service;
use Common\Basic\Status;

class DistributorMessageService{
    public function searchMessages($map, $orderBy = 'add_time DESC', $start, $limit){
    	$distributor_id = $map['distributor_id'];
    	$reg_time = $map['reg_time'];
    	if (isset($map['type'])) {
    		if ($map['type'] == 0) {
    			unset($map['distributor_id']);
    			unset($map['reg_time']);
    		}elseif($map['type']==100 && $distributor_id>0){
    			$map['distributor_id']=array('in',array(0,$distributor_id));
    			unset($map['type']);
    			unset($map['reg_time']);
    		}
    	}
		$map['add_time'] = array('lt', NOW_TIME);
		
        $list = $this->distributorMessageDao()->searchMessages($map, $orderBy, $start, $limit);
        return $this->outputForList($list, $distributor_id, $reg_time);
    }
	
	public function searchMessagesCount($map){
		$distributor_id = $map['distributor_id'];
		$reg_time = $map['reg_time'];
		if ($map['type'] == 0) {
			unset($map['distributor_id']);
		}elseif($map['type']==100 && $distributor_id>0){
			$map['distributor_id']=array('in',array(0,$distributor_id));
			unset($map['type']);
			unset($map['reg_time']);
		}
		$map['add_time'] = array('lt', NOW_TIME);
		
		return $this->distributorMessageDao()->searchMessagesCount($map);
    }
    
    private function outputForList($list, $distributor_id, $reg_time) {
    	if (!empty($list)) {
    		$msg_ids = $cat_ids = $distributor_ids = $admin_ids = array();
    		foreach ($list as $v) {
    			$msg_ids[] = $v['msg_id'];
    			$cat_ids[] = $v['cat_id'];
    			$distributor_ids[] = $v['distributor_id'];
    			$admin_ids[] = $v['admin_id'];
    		}
    		//已读
    		$map = array(
    				'msg_id'=>array('in', $msg_ids),
    				'distributor_id'=>$distributor_id
    		);
    		$message_status = $this->messageStatusDao()->where($map)->getField('id, msg_id');
    		//分类
    		$map = array(
    				'cat_id'=>array('in', $cat_ids),
    		);
    		$cats = $this->messageCatDao()->searchFieldRecords($map);
    		//店铺
    		$map = array(
    				'distributor_id'=>array('in', $distributor_ids),
    		);
    		$distributors = $this->distributorService()->getFieldData($map);
    		//发送人
    		$admins = $this->adminInfoDao()->getRecords($admin_ids);
    		
    		foreach ($list as $k => $v) {
    			//判断是否已读
    			if (in_array($v['msg_id'], $message_status) || $v['add_time'] < $reg_time) {
    				$list[$k]['is_read'] = 1;
    			}else {
    				$list[$k]['is_read'] = 0;
    			}
    			//分类
    			$list[$k]['cat_name'] = $cats[$v['cat_id']];
    			//店铺
    			$list[$k]['distributor_name'] = $distributors[$v['distributor_id']]['distributor_name'];
    			//发送人
    			$list[$k]['admin_name'] = $admins[$v['admin_id']]['admin_name'];
    		}
    	}
    	
    	return $list;
    }
    
    public function getMessageInfo($map){
    	$map['add_time'] = array('lt', NOW_TIME);
    	$info = $this->distributorMessageDao()->getMessageInfo($map);
    	return $this->outputForInfo($info);
    }
    
    public function searchMessageInfo($map, $orderby = 'add_time DESC'){
    	$map['add_time'] = array('lt', NOW_TIME);
    	return $this->distributorMessageDao()->where($map)->order($orderby)->find();
    }
    
    public function getReadMessage($map) {
    	return $this->messageStatusDao()->where($map)->getField('id, msg_id');
    }
    
    private function outputForInfo($info) {
    	if (!empty($info)) {
    		//已读
    		$map = array(
    				'msg_id'=>$info['msg_id'],
    				'distributor_id'=>$info['distributor_id'],
    		);
    		$message_status = $this->messageStatusDao()->where($map)->getField('id, msg_id');
    		//分类
    		$cat = $this->messageCatDao()->getRecord($info['cat_id']);
    		//店铺
    		$distributor = $this->distributorService()->getInfo($info['distributor_id']);
    		
    		//判断是否已读
    		if (in_array($info['msg_id'], $message_status) || $info['add_time'] < $distributor['add_time']) {
    			$info['is_read'] = 1;
    		}else {
    			$info['is_read'] = 0;
    		}
    		//分类
    		$info['cat_name'] = $cat['cat_name'];
    		//店铺
    		$info['distributor_name'] = $distributor['distributor_name'];
    	}
    	 
    	return $info;
    }
	
	public function addMessage($distributor_id, &$data){
		$this->checkData($data);
		$data['distributor_id'] = $distributor_id;
		$data['add_time'] < 1 && $data['add_time'] = time();
		return $this->distributorMessageDao()->add($data);
	}
	
	public function addAllMessage($distributor_ids, &$data){
		$this->checkData($data);
		if(empty($distributor_ids)) throw new \Exception('缺少参数');
		$map = array('distributor_id'=>array('in', $distributor_ids));
		$distributors=$this->distributorService()->getFieldData($map);
		$data['add_time'] < 1 && $data['add_time'] = time();
		foreach($distributor_ids as $key=>$val){
			$data['distributor_id']=$val;
			$datas[]=$data;
		}
		
		return $this->distributorMessageDao()->addAll($datas);
	}
	
	public function modify($map, $data){
		$this->checkData($data);
		return $this->distributorMessageDao()->where($map)->save($data);
	}
	
	private function checkData($data) {
		if (empty($data['title'])) {
			throw new \Exception('消息主题不能为空');
		}
		if (empty($data['content'])) {
			throw new \Exception('消息内容不能为空');
		}
		return true;
	}
	
	public function messageDelete($id){
		$map = array('msg_id'=>id);
		$result = $this->distributorMessageDao()->delMessage($map);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function setRead($params){
		$map = array(
				'msg_id'=>$params['msg_id'],
				'distributor_id'=>$params['distributor_id']
		);
		$info = $this->messageStatusDao()->where($map)->find();
		if ($info) {
			return true;
		}
		
		M()->startTrans();
		
		$data = array(
				'msg_id'=>$params['msg_id'],
				'distributor_id'=>$params['distributor_id']
		);
		$result = $this->messageStatusDao()->add($data);
		if ($result === false) {
			M()->rollback();
			return false;
		}
		
		$result = $this->distributorMessageDao()->where(array('msg_id'=>$params['msg_id']))->setInc('read_count');
		if ($result === false) {
			M()->rollback();
			return false;
		}
		
		M()->commit();
	}
	
	private function distributorMessageDao(){
		return D('Common/Distributor/Message');
	}
	
	private function messageStatusDao(){
		return D('Common/Distributor/MessageStatus');
	}
	
	private function messageCatDao(){
		return D('Common/Distributor/MessageCat');
	}
	
	private function distributorService(){
		return D('Distributor','Service');
	}
	
	private function adminInfoDao(){
		return D('Common/Admin/AdminInfo');
	}
}