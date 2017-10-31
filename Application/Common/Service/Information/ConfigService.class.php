<?php
namespace Common\Service\Information;

class ConfigService{
	const Weixin = 'weixin';
	const WeixinToken = 'weixintoken';
	
	//查找配置
	public function getConfig($type){
		$map=array('type'=>$type);
		$config=$this->configDao()->findRecord($map);
		return $config;
	}
	
	public function findConfigs($type,$field='fkey,fval'){
		$map=array('type'=>$type);
		$configs=$this->configDao()->findConfigs($map,$field);
		foreach ($configs as $key => $value) {
			
		}
		$configs['service_promise_arr'] = explode(',', $configs['service_promise']);
		return $configs;
	}
	
	public function getConfigs($type,$field='fkey, fval, type'){
		$map = array('type'=>$type);
		$configs = $this->configDao()->findConfigs($map,$field);
		$list = array();
		foreach ($configs as $key => $value) {
			$arr = explode("\n", $value['fval']);
			$new_arr = array();
			foreach ($arr as $v) {
				$new_arr[] = trim($v);
			}
			$list[$key] = $new_arr;
		}
		return $list;
	}
	
	//查找配置
	public function findSystemConfigs($type,$field='fkey,fval'){
		$map=array('type'=>$type);
		$configs=$this->configDao()->findConfigs($map,$field);
		foreach ($configs as $key => $value) {
			
		}
		return $configs;
	}
	
	//编辑配置
	public function setConfig($params,$type){
		if(empty($params) || empty($type)) throw new \Exception('缺少参数');
		M()->startTrans();
		foreach($params as $key=>$val){
			$map=array('fkey'=>$key,'type'=>$type);
			$save_data=array('fval'=>$val);
			$result=$this->configDao()->updateConfig($map,$save_data);
			if($result===false){
				M()->rollback();
				throw new \Exception('编辑失败');
			}		
		}
		M()->commit();
	}
	
	public function updateConfigs($type, $configs){
		$cache_name = $this->sckey.$type;
		F($cache_name, null);
		M()->startTrans();
		foreach($configs as $ko => $vo){
			$map = array(
					'fkey'=>$ko,
					'type'=>$type,
			);
			$config = $this->ConfigDao()->findRecord($map);
			if ($config) {
				$data = array(
						'fval'=>$vo,
				);
				$result = $this->ConfigDao()->updateConfig($map, $data);
				if ($result === false){
					M()->rollback();
					throw new \Exception('保存失败');
				}
			}else {
				$data = array(
						'fkey'=>$ko,
						'fval'=>$vo,
						'type'=>$type,
				);
				$result = $this->ConfigDao()->addConfig($data);
				if ($result === false){
					M()->rollback();
					throw new \Exception('添加失败');
				}
			}
		}
		M()->commit();
		return true;
	}
	
	public function findWeixinConfigs(){
		return $this->findConfigs(self::Weixin);
	}
	
	public function findWeixinSubscribeConfigs(){
		return $this->findConfigs(self::Weixin);
	}
	
	public function findWeixinToken(){
		return $this->findConfigs(self::WeixinToken);
	}
	
	public function updateWeixinToken($configs){
		$cache_name = $this->sckey.$type;
		F($cache_name, null);
		M()->startTrans();
		foreach($configs as $ko => $vo){
			$map = array(
					'fkey'=>$ko,
					'type'=>self::Weixin,
			);
			$config = $this->ConfigDao()->findConfig($map);
			if ($config) {
				$data = array(
						'fval'=>$vo,
				);
				$result = $this->ConfigDao()->updateConfig($map, $data);
				if ($result === false){
					M()->rollback();
					throw new \Exception('保存失败');
				}
			}else {
				$data = array(
						'fkey'=>$ko,
						'fval'=>$vo,
						'type'=>self::Weixin,
				);
				$result = $this->ConfigDao()->addConfig($data);
				if ($result === false){
					M()->rollback();
					throw new \Exception('添加失败');
				}
			}
		}
		M()->commit();
		return true;
	}
	
	protected function configDao(){
		return D('Common/Information/Config/Config');
	}
}