<?php
namespace Main\Controller\User;
use Main\Controller\MainController;
use Common\Basic\Pager;

class MessageController extends MainController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
		
		$this->assign('page_title', '个人中心');
    }
	
    public function indexAction(){
    	//未读消息统计
    	$map = array(
    			'type'=>0,
    			'add_time'=>array('gt',$this->user['reg_time'])
    	);
    	$message_status = M('message_status')->where(array('user_id'=>$this->user['user_id']))->getField('id, msg_id');
    	if ($message_status) {
    		$map['msg_id'] = array('not in', $message_status);
    	}
    	$system_count = $this->messageService()->searchMessagesCount($map);
    	$this->assign('system_count', $system_count);
    	
    	$map = array(
    			'type'=>1,
    			'user_id'=>$this->user['user_id'],
    	);
    	if ($message_status) {
    		$map['msg_id'] = array('not in', $message_status);
    	}
    	$private_count = $this->messageService()->searchMessagesCount($map);
    	$this->assign('private_count', $private_count);
    	
    	$get = I('get.');
    	$get['type'] = $get['type'] ? $get['type'] : 0;
    	$this->assign('get', $get);
    	
		$page = 1; $pagesize = 10;
		$this->pageAndSize($page, $pagesize);
		$map = array('type'=>$get['type']);
		$map['user_id'] = $this->user['user_id'];
		$map['reg_time'] = $this->user['reg_time'];
		$count = $this->messageService()->searchMessagesCount($map);
		$list = array();
		if ($count) {
			$list = $this->messageService()->searchMessages($map, 'add_time desc', $page, $pagesize);
			$this->assign('list', $list);
			$pager = new Pager($count, $pagesize);
			$this->assign('pages', $pager->show_pc());
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