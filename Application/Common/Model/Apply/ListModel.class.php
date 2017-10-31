<?php
namespace Common\Model\Apply;
use Think\Model\RelationModel;

class ListModel extends RelationModel {
    protected $tableName = 'apply_list';
	
	public function getFieldRecord($map,$field)
	{ 
		return $this->where($map)->getField($field);
	}
	
	public function getRecord($id){
		if ($id < 1) return [];
   		return $this->find($id);
	}
	
	public function getRecords($ids){
		if (!is_array($ids) || count($ids) < 1) return [];
		$map = [
			'apply_id'=>['in', $ids],
		];
		$list = $this->where($map)->select();
		$result = [];
		foreach ($list as $vo){
			$result[$vo['apply_id']] = $vo;
		}
		return $result;
	}
	
	public function addRecord($data){
		return $this->add($data);
	}
	
	public function saveRecord($data,$map){
		return $this->where($map)->save($data);
	}
	
	public function searchRecordsCount($map)
	{ 
		return $this->where($map)->count();
	}
	
	public function searchRecords($map,$order,$start,$end){
        return $this->where($map)->order($order)->page($start,$end)->select();
    }
	
	public function deleteRecord($map){
		return $this->where($map)->delete();
	}
	
	public function searchRecordsnopage(){
		return $this->where($map)->delete();
	}
}