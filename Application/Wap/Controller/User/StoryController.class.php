<?php
namespace Wap\Controller\User;
use Wap\Controller\WapController;
use Common\Model\User\UserAccountModel;

class StoryController extends WapController {
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
		
		$this->assign('page_title', '个人中心');
    }
	
    public function indexAction(){
    	$get = I('get.');
    	$params = array(
	    		'page'=>$get['p'],
	    		'pagesize'=>10,
	    		'keyword'=>$get['keyword'],
	    		'cat_id'=>$get['cat_id'],
    			'user_id'=>$this->user['user_id']
    	);
    	$result = $this->storyinfoService()->infoPagerList($params);
    	if ($result['count'] > 0){
    		$this->assign('list', $result['list']);
    	}
		$this->assign('get', $get);
		$this->assign('catlist',$this->storyinfoService()->catlist($params));
		
		
		
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$post = I('post.');
			$post['user_id'] = $this->user['user_id'];
			
			$images = createBase64Image($post['story_image']);
			$post['story_image'] = $images[0];
			try {
				$result = $this->storyinfoService()->infoCreateOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功',U('index'),0);
		}
		
		//获取粉丝分类列表
		$cat_list=$this->storyinfoService()->getFieldList(array(),'cat_id,parent_id,cat_name,picture,is_show,sort_order');
		$cat_list=node_merge($cat_list,0,1,'cat_id');
		
		$this->assign('story_cat_list',$cat_list);
		
		$this->assign('catlist',$this->storyinfoService()->catlist($params));
		$this->display('edit');
	}
	
	public function editAction(){
		$story_id=I('id')?I('id'):I('get.id');
		$info=$this->storyinfoService()->getInfo($story_id);
		$this->assign('info',$info);
		if(IS_POST){
			$post = I('post.');
			$post['user_id'] = $this->user['user_id'];
			$post['status']=2;
			$story_picture=$post['story_picture'];
			unset($post['story_picture']);
			
			if(empty($story_picture)){
				$images = createBase64Image($post['story_image']);
				if(!empty($images[0])){
					$post['story_image'] = $images[0];
				}
			}else{
				$post['story_image']=$story_picture;
			}
			
			try {
				$result = $this->storyinfoService()->infoCreateOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('编辑成功',U('index'),0);
		}
		
		//获取粉丝分类列表
		$cat_list=$this->storyinfoService()->getFieldList(array(),'cat_id,parent_id,cat_name,picture,is_show,sort_order');
		$cat_list=node_merge($cat_list,0,1,'cat_id');
		
		$this->assign('story_cat_list',$cat_list);
		
		$this->assign('catlist',$this->storyinfoService()->catlist($params));
		$this->display('edit');
	}
	
	public function uploadAction() {
		$post = I('post.');
		$images = createBase64Image(array('image_datas'=>$post['filed']));
		if ($images) {
			$result = array(
					'code'=>100,
					'summary'=>'success',
					'data'=>array('url'=>picurl($images['0']))
			);
		}else {
			$result = array(
					'code'=>101,
					'summary'=>'fail',
					'data'=>'上传失败'
			);
		}
		echo json_encode($result);exit;
	}
	
	public function rewardAction(){
		$get = I('get.');
		$this->assign('get', $get);
	
		$params = array(
				'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
				'pagesize'=>10,
				'user_id'=>$this->user['user_id'],
				'pay_status'=>1
		);
		$datas = $this->storyinfoService()->rewardPagerList($params);
		if ($datas['count'] > 0){
			$this->assign('list', $datas['list']);
		}
		
		if(IS_AJAX){
			if(empty($datas['list'])){
				$clist = '';
			}else{
				$clist = $this->renderPartial('_reward');
			}
			die($clist);
		}
	
		$this->display();
	}
	
	public function getrewardAction(){
		$get = I('get.');
		$this->assign('get', $get);
		
		$params = array(
				'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
				'pagesize'=>10,
				'user_id'=>$this->user['user_id'],
				'change_type'=>UserAccountModel::ChangeTypeGetReward
		);
		$datas = $this->userAccountService()->rewardPagerList($params);
		if ($datas['count'] > 0){
			$this->assign('list', $datas['list']);
		}
		
		if(IS_AJAX){
			if(empty($datas['list'])){
				$clist = '';
			}else{
				$clist = $this->renderPartial('_getreward');
			}
			die($clist);
		}
		
		$this->display();
	}
	
	private function storyinfoService(){
		return D('Story', 'Service');
	}
	
	private function userAccountService(){
		return D('UserAccount', 'Service');
	}
}