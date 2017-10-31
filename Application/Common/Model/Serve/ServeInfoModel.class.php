<?php
namespace Common\Model\Serve;
use Think\Model\RelationModel;

class ServeInfoModel extends RelationModel {
    protected $tableName = 'serve_info';
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
    public function allRecords(){
    	return $this->where($map)->select();
    }
    
    public function like($serve_id){
    	return $this->where(array('serve_id'=>$serve_id))->setInc('good_num');
    }
    
    public function clap($serve_id){
    	return $this->where(array('serve_id'=>$serve_id))->setInc('bad_num');
    }
}