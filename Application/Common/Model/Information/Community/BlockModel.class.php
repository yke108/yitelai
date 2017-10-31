<?php
namespace Common\Model\Information\Community;
use Think\Model\RelationModel;

class BlockModel extends RelationModel {
    protected $trueTableName = 'info_community_block';
    
    protected $_validate = array(
    		array('name','require','板块名称不能为空',1),
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
    
    //定义查询方法
    public function searchAllRecords($map, $orderBy){
    	return $this->where($map)->order($orderBy)->select();
    }
    
    public function searchRecordsField($map){
    	return $this->where($map)->getField('block_id, name, picture');
    }
}