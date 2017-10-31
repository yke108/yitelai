<?php
namespace Common\Model\Beauty;
use Think\Model\RelationModel;

class InfoModel extends RelationModel {
    protected $tableName = 'beauty_info';
    
    protected $_validate = array(
    		array('picture','require','图片不能为空',1),
    		array('cat_id','require','分类不能为空',1),
    		array('name','require','姓名不能为空',1),
    		array('age','require','年龄不能为空',1),
    		array('sign','require','星座不能为空',1),
    		array('career','require','职业不能为空',1),
    		array('intro','require','简介不能为空',1),
    		array('content','require','展示面不能为空',1),
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
    
    public function searchRecordsField($map){
    	return $this->where($map)->getField('beauty_id, name, picture');
    }
    
    public function getRecordsField($ids){
    	$map['beauty_id'] = array('in', $ids);
    	return $this->where($map)->getField('beauty_id, name, picture');
    }
}