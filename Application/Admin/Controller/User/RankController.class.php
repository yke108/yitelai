<?php
namespace Admin\Controller\User;
use Admin\Controller\FController;
use Common\Basic\Pager;

class RankController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'user',
			'ac'=>'user_rank_index',
		);
		$this->sbset($set);
	}
	
     public function indexAction($id = 0){
    	session('back_url', __SELF__);
    	
		$get = I('get.');
		$this->assign('get', $get);
		
		$size=12;
    	$params = array(
    		'page'=>$get['p'],
			'pagesize'=>$size,
    	);
    	if (!empty($get['nick_name'])) {
    		$params['nick_name'] = $get['nick_name'];
    	}
    	if (!empty($get['start_time'])) {
    		$params['start_time'] = $get['start_time'];
    	}
    	if (!empty($get['end_time'])) {
    		$params['end_time'] = $get['end_time'];
    	}
    	if (!empty($get['parent_id'])) {
    		$params['parent_id'] = $get['parent_id'];
    	}
		
    	$result = $this->userRankService()->getPagerList($params);
    	$list = array();
    	foreach ($result['list'] as $v) {
    		$map = array('rank_id'=>$v['rank_id']);
    		$v['user_count'] = $this->userService()->getCount($map);
    		$list[] = $v;
    	}
    	if ($result['count'] > 0){
			$this->assign('list', $list);
    		$pager = new Pager($result['count'], $size);
    		$this->assign('pager', $pager->show());
    	}
		
		$this->display();
    }
    
	public function addAction(){
		if(IS_POST){
			$params = I('post.');
			try {
				$result = $this->userRankService()->createOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功', U('index'));
		}
		
		$this->display('edit');
	}
	
	public function editAction($rank_id = 0){
		$info = $this->userRankService()->findInfo($rank_id);
		if(empty($info)) $this->error('内容不存在');
		
		if(IS_POST){
			$params = I('post.');
			try {
				$result = $this->userRankService()->createOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功', session('back_url'));
		}
		
	$this->assign('info', $info);
		
		$this->display();
	}
	
	public function delAction($rank_id = 0){
		$info = $this->userRankService()->findInfo($rank_id);
		if(empty($info)) $this->error('内容不存在');
		try {
			$result = $this->userRankService()->delete($rank_id);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功', session('back_url'));
	}
	
	private function userRankService($param) {
		return D('UserRank', 'Service');
	}
}