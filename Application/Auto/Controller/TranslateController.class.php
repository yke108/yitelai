<?php
namespace Auto\Controller;
use Think\Controller;

class TranslateController extends Controller {
	//转化数据
	public function indexAction(){
		for ($i = 0; $i < 50; $i++){
			$result = $this->dealOne();
			if ($result === false) break;
		}
		echo 'OK';
	}
	
	public function dealOne(){
		$info = $this->importRecordService()->findNotTranslateRecord();
		if (empty($info)) return false;
		$data = unserialize($info['record_content']);
		$data_type_array = $this->dataTypeDao()->ReturnType();
		$type_table = $data_type_array[$info['data_type']]['table'];
		M()->startTrans();
		try {
			if ($type_table == 'Product'){
				if ($info['oper_status'] == 1){
					$data_id = $this->productService()->productCreateOrModify($data);
				}
			} elseif ($type_table == 'Number') {
				if ($info['oper_status'] == 1){
					$data_id = $this->numberService()->numberCreateOrModify($data);
				} 
				if ($info['oper_status'] == 2){
					$data_id = $this->numberService()->numberModifyByCode($data);
				} 
			}  elseif ($type_table == 'Card') {
				if ($info['oper_status'] == 1){
					$data_id = $this->cardService()->cardCreateOrModify($data);
				} 
				if ($info['oper_status'] == 2){
					$data_id = $this->cardService()->cardModifyByCode($data);
				} 
			}  elseif ($type_table == 'Package') {
				if ($info['oper_status'] == 1){
					$data_id = $this->packageService()->packageCreateOrModify($data);
				} 
			}  elseif ($type_table == 'Pikai') {
				if ($info['oper_status'] == 1) {
					$data_id = $this->pikaiService()->pikaiCreateOrModify($data);
				}
				if ($info['oper_status'] == 2) {
					$data_id = $this->pikaiService()->pikaiModifyByCode($data);
				}
			}
		} catch (\Exception $e) {
			$message = $e->getMessage();
		}
		
		try {
			if($data_id > 0){
				$save_data=array(
					'record_id'=>$info['record_id'],
					'change_status'=>3,
					'value_id'=>$data_id,
					'fail_cause'=>''
				);
				$this->importRecordService()->ImportRecordCreateOrModify($save_data);
				$save_data = array(
					'import_id'=>$info['import_id'],
					'record_success'=>array('exp', 'record_success+1'),
				);
				$this->importFileService()->ImportFileCreateOrModify($save_data);
			} else {
				empty($message) && $message = '未知数据';
				$save_data=array(
					'record_id'=>$info['record_id'],
					'change_status'=>2,
					'value_id'=>$data_id,
					'fail_cause'=>$message,
				);
				$this->importRecordService()->ImportRecordCreateOrModify($save_data);
			}
		} catch (\Exception $e) {
			M()->rollback();
		}
		
		M()->commit();
	}
	
	private function productService(){
		return D('Product', 'Service');
	}
	
	private function pikaiService(){
		return D('Pikai', 'Service');
	}
	
	private function numberService(){
		return D('Number', 'Service');
	}
	
	private function cardService(){
		return D('Card', 'Service');
	}
	
	private function packageService(){
		return D('Package', 'Service');
	}
	
	private function importRecordService(){
		return D('ImportRecord', 'Service');
	}
	
	private function importFileService(){
		return D('ImportFile', 'Service');
	}
	
	private function dataTypeDao(){
		return new \Common\Basic\DataType();
	}
}