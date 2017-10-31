<?php
namespace Common\Service\Information;

class UserAddressService{
	const DEFAULT_ADDRESS_NO = 0; 
	const DEFAULT_ADDRESS_YES = 1;
	
	public function getUserAddressList($userid, $orderby = ''){
		$map = array(
			'user_id'=>$userid,
		);
		$orderby = $orderby ? $orderby : 'address_id desc';
		return $this->userAddressDao()->where($map)->order($orderby)->select();
	}
	
	public function getPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		if ($params['map']) {
			$map = $params['map'];
		}else {
			$map = array();
		}
		if (!empty($params['user_id'])) {
			$map['user_id'] = $params['user_id'];
		}
		
		$count = $this->userAddressDao()->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'address_id DESC' : $params['orderby'];
			$list = $this->userAddressDao()->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
				'list'=>$list,
				'count'=>$count,
		);
	}
	
	public function getAddress($user_id){
		return $this->userAddressDao()->where(array('user_id'=>$user_id))->order('address_id DESC')->find();
	}
	
	public function findDefaultAddress($user_id){
		$map = array(
				'user_id'=>$user_id,
				'is_default'=>1
		);
		return $this->userAddressDao()->where($map)->find();
	}
	
	public function getAddressById($id, $userid){
		$map = array(
			'address_id'=>$id,
		);
		if($userid > 0){
			$map['user_id'] = $userid;
		}
		return $this->userAddressDao()->where($map)->find();
	}
	
	public function setDefaultAddress($id, $userid){
		M()->startTrans();
		
		$map = array('user_id'=>$userid);
		$data = array('is_default'=>0);
		$result = $this->userAddressDao()->where($map)->save($data);
		if ($result === false) {
			M()->rollback();
			throw new \Exception('添加地址失败');
		}
		
		$map = array('user_id'=>$userid, 'address_id'=>$id);
		$data = array('is_default'=>1);
		$result = $this->userAddressDao()->where($map)->save($data);
		if ($result === false) {
			M()->rollback();
			throw new \Exception('添加地址失败');
		}
		
		M()->commit();
		
		return $result;
	}
	
	public function updateAddressInfo($id, $fields){
		$map = array(
			'address_id'=>$id,
		);
		
		return $this->userAddressDao()->where($map)->save($fields);
	}
	
	public function deleteAddress($id, $userid = 0){
		$map = array(
			'address_id'=>$id,
		);
		$result = $this->userAddressDao()->where($map)->delete();
		return $result > 0;
	}
	
	public function addAddressToUser($user_id, $data){
		M()->startTrans();
		/* $map = array('user_id'=>$user_id);
		$result = $this->userAddressDao()->where($map)->save(array('is_default'=>0));
		if ($result === false) {
			M()->rollback();
			throw new \Exception('添加地址失败');
		} */
		
		$data['user_id'] = $user_id;
		//$data['is_default'] = 0;
		$address_id = $this->userAddressDao()->add($data);
		if ($address_id === false) {
			M()->rollback();
			throw new \Exception('添加地址失败');
		}
		
		M()->commit();
		
        return $address_id;
	}
	
	private function userAddressDao(){
		return D('Common/Information/User/Address');
	}
	
	private function UserInfoDao(){
		return D('Common/Information/User/Info');
	}
}
