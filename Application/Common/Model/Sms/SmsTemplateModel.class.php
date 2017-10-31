<?php
namespace Common\Model\Sms;
use Think\Model\RelationModel;

class SmsTemplateModel extends RelationModel {
    protected $tableName = 'sms_template';
    protected $sckey = 'sms_template/';
    
	public function getRecord($id){
		if ($id < 1) return array();
		$cache_name = $this->sckey.$id;
		$template = F($cache_name);
		if(empty($template)){
			$template = $this->find($id);
			F($cache_name, $template);
		}
   		return $template;
	}
	
	public function findRecord($map){
		return $this->where($map)->find();
	}
	
	public function saveRecord($data){
		$cache_name = $this->sckey.$data['template_id'];
		F($cache_name, null);
		return $this->save($data);
	}
	
	public function searchRecords($map, $orderBy, $start, $limit){
        return $this->where($map)->order($orderBy)->page($start, $limit)->select();
    }
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
    }

}