<?php
namespace Common\Model\Sms;
use Think\Model;

class SmsCodeModel extends Model{
	protected $tableName = 'sms_code';
	
	public function getRecord($id){
		return $this->find($id);
	}
	
	public function getCode($id){
		return $this->find($id);
	}
	
	public function psd($code){
		return md5($code.md5($code.'kzl3eho6qw1eids8'));
	}
}