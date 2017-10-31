<?php
namespace Common\Model\Cook;
use Think\Model\RelationModel;

class BookModel extends RelationModel {
    protected $tableName = 'cook_book';
    
    protected $_validate = array(
    		array('user_id','require','作者不能为空',1),
    		array('name','require','标题不能为空',1),
    		array('picture','require','主图不能为空',1),
    		//array('cat_id','require','分类不能为空',1),
    		//array('label_ids','require','标签不能为空',1),
    		array('tech','require','工艺不能为空',1),
    		array('difficulty','require','难度不能为空',1),
    		array('number','require','人数不能为空',1),
    		array('taste','require','口味不能为空',1),
    		array('prepare_time','require','准备时间不能为空',1),
    		array('cook_time','require','烹饪时间不能为空',1),
    		//array('from','require','来源不能为空',1),
    		//array('author','require','作者不能为空',1),
    		//array('description','require','描述不能为空',1),
    		//array('content','require','内容不能为空',1),
    		array('material','require','使用材料不能为空',1),
    		array('steps','require','制作步骤不能为空',1),
    );
    
    protected $_auto = array (
    		array('add_time','time',1,'function'), // 对add_time字段在更新的时候写入当前时间戳
    		array('update_time','time',2,'function'), // 对update_time字段在更新的时候写入当前时间戳
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

	public function searchFieldRecords($map, $field, $orderBy, $start, $limit){
		return $this->where($map)->field($field)->order($orderBy)->page($start, $limit)->select();
	}

    public function searchAllRecords($map, $orderBy){
    	return $this->where($map)->order($orderBy)->select();
    }
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
    }
    
    public function getBooksByIds($ids){
    	$map['book_id'] = array('in', $ids);
    	return $this->where($map)->getField("book_id, name, picture");
    }
}