<?php
namespace News\Controller\User;
use News\Controller\WapController;
use Common\Model\User\UserAccountModel;

class NewsController extends WapController {
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
    }
	
    public function indexAction(){
		$this->display();
    }
	
	public function addAction(){
		if (IS_POST) {
			$post = I('post.');
			$post['title'] = $post['content'] ? $post['content'] : '话题';
			$post['cat_id'] = 0;
			$post['source_id'] = 0;
			$post['author_id'] = 0;
			$post['user_id'] = $this->user['user_id'];
			try {
				$result = $this->newsService()->infoCreateOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('发布成功', U('user/index'));
		}
		$this->assign('page_title', '发布话题');
		$this->display('edit');
	}
	
	public function editAction($id = 0){
		$this->assign('page_title', '编辑话题');
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
		$datas = $this->newsRewardService()->rewardPagerList($params);
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
	
	private function newsService(){
		return D('Information\News', 'Service');
	}
	
	private function newsRewardService(){
		return D('Information\NewsReward', 'Service');
	}
	
	private function userAccountService(){
		return D('Information\UserAccount', 'Service');
	}
}