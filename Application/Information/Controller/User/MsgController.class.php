<?php
namespace Information\Controller\User;
use Information\Controller\FController;
use Common\Basic\Pager;
use Common\Basic\Status;

class MsgController extends FController {
	public function _initialize(){
		parent::_initialize();	
		$set = array(
			'in'=>'user',
			'ac'=>'user_msg_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	session('back_url', __SELF__);
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'msg_type'=>Status::MsgTypeNotice,
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
    	$result = $this->userMsgService()->infoPagerList($params);
    	if ($result['count'] > 0){
    		$this->assign('list', $result['list']);
    		$pager = new Pager($result['count'], $params['pagesize']);
    		$this->assign('pager', $pager->show());
    	}
    	
    	$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$data = $_POST;
			
			unset($data['user_id']);
			unset($data['user_ids']);
			if($_POST['type']==0){
				$user_ids=0;
			}else{
				$user_ids=$_POST['user_ids'];
			}
			
			try{
				if($data['type']==0){	
					$this->userMsgService()->addMessage($user_ids,$data);
				}else{
					$this->userMsgService()->addAllMessage($user_ids,$data);
				}
			}catch(\Exception $e){
				$this->error($e->getMessage());
			}
			
			$this->success('保存成功', U('index'));
		}
		$this->display('edit');
	}
	
	public function editAction($id = 0){
		$info = $this->userMsgService()->getInfo($id);
		if(empty($info)){
			$this->error('消息不存在');
		}
		
		if(IS_POST){
			$data = $_POST;
			
			unset($data['user_id']);
			unset($data['user_ids']);
			if($_POST['type']==0){
				$user_ids=0;
			}else{
				$user_ids=$_POST['user_ids'];
			}
			
			try{
				if($data['type']==0){
					$res = $this->userMsgService()->infoCreateOrModify($data);
				}else{
					$this->userMsgService()->addAllMessage($user_ids,$data);
				}
			}catch(\Exception $e){
				$this->error($e->getMessage());
			}
			
			if($res === false){
				$this->error('保存失败');
			}
			
			$this->success('保存成功', session('back_url'));
		}
		
		$this->assign('info', $info);
		
		$this->display();
	}
	
    public function delAction($id = 0){
		$info = $this->userMsgService()->getInfo($id);
		if(empty($info)){
			$this->error('消息不存在');
		}
		try {
			$this->userMsgService()->infoDelete($id);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功');
    }
	
	public function usersAction(){
		layout('Layout/sel');
		$p=I('p')?I('p'):I('get.p');
		$size=12;
		$map=array();
		$params=array('map'=>$map,'pagesize'=>$size,'page'=>$p);
		$result=$this->userService()->userPagerList($params);
		$pager=new Pager($result['count'],$size);
		$this->assign('list',$result['list']);
		$this->assign('pager',$pager->show());
		$this->display();
	}
	
	private function userMsgService(){
		return D('Information\UserMsg', 'Service');
	}
	
	private function userService(){
		return D('Information\User', 'Service');
	}
}