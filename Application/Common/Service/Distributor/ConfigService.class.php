<?php
namespace Common\Service\Distributor;

class ConfigService{
	const Weixin = 'weixin';
	const WeixinToken = 'weixintoken';
	
	private $ConfigDao;
	
	private $sckey = 'distributor_config';
	private $fkeys = array(
			'system'=>array('name'),
			'weixin'=>array('js_app_id', 'js_app_secret', 'token', 'mchid', 'key'),
	);
	
	public function __construct(){
		$this->ConfigDao = D('Common/Distributor/Config');
	}
	
	public function getDistributorIdByWeixin($weixn_name) {
		$map = array(
				'ckey'=>'weixin_name',
				'cval'=>$weixn_name,
				'ctype'=>'weixin'
		);
		$config = $this->ConfigDao->findConfig($map);
		return $config['distributor_id'];
	}
	
	public function updateConfigs($type, $configs, $distributor_id){
		$cache_name = $this->sckey.$type.$distributor_id;
		F($cache_name, null);
		M()->startTrans();
		foreach($configs as $ko => $vo){
			$map = array(
					'ckey'=>$ko,
					'ctype'=>$type,
					'distributor_id'=>$distributor_id,
			);
			$config = $this->ConfigDao->findConfig($map);
			if ($config) {
				$data = array(
						'cval'=>$vo,
				);
				$result = $this->ConfigDao->updateConfig($map, $data);
				if ($result === false){
					M()->rollback();
					throw new \Exception('保存失败');
				}
			}else {
				$data = array(
						'distributor_id'=>$distributor_id,
						'ckey'=>$ko,
						'cval'=>$vo,
						'ctype'=>$type,
				);
				$result = $this->ConfigDao->addConfig($data);
				if ($result === false){
					M()->rollback();
					throw new \Exception('添加失败');
				}
			}
		}
		M()->commit();
		return true;
	}
	
	public function findAndroidVersionConfigs(){
		return $this->findConfigs(self::AndroidVersion);
	}
	
	public function findAboutUsConfigs(){
		return $this->findConfigs(self::AboutUs);
	}
	
	public function findWeixinConfigs($distributor_id){
		return $this->findConfigs(self::Weixin, $distributor_id);
	}
	
	public function findWeixinToken(){
		return $this->findConfigs(self::WeixinToken);
	}
	
	public function findMobileNavConfigs(){
		return $this->findConfigs(self::MobileNav);
	}
	
	public function findWeixinAppConfigs(){
		return $this->findConfigs(self::WeixinApp);
	}
	
	public function findPcTopQrcodeConfigs(){
		return $this->findConfigs(self::PcTopQrcode);
	}
	
	public function updateWeixinToken($configs){
		return $this->updateConfigs(self::WeixinToken, $configs);
	}
	
	public function findConfigs($type, $distributor_id){
		$cache_name = $this->sckey.$type.$distributor_id;
		$configs = F($cache_name);
		if(empty($configs)){
			$map = array(
					'ctype'=>$type,
					'distributor_id'=>$distributor_id,
			);
			$tl = $this->ConfigDao->findConfigs($map);
			$configs = array();
			foreach($tl as $vo){
				$configs[$vo['ckey']] = $vo['cval'];
			}
			$configs['_type'] = $type;
			F($cache_name, $configs);
		}
		return $configs;
	}
	
	public function findConfig($fkey, $type, $distributor_id){
		$map = array(
				'ckey'=>$fkey,
				'ctype'=>$type,
				'distributor_id'=>$distributor_id,
		);
		return $this->ConfigDao->findConfig($map);
	}
}