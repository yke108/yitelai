<?php
namespace Common\Model\Distributor;
use Think\Model\RelationModel;

class TypeModel extends RelationModel {
	// tableName属性来改变默认的规则
    protected $tableName = 'distributor_type';
    protected $pk = 'type_id';
    
    protected $_validate = array(
    		array('type_name','require','类型名称不能为空'), //默认情况下用正则进行验证
    		array('type_name','','类型名称已存在',0,'unique',3), // 验证cat_name字段是否唯一
    );
    
    protected $_auto = array (
    		
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
	
	public function delRecords($ids){
		return $this->where(array('type_id'=>array('in',$ids)))->delete();
	}
	
	public function searchRecords($map, $orderBy, $start, $limit){
        return $this->where($map)->order($orderBy)->page($start, $limit)->select();
    }
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
    }
    
    //定义查询方法
    public function searchAllRecords($map){
    	return $this->where($map)->select();
    }
    
    public function searchRecordsField($map){
    	return $this->where($map)->getField('type_id, parent_id, type_name, deposit, service_charge, platform_take');
    }
    
    public function getTypesByIds($ids){
    	$map['type_id'] = array('in', $ids);
    	return $this->where($map)->getField('type_id, parent_id, type_name, deposit, service_charge, platform_take');
    }
}