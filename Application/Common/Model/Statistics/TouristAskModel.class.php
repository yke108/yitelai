<?php
namespace Common\Model\Statistics;
use Think\Model\RelationModel;

class TouristAskModel extends RelationModel {
    protected $tableName = 'tourist_ask';
    
    protected $_validate = array(
    		array('region_code','require','地区不能为空',1),
    		//array('shop_id','require','店铺不能为空',1),
    );
    
    protected $_auto = array (
    		array('inputtime','time',1,'function'),
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
	
	public function searchRecord($map, $orderBy){
		return $this->where($map)->order($orderBy)->find();
	}
	
	public function saveRecord($data){
		return $this->save($data);
	}
	
	public function delRecord($id){
		return $this->delete($id);
	}
	
	public function updateRecord($map, $data){
		return $this->where($map)->save($data);
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
}