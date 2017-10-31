<?php
namespace Common\Model\Material;
use Think\Model\RelationModel;

class CatModel extends RelationModel {
    protected $tableName = 'material_cat';
    protected $pk = 'cat_id';
    
    protected $_validate = array(
    		array('cat_name','require','分类名称不能为空'), //默认情况下用正则进行验证
    		array('parent_id,cat_name','','分类名称已存在',0,'unique',3), // 在新增的时候验证name字段是否唯一
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
		return $this->where(array('cat_id'=>array('in',$ids)))->delete();
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
    	return $this->where($map)->order($orderBy)->getField('cat_id,cat_name');
    }
}