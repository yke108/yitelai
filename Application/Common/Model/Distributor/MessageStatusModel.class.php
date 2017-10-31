<?php
namespace Common\Model\Distributor;
use Think\Model;

class MessageStatusModel extends Model{
	protected $tableName = 'distributor_message_status';
	protected $pk = 'id';
	
	public function searchMessagesCount($map){
		return $this->where($map)->count();
    }
	
    public function searchMessages($map, $orderBy, $start, $limit){
    	return $this->where($map)->order($orderBy)->page($start, $limit)->select();
    }
    
    public function getMessageInfo($map){
    	return $this->where($map)->find();
    }
    
    public function delMessage($map){
    	return $this->where($map)->delete();
    }
}