<?php
namespace Admin\Controller\Story;
use Admin\Controller\FController;
use Common\Basic\Pager;
use Admin\Basic\Purview;

class RewardController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'story',
			'ac'=>'story_reward_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction($id = 0){
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$this->assign('catlist', $this->storyinfoService()->catlist());
    	
    	$params = array(
	    		'page'=>$get['p'],
	    		'pagesize'=>10,
    	);
		if($get['keyword']){
			$params['keyword']=$get['keyword'];
		}
		if($get['cat_id']){
			$params['cat_id']=$get['cat_id'];
		}
		if($get['pay_status']){
			$params['pay_status']=$get['pay_status'];
		}
    	$result = $this->storyinfoService()->rewardPagerList($params);
		
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
}