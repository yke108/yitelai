<?php
namespace Wap\Controller\User;
use Wap\Controller\WapController;
use Common\Basic\PageMore as Pager;

class NoticeController extends WapController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
    }
	
    public function indexAction(){
		$page = 1; $pagesize = 10;
		$this->pageAndSize($page, $pagesize);
//		$map = array(
//			'a.user_id'=>$this->user['user_id'],
//		);
//		$orderBy = 'a.add_time desc';
//		$messageService = $this->messageService();
//		$list = $messageService->searchMessages($map, $orderBy, $page, $pagesize);
//		
//		$count = $messageService->searchMessagesCount($map);
//		$pager = new Pager($count, $pagesize);
//		$this->assign('pager', $pager->show());
//		
//		$this->assign('x_wrap_id', 'message_list_wrap_id');
//		$this->assign('x_pager_id', 'message_pager_id');
//		$list = array(1,2,3,4,5);

		$count = $this->messageService()->searchMessagesCount();
		$list = array();
		if ($count) {
			$list = $this->messageService()->searchMessages('', 'add_time desc', $page, $pagesize);
		}
		
		$this->assign('list', $list);
		$this->display('index');
    }
	
	public function infoAction($id = 0){
		$map = array('msg_id'=>$id);
		$info = $this->messageService()->getMessageInfo($map);
		$this->assign('info', $info);
		$this->display();
	}
	
	private function messageEvent(){
		return D('Message', 'Event');
	}
	
	private function messageService(){
		return D('Message', 'Service');
	}
}