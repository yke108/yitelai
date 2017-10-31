<?php
namespace Common\Model\Weixin;
use Think\Model\RelationModel;

class WxNewsModel extends RelationModel {
    protected $tableName = 'wx_news';
    
    protected $_validate = array(
    		array('title', 'require', '标题不能为空', 1),
    		array('description', 'require', '描述不能为空', 1),
    		array('url', 'require', '链接地址不能为空', 1),
    		array('picture', 'require', '图片不能为空', 1),
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
    public function searchAllRecords($map, $orderBy){
    	return $this->where($map)->order($orderBy)->select();
    }
}