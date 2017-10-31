<?php
namespace Common\Model;
use Think\Model;

class CollectModel extends Model{	
	public function searchRecordCount($map){
		return $this->alias('a')
				->join('JOIN __DISTRIBUTOR_GOODS__ b ON b.record_id=a.id_value')
				->where($map)->count();
	}
	
	public function searchRecordList($map, $orderBy, $start, $limit){
		return $this->alias('a')->field('b.*, a.*')
				->join('JOIN __DISTRIBUTOR_GOODS__ b ON b.record_id=a.id_value')
				->join('JOIN __GOODS__ c ON c.goods_id=b.goods_id')
				->where($map)->order($orderBy)->page($start, $limit)->select();
	}
	
	public function searchDistributorRecordCount($map){
		return $this->alias('a')
				->join('JOIN __DISTRIBUTOR_INFO__ b ON b.distributor_id=a.id_value')
				//->join('LEFT JOIN __DISTRIBUTOR_GOODS__ c ON c.distributor_id=b.distributor_id')
				->where($map)->count();
	}
	
	public function searchDistributorRecordList($map, $orderBy, $start, $limit){
		return $this->alias('a')->field('b.*, a.*')
				->join('JOIN __DISTRIBUTOR_INFO__ b ON b.distributor_id=a.id_value')
				//->join('LEFT JOIN __DISTRIBUTOR_GOODS__ c ON c.distributor_id=b.distributor_id')
				->where($map)->order($orderBy)->page($start, $limit)->select();
	}
	
	public function getRecord($id){
		return $this->find($id);
	}
	
	public function getRecordInfo($map){
		return $this->where($map)->find();
	}
	
	public function addRecord($data){
		return $this->add($data);
	}
	
	public function delRecord($map){
		return $this->where($map)->delete();
	}
	
	public function searchRecords($map, $orderBy, $start, $limit){
		return $this->where($map)->order($orderBy)->page($start, $limit)->select();
	}
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
	}
	
	public function searchAllRecords($map, $orderBy){
		return $this->where($map)->order($orderBy)->select();
	}
	
	public function searchDistinctUserid($map){
		return $this->where($map)->distinct(true)->field('user_id')->select();
	}
}