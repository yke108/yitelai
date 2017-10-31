<?php
namespace Home\Controller\Point;
use Home\Controller\BaseController;
use Common\Basic\Genre;

class IndexController extends BaseController {	
	public function _initialize(){
		parent::_initialize();
    }
    
    protected function _purviewCheck() {
    	$this->purviewCheck('index');
    }
	
    public function indexAction() { /*积分管理 */
    	$page = intval(I('p')) > 0 ? intval(I('p')) : 1;
    	$params = array(
    			'page'=>$page,
    			'pagesize'=>$this->pagesize,
    	);
    	//店铺
    	if (session('distributor_id')) {
    		$params['distributor_id'] = session('distributor_id');
    	}
    	if (I('keyword')) {
    		$params['mobile'] = I('keyword');
    	}
    	$result = $this->userService()->userPagerList($params);
    	if ($result['count'] > 0){
    		$this->assign('list', $result['list']);
    		$this->assign('count', $result['count']);
    	}
    	
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
		if (session('distributor_id')) {
			$params['distributor_id'] = session('distributor_id');
		}
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
		
		//用户
		$map = array(
				'user_id'=>$user_id,
		);
		if (session('distributor_id')) {
			$map['distributor_id'] = session('distributor_id');
		}
		$user = $this->userService()->getUser($map);
		if (empty($user)) $this->error('用户不存在');
		$this->assign('user', $user);
		
		$this->assign('page_title', '积分列表');
		$this->display();
	}
	
	public function remarkAction($user_id = 0) { /*NoPurview*/
		//用户
		$user = $this->userService()->getUserInfo($user_id);
		if (empty($user)) $this->error('用户不存在');
		
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
}