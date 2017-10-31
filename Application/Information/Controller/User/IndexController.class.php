<?php
namespace Information\Controller\User;
use Information\Controller\FController;
use Common\Basic\Pager;

class IndexController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'user',
			'ac'=>'user_index_index',
		);
		$this->sbset($set);
		$sex=\Common\Basic\User::$sex;
		$this->assign('sex',$sex);
		$user_type=\Common\Basic\User::$user_type;
		$this->assign('user_type',$user_type);	
		$recharge_type=\Common\Basic\User::$recharge_type;
		$this->assign('recharge_type',$recharge_type);	
	}
	
	public function indexAction(){
		$get = I('get.');
		$this->assign('get', $get);
		
		$params = array(
				'page'=>intval(I('p')) ? intval(I('p')) : 1,
				'pagesize'=>$this->pagesize,
		);
		if (!empty($get['keyword'])) {
			$params['keyword'] = $get['keyword'];
		}
		if (!empty($get['start_time'])) {
			$params['start_time'] = $get['start_time'];
		}
		if (!empty($get['end_time'])) {
			$params['end_time'] = $get['end_time'];
		}
		$result = $this->UserService()->userPagerList($params);
		if ($result['count'] > 0){
			$this->assign('list', $result['list']);
			$pager = new Pager($result['count'], $params['pagesize']);
			$this->assign('pager', $pager->show());
		}
		
		$this->display();
	}
	
	public function infoAction($id = 0){
		$info = $this->UserService()->getUserInfo($id);
		if(empty($info)) $this->error('内容不存在');
		$info['_list'] = explode(',', $info['_list']);
		$this->assign('info', $info);
		$this->display();
	}
	
	private function userService(){
		return D('Information\User','Service');
	}
}