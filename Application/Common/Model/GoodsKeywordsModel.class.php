<?php
namespace Common\Model;
use Think\Model;

class GoodsKeywordsModel extends Model{
	// tableName属性来改变默认的规则
	protected $tableName = 'goods_keywords';
	protected $pk = 'search_id';
	
	protected $_validate = array(
			array('keyword','require','名称不能为空'), //默认情况下用正则进行验证
	);
	
	protected $_auto = array (
			//array('add_time','time',1,'function'), // 对update_time字段在更新的时候写入当前时间戳
			//array('update_time','time',2,'function'), // 对update_time字段在更新的时候写入当前时间戳
	);
	
	public function getRecord($id){
		return $this->find($id);
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
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
	}
	
	public function searchAllRecords($map, $orderBy){
		return $this->where($map)->order($orderBy)->select();
	}
}