<?php
namespace Common\Model\Zan;
use Think\Model\RelationModel;

class LogModel extends RelationModel {
    protected $tableName = 'zan_log';
    protected $pk = 'zan_id';
    
    protected $_validate = array(
    		array('user_id','require','用户ID不能为空'), //默认情况下用正则进行验证
    		array('ref_type','require','关联类型不能为空'), //默认情况下用正则进行验证
    		array('ref_id','require','关联ID不能为空'), //默认情况下用正则进行验证
    );
    
    protected $_auto = array (
    		array('add_time','time',1,'function'), // 对add_time字段在更新的时候写入当前时间戳
    );
    
	public function getRecord($id){
   		return $this->find($id);
	}
	
	public function findRecord($map){
		return $this->where($map)->find();
	}
	
	public function delRecord($id){
		return $this->delete($id);
	}
	
	public function delRecords($map){
		return $this->where($map)->delete();
	}
	
	public function searchRecords($map, $orderBy, $start, $limit){
        return $this->where($map)->order($orderBy)->page($start, $limit)->select();
    }
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
    }
}