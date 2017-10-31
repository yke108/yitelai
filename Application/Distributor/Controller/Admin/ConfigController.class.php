<?php
namespace Distributor\Controller\Admin;
use Distributor\Controller\FController;
use Common\Basic\Pager;

class ConfigController extends FController {
	public function _initialize(){
		parent::_initialize();
		$set = array(
			'in'=>'system',
			'ac'=>'admin_config_system',
		);
		$this->sbset($set);
    }
    
	public function systemAction(){
		$info = $this->distributorConfigService()->findConfigs('system', $this->org_id);
		$this->assign('info', $info);
		$this->display();
    }
    
    public function weixinAction(){
    	$info = $this->distributorConfigService()->findConfigs('weixin', $this->org_id);
    	$this->assign('info', $info);
    	$set = array(
    			'in'=>'weixin',
    			'ac'=>'admin_config_weixin',
    	);
    	$this->sbset($set);
    	$this->display();
    }
    
    public function brandAction(){
    	$info = $this->distributorConfigService()->findConfigs('brand', $this->org_id);
    	$this->assign('info', $info);
    	//$this->sbset('admin_config_brand');
    	$set = array(
    			'in'=>'brand',
    			'ac'=>'admin_config_brand',
    	);
    	$this->sbset($set)->display();
    }
    
    public function dutyAction() {
    	$info = $this->distributorConfigService()->findConfigs('system', $this->org_id);
    	$this->assign('info', $info);
    	$set = array(
    			'in'=>'salesman',
    			'ac'=>'admin_config_duty',
    	);
    	$this->sbset($set)->display();
    }

	public function updateAction(){
		if(IS_POST){
			$post = I('post.');
			if(empty($post['type'])) $this->error('操作失败');
			M()->startTrans();
			if($this->distributorConfigService()->updateConfigs($post['type'], $post['c'], $this->org_id) === false){
				M()->rollback();
				$this->error('保存失败');
			}
			M()->commit();
			$this->success('保存成功');
		}
		$this->error('页面不存在');
	}
}