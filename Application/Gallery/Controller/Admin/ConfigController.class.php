<?php
namespace Gallery\Controller\Admin;
use Gallery\Controller\FController;
use Common\Basic\Pager;
use Common\Basic\SystemConst;

class ConfigController extends FController {
	public function _initialize(){
		parent::_initialize();
		$set = array(
			'in'=>'admin',
			'ac'=>'admin_config_system',
		);
		$this->sbset($set);
    }
    
	public function systemAction(){
		$info = $this->configService()->findConfigs('system');
		$this->assign('info', $info);
		$this->display();
    }
    
    public function appaboutAction(){
    	$info = $this->configService()->findConfigs(SystemConst::ConfAppAbout);
    	$this->assign('info', $info);
    	$this->display();
    }
    
    public function androidversionAction(){
    	$info = $this->configService()->findConfigs(SystemConst::ConfAndroidVersion);
    	$this->assign('info', $info);
    	$this->display();
    }
    
    public function weixinAction(){
    	$info = $this->configService()->findConfigs('weixin');
    	$this->assign('info', $info);
    	$set = array(
    			'in'=>'weixin',
    			'ac'=>'admin_config_weixin',
    	);
    	$this->sbset($set);
    	$this->display();
    }
    
    public function weixinshareAction(){
    	$info = $this->configService()->findConfigs('system');
    	$this->assign('info', $info);
    	$set = array(
    			'in'=>'weixin',
    			'ac'=>'admin_config_weixinshare',
    	);
    	$this->sbset($set);
    	$this->display();
    }
    
    public function merchantAction(){
    	$info = $this->configService()->findConfigs('merchant');
    	$this->assign('info', $info);
    	$set = array(
    			'in'=>'merchant',
    			'ac'=>'admin_config_merchant',
    	);
    	$this->sbset($set)->display();
    }
    
    public function pointAction(){
    	$info = $this->configService()->findConfigs('point');
    	$this->assign('info', $info);
    	$set = array(
    			'in'=>'point',
    			'ac'=>'admin_config_point',
    	);
    	$this->sbset($set)->display();
    }
	
	public function updateAction(){
		if(IS_POST){
			$post = I('post.');
			if(empty($post['type'])) $this->error('操作失败');
			try {
				$this->configService()->updateConfigs($post['type'], $post['c']);
				$this->success('保存成功');
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		$this->error('页面不存在');
	}
	
	public function updateWeixinAction(){
		if(IS_POST){
			$post = I('post.');
			if(empty($post['type'])) $this->error('操作失败');
			try {
				$this->configService()->updateConfigs($post['type'], $post['c']);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
	
		$config = $this->configService()->findWeixinConfigs();
		$access_token = $this->weixinService()->getAccessToken($config['js_app_id'], $config['js_app_secret']);
		if (!$access_token) {
			$this->error('保存失败');
		}
		$this->success('保存成功');
	}
	
	public function subscribeAction(){
		$info = $this->configService()->findWeixinSubscribeConfigs();
		$this->assign('info', $info);
	
		$set = array(
				'in'=>'weixin',
				'ac'=>'admin_config_subscribe',
		);
		$this->sbset($set);
		$this->display();
	}
	
	public function weixinAddressAction(){
		$weixinaddress = array(
				array(
						'label'=>'微信端首页',
						'url'=>str_replace('gajadmin', 'wap', DK_DOMAIN.U('index/index/index'))
				),
				array(
						'label'=>'分类页',
						'url'=>str_replace('gajadmin', 'wap', DK_DOMAIN.U('mall/category/index'))
				),
				array(
						'label'=>'购物车',
						'url'=>str_replace('gajadmin', 'wap', DK_DOMAIN.U('mall/cart/index'))
				),
				array(
						'label'=>'个人中心',
						'url'=>str_replace('gajadmin', 'wap', DK_DOMAIN.U('user/index/index'))
				),
				array(
						'label'=>'订单管理',
						'url'=>str_replace('gajadmin', 'wap', DK_DOMAIN.U('user/order/index'))
				)
		);
		$this->assign('weixinaddress', $weixinaddress);
		
		$set = array(
				'in'=>'weixin',
				'ac'=>'admin_config_weixinaddress',
		);
		$this->sbset($set);
		$this->display();
	}
	
	private function weixinService() {
		return D('Weixin', 'Service');
	}
}