<?php
namespace Common\Model\Material;
use Think\Model\RelationModel;

class FileModel extends RelationModel {
    protected $tableName = 'material_file';
    protected $pk = 'material_id';
    
    protected $_validate = array(
    		array('material_id','require','素材不能为空'), //默认情况下用正则进行验证
    		array('file_name','require','文件名称不能为空'), //默认情况下用正则进行验证
    		array('upload_path','require','文件不能为空'), //默认情况下用正则进行验证
    );
    
    protected $_auto = array (
    		//array('add_time','time',2,'function'), // 对add_time字段在更新的时候写入当前时间戳
    		//array('update_time','time',2,'function'), // 对update_time字段在更新的时候写入当前时间戳
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
}