<?php
namespace Admin\Controller\Point;
use Admin\Controller\FController;
use Common\Basic\Pager;
use Common\Basic\Status;
use Common\Logic\PointLogic;

class IndexController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'point',
			'ac'=>'point_index_config',
		);
		$this->sbset($set);
		
	}
	
	public function indexAction(){
		$get = I('get.');
		$this->assign('get', $get);
		
		$keyword=I('keyword')?I('keyword'):I('get.keyword');
		$start_time=I('start_time')?I('start_time'):I('get.start_time');
		$end_time=I('end_time')?I('end_time'):I('get.end_time');
		$user_id=I('user_id')?I('user_id'):I('get.user_id');
		$change_type=I('change_type')?I('change_type'):I('get.change_type');
		
		$map=array();
		if($keyword!=''){
			$keyword_map=array('u.nick_name'=>array('like',"%{$keyword}%"),'u.mobile'=>array('like',"%{$keyword}%"),'_logic'=>'or');
			$map['_complex'] = $keyword_map;
		}
		if($start_time!=''){
			$map['add_time'][]=array('egt', strtotime($start_time));
		}
		if($end_time!=''){
			$map['add_time'][]=array('elt', strtotime($end_time) + 86400);
		}
		if($user_id!=''){
			$map['a.user_id']=$user_id;
		}
		if($change_type!=''){
			$map['a.change_type']=$change_type;
		}
		
		/* $user_id=$this->UserSercice()->getUserId($map);
		if(!empty($user_id)){
			$map['a.user_id']=array('in',$user_id);
		} */
		
		$params=array(
				'page'=>I('p')?I('p'):I('get.p'),
				'pagesize'=>$this->pagesize,
				'map'=>$map,
		);
		$result=$this->PointService()->pointPagerList($params);
		$this->assign('list',$result['list']);
		$pager=new Pager($result['count'], $params['pagesize']);
		$this->assign('page',$pager->show());
		
		//变动类型
		$this->assign('type_list', PointLogic::$ctypes);
		
		$set=array(
			'ac'=>'point_index_index',
		);
		$this->sbset($set);
		$this->display();
	}
	
    public function configAction(){
    	$get = I('get.');
    	$this->assign('get', $get);
    	
		if(IS_POST){
			$post=I('post.');
			try{
				$this->ConfigService()->setConfig($post,'point');
			}catch(\Exception $e){
				$this->error($e->getMessage());
			}
			$this->success('设置成功');
		}
		
    	$config=$this->ConfigService()->findSystemConfigs('point','fkey,fval,ftitle,fdesc,field_type');
		$this->assign('config',$config);
		
		$this->display();
    }
	
	//添加积分
	public function plusAction(){
		if(IS_POST){
			$post=I('post.');
			$user_id=I('user_id');
			$user_arr=explode(',',$user_id);
			$post['admin_id']=$_SESSION['admin']['uid'];
			M()->startTrans();
			foreach($user_arr as $key=>$val){	
				try{
					$this->PointService()->pointPlus($post);
				}catch(\Exception $e){
					M()->rollback();
					$this->error($e->getMessage());
				}
			}
			M()->commit();
			$this->success('积分扣除成功');
		}
		$set=array(
			'ac'=>'point_index_index',
		);
		$this->sbset($set);
		$this->display('edit');
	}
	
	//扣除积分
	public function reductAction(){
		if(IS_POST){
			$post=I('post.');
			$user_id=I('user_id');
			$user_arr=explode(',',$user_id);
			$post['admin_id']=$_SESSION['admin']['uid'];
			M()->startTrans();
			foreach($user_arr as $key=>$val){	
				try{
					$this->PointService()->pointReduct($post);
				}catch(\Exception $e){
					M()->rollback();
					$this->error($e->getMessage());
				}
			}
			M()->commit();
			$this->success('积分赠送成功');
		}
		$set=array(
			'ac'=>'point_index_index',
		);
		$this->sbset($set);
		$this->display('edit');
	}
	
	public function select_userAction(){
		$keyword=I('keyword')?I('keyword'):I('get.keyword');
		if($keyword!=''){
			$map=array('nick_name'=>array('like',"%{$keyword}%"),'_logic'=>'or','mobile'=>array('like',"%{$keyword}%"));
		}
		$params=array('map'=>$map,'page'=>1,'pagesize'=>100000);
		$result=$this->UserSercice()->userPagerList($params);
		$list=$result['list'];
		//var_dump($list);die();
		$this->assign('user_list',$list);
		if(IS_AJAX && IS_POST){
			$html=$this->renderPartial('_user');
			$this->ajaxReturn(array('html'=>$html));
		}
		$this->display();
	}
	
	private function PointService(){
		return D('Point',"Service");
	}
	private function UserSercice(){
		return D('User',"Service");
	}
	
	
	
}