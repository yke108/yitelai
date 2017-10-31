<?php
namespace Common\Service;
use Common\Basic\CsException;

class SessionService{
	public function userCheck($params){
		$map = array(
			'sesskey'=>$params['sesskey'],
		);
		$sess = $this->userSessionDao()->findRecord($map);
		if(empty($sess) || $sess['expiry'] <= NOW_TIME || $sess['user_id'] != $params['user_id']){
			throw new CsException('登录超时', 32);
		}
	}
	
	public function tokenCheck($token){
		$sess = $this->userSessionDao()->getRecord($token);
		if(empty($sess) || $sess['expiry'] <= NOW_TIME){
			throw new CsException('登录超时', 32);
		}
		return $sess['user_id'];
	}
	
	public function createUserSession($user_id){
		$sessionDao = $this->userSessionDao();
		$sessionDao->clearExpiryRecords($user_id);
		$sess = str_replace('.', md5($user_id), uniqid(rand(10000,99999),true));
		$data = array(
			'sesskey'=>$sess,
			'user_id'=>$user_id,
		);
		if ($sessionDao->addRecord($data) === false){
			throw new CsException('登录失败', 10010);
		}
		return $sess;
	}
	
	public function adminCheck($params){
		$map = array(
			'sesskey'=>$params['sesskey'],
		);
		$sess = $this->adminSessionDao()->findRecord($map);
		if(empty($sess) || $sess['expiry'] <= NOW_TIME || $sess['admin_id'] != $params['admin_id']){
			throw new CsException('登录超时', 32);
		}
	}
	
	public function adminTokenCheck($token){
		$map = array(
			'sesskey'=>$token,
		);
		$sess = $this->adminSessionDao()->findRecord($map);
		if(empty($sess) || $sess['expiry'] <= NOW_TIME){
			throw new CsException('登录超时', 32);
		}
		return $sess['admin_id'];
	}
	
	public function createAdminSession($admin_id){
		$sessionDao = $this->adminSessionDao();
		$sessionDao->clearExpiryRecords($admin_id);
		$sess = str_replace('.',rand(10000,99999), uniqid(rand(10000,99999),true));
		$data = array(
			'sesskey'=>$sess,
			'admin_id'=>$admin_id,
		);
		if ($sessionDao->addRecord($data) === false){
			throw new CsException('登录失败', 10010);
		}
		return $sess;
	}
	
	private function userSessionDao(){
		return D('Common/User/UserSession');
	}
	
	private function adminSessionDao(){
		return D('Common/Admin/AdminSession');
	}
}

