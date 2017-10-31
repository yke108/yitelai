<?php
namespace Admin\Controller\Recharge;
use Admin\Controller\FController;
use Common\Basic\Pager;

class ActivityController extends FController {
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'recharge',
			'ac'=>'recharge_activity_index',
		);
		$this->sbset($set);
		
	}
	public function indexAction(){
		$p=I('p')?I('p'):I('get.p');
		$p=$p?$p:1;
		$size=12;
		$params=array('page'=>$p,'pagesize'=>$size);
		$result=$this->RechargeService()->activityPagerList($params);
		$pager=new Pager($result['count'],$size);
		$this->assign('list',$result['list']);
		$this->assign('page',$pager->show());
		$this->display();
	}
	
	public function addAction(){
		if(IS_POST){
			$post=I('post.');
			try{
				$this->RechargeService()->activityCreateOrModify($post);
			}catch(\Exception $e){
				$this->error($e->getMessage());
			}
			$this->success('添加成功',U('index'));
		}
		$this->display('edit');
	}
	
	public function editAction($id){
		$info=$this->RechargeService()->getActivity($id);
		if(empty($info)){
			$this->error('活动不存在');
		}
		
		if(IS_POST){
			if($info['status']>0){
				$this->error('请先关闭该活动');
			}
			
			$post=I('post.');
			$post['activity_id']=$id;
			try{
				$this->RechargeService()->activityCreateOrModify($post);
			}catch(\Exception $e){
				$this->error($e->getMessage());
			}
			$this->success('修改成功',U('index'));
		}
		$this->assign('info',$info);
		$this->display();
	}
	public function delAction($id){
		$info=$this->RechargeService()->getActivity($id);
		if(empty($info)){
			$this->error('活动不存在');
		}
		
		if($info['status']>0){
			$this->error('请先关闭该活动');
		}
		$map['activity_id']=$id;
		try{
			$this->RechargeService()->activityDelete($map);
		}catch(\Exception $e){
			$this->error($e->getMessage());
		}
		$this->success('活动删除成功');
	}
	
	//修改活动状态
	public function change_statusAction($id){
		$info=$this->RechargeService()->getActivity($id);
		if(empty($info)){
			$this->error('活动不存在');
		}
		$params['status']=$info['status']>0?0:1;
		$params['activity_id']=$id;
		
		try{
			$this->RechargeService()->changeStatus($params);
		}catch(\Exception $e){
			$this->error($e->getMessage());
		}
		$this->AjaxReturn(array('info'=>'修改状态成功','status'=>0,'force_redirect_page'=>1,'url'=>U('index')));
	}
	
	
   	private function RechargeService(){
		return D('Recharge','Service');
	}
	
	
	private function UserSercice(){
		return D('User',"Service");
	}
	
	
	
}