<?php
namespace Gallery\Controller;
use Think\Controller;

class FController extends Controller {
	private $_sidebar_in_name;
	private $_sidebar_act_name;
	protected $sys_id = 4;
	protected $org_id = 0;
	protected $information_info = array();
	protected $system_config = array();
	protected $purview_checked = false;
	protected $pagesize = 20;
	
	public function _initialize(){
		$this->settingForAll();
		$uid = session('uid');
		/* 验证管理员身份 */
		if (intval($uid) < 1){
			$adminService = $this->adminService();
			$cuid = cookie('uid');
			$cpwd = cookie('pwd');
			empty($cpwd) && $this->sessLoginError();
			$row = $adminService->getAdmin($cuid);
			empty($row) && $this->sessLoginError();
			md5($row['password'].$row['salt']) != $cpwd && $this->sessLoginError();
			$adminService->logined($row['admin_id']);
			$this->sessionSet($row);
		}
		
		$this->assign('avatar', session('avatar'));
		$priv_str = str_replace('/','_',strtolower(CONTROLLER_NAME.'_'.ACTION_NAME));
		$this->purviewCheck($priv_str); //默认按Controller/Action进行权限检测
		if (!IS_AJAX){
			$menulist= $this->systemService()->menuList($this->sys_id, session('action_list'));
			$this->assign('menulist', $menulist);
		}
    }
	
	protected function settingForAll(){
		session(array('prefix'=>'information'));
		cookie(array('prefix'=>'information_'));
		$this->org_id = session('org_id');
		$this->system_config = $this->configService()->findConfigs('system');
		$this->assign('sysconfig',$this->system_config);
	}
	
	protected function sessionSet($info){
		foreach((array)$info as $k => $vo){
			if($k == 'admin_id'){
				session('uid', $vo);
				continue;
			}
			if($k == 'admin_name'){
				session('username', $vo);
				continue;
			}
			$ls = array(
				'store_id', 'store_name', 'action_list', 'last_time',
				'avatar', 'org_id',
			);
			if(in_array($k, $ls)){
				session($k, $vo);
			}
		}
	}
	
	protected function purviewCheck($priv_str = ''){
		if ($this->purview_checked) return;
		$this->purview_checked = true;
		if ($priv_str === false) return;
		$action_list = session('action_list');
	    if ($action_list == 'all') return;
		//把控制器名称中的模块名称去掉
		empty($priv_str) && $priv_str = str_replace('/','_',strtolower(CONTROLLER_NAME.'_'.ACTION_NAME));
		if (strpos(','.$action_list.',', ','.$priv_str.',') === false){
	    	$this->error('权限不够， 无法操作');
	    }
	}
	
    protected function error($message='',$jumpUrl='',$ajax=false) {
		parent::error($message, $jumpUrl, $ajax);
		exit;
    }

    protected function success($message='',$jumpUrl='',$ajax=false) {
		parent::success($message, $jumpUrl, $ajax);
		exit;
    }
    
	private function sessLoginError(){
		cookie(null);
        if (IS_AJAX){
			$this->ajaxReturn(array('error' => 1, 'message' => '未登录'));
        } else {
			header("Location:".U('site/login'));
        }
        exit;
	}
	
	protected function renderPartial($templateFile='',$content='',$prefix=''){
		layout(false);
		return $this->fetch($templateFile,$content,$prefix);
	}
	
	protected function sbset($ps, $in = ''){
		if (is_array($ps)){
			if(!empty($ps['in'])){
				$this->_sidebar_in_name = $ps['in'];
			} 
			if(!empty($ps['ac'])){
				$this->_sidebar_act_name = $ps['ac'];
			}
		} else {
			$this->_sidebar_act_name = $ps;
			if(!empty($in)){
				$this->_sidebar_in_name = $in;
			}
		}
		return $this;
	}
	
	protected function display($templateFile='',$charset='',$contentType='',$content='',$prefix='') {
		if(IS_AJAX){
			$ret = array(
				'status'=>2,
				'info'=>$this->renderPartial($templateFile, $content,$prefix),
			);
			$this->ajaxReturn($ret);
		} else {
			$this->assign('sidebar_group', $this->_sidebar_in_name);
			$this->assign('sidebar_menu', $this->_sidebar_act_name);
			$this->assign($this->_sidebar_in_name, 'in');
			$this->assign($this->_sidebar_act_name, ' class="active"');
	        $this->view->display($templateFile,$charset,$contentType,$content,$prefix);
		}
    }
	
    protected function _empty(){
        $this->redirect('index/index');
    }
    
    protected function systemService(){
    	return D('System', 'Service');
    } 
    
    protected function adminService(){
    	return D('Admin', 'Service');
    }
    
	protected function configService(){
		return D('Config', 'Service');
	}
}