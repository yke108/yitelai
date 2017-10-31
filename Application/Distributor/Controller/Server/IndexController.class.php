<?php
namespace Distributor\Controller\Server;
use Distributor\Controller\FController;
use Common\Basic\Pager;
use Common\Basic\Status;

class IndexController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'server',
			'ac'=>'server_index_index',
		);
		$this->sbset($set);
		$sex=\Common\Basic\User::$sex;
		$this->assign('sex',$sex);
		$user_type=\Common\Basic\User::$user_type;
		$this->assign('user_type',$user_type);	
	}
	
    public function indexAction(){
    	$map = array(
    			'user_type'=>Status::UserTypeServer,
    			'distributor_id'=>$this->org_id
    	);
		$this->listDisplayAction($map);
    }
    
    private function listDisplayAction($map = array()){
    	$get = I('get.');
    	$this->assign('get', $get);
    
    	$size=12;
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
    	$result = $this->userService()->userPagerList($params);
    	if ($result['count'] > 0){
    		//$list=node_merge($result['list'],0,1,'user_id');
    		$this->assign('list', $result['list']);
    		$pager = new Pager($result['count'], $size);
    		$this->assign('pager', $pager->show());
    	}
    
    	$this->display('index');
    }
	
	public function addAction(){
		if(IS_POST){
			$post = I('post.');
			$user = $this->userService()->getUserInfoWithMobile($post['mobile']);
			if (empty($user)) {
				$this->error('用户不存在');
			}
			if (!empty($user['distributor_id'])) {
				$this->error('用户已添加');
			}
			try {
				$data = array(
						'user_type'=>Status::UserTypeServer,
						'distributor_id'=>$this->org_id,
						'remark'=>$post['remark']
				);
				$result = $this->userService()->modify($user, $data);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功', U('index'));
		}
		
		$this->display();
	}
	
	public function editAction($user_id = 0){
		$map = array(
				'user_id'=>$user_id,
				'distributor_id'=>$this->org_id,
		);
		$user = $this->userService()->getUser($map);
		if (empty($user)) $this->error('用户不存在');
		
		if(IS_POST){
			$post = I('post.');
			$data = array(
					'salary_amount'=>$post['salary_amount'],
					'commission_amount'=>$post['commission_amount'],
					'ht_amount'=>$post['ht_amount'],
					'late_amount'=>$post['late_amount'],
			);
			try {
				$result = $this->userService()->modify($user, $data);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('编辑成功', U('index'));
		}
	
		$this->display();
	}
	
	public function delAction($user_id = 0){
		$map = array(
				'user_id'=>$user_id,
				'distributor_id'=>$this->org_id
		);
		$user = $this->userService()->getUser($map);
		if (empty($user)) {
			$this->error('业务员不存在');
		}
		try {
			$data = array(
					'user_type'=>1,
					'distributor_id'=>0,
			);
			$result = $this->userService()->modify($user, $data);
			$this->success('删除成功');
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	public function up_memberAction(){
		$get = I('get.');
		$this->assign('get', $get);
		$user_id=I('user_id')?I('user_id'):I('get.user_id');
		$size=12;
    	$params = array(
						'page'=>$get['p'],
						'pagesize'=>$size,
						'parent_id'=>$user_id,
						);		
		$result=$this->userService()->userPagerList($params);
		$pager=new Pager($result['count'],$size);
		$this->assign('list',$result['list']);
		$this->assign('pager',$pager->show());
		
		$this->display();
	}
}