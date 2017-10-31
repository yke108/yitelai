<?php
namespace Common\Model\Feedback;
use Think\Model\RelationModel;

class FeedbackReplyModel extends RelationModel {
    protected $tableName = 'feedback_reply';
    
    protected $_validate = array(
    		array('log_id','require','留言ID不能为空',1),
    		array('content','require','内容不能为空',1),
    		//array('ref_id','require','操作者不能为空',1),
    		array('ref_type','require','操作者类型不能为空',1),
    );
    
    protected $_auto = array (
    		array('add_time','time',1,'function'),
    );
    
	public function getRecord($id){
   		return $this->find($id);
	}
	
	public function findRecord($map){
		return $this->where($map)->find();
	}
	
	public function addRecord($data){
		$data['add_time'] = NOW_TIME;
		return $this->add($data);
	}
	
	public function saveRecord($data){
		return $this->save($data);
	}
	
	public function delRecord($id){
		return $this->delete($id);
	}
	
	public function searchRecords($map, $orderBy, $start, $limit){
        return $this->where($map)->order($orderBy)->page($start, $limit)->select();
    }
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
    }
    
    //定义查询方法
    public function searchAllRecords($map, $orderBy){
    	return $this->where($map)->order($orderBy)->select();
    }
    
    public function searchDistinctUserid($map){
    	return $this->where($map)->distinct(true)->field('user_id')->select();
    }
}