<?php
namespace Common\Model\Material;
use Think\Model\RelationModel;

class InfoModel extends RelationModel {
    protected $tableName = 'material_info';
    protected $pk = 'material_id';
    
    protected $_validate = array(
    		array('material_id','require','素材编号不能为空'), //默认情况下用正则进行验证
    		array('material_title','require','素材名称不能为空'), //默认情况下用正则进行验证
    		array('cat_id','require','请选择素材分类'), //默认情况下用正则进行验证
    		array('designer_id','require','请选择设计师'), //默认情况下用正则进行验证
    		array('is_show',array(0, 1),'值的范围不正确',2,'in'), // 当值不为空的时候判断是否在一个范围内
    		array('is_recommend',array(0, 1),'值的范围不正确',2,'in'), // 当值不为空的时候判断是否在一个范围内
    		array('material_image','require','素材主图不能为空'), //默认情况下用正则进行验证
    		array('material_gallery','require','素材相册不能为空'), //默认情况下用正则进行验证
    );
    
    protected $_auto = array (
    		array('add_time','time',2,'function'), // 对add_time字段在更新的时候写入当前时间戳
    		array('update_time','time',2,'function'), // 对update_time字段在更新的时候写入当前时间戳
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
    
    public function searchRecord($map, $orderBy){
    	return $this->where($map)->order($orderBy)->find();
    }
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
    }
    
    public function searchAllRecords($map){
    	return $this->where($map)->select();
    }
    
    public function searchFieldRecords($map, $field = 'material_id, material_title, material_image'){
    	return $this->where($map)->getField($field);
    }
}