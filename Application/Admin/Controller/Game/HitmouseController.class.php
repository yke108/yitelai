<?php
namespace Admin\Controller\Game;
use Admin\Controller\FController;
use Common\Basic\Pager;

class HitmouseController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'game',
			'ac'=>'game_hitmouse_index',
		);
		$this->sbset($set);
		
	}
	
	public function indexAction(){
		$page=I('p')?I('p'):I('get.p');
		$size=12;
		$keyword=I('keyword')?I('keyword'):I('get.keyword');
		$start_time=I('start_time')?I('start_time'):I('get.start_time');
		$end_time=I('end_time')?I('end_time'):I('get.end_time');
		$user_id=I('user_id')?I('user_id'):I('get.user_id');
		$map=array();
		
		
		if($start_time!=''){
			$map['add_time'][]=array('egt', strtotime($start_time));
		}
		if($end_time!=''){
			$map['add_time'][]=array('elt', strtotime($end_time) + 86400);
		}
		if($user_id!=''){
			$map['a.user_id']=$user_id;
		}
		
		/* $user_id=$this->UserSercice()->getUserId($map);
		if(!empty($user_id)){
			$map['a.user_id']=array('in',$user_id);
		} */
		
		$params=array('page'=>$page,'pagesize'=>$size,'map'=>$map,'keyword'=>$keyword);
		
		$result=$this->hitMouseService()->infoPagerList($params);
		$this->assign('list',$result['list']);
		$this->assign('users',$result['users']);
		
		
		$this->assign('post',$_POST);
		$this->display();
	}
	
    public function configAction(){
    	$get = I('get.');
    	$this->assign('get', $get);
    	
		if(IS_POST){
			$post=I('post.');
			try{
				$this->ConfigService()->setConfig($post,'game_hit_mouse');
			}catch(\Exception $e){
				$this->error($e->getMessage());
			}
			$this->success('设置成功');
		}
		
    	$config=$this->ConfigService()->findSystemConfigs('game_hit_mouse','fkey,fval,ftitle,fdesc,field_type');
		$this->assign('config',$config);
		
		$set=array('ac'=>'game_hitmouse_config');
		$this->sbset($set);
		$this->display();
    }
	
	
	private function hitMouseService(){
		return D('HitMouse',"Service");
	}
	private function UserSercice(){
		return D('User',"Service");
	}
	
	
	
}