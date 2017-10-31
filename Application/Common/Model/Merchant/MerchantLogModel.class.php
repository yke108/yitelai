<?php
namespace Common\Model\Merchant;
use Think\Model\RelationModel;

class MerchantLogModel extends RelationModel {
    protected $tableName = 'merchant_log';
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
    public function searchAllRecords($map,$orderBy){
    	return $this->where($map)->order($orderBy)->select();
    }
    //查询规定条数的方法
    public function shouRecords($map,$orderBy,$limit){
    	return $this->where($map)->order($orderBy)->limit($limit)->select();
    }
}