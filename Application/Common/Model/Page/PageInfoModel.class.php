<?php
namespace Common\Model\Page;
use Think\Model\RelationModel;

class PageInfoModel extends RelationModel {
    protected $tableName = 'page_info';
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
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
    }
    //
    public function allRecords($map,$orderBy){
    	return $this->where($map)->order($orderBy)->select();
    }
    //查询规定条数的方法
    public function shouRecords($map,$orderBy,$limit){
    	return $this->where($map)->order($orderBy)->limit($limit)->select();
    }
    public function getFieldRecords($map, $field = 'page_id, page_picture, page_title, page_intro, page_content, page_type, page_type'){
    	return $this->where($map)->getField($field);
    }
}