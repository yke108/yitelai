<?php
namespace Admin\Controller\Story;
use Admin\Controller\FController;
use Common\Basic\Pager;
use Admin\Basic\Purview;

class StoryinfoController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'story',
			'ac'=>'story_storyinfo_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction($id = 0){
    	$get = I('get.');
    	$params = array(
    		'page'=>$get['p'],
    		'pagesize'=>10,
    		'keyword'=>$get['keyword'],
			//'status'=>$get['status'],
    		'cat_id'=>$get['cat_id'],
			'_needAdmin'=>1,
    	);
		if($get['status']!='all'){
			$params['status']=$get['status'];
		}
    	$result = $this->storyinfoService()->infoPagerList($params);
		
    	if ($result['count'] > 0){
    		$this->assign('list', $result['list']);
			$this->assign('admins', $result['admins']);
    		$pager = new Pager($result['count'], $result['pagesize']);
    		$this->assign('pager', $pager->show());
    	}
		$this->assign('get', $get);
		$this->assign('catlist',$this->storyinfoService()->catlist($params));
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$params = I('post.');
			$params['admin_id']=session('uid');
			try {
				$result = $this->storyinfoService()->infoCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功',U('index'),0);
		}
		$this->assign('catlist',$this->storyinfoService()->catlist($params));
		$this->display('edit');
	}
	
	public function editAction($id = 0){
		$this->purviewCheck('storyinfo_edit');
		$info = $this->storyinfoService()->getInfo($id);

		if(empty($info)) $this->error('内容不存在');
		if(IS_POST){
			$params = I('post.');
			$params['story_id'] = $info['story_id'];
			$params['add_time'] = $info['add_time'];
			$params['update_time'] = $info['update_time'];
			$params['view_num'] = $info['view_num'];
			$params['bad_num'] = $info['bad_num'];
			// var_dump($params);exit;
			try {
				$result = $this->storyinfoService()->infoCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功',U('index'),0);
		}
		$this->assign('info', $info);
		$this->assign('catlist',$this->storyinfoService()->catlist($params));
		$this->display();
	}
	
	public function modifyAction($id = 0){
		$this->assign('get',I('get.'));
		$this->purviewCheck('storyinfo_edit');
		$info = $this->storyinfoService()->getInfo($id);

		if(empty($info)) $this->error('内容不存在');
		if(IS_POST){
			$params = I('post.');
			$params['story_id'] = $info['story_id'];
			$params['add_time'] = $info['add_time'];
			$params['update_time'] = $info['update_time'];
			$params['view_num'] = $info['view_num'];
			$params['bad_num'] = $info['bad_num'];
			$params['status'] =1;
			// var_dump($params);exit;
			try {
				$result = $this->storyinfoService()->infoCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功',U('index'),0);
		}
		$this->assign('info', $info);
		$this->assign('catlist',$this->storyinfoService()->catlist($params));
		$this->display();
	}
	
	public function delAction($id = 0){
		$info = $this->storyinfoService()->getInfo($id);
		if(empty($info)) $this->error('内容不存在');
		try {
			$result = $this->storyinfoService()->infoDelete($info['story_id']);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功',U('index'),0);
	}
	
	public function change_orderAction(){
		$post=I('post.');
		$result=$this->storyinfoService()->infoUpdate($post);
		if($result==false){
			$this->ajaxReturn(array('error'=>1,'msg'=>'失败'));
		}else{
			$this->ajaxReturn(array('error'=>0,'msg'=>'成功'));
		}
	}
	
	public function change_statusAction(){
		$id=I('get.id')?I('get.id'):I('story_id');
		try{
			$this->storyinfoService()->changeStatus($id);
		}catch(\Exception $e){
			$this->error($e->getMessage());
		}
		$this->success('置顶成功');
	}
	
	public function set_show_indexAction($id){
		
		try{
			$this->storyinfoService()->storyIsIndex($id);
		}catch(\Exception $e){
			$this->error($e->getMessage());
		}
		$this->success('推荐首页成功');
	}
	
	public function modify_statusAction(){
		$story_id=I('story_id')?I('story_id'):I('get.story_id');
		if(!empty($story_id)){
			$story_id=explode(',',$story_id);
		}else{
			die();
		}
		try{
			$this->storyinfoService()->modifyStatus($story_id);
		}catch(\Exception $e){
			$this->error($e->getMessage());
		}
		$this->success('审核成功');
	}
	
	private function storyinfoService(){
		return D('Story', 'Service');
	}
}