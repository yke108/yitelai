<?php
namespace Common\Service;
class UserQrcodeService{
	public function createQrcode(&$user, $qrcode = false){
		if($qrcode === false){
			$qrcode = $this->findQrcodeOfUser($user);
		}
		if(empty($qrcode)){
			$data = array(
				'user_id'=>$user['user_id'],
			);
			$qrcode_id = $this->userQrcodeDao()->add($data);
            //var_dump($this->userQrcodeDao()->getLastSql());
            //exit();
			if($qrcode_id < 1){
				throw new \Exception('二维码生成失败');
			}
		} else {
			$qrcode_id = $qrcode['qrcode_id'];
		}
		//获取微信二维码
		$weixinService = $this->weixinService();
		$result = $weixinService->getQrcode($qrcode_id);
		//保存二维码信息
		$data = array(
			'qrcode_url'=>$result['qrcode_url'],
			'expire_time'=>$result['expire_time'],
		);
		$map = array(
			'qrcode_id'=>$qrcode_id,
		);
		$result = $this->userQrcodeDao()->where($map)->save($data);
        //var_dump($this->userQrcodeDao()->getLastSql());
        //exit();
        if($result) {
            return $data;
        }
        else {
            return $result;
        }
	}
	
	public function createQrcodeDistributor($distributor_id, $qrcode = false){
		if($qrcode === false){
			$qrcode = $this->findQrcodeOfDistributor($distributor_id);
		}
		if(empty($qrcode)){
			$data = array(
					'distributor_id'=>$distributor_id,
			);
			$qrcode_id = $this->userQrcodeDao()->add($data);
			//var_dump($this->userQrcodeDao()->getLastSql());
			//exit();
			if($qrcode_id < 1){
				throw new \Exception('二维码生成失败');
			}
		} else {
			$qrcode_id = $qrcode['qrcode_id'];
		}
		//获取微信二维码
		$weixinService = $this->weixinService();
		$result = $weixinService->getQrcode($qrcode_id);
		//保存二维码信息
		$data = array(
				'qrcode_url'=>$result['qrcode_url'],
				'expire_time'=>$result['expire_time'],
		);
		$map = array(
				'qrcode_id'=>$qrcode_id,
		);
		$result = $this->userQrcodeDao()->where($map)->save($data);
		//var_dump($this->userQrcodeDao()->getLastSql());
		//exit();
		if($result) {
			return $data;
		}
		else {
			return $result;
		}
	}
	
	public function findQrcodeOfUser($user){
		$map = array(
			'user_id'=>$user['user_id'],
		);
		return $this->userQrcodeDao()->where($map)->find();
	}
	
	public function findQrcodeOfDistributor($distributor_id){
		$map = array(
				'distributor_id'=>$distributor_id,
		);
		return $this->userQrcodeDao()->where($map)->find();
	}
	
	private function userQrcodeDao(){
		return D('UserQrcode');
	}
	
	private function weixinService(){
		return D('Weixin', 'Service');
	}
}
