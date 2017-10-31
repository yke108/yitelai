<?php
namespace Common\Model\Designer;
use Think\Model\RelationModel;

class DesignerInfoModel extends RelationModel {
    protected $tableName = 'designer_info';
	
	public function getFieldGroupRecord($map,$field,$group,$limit=20){
		return $this->where($map)->group($group)->page(1,$limit)->getField($field);
	}
	
	public function getFieldRecord($map,$field='designer_id,designer_name'){
		return $this->where($map)->getField($field);
	}
	
	public function getIdsRecord($ids){
		if (empty($ids)) return array();
		if (!is_array($ids)) $ids = array($ids);
		$map = array(
			'designer_id'=>array('in', $ids),
		);
		$list=$this->where($map)->getField('designer_id, designer_name, designer_image, tel, region_code');
		if(!empty($list)){
			foreach($list as $key=>$val){
				$list[$key]['region_name']=$this->regionDao()->getProvinceCity($val['region_code']);
			}
		}
		return $list;
	}
	
	public function getRecord($id){
   		return $this->find($id);
	}
	
	public function findRecord($map){
		return $this->where($map)->find();
	}
	
	public function addRecord($data){
		return $this->add($data);
	}
	
	public function saveRecord($data,$map){
		return $this->where($map)->save($data);
	}
	
	public function delRecord($id){
		return $this->delete($id);
	}
	
	public function searchRecords($map, $orderBy, $start, $limit){
        return $this->where($map)->order($orderBy)->page($start, $limit)->select();
    }


	public function searchRecordsList($map = array(), $field = array(), $orderBy = array(), $start, $limit){
		return $this->where($map)->field($field)->order($orderBy)->page($start, $limit)->select();
	}
	
	public function searchRecordsCount($map){
		return $this->where($map)->count();
    }
	
    //定义查询方法
    public function allRecords($map,$orderby){
    	return $this->where($map)->order($orderby)->select();
    }
	
	private function regionDao(){
		return D('Common/Region');
	}
}