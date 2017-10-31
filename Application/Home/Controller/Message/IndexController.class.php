<?php
namespace Home\Controller\Message;
use Home\Controller\BaseController;
use Common\Basic\Status;

class IndexController extends BaseController {	
	public function _initialize(){
		parent::_initialize();
    }
    
    /* public function _purviewCheck(){
    	$this->purviewCheck(false);
    } */
    
    protected function _purviewCheck(){
    	$this->purviewCheck('index');
    }
	
    public function indexAction() { /*通知公告*/
    	//店铺
    	if (session('distributor_id')) {
    		$map['distributor_id'] = array('in', array(session('distributor_id'), 0));
    	}
    	
    	$msg_type_list = array();
    	foreach (Status::$msgTypeList as $k => $v) {
    		if (in_array($k, array(Status::MsgTypeNotice, Status::MsgTypeShipping, Status::MsgTypeRegion))) {
    			//读取最新一条消息
    			$map['msg_type'] = $k;
    			//已读消息
    			if (session('distributor_id')) {
    				$message_status = $this->distributorMessageService()->getReadMessage($map);
    				if ($message_status) {
    					$map['msg_id'] = array('not in', $message_status);
    				}
    			}
    			$message = $this->distributorMessageService()->searchMessageInfo($map);
    			$msg_type_list[] = array(
    					'type_id'=>$k,
    					'type_name'=>$v,
    					'message'=>$message,
    			);
    		}
    	}
    	$this->assign('msg_type_list', $msg_type_list);
    	
    	$this->assign('sys_id', session('sys_id'));
    	
    	$this->assign('page_title', '信息分类');
    	$this->display();
    }
    
    public function listAction() { /*NoPurview*/
    	//店铺
    	if (session('sys_id') == 2) {
    		$map['distributor_id'] = array('in', array(session('distributor_id'), 0));
    	}
    	$type = I('type');
    	if (isset($type)) {
    		$map['msg_type'] = $type;
    	}
    	if (I('store_id')) {
    		$map['distributor_id'] = I('store_id');
    	}
    	$start_time = I('start_time');
    	if ($start_time) {
    		$map['add_time'][] = array('egt', strtotime($start_time));
    	}
    	$end_time = I('end_time');
    	if ($end_time) {
    		$map['add_time'][] = array('elt', strtotime($end_time) + 86400);
    	}
    	$count = $this->distributorMessageService()->searchMessagesCount($map);
    	$this->assign('count', $count);
    	if ($count) {
    		$page = intval(I('p')) > 0 ? intval(I('p')) : 1;
    		$pagesize = $this->pagesize;
    		$list = $this->distributorMessageService()->searchMessages($map, 'add_time DESC', $page, $pagesize);
    		$this->assign('list', $list);
    	}
    	
    	if (IS_AJAX) {
    		if(empty($list)){
    			$clist = '';
    		}else{
    			$clist = $this->renderPartial('_list');
    		}
    		$this->ajaxReturn(array('html'=>$clist, 'p'=>$page+1));
    	}
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	//店铺列表
    	$map = array('status'=>Status::DistributorStatusNormal);
    	$distributor_list = $this->distributorService()->getAllList($map);
    	$this->assign('distributor_list', $distributor_list);
    	
    	$this->assign('sys_id', session('sys_id'));
    	
    	$this->assign('page_title', '信息列表');
    	$this->display();
    }
    
    public function infoAction($id = 0) { /*NoPurview*/
    	$map = array(
    			'msg_id'=>$id,
    			'distributor_id'=>array('in', array('0', session('distributor_id'))),
    	);
    	$info = $this->distributorMessageService()->getMessageInfo($map);
    	if( empty($info) ){
    		$this->error('消息不存在');
    	}
    	$this->assign('info', $info);
    	
    	//消息类型
    	$this->assign('msg_types', Status::$msgTypeList);
    	
    	//店铺设为已读
    	if (session('distributor_id')) {
    		$params = array(
    				'msg_id'=>$id,
    				'distributor_id'=>session('distributor_id'),
    		);
    		if ($this->distributorMessageService()->setRead($params) === false) {
    			$this->error('操作错误');
    		}
    	}
    	
    	$this->assign('page_title', '信息详情');
    	$this->display();
    }
    
    protected function addBefore(){
    	$this->purviewCheck();
    }
    
    public function addAction(){ /*发布公告*/
    	if (IS_POST) {
    		$post = I('post.');
    		
    		$this->checkData($post);
    		$post['admin_id'] = session('uid');
    		
    		try{
    			if ($post['msg_type'] == 0) { //公告信息
    				$distributor_ids = 0;
    				$post['type'] = 0;
    				unset($post['distributor_id']);
    				$this->distributorMessageService()->addMessage($distributor_ids, $post);
    			}elseif ($post['msg_type'] == 1) { //发货信息
    				$distributor_ids = array($post['distributor_id']);
    				unset($post['distributor_id']);
    				$this->distributorMessageService()->addAllMessage($distributor_ids, $post);
    			}elseif ($post['msg_type'] == 2) { //区域信息
    				$region_list = $this->regionService()->getChildList($post['city']);
    				$region_codes = array_keys($region_list);
    				$map['region_code'] = array('in', $region_codes);
					$map['status'] = Status::DistributorStatusNormal;
    				$distributor_list = $this->distributorService()->getAllList($map);
    				if (empty($distributor_list)) {
    					$this->error('该区域无店铺，无法发布区域信息');
    				}
    				$distributor_ids = array_keys($distributor_list);
    				unset($post['distributor_id']);
    				$this->distributorMessageService()->addAllMessage($distributor_ids, $post);
    			}
    		}catch(\Exception $e){
    			$this->error($e->getMessage());
    		}
    		
    		$this->success('发布成功', U('index'));
    	}
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	//消息类型
    	$this->assign('msg_type_list', Status::$msgTypeList);
    	
    	//选择的店铺
    	if ($get['store_id']) {
    		$distributor_info = $this->distributorService()->getInfo($get['store_id']);
    		$this->assign('distributor', $distributor_info);
    	}
    	
    	$this->assign('page_title', '信息发布');
    	$this->display();
    }
    
    public function distributor_listAction() { /*NoPurview*/
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
				'status'=>Status::DistributorStatusNormal,
    	);
    	if (I('keyword')) {
    		$params['keyword'] = I('keyword');
    	}
    	$result = $this->distributorService()->getPagerList($params);
    	$this->assign('list', $result['list']);
    	$this->assign('count', $result['count']);
    	
    	if (IS_AJAX) {
    		if(empty($result['list'])){
    			$clist = '';
    		}else{
    			$clist = $this->renderPartial('_distributor_list');
    		}
    		$this->ajaxReturn(array('html'=>$clist, 'p'=>$params['page']+1));
    	}
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$this->assign('page_title', '选择店铺');
    	$this->display();
    }
    
    private function checkData($data) {
    	if ($data['msg_type'] === '') {
    		$this->error('请选择发布类型');
    	}
    	/* if (empty($data['user_id']) && empty($data['distributor_id'])) {
    		$this->error('请选择发送对象');
    	} */
    	if (empty($data['title'])) {
    		$this->error('公告标题不能为空');
    	}
    	if (empty($data['content'])) {
    		$this->error('请输入公告详细内容');
    	}
    }
    
    private function distributorMessageService() {
    	return D('DistributorMessage', 'Service');
    }
    
    private function distributorService() {
    	return D('Distributor', 'Service');
    }
    
    private function messageService(){
    	return D('Message','Service');
    }
    
    private function regionService(){
    	return D('Region','Service');
    }
}