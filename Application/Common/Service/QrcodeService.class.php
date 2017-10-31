<?php
namespace Common\Service;
use Think\Model;

class QrcodeService{
	public function infoBySceneId($scene_id){
		$info = $this->weixinQrcodeDao()->getRecord($scene_id);
		if (empty($info)) return array();
		if ($info['qrcode_type'] == 'clinic'){
			return array(
				'qrcode_type'=>'clinic',
				'clinic_id'=>$info['ref_id'],
			);
		}elseif ($info['qrcode_type'] == 'drug'){
			return array(
				'qrcode_type'=>'drug',
				'drug_id'=>$info['ref_id'],
			);
		}
	}
	
	public function getQrcodeIdOfClinic($id){
		$map = array(
			'qrcode_type'=>'clinic',
			'ref_id'=>$id,
		);
		$info = $this->weixinQrcodeDao()->findRecord($map);
		$scene_id = $info['qrcode_id'];
		if ($scene_id < 1){
			$scene_id = $this->weixinQrcodeDao()->addRecord($map);
		}
		return $scene_id;
	}
	
	public function getQrcodeIdOfDrug($id){
		$map = array(
			'qrcode_type'=>'drug',
			'ref_id'=>$id,
		);
		$info = $this->weixinQrcodeDao()->findRecord($map);
		$scene_id = $info['qrcode_id'];
		if ($scene_id < 1){
			$scene_id = $this->weixinQrcodeDao()->addRecord($map);
		}
		return $scene_id;
	}
	
	private function weixinQrcodeDao(){
		return D('Common/Qrcode/WeixinQrcode');
	}
}