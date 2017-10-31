<?php
namespace Home\Controller;
use Think\Controller;
use Common\Basic\JsConst;

abstract class BaseController extends Controller {
	private $openid_ck = 'openid_ck8';
	public $login_check = false;
	public $purview_checked = false;
	public $user = array();
	public $login_url = '';
	public $pagesize = 20;
	protected $sys_id = 5;
	
	public function _initialize(){
		$this->sessionCreate();
		$actbefore = ACTION_NAME.'Before';
		if (method_exists($this, $actbefore)){
			$this->$actbefore();
		}
		if (method_exists($this, '_purviewCheck')){
			$this->_purviewCheck();
		}
		$this->purviewCheck();
		$controller_name = strtolower(CONTROLLER_NAME);
		$carr = explode('/', $controller_name);
		$this->assign('croot', $carr[0]);
		$this->assign('controller_name', $controller_name);
		
		//首页、登录和注册链接
		$url = DK_DOMAIN;
		$this->assign('url', $url);
		$this->login_url = DK_DOMAIN.'/wap/index.php/index/site/login.html';
		$this->assign('login_url', $this->login_url);
		$reg_url = DK_DOMAIN.'/wap/index.php/index/site/reg.html';
		$this->assign('reg_url', $reg_url);
		
		//来源url
		if (!IS_POST) {
			session('wap_from_url', $_SERVER['HTTP_REFERER']);
		}
    }
    
    private function sessionCreate(){
    	$token = cookie('t');
    	if (empty($token)) return;
    	if (session('uid') > 0 && session('action_list')) return;
    	$admin_id = $this->sessionService()->adminTokenCheck($token);
    	if ($admin_id > 0){
    		session('uid', $admin_id);
    		$admin_info = $this->adminService()->getAdmin($admin_id);
    		$action_list = $admin_info['oa_action_list'] ? $admin_info['oa_action_list'] : 'x';
    		session('action_list', $action_list);
    		session('sys_id', $admin_info['sys_id']); //所属平台
    		session('is_admin', $admin_info['is_admin']); //是否超级管理员
    		session('org_id', $admin_info['org_id']); //品牌商或店铺ID
    		session('role_id', $admin_info['role_id']); //角色
    		$distributor_info = Service('Distributor')->getInfo($admin_info['org_id']);
    		if ($distributor_info) {
    			session('distributor_id', $distributor_info['distributor_id']);
    		}
    	} else {
    		cookie('token', null);
    		header('Location:abc://'.JsConst::jscall('nologin'));
    	}
    }
    
    protected function purviewCheck($priv_str = ''){
    	if ($this->purview_checked) return;
    	$this->purview_checked = true;
    	if ($priv_str === false) return;
    	empty($priv_str) && $priv_str = strtolower(CONTROLLER_NAME.'/'.ACTION_NAME);
    	if (!haspv($priv_str)) {
    		$this->error('权限不够， 无法操作');
    	}
    }
	
    protected function error($message='',$jumpUrl='',$ajax=false) {
    	empty($jumpUrl) && $jumpUrl = '#';
		parent::error($message, $jumpUrl, $ajax);
		exit;
    }

    protected function success($message='',$jumpUrl='',$ajax=false) {
		parent::success($message, $jumpUrl, $ajax);
		exit;
    }

	protected function renderPartial($templateFile='',$content='',$prefix=''){
		layout(false);
		return $this->fetch($templateFile,$content,$prefix);
	}
	
    protected function display($templateFile='',$charset='',$contentType='',$content='',$prefix='') {
		if(IS_AJAX){
			if($_GET['is_trp'] == 1){
				$ret = array(
					'status'=>2,
					'info'=>$this->renderPartial('_'.$templateFile),
					'pager'=>$this->get('pager'),
					'wrap_id'=>$this->get('x_wrap_id'),
					'pager_id'=>$this->get('x_pager_id'),
				);
			} else {
				$ret = array(
					'status'=>2,
					'info'=>$this->renderPartial($templateFile, $content,$prefix),
					'url'=>U('', I('get.')),
				);
			}
			$this->ajaxReturn($ret);
		} else {
			$this->view->display($templateFile,$charset,$contentType,$content,$prefix);
			exit;
		}
    }
	
    protected function _empty(){
        $this->redirect('index/index');
    }
	
	protected function userService(){
		return D('User', 'Service');
	}
	
	protected function distributorConfigService(){
		return D('Distributor\Config', 'Service');
	}
	
	protected function configService(){
		return D('Config', 'Service');
	}
	
	protected function sessionService(){
		return D('Session', 'Service');
	}
	
	protected function adminService(){
		return D('Admin', 'Service');
	}
}
