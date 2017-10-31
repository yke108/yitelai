<?php
namespace Common\Model\Material;
use Think\Model\RelationModel;

class DownModel extends RelationModel {
    protected $tableName = 'material_down';
    protected $pk = 'log_id';
    
    protected $_validate = array(
    		array('user_id','require','用户不能为空'), //默认情况下用正则进行验证
    		array('material_id','require','素材不能为空'), //默认情况下用正则进行验证
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
	
	public function updateRecords($map, $data){
		return $this->where($map)->save($data);
	}
	
	public function delRecord($id){
		return $this->delete($id);
	}
	
	public function delRecords($ids){
		return $this->where(array('label_id'=>array('in',$ids)))->delete();
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
    
    public function searchRecordsField($map, $orderBy){
    	return $this->where($map)->order($orderBy)->getField('label_id, label_name');
    }
}