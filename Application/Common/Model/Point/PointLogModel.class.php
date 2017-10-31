<?php
namespace Common\Model\Point;
use Think\Model\RelationModel;

class PointLogModel extends RelationModel {
    protected $tableName = 'point_log';
    
    protected $_validate = array(
    		array('user_id','require','用户ID不能为空'), //默认情况下用正则进行验证
    		array('point_old','require','原有积分不能为空'), //默认情况下用正则进行验证
    		array('point_change','require','积分变化数不能为空'), //默认情况下用正则进行验证
    		array('change_type','require','变化类型不能为空'), //默认情况下用正则进行验证
    		array('change_desc','require','变化原因描述不能为空'), //默认情况下用正则进行验证
    );
    
    protected $_auto = array (
    		array('add_time','time',1,'function'), // 对update_time字段在更新的时候写入当前时间戳
    );
    
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
        $result = $this->alias('a')->field('a.*, u.nick_name')
        			->join("left join __USER_INFO__ u on u.user_id=a.user_id")
        			->where($map)->order($orderBy)->page($start, $limit)->select();
    	return $result;
	}
	
	public function searchRecordsCount($map){
		return $this->alias('a')
				->join("left join __USER_INFO__ u on u.user_id=a.user_id")
				->where($map)->count();
    }
	
	public function deleteRecord($map){
		return $this->where($map)->delete();
	}
	
	public function searchDistinctUserid($map){
		return $this->where($map)->distinct(true)->field('user_id')->select();
	}
}