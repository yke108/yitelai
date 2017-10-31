<?php
namespace Common\Model\Region;
use Think\Model\RelationModel;

class RegionModel extends RelationModel {
    protected $tableName = 'region';
	public function getRecord($id){
   		return $this->find($id);
	}
	
	public function findRecord($map){
		return $this->where($map)->find();
	}
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
    }
    
    public function searchAllRecords($map){
    	$s_key = $this->tableName."_searchAllRecords_";
    	$datas = S($s_key);
    	if(!$datas){
    		$map['id'] = array('neq',1);
    		$list = $this->where($map)->select();
    		$datas = node_merge($list,$pid=1);
    	}
    	return $datas;
    }
}