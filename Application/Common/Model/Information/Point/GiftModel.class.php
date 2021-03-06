<?php
namespace Common\Model\Information\Point;
use Think\Model\RelationModel;

class GiftModel extends RelationModel {
    protected $trueTableName = 'info_point_gift';
    
    public function getFieldRecord($map,$field){
		return $this->where($map)->getField($field);
	}
	
    public function findRecord($map){
		return $this->where($map)->find();
	}
	
	public function getRecord($id){
   		return $this->find($id);
	}
	
	public function addRecord($data){
		return $this->add($data);
	}
	
	public function saveRecord($data){
		return $this->save($data);
	}
	
	public function searchRecords($map, $orderBy, $start, $limit){
        $result = $this->where($map)->order($orderBy)->page($start, $limit)->select();
    	return $result;
	}
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
    }
	
	public function deleteRecord($map){
		return $this->where($map)->delete();
	}
}