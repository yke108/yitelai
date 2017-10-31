<?php
namespace Wap\Controller\User;
use Wap\Controller\WapController;

class CustomerController extends WapController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
		
		$this->assign('page_title', '个人中心');
    }
	
    public function indexAction(){
		$this->display();
    }
    
	public function blogAction(){
		$get = I('get.');
		$get['type'] = $get['type'] ? $get['type'] : 0;
		$this->assign('get',$get);
		
		if(IS_POST){
			$post=I('post.');
			$post['reply_time']=NOW_TIME;
			$post['status']=1;
			try{
				$this->ClinicMessageService()->replyMessage($post);
			}catch(\Exception $e){
				$this->ajaxReturn(array('error'=>1,'msg'=>$e->getMessage()));
			}
			$this->ajaxReturn(array('error'=>0,'msg'=>'回复成功'));
		}
		$p=I('p')?I('p'):I('get.p');
		$p=$p?$p:1;
		$size=12;
		$map=array('a.clinic_id'=>$this->user['clinic_id'],'a.status'=>$get['type']);
		$reply_params=$not_reply_params=array('map'=>$map,'page'=>$p,'pagesize'=>$size);
		$reply_params['map']['a.status']=1;
		$not_reply_result=$this->ClinicMessageService()->PagerList($not_reply_params);
		$this->assign('not_reply_list',$not_reply_result['list']);
		$reply_result=$this->ClinicMessageService()->PagerList($reply_params);
		
		$this->assign('reply_list',$reply_result['list']);
		$this->display();
    }
    
	public function bespeakAction(){
		$p=I('p')?I('p'):I('get.p');
		$p=$p?$p:1;
		$size=12;
		$type=I('type')?I('type'):I('get.type');
		$type=$type?$type:1;
		$status=$type==1?0:1;
		$map=array('a.clinic_id'=>$this->user['clinic_id'],'a.status'=>$status);
		$params=array('map'=>$map,'page'=>$p,'pagesize'=>$size);
		$result=$this->AppointmentService()->PagerList($params);
		
		$this->assign('list',$result['list']);
		$this->assign('type',$type);
		
		if(IS_AJAX){
			if(empty($result['list'])){
				$clist = '';
			}else{
				$clist = $this->renderPartial('_bespeak');
			}
			die($clist);
		}
		
		$this->display();
    }
	//把预约标记为已处理
	public function checkBespeakAction(){
		$id=I('bespeak_id')?I('bespeak_id'):I('get.bespeak_id');
		$params=array('map'=>array('id'=>$id,'clinic_id'=>$this->user['clinic_id']),'data'=>array('status'=>1));
		try{
			$this->AppointmentService()->checkAppointment($params);
		}catch( \Exception $e){
			$this->ajaxReturn(array('error'=>1,'msg'=>$e->getMessage()));
		}
		$this->ajaxReturn(array('error'=>0,'msg'=>'确认处理成功'));
	}
	
	public function askAction(){
		if(IS_POST){
			$post=I('post.');
			$clinic_info=$this->userService()->getClinic($this->user['user_id']);
			$post['agent_id']=$clinic_info['agent_id'];
			$post['clinic_id']=$clinic_info['clinic_id'];
			$post['add_time']=NOW_TIME;
			
			try{
				$this->ClinicMsgAgentService()->subMessage($post);
			}catch( \Exception $e ){
				$this->ajaxReturn(array('error'=>1,'msg'=>$e->getMessage()));
			}
			$this->ajaxReturn(array('error'=>0,'msg'=>'提交成功'));
		}
		$this->display();
	}
	public function ask_logAction(){
		$p=I('p')?I('p'):I('get.p');
		$p=$p?$p:1;
		$size=12;
		//$type=I('type')?I('type'):I('get.type');
//		$type=$type?$type:1;
//		$status=$type==1?0:1;
		$map=array('a.clinic_id'=>$this->user['clinic_id']);
		$params=array('map'=>$map,'page'=>$p,'pagesize'=>$size);
		$result=$this->ClinicMsgAgentService()->PagerList($params);
		
		$this->assign('list',$result['list']);
		$this->assign('type',$type);
		
		$this->display();
	}
	
	
	
	private function AppointmentService(){
		return D('ClinicAppointment','Service');
	}
	
	private function ClinicMessageService(){
		return D('ClinicMessage','Service');
	}
	private function ClinicMsgAgentService(){
		return D('ClinicMsgAgent','Service');
	}
}