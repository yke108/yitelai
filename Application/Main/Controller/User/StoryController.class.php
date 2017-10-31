<?php
namespace Main\Controller\User;
use Main\Controller\MainController;
use Common\Basic\Pager;
use Common\Basic\Status;
use Common\Model\User\UserAccountModel;
use Common\Logic\PointLogic;

class StoryController extends MainController {
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
    		$pager = new Pager($result['count'], $result['pagesize']);
    		$this->assign('pager', $pager->show());
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
			
			//发表文章送积分
			$point_config = $this->ConfigService()->findSystemConfigs('point','fkey,fval,ftitle,fdesc,field_type');
			$params = array(
					'user_id'=>$this->user['user_id'],
					'point'=>$point_config['publish']['fval'],
					'type'=>PointLogic::PointTypePublish,
					'ref_id'=>$result['story_id']
			);
			$result = $this->pointService()->addUserPoint($params);
			if($result === false){
				$this->error('赠送积分失败');
			}
			
			$this->success('添加成功', U('index'));
		}
		
		//获取粉丝分类列表
		$cat_list=$this->storyinfoService()->getFieldList(array(),'cat_id,parent_id,cat_name,picture,is_show,sort_order');
		$cat_list=node_merge($cat_list,0,1,'cat_id');
		
		$this->assign('story_cat_list',$cat_list);
		
		$this->assign('catlist',$this->storyinfoService()->catlist($params));
		$this->display('edit');
	}
	
	public function editAction(){
		if(IS_POST){
			$post = I('post.');
			$post['user_id'] = $this->user['user_id'];
			$post['status']=2;
			if(empty($post['picture'])){
				$images = createBase64Image($post['story_image']);
				$post['story_image'] = $images[0];
			}else{
				$post['story_image']=$post['picture'];
			}
			
			unset($post['picture']);
			
			
			try {
				$result = $this->storyinfoService()->infoCreateOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('编辑成功',U('index'),0);
		}
		
		$id=I('get.id');
		$info=$this->storyinfoService()->getInfo($id);
		$this->assign('info',$info);
		
		//获取粉丝分类列表
		$cat_list=$this->storyinfoService()->getFieldList(array(),'cat_id,parent_id,cat_name,picture,is_show,sort_order');
		$cat_list=node_merge($cat_list,0,1,'cat_id');
		
		$this->assign('story_cat_list',$cat_list);
		
		$this->assign('catlist',$this->storyinfoService()->catlist());
		$this->display('edit');
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
		$result = $this->storyinfoService()->rewardPagerList($params);
		if ($result['count'] > 0){
			$this->assign('list', $result['list']);
			$pager = new Pager($result['count'], $params['pagesize']);
			$this->assign('pager', $pager->show());
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
		$result = $this->userAccountService()->rewardPagerList($params);
		if ($result['count'] > 0){
			$this->assign('list', $result['list']);
			$pager = new Pager($result['count'], $params['pagesize']);
			$this->assign('pager', $pager->show());
		}
	
		$this->display();
	}
	
	private function storyinfoService(){
		return D('Story', 'Service');
	}
	
	private function userAccountService(){
		return D('UserAccount', 'Service');
	}
	
	private function pointService(){
		return D('Point', 'Service');
	}
}