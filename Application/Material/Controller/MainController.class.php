<?php
namespace Material\Controller;
use Think\Controller;
//use Think\Log;

abstract class MainController extends Controller {
	public $login_check = false;
	public $user = array();
	public $cat_list = array();
	public $ad_list = array();
	public $sysconfig = array();
	public $login_url = '';
	
	public function _initialize(){
		$controller_name = strtolower(CONTROLLER_NAME);
		$carr = explode('/', $controller_name);
		$this->assign('croot', $carr[0]);
		$this->assign('controller_name', $controller_name);
		$this->assign('action_name', ACTION_NAME);
		
		//session('userid', 5); //debug
		if(session('userid')){
			$userid = session('userid');
			$this->user = $this->userService()->getUserInfo($userid);
			$this->user['user_id'] < 1 && session('userid', null);
			$this->user['user_thumb_img']=$this->user['avatar']!=''?str_replace('/upload/','/upload/thumbs/b200/',$this->user['avatar']):'';
			$this->assign('user', $this->user);
		}
		
		if ($this->login_check && empty($userid)) {
			if (IS_AJAX) {
				$this->error('请先登录', U('index/site/login'));
			}
			$this->redirect('index/site/login');
		}
		
		//系统设置
		$this->sysconfig = $this->configService()->findConfigs('system');
		$this->assign('sysconfig', $this->sysconfig);
		
		//导航栏
		$params = array(
				'is_show'=>1,
				'distributor_id'=>0,
				'type'=>0,
		);
		$nav = $this->navService()->getPagerList($params);
		$this->assign('nav_list', $nav['list']);
		
		//首页、登录和注册链接
		$url = DK_DOMAIN;
		$this->assign('url', $url);
		$this->login_url = DK_DOMAIN.'/index.php/index/site/login.html';
		$this->assign('login_url', $this->login_url);
		$reg_url = DK_DOMAIN.'/index.php/index/site/reg.html';
		$this->assign('reg_url', $reg_url);
		$logout_url = DK_DOMAIN.'/index.php/index/site/logout.html';
		$this->assign('logout_url', $logout_url);
		
		//广告
		$params = array(
				'distributor_id'=>0
		);
		$this->ad_list = $this->adService()->infoAllList($params);
		$this->assign('ad_list', $this->ad_list);
		
		//友情链接
		$friendlink_datas = $this->friendLinkService()->infoPagerList();
		$this->assign('friendlink_list', $friendlink_datas['list']);
		
		//cookie
		$this->assign('pc_top', cookie('pc_top'));
		$this->assign('pc_goods_list_top', cookie('pc_goods_list_top'));
		$this->assign('pc_goods_list_bottom', cookie('pc_goods_list_bottom'));
		
		//来源url
		if (!IS_POST) {
			session('from_url', $_SERVER['HTTP_REFERER']);
		}
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
        $this->redirect('index/index/index');
    }
	
	protected function userService(){
		return D('User', 'Service');
	}
	
	protected function configService(){
		return D('Config', 'Service');
	}
	
	protected function navService() {
		return D('Nav', 'Service');
	}
	
	protected function adService(){
		return D('Ad', 'Service');
	}
	
	protected function friendLinkService(){
		return D('FriendLink', 'Service');
	}
	
	protected function goodsKeywordsService() {
		return D('GoodsKeywords', 'Service');
	}

	protected function goodsCatService(){
		return D('GoodsCat', 'Service');
	}
	
	protected function cartService(){
		return D('Cart', 'Service');
	}
}
