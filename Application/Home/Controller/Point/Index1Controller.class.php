<?php
namespace Home\Controller\Point;
use Home\Controller\BaseController;
use Common\Basic\Genre;
use Common\Logic\PointLogic;

class IndexController extends BaseController {	
	public function _initialize(){
		parent::_initialize();
    }
    
    protected function _purviewCheck() {
    	$this->purviewCheck('index');
    }
	
    public function indexAction() { /*积分管理 */
    	//积分列表
    	$page = intval(I('p')) > 0 ? intval(I('p')) : 1;
    	$params = array(
    			'page'=>$page,
    			'pagesize'=>$this->pagesize,
    	);
    	if (I('keyword')) {
    		$params['keyword'] = I('keyword');
    	}
    	if (I('start_time')) {
    		$params['start_time'] = I('start_time');
    	}
    	if (I('end_time')) {
    		$params['end_time'] = I('end_time');
    	}
    	if (I('start_point')) {
    		$params['start_point'] = I('start_point');
    	}
    	if (I('end_point')) {
    		$params['end_point'] = I('end_point');
    	}
    	if (I('change_type')) {
    		$params['change_type'] = I('change_type');
    	}
    	if (I('user_id')) {
    		$params['user_id'] = I('user_id');
    	}
    	$result = $this->pointService()->pointPagerList($params);
    	$this->assign('list', $result['list']);
    	$this->assign('count', $result['count']);
    	
    	if (IS_AJAX) {
    		if(empty($result['list'])){
    			$clist = '';
    		}else{
    			$clist = $this->renderPartial('_index');
    		}
    		$this->ajaxReturn(array('html'=>$clist, 'p'=>$page+1));
    	}
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	//店铺
    	if (session('sys_id') == 1) {
    		$map = array('status'=>Status::DistributorStatusNormal);
    		$distributor_list = $this->distributorService()->getAllList($map);
    		$this->assign('distributor_list', $distributor_list);
    	}
    	
    	//客户标签
    	$user_list = $this->pointService()->selectDistinctUserid();
    	$user_ids = array();
    	if ($user_list) {
    		foreach ($user_list as $v) {
    			$user_ids[] = $v['user_id'];
    		}
    	}
    	$map = array();
    	if ($user_ids) {
    		$map['user_id'] = array('in', $user_ids);
    	}
    	$user_list = $this->userService()->userAllList($map);
    	$this->assign('user_list', $user_list);
    	
    	$this->assign('page_title', '积分管理');
    	$this->display();
    }
	
	public function listAction($user_id = 0) { /*NoPurview*/
		//积分列表
		$page = intval(I('p')) > 0 ? intval(I('p')) : 1;
		$params = array(
				'page'=>$page,
				'pagesize'=>$this->pagesize,
				'user_id'=>$user_id,
		);
		/* if (I('keyword')) {
			$params['keyword'] = I('keyword');
		} */
		$result = $this->pointService()->pointPagerList($params);
		$this->assign('list', $result['list']);
		$this->assign('count', $result['count']);
		
		if (IS_AJAX) {
			if(empty($result['list'])){
				$clist = '';
			}else{
				$clist = $this->renderPartial('_list');
			}
			$this->ajaxReturn(array('html'=>$clist, 'p'=>$page+1));
		}
		
		$get = I('get.');
		$this->assign('get', $get);
		
		//用户
		$user = $this->userService()->getUserInfo($user_id);
		$this->assign('user', $user);
		
		$this->assign('page_title', '积分列表');
		$this->display();
	}
	
	public function remarkAction($user_id = 0) { /*NoPurview*/
		//用户
		$user = $this->userService()->getUserInfo($user_id);
		if (empty($user)) {
			$this->error('用户不存在');
		}
		
		if (IS_POST) {
			$post = I('post.');
			$data = array('remark'=>$post['remark']);
			try {
				$this->userService()->modify($user, $data);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('备注成功', U('list', array('user_id'=>$user['user_id'])));
		}
		
		$this->assign('user', $user);
		
		$this->assign('page_title', '积分备注');
		$this->display();
	}
	
	private function pointService(){
		return D('Point', 'Service');
	}
	
	private function distributorService(){
		return D('Distributor', 'Service');
	}
}