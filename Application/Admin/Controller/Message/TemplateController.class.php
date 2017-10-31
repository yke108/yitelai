<?php
namespace Admin\Controller\Message;
use Admin\Controller\FController;
use Common\Basic\Pager;

class TemplateController extends FController {
	public function _initialize(){
		parent::_initialize();	
		$set = array(
			'in'=>'message',
			'ac'=>'message_template_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	
    	$page = intval($_GET['p']);
    	if($page <= 0){
    		$page = 1;
    	}
    	$pagesize = 15;
    	    	
    	$list = M('UserMessage')->where($map)->page($page.','.$pagesize)->order('add_time desc')->select();
    	$count = M('UserMessage')->where($map)->count();
    	$pager = new Pager($count, $pagesize);
    	
    	$this->assign('list', $list);
    	
    	/*if(IS_AJAX){
    		$this->ajaxReturn(array(
    				'error'=>0,
    				'overwrite'=>1,
    				'pager'=>$pager->show(),
    				'clist'=>$this->renderPartial('_index'),
    		));
    	}*/
    	$this->assign('pager', $pager->show());
    	
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
					$this->messageService()->addMessage($user_ids,$data);
				}else{
					$this->messageService()->addAllMessage($user_ids,$data);
				}
			}catch(\Exception $e){
				$this->error($e->getMessage());
			}
			
			$this->success('保存成功');
		}
		$this->display('edit');
	}
	
	public function editAction($id = 0){
		$gmap = array(
			'msg_id'=>$id,
		);
		$info = M('UserMessage')->where($gmap)->find();
		if(empty($info)){
			$this->error('消息不存在');
		}
		if(IS_POST){
			$data = $_POST;
			if($_POST['user_name']){
				$user = M('UserInfo')->where("user_name='".$_POST['user_name']."' or mobile='".$_POST['user_name']."'")->find();
				if(!$user){
					$this->error('没有找到该会员,请重新输入');
				}
			}				
			$data['user_id']=$user['user_id']?$user['user_id']:0;
			$res = M('UserMessage')->where($gmap)->save($data);
			if($res === false){
				$this->error('保存失败');
			}
			
			$this->success('保存成功');
	}

		$this->assign('info', $info);
		$this->display();
	}
	
    public function delAction($id = 0){
		$gmap = array(
			'msg_id'=>$id,
		);
		$info = M('UserMessage')->where($gmap)->find();
		if(empty($info)){
			$this->error('消息不存在');
		}
		M('UserMessage')->where($gmap)->delete();
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
	
	private function messageService(){
		return D('Message','Service');
	}
}