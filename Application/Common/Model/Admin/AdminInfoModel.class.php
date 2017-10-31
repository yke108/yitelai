<?php
namespace Common\Model\Admin;
use Think\Model\RelationModel;

class AdminInfoModel extends RelationModel {
    protected $tableName = 'admin';
	public function getRecord($id){
   		return $this->find($id);
	}
	
	public function getRecords($ids){
		if (!is_array($ids) || count($ids) < 1) return array();
		$map = array(
			'admin_id'=>array('in', $ids),
		);
		return $this->where($map)->getField('admin_id,role_id,admin_name,mobile,avatar');
	}
	
	public function getFieldRecord($map,$field='admin_id,role_id,admin_name,mobile,avatar'){
		return $this->where($map)->getField($field);
	}

	public function rizhiRecord($id){
   		return $this->find($id);
	}

	public function findRecord($map){
		return $this->where($map)->find();
	}

	public function findFieldRecord($map, $filed){
		return $this->where($map)->field($filed)->find();
	}

	public function addRecord($data){
		if (!empty($data['password'])){
			$data['salt'] = rand(1000,9999);
			$data['password'] = $this->password($data['password'], $data['salt']);
		}
		return $this->add($data);
	}
	
	public function saveRecord($data){
		if (!empty($data['password'])){
			$data['salt'] = rand(1000,9999);
			$data['password'] = $this->password($data['password'], $data['salt']);
		}
		return $this->save($data);
	}
	
	public function updateRole($role_id, $action_list, $oa_action_list){
		$map = array(
			'role_id'=>$role_id,
		);
		$data = array(
				'action_list'=>$action_list,
				'oa_action_list'=>$oa_action_list,
		);
		$this->where($map)->save($data);
	}
	
	public function updateOaRole($role_id, $action_list){
		$map = array(
			'oa_role_id'=>$role_id,
		);
		$data = array(
			'oa_action_list'=>$action_list,
		);
		$this->where($map)->save($data);
	}
	
	public function clearRole($role_id){
		$map = array(
			'role_id'=>$role_id,
		);
		$data = array(
			'role_id'=>0,
			'action_list'=>'',
		);
		$this->where($map)->save($data);
	}
	
	public function clearOaRole($role_id){
		$map = array(
			'role_id'=>$role_id,
		);
		$data = array(
			'oa_role_id'=>0,
			'oa_action_list'=>'',
		);
		$this->where($map)->save($data);
	}
	
	public function errorCountIncrease($admin_id){
		$map = array(
			'admin_id'=>$admin_id,
		);
		return $this->where($map)->setInc('error_count');
	}
	
	public function logined($admin_id, $password = ''){
		$map = array(
			'admin_id'=>$admin_id,
		);
		
		$data = array(
			'last_login'=>time(),
			'last_ip'=>get_client_ip(),
			'error_count'=>0,
		);
		if ($password){
			$data['salt'] = rand(1000,99999);
			$data['password'] = $this->password($password, $data['salt']);
		}
		return $this->where($map)->save($data);
	}
	
	public function password($password, $salt = ''){
		$str = md5(md5($password).$salt);
		$str = md5(substr($str, 3, 26));
		return $str;
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

	public function updateAdmin($where = array(), $data = array())
	{
		return $this->where($where)->data($data)->save();
	}
}