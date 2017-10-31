<?php
namespace Common\Model\System;
use Think\Model\RelationModel;

class SystemMenuModel extends RelationModel {
    protected $tableName = 'system_menu';
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
        return $this->where($map)->order($orderBy)->page($start, $limit)->select();
    }
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
    }
    
	public function deleteRecord($map){
		return $this->where($map)->delete();
	}
	
	public function getMenus($map){
		return $this->where($map)->order('parent_id asc, sort_order asc')
		->getField('menu_id, menu_name, menu_code, parent_id, menu_url, menu_cls, sort_order');
	}
}