<?php
namespace Common\Model\Cook;
use Think\Model\RelationModel;

class CommentModel extends RelationModel {
    protected $tableName = 'cook_comment';
    
    protected $_validate = array(
    		array('user_id','require','用户不能为空',1),
    		array('book_id','require','菜谱不能为空',1),
    		array('content','require','评论内容不能为空',1),
    );
    
    protected $_auto = array (
    		array('add_time','time',1,'function'), // 对add_time字段在更新的时候写入当前时间戳
    		array('update_time','time',2,'function'), // 对update_time字段在更新的时候写入当前时间戳
    );
    
	public function getRecord($id){
   		return $this->find($id);
	}
	
	public function findRecord($map){
		return $this->where($map)->find();
	}
	
	public function addRecord($data){
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
    
    public function searchAllRecords($map, $orderBy){
    	return $this->where($map)->order($orderBy)->select();
    }
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
    }
}