<?php
namespace Common\Model\Weixin;
use Think\Model\RelationModel;

class WxTemplateModel extends RelationModel {
    protected $tableName = 'wx_template';
    
    protected $_validate = array(
    		array('content', 'require', '内容不能为空', 1),
    );
    
    protected $_auto = array (
    		array('add_time','time',1,'function'), // 对add_time字段在插入的时候写入当前时间戳
    		array('update_time','time',2,'function'), // 对update_time字段在更新的时候写入当前时间戳
    );
    
	public function getRecord($id){
   		return $this->find($id);
	}
	
	public function findRecord($map){
		return $this->where($map)->find();
	}
	
	public function addRecord($data){
		$data['add_time'] = NOW_TIME;
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
    public function searchAllRecords($map){
    	return $this->where($map)->select();
    }
}