<?php
namespace Common\Model\Goods;
use Think\Model\RelationModel;

class GoodsCatDistributorModel extends RelationModel {
    protected $tableName = 'goods_cat_distributor';
    
	public function getRecord($id){
   		return $this->find($id);
	}
	
	public function addRecord($data){
		$data['add_time'] = NOW_TIME;
		return $this->add($data);
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
}