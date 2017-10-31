<?php
namespace Wap\Controller\User;
use Wap\Controller\WapController;
use Common\Basic\PageMore as Pager;

class ClinicController extends WapController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
    }
	
    public function indexAction(){
		$user_id=$this->user['user_id'];
		$info=$this->UserService()->getMyClinic($user_id);
		$this->assign('info',$info);
		$this->display();
    }
    
    public function bespeakAction(){
		if(IS_POST){
			$post=I('post.');
			$post['user_id']=$this->user['user_id'];
			$post['clinic_id']=D('UserInfo')->where("user_id='".$this->user['user_id']."'")->getField('my_clinic_id');
			$post['appointment_time']=strtotime($post['appointment_time']);
			$post['add_time']=NOW_TIME;
			try{
				$this->AppointmentService()->subAppointment($post);
			}catch( \Exception $e ){
				$this->ajaxReturn(array('error'=>1,'msg'=>$e->getMessage()));
			}
			$this->ajaxReturn(array('error'=>0,'msg'=>'提交预约成功'));
		}
    	$this->display();
    }
    
    public function askAction(){
		if(IS_POST){
			$post=I('post.');
			$db_data=D('UserInfo')->where("user_id='".$this->user['user_id']."'")->field('nick_name,my_clinic_id')->find();
			$post['user_id']=$this->user['user_id'];
			$post['clinic_id']=$db_data['my_clinic_id'];
			$post['user_name']=$db_data['nick_name'];
			$post['add_time']=NOW_TIME;
			try{
				$this->ClinicMessageService()->subMessage($post);
			}catch( \Exception $e ){
				$this->ajaxReturn(array('error'=>1,'msg'=>$e->getMessage()));
			}
			$this->ajaxReturn(array('error'=>0,'msg'=>'提交成功'));
		}
		
    	$this->display();
    }
	//咨询记录
	public function ask_logAction(){
		$p=I('p')?I('p'):I('get.p');
		$p=$p?$p:1;
		$size=12;
		$map=array('a.user_id'=>$this->user['user_id'],'a.status'=>0);
		$reply_params=$not_reply_params=array('map'=>$map,'page'=>$p,'pagesize'=>$size);
		$reply_params['map']['a.status']=1;
		$not_reply_result=$this->ClinicMessageService()->PagerList($not_reply_params);
		$this->assign('not_reply_list',$not_reply_result['list']);
		$reply_result=$this->ClinicMessageService()->PagerList($reply_params);
		
		$this->assign('reply_list',$reply_result['list']);
		
		$this->display();
	}
    
    public function historyAction(){
		$p=I('p')?I('p'):I('get.p');
		$p=$p?$p:1;
		$size=12;
		$map=array('a.user_id'=>$this->user['user_id']);
		$params=array('map'=>$map,'page'=>$p,'pagesize'=>$size);
		$result=$this->AppointmentService()->PagerList($params);
    	$this->assign('list',$result['list']);
		
		$this->display();
    }
	public function infoAction(){
		$map=array('clinic_id'=>$this->user['clinic_id']);
		$info=$this->clinicService()->findClinic($map);
		
		if(empty($info)){
			$this->redirect('clinic/index');
		}
		$info['region_lang']=$this->regionService()->getDistrictFullName($info['region_code']);
		$info['clinic_gallery']=$info['clinic_gallery']!=''?explode(',',$info['clinic_gallery']):'';
		$this->assign('info',$info);
		
		$this->display();
	}
	
	
	private function AppointmentService(){
		return D('ClinicAppointment','Service');
	}
	
	private function ClinicMessageService(){
		return D('ClinicMessage','Service');
	}
	
	private function ClinicService(){
		return D('Clinic','Service');
	}
	private function RegionService(){
		return D('Region','Service');
	}
	
	
}