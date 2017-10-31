<?php
namespace Distributor\Controller\User;
use Distributor\Controller\FController;
use Common\Basic\Pager;

class IndexController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'user',
			'ac'=>'user_index_index',
		);
		$this->sbset($set);
		$sex=\Common\Basic\User::$sex;
		$this->assign('sex',$sex);
		$user_type=\Common\Basic\User::$user_type;
		$this->assign('user_type',$user_type);	
	}
	
    public function indexAction(){
		$this->listDisplayAction();
    }
    
    public function salesmanAction(){
    	$map = array(
    			'user_type'=>3,
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
    
    	$result = $this->UserService()->userPagerList($params);
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
			$user = $this->UserService()->getUserInfoWithMobile($post['mobile']);
			if (empty($user)) {
				$this->error('用户不存在');
			}
			try {
				$data = array(
						'user_type'=>3,
						'distributor_id'=>$this->org_id,
						'remark'=>$post['remark']
				);
				$result = $this->UserService()->modify($user, $data);
				$this->success('添加成功', U('salesman'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		
		$this->display('edit');
	}
	
	private function UserService(){
		return D('User','Service');
	}
}