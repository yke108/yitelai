<?php
namespace Distributor\Controller\Message;
use Distributor\Controller\FController;
use Common\Basic\Pager;
use Common\Basic\Status;

class IndexController extends FController {
	public function _initialize(){
		parent::_initialize();	
		$set = array(
			'in'=>'message',
			'ac'=>'message_index_index',
		);
		$this->sbset($set);
		
		//消息类型
		$this->assign('msg_types', Status::$msgTypeList);
    }
	
    public function indexAction(){
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$page = intval(I('p')) ? intval(I('p')) : 1;
    	$pagesize = $this->pagesize;
    	
    	$map = array('distributor_id'=>$this->org_id, 'type'=>100);
    	if ($get['keyword']) {
    		$map['keyword'] = $get['keyword'];
    	}
    	if ($get['cat_id']) {
    		$map['cat_id'] = $get['cat_id'];
    	}
    	if ($get['start_time']) {
    		$map['add_time'][] = array('egt', strtotime($get['start_time']));
    	}
    	if ($get['end_time']) {
    		$map['add_time'][] = array('elt', strtotime($get['end_time']) + 86400);
    	}
    	if ($get['msg_type']) {
    		$map['msg_type'] = $get['msg_type'];
    	}
    	
    	$count = $this->distributorMessageService()->searchMessagesCount($map);
    	if ($count) {
    		$list = $this->distributorMessageService()->searchMessages($map, 'add_time DESC', $page, $pagesize);
    		$pager = new Pager($count, $pagesize);
    		$this->assign('list', $list);
    		$this->assign('pager', $pager->show());
    	}
    	
		$this->display();
    }
	
	public function viewAction($id = 0){
		$map = array(
				'msg_id'=>$id,
		);
		$info = $this->distributorMessageService()->getMessageInfo($map);
		if( empty($info) || ($info['distributor_id'] > 0 && $info['distributor_id'] != $this->org_id) ){
			$this->error('消息不存在');
		}
		$this->assign('info', $info);
		
		//设为已读
		$params = array(
				'msg_id'=>$id,
				'distributor_id'=>$this->org_id
		);
		if ($this->distributorMessageService()->setRead($params) === false) {
			$this->error('操作错误');
		}
		
		$this->display();
	}
	
	private function distributorMessageService(){
		return D('DistributorMessage','Service');
	}
}