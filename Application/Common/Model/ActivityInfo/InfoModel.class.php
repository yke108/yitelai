<?php
namespace Common\Model\ActivityInfo;
use Think\Model\RelationModel;

class InfoModel extends RelationModel {
    protected $tableName = 'activity_info';
    
    protected $_validate = array(
    		array('picture','require','图片不能为空',1),
    		//array('image','require','详情页图片不能为空',1),
    		array('title','require','标题不能为空',1),
    		array('sub_title','require','副标题不能为空',1),
    		//array('cat_id','require','分类不能为空',1),
    		array('start_time','require','活动开始时间不能为空',1),
    		array('end_time','require','活动结束时间不能为空',1),
    		array('place','require','活动地点不能为空',1),
    		//array('description','require','描述不能为空',1),
    		array('content','require','项目内容不能为空',1),
    		array('content1','require','投资计划内容不能为空',1),
    		array('content2','require','预计回报内容不能为空',1),
    		array('content3','require','实施计划内容不能为空',1),
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

	public function findFieldRecord($map, $field = array()){
		return $this->where($map)->field($field)->find();
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
    	return $this->where($map)->getField('activity_id, title, picture');
    }
    
    public function getRecordsField($ids){
    	$map['activity_id'] = array('in', $ids);
    	return $this->where($map)->getField('activity_id, title, picture');
    }
}