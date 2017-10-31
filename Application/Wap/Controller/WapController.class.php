<?php
namespace Wap\Controller;
use Think\Controller;
//use Think\Log;

abstract class WapController extends Controller {
	private $_in_weixin = 0;
	public $openid_ck = 'openid_ck8';
	public $uid = 'uid';
	public $login_check = false;
	public $user = array();
	public $pagesize = 20;
	
	public function _initialize(){
		$controller_name = strtolower(CONTROLLER_NAME);
		$carr = explode('/', $controller_name);
		$this->assign('croot', $carr[0]);
		$this->assign('controller_name', $controller_name);
		
		$this->in_weixin();
		$this->assign('is_weixin', $this->_in_weixin);
		
		$openid_ck=cookie($this->openid_ck);
		if (empty($openid_ck)) {
			$this->weixinAutoLogin();
		}
		
		$uid = I('uid');
		if ($uid > 0) {
			cookie($this->uid, $uid);
		}

		//!$this->in_weixin() && session('userid', 7); //debug
		//$this->in_weixin() && session('userid') == 7 && session('userid', null);
		if(session('userid') < 1){
			//$this->weixinAutoLogin();
			
			//更新登录时间
			//$this->userService()->modify($this->user, array('last_login'=>NOW_TIME));
		} else {
			$userid = session('userid');
			$this->user = $this->userService()->getUserInfo($userid);
			$this->user['user_id'] < 1 && session('userid', null);
			$this->assign('user', $this->user);
		}
		
		if ($this->login_check && empty($userid)) {
			if (IS_AJAX) {
				$this->error('请先登录', U('index/site/login'));
			}
			$this->redirect('index/site/login', array('dis_id'=>I('dis_id')));
		}
		
		//系统设置
		$this->sysconfig = $this->configService()->findConfigs('system');
		$this->assign('sysconfig', $this->sysconfig);
		
		//$wxhosturl = DK_DOMAIN.U('index/site/reg', array('uid'=>$this->user['user_id']));
		//$this->assign('wxhosturl', $wxhosturl);
			
		$configService = $this->configService();
		$configs = $configService->findWeixinConfigs();
		$signPackage = $this->weixinService()->jsSignPackage($configs);
		$this->assign('signPackage',$signPackage);
		
		//购物车数量
		$params = array(
				'user_id'=>session('userid')
		);
		$cart_num = Service('Cart')->getCartNumber($params);
		$this->assign('cart_num', $cart_num);
		
		//广告
		$params = array(
				'distributor_id'=>0
		);
		$this->ad_list = $this->adService()->infoAllList($params);
		$this->assign('ad_list', $this->ad_list);
		
		//来源url
		if (!IS_POST) {
			session('wap_from_url', $_SERVER['HTTP_REFERER']);
		}
		
		//是否申请了分销员
		if($this->user['user_id']>0){
			$apply_agent_info=D('Saleman','Service')->userGetInfo($this->user['user_id']);
			if(!empty($apply_agent_info)){
				$this->assign('apply_agent_info',$apply_agent_info);
			}
		}
		
    }
    
    private function weixinAutoLogin(){
    	if (!$this->in_weixin()) return;
    	$openid = cookie($this->openid_ck);
    	if (!empty($openid)){
    		$userid = session('userid');
			$this->user = $this->userService()->getUserInfoWithOpenid($openid);
			$this->user['user_id'] < 1 && cookie($this->openid_ck, null);
    		//session('userid', $this->user['user_id']);
        	$this->assign('user', $this->user);
        	return;
    	} 
    	$config = $this->configService()->findWeixinConfigs();
    	$access_token = $this->weixinService()->getAccessToken($config['js_app_id'], $config['js_app_secret']);
        $jssdk = new \Common\Component\Weixin\Wxjssdk($config['js_app_id'], $config['js_app_secret'],$access_token);
        $get = I('get.');
    	$url = U('', $get, 'html', true);
        if (!isset($get['code'])){
        	$url = $jssdk->createOauthUrlForUserInfoCode($url);
        	Header("Location: $url");
        }else{
        	$code = $get['code'];
        	$jssdk->setCode($code);
        	$data = $jssdk->getAccessData();
        	$openid = $data['openid'];
        	if(empty($openid)) return;
        	$access_token = $data['access_token'];
			$url = "https://api.weixin.qq.com/sns/userinfo?access_token={$access_token}&openid={$openid}";
			$userinfo_json = $jssdk->httpGet($url);
			$userinfo = json_decode($userinfo_json,true);
        	$this->user = $this->userService()->registerByWeixin($userinfo);
        	//session('userid', $this->user['user_id']);
        	cookie($this->openid_ck, $openid,3600*24*365);
        	$this->assign('user', $this->user);
        }
    }
    
    //判断是否微信浏览器
    public function in_weixin(){
    	if ($this->_in_weixin < 1){
    		$useragent = addslashes($_SERVER['HTTP_USER_AGENT']);
        	$this->_in_weixin = strpos($useragent, 'MicroMessenger') !== false ? 1 : 2;
    	}
    	return $this->_in_weixin == 1;
    }
	
	protected function pageAndSize(&$p, &$size){
		$get = I('get.');
		$p2 = $get['p'];
		$size2 = $get['size'];
		if($p2 > 0){
			$p = $p2;
		}
		if($size2 > 0){
			$size = $size2;
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
	
	protected function specAdTypeToLink(&$ad){
		switch($ad['ad_type']){
			case 1:{ //注册页面
				$ad['ad_value'] = U('index/site/reg');
				break;
			}
		}
		return $ad;
	}
	
    protected function _empty(){
        $this->redirect('index/index/index', array('dis_id'=>I('dis_id')));
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
	
	protected function weixinService(){
		return D('Weixin', 'Service');
	}
	
	protected function navService() {
		return D('Nav', 'Service');
	}
	
	protected function adService(){
		return D('Ad', 'Service');
	}
	
	protected function statisticsService(){
		return D('Statistics', 'Service');
	}
}
