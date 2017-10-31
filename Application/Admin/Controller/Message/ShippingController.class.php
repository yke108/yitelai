<?php
namespace Admin\Controller\Message;
use Admin\Controller\FController;
use Common\Basic\Pager;
use Common\Basic\Status;

class ShippingController extends FController {
	public function _initialize(){
		parent::_initialize();	
		$set = array(
			'in'=>'message',
			'ac'=>'message_shipping_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	session('back_url', __SELF__);
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	//分类
    	$cat_list = $this->messageCatService()->catOptionList();
    	$this->assign('cat_list', $cat_list);
    	
    	$page = intval(I('p')) ? intval(I('p')) : 1;
    	$pagesize = $this->pagesize;
    	
    	$map = array('msg_type'=>Status::MsgTypeShipping);
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
    	
    	$count = $this->distributorMessageService()->searchMessagesCount($map);
    	if ($count) {
    		$list = $this->distributorMessageService()->searchMessages($map, 'add_time DESC', $page, $pagesize);
    		$pager = new Pager($count, $pagesize);
    		$this->assign('list', $list);
    		$this->assign('pager', $pager->show());
    	}
    	
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$data = $_POST;
			
			unset($data['distributor_id']);
			unset($data['distributor_ids']);
			if($_POST['type']==0){
				$distributor_ids=0;
			}else{
				$distributor_ids=$_POST['distributor_ids'];
			}
			$data['admin_id'] = session('uid');
			$data['msg_type'] = Status::MsgTypeShipping;
			try{
				if($data['type']==0){	
					$this->distributorMessageService()->addMessage($distributor_ids,$data);
				}else{
					$this->distributorMessageService()->addAllMessage($distributor_ids,$data);
				}
			}catch(\Exception $e){
				$this->error($e->getMessage());
			}
			
			$this->success('保存成功', U('index'));
		}
		
		//分类
		$cat_list = $this->messageCatService()->catOptionList();
		$this->assign('cat_list', $cat_list);
		
		$this->display('edit');
	}
	
	public function editAction($id = 0){
		$map = array(
			'msg_id'=>$id,
		);
		$info = $this->distributorMessageService()->getMessageInfo($map);
		if(empty($info)){
			$this->error('消息不存在');
		}
		
		if(IS_POST){
			$data = $_POST;
			$data['admin_id'] = session('uid');
			$data['update_time'] = NOW_TIME;
			try {
				$res = $this->distributorMessageService()->modify($map, $data);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('保存成功', session('back_url'));
		}
		
		//分类
		$cat_list = $this->messageCatService()->catOptionList('', '', $info['cat_id']);
		$this->assign('cat_list', $cat_list);
		
		$this->assign('info', $info);
		$this->display();
	}
	
    public function delAction($id = 0){
		try {
			$this->distributorMessageService()->messageDelete($id);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功');
    }
	
	public function distributorsAction(){
		layout('Layout/sel');
		$p=I('p')?I('p'):I('get.p');
		$size=12;
		$map=array();
		$params=array('map'=>$map,'pagesize'=>$size,'page'=>$p);
		$result=$this->distributorService()->getPagerList($params);
		$pager=new Pager($result['count'],$size);
		$this->assign('list',$result['list']);
		$this->assign('pager',$pager->show());
		$this->display();
	}
	
	private function distributorMessageService(){
		return D('DistributorMessage','Service');
	}
	
	private function messageCatService(){
		return D('DistributorMessageCat','Service');
	}
	
	private function distributorService(){
		return D('Distributor','Service');
	}
}