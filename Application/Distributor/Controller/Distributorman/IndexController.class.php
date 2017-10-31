<?php
namespace Distributor\Controller\Distributorman;
use Distributor\Controller\FController;
use Common\Basic\Pager;

class IndexController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'distributorman',
			'ac'=>'distributorman_index_index',
		);
		$this->sbset($set);
		$sex=\Common\Basic\User::$sex;
		$this->assign('sex',$sex);
		$user_type=\Common\Basic\User::$user_type;
		$this->assign('user_type',$user_type);	
	}
	
    public function indexAction(){
    	session('back_url', __SELF__);
		$this->listDisplayAction();
    }
    
    public function nocheckAction(){
    	session('back_url', __SELF__);
    	$this->sbset('distributorman_index_nocheck');
    	$map = array(
    			'status'=>0
    	);
    	$this->listDisplayAction($map);
    }
    
    public function passAction(){
    	session('back_url', __SELF__);
    	$this->sbset('distributorman_index_pass');
    	$map = array(
    			'status'=>1
    	);
    	$this->listDisplayAction($map);
    }
	
    public function nopassAction(){
    	session('back_url', __SELF__);
    	$this->sbset('distributorman_index_nopass');
    	$map = array(
    			'status'=>array('in', array(2, 4))
    	);
    	$this->listDisplayAction($map);
    }
	
    public function platformpassAction(){
    	session('back_url', __SELF__);
    	$this->sbset('distributorman_index_platformpass');
    	$map = array(
    			'status'=>3
    	);
    	$this->listDisplayAction($map);
    }
    
    public function platformnopassAction(){
    	session('back_url', __SELF__);
    	$this->sbset('distributorman_index_platformnopass');
    	$map = array(
    			'status'=>2
    	);
    	$this->listDisplayAction($map);
    }
    
    private function listDisplayAction($map = array()){
    	$get = I('get.');
    	$this->assign('get', $get);
    
    	$size=12;
		$map['type']=2;
    	$map['distributor_id'] = $this->org_id;
    	$params = array(
    			'page'=>$get['p'],
    			'pagesize'=>$size,
    			'map'=>$map
    	);
    	if (!empty($get['nick_name'])) {
    		$params['nick_name'] = $get['nick_name'];
    	}
    	if (!empty($get['mobile'])) {
    		$params['mobile'] = $get['mobile'];
    	}
    	if (!empty($get['start_time'])) {
    		$params['start_time'] = $get['start_time'];
    	}
    	if (!empty($get['end_time'])) {
    		$params['end_time'] = $get['end_time'];
    	}
    
    	$result = $this->salemanService()->infoPagerList($params);
    	if ($result['count'] > 0){
    		//$list=node_merge($result['list'],0,1,'user_id');
    		$this->assign('list', $result['list']);
			$this->assign('users',$result['users']);
    		$pager = new Pager($result['count'], $size);
    		$this->assign('pager', $pager->show());
    	}
    
    	$this->display('index');
    }
	
	public function checkAction($apply_id = 0){
		$info=$this->salemanService()->getInfo($apply_id);
		$this->assign('apply_info',$info);
		$user_map = array(
				'user_id'=>$info['user_id'],
				'distributor_id'=>$this->org_id
		);
		$user = $this->UserService()->getUser($user_map);
		if(empty($user)) $this->error('用户不存在');
		$this->assign('info', $user);
		
		if(IS_POST){
			$post = I('post.');
			if ($user['status'] > 0) {
				$this->error('分销员已审核');
			}
			if (!in_array($post['status'], array(1,2))) {
				$this->error('请选择审核状态');
			}
			$data = array(
					'status'=>$post['status'],
					'feedback'=>$post['feedback'],
					'apply_id'=>$post['apply_id'],
			);
			try {
				$result = $this->SalemanService()->check($data);
				$this->success('审核成功', session('back_url'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		
		$this->display();
	}
	private function salemanService(){
		return D('Saleman','Service');
	}
}