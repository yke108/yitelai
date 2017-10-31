<?php
namespace Wap\Controller\User;
use Wap\Controller\WapController;

class MessageController extends WapController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
	
		$this->assign('page_title', '个人中心');
	}
	
	public function indexAction(){
		$get = I('get.');
		$this->assign('get', $get);
		 
		$page = 1; $pagesize = 10;
		$this->pageAndSize($page, $pagesize);
		$map['user_id'] = $this->user['user_id'];
		$map['reg_time'] = $this->user['reg_time'];		
		
		$count = $this->messageService()->searchMessagesCount($map);
		$list = array();
		if ($count) {
			$map['type']=100;
			$list = $this->messageService()->searchMessages($map, 'add_time desc', $page, $pagesize);
			$this->assign('list', $list);
		}
		
		if(IS_AJAX){
			if(empty($list)){
				$clist = '';
			}else{
				$clist = $this->renderPartial('_index');
			}
			die($clist);
		}
		
		$this->display();
	}
	
	public function infoAction($id = 0){
		$map = array('msg_id'=>$id);
		$info = $this->messageService()->getMessageInfo($map);
		if ($info['type'] == 1 && $info['user_id'] != $this->user['user_id']) {
			$this->error('数据不存在');
		}
		$this->assign('info', $info);
	
		//设为已读
		$params = array(
				'msg_id'=>$id,
				'user_id'=>$this->user['user_id']
		);
		if ($this->messageService()->setRead($params) === false) {
			$this->error('操作错误');
		}
	
		$this->display();
	}
	
	private function messageEvent(){
		return D('Message', 'Event');
	}
	
	private function messageService(){
		return D('Message', 'Service');
	}
}