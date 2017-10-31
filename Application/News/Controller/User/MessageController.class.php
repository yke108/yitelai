<?php
namespace News\Controller\User;
use News\Controller\WapController;

class MessageController extends WapController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
	
		$this->assign('page_title', '消息通知');
	}
	
	public function indexAction(){
		$get = I('get.');
		$this->assign('get', $get);
		 
		$page = intval(I('p')) ? intval(I('p')) : 1; $pagesize = 10;
		$this->pageAndSize($page, $pagesize);
		$map['user_id'] = $this->user['user_id'];
		$map['reg_time'] = $this->user['reg_time'];		
		
		$count = $this->userMsgService()->searchMessagesCount($map);
		$this->assign('count', $count);
		$list = array();
		if ($count) {
			$map['type']=100;
			$list = $this->userMsgService()->searchMessages($map, 'add_time desc', $page, $pagesize);
			$this->assign('list', $list);
		}
		
		if(IS_AJAX){
			if(empty($list)){
				$clist = '';
			}else{
				$clist = $this->renderPartial('_index');
			}
			$this->ajaxReturn(array('html'=>$clist, 'p'=>$page+1));
		}
		
		$this->display();
	}
	
	public function infoAction($id = 0){
		$map = array('msg_id'=>$id);
		$info = $this->userMsgService()->getMessageInfo($map);
		if ($info['type'] == 1 && $info['user_id'] != $this->user['user_id']) {
			$this->error('数据不存在');
		}
		$this->assign('info', $info);
		
		//设为已读
		$params = array(
				'msg_id'=>$id,
				'user_id'=>$this->user['user_id']
		);
		if ($this->userMsgService()->setRead($params) === false) {
			$this->error('操作错误');
		}
		
		$this->display();
	}
	
	private function messageEvent(){
		return D('Message', 'Event');
	}
	
	private function userMsgService(){
		return D('Information\UserMsg', 'Service');
	}
}