<?php
namespace Main\Controller\User;
use Main\Controller\MainController;
use Common\Basic\Pager;
use Common\Basic\Status;
use Common\Basic\Genre;

class HistoryController extends MainController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
		
		$this->assign('page_title', '个人中心');
    }
	
    public function browseAction(){
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>12,
    			'user_id'=>session('userid'),
    			'collect_type'=>Genre::CollectTypeGoodsFoot,
    	);
    	$datas = $this->collectService()->getCollectGoodsList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $params['pagesize']);
    	$this->assign('pages', $pager->show_pc());
    	
    	$this->display();
    }

	public function storyAction()
	{
		$params = array(
			'page' => intval(I('p')) > 0 ? intval(I('p')) : 1,
			'pagesize' => 12,
			'user_id' => session('userid'),
			'collect_type' => Genre::CollectTypeStoryFoot,
		);
		$datas = $this->collectService()->collectStoryList($params);
		$this->assign('list', $datas['list']);
		$pager = new Pager($datas['count'], $params['pagesize']);
		$this->assign('pages', $pager->show_pc());
		$this->assign('user_title', "文章浏览记录");
		$this->display();
	}
    
    public function inquiryAction(){
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>12,
    			'user_id'=>session('userid'),
    	);
    	$datas = $this->inquiryService()->getPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $params['pagesize']);
    	$this->assign('pages', $pager->show_pc());
    	$this->display();
    }

	//建议投诉
	public function feedbackAction()
	{
		$search_info = I('');
		$params = array(
			'page' => intval(I('p')) > 0 ? intval(I('p')) : 1,
			'pagesize' => 12,
			'user_id' => session('userid'),
			'type' => $search_info['type'],
			'content' => $search_info['content'],
		);
		$datas = $this->feedbackService()->feedbackPagerList($params);
		$this->assign('list', $datas['list']);
		$pager = new Pager($datas['count'], $params['pagesize']);
		$this->assign('pages', $pager->show_pc());
		$this->assign('user_title', "建议投诉");
		$this->assign('search_info', $search_info);
		$this->display();
	}

	//建议投诉-详情
	public function feedbackdetailAction()
	{
		$log_id = I('get.log_id');
		if(empty($log_id)) $this->error("发生致命错误");
		$feedbackData = $this->feedbackService()->feedbackFind($log_id);
		$this->assign('feedbackFind', $feedbackData['feedbackFind']);
		$this->assign('reply_list', $feedbackData['reply_list']);
		$this->assign('user_title', "建议投诉");
		$this->display();
	}

	public function menureleaseAction()
	{
		$params = array(
			'page' => intval(I('p')) > 0 ? intval(I('p')) : 1,
			'pagesize' => 12,
			'user_id' => session('userid'),
		);
		$datas = $this->cookBokService()->infoFieldPagerList($params);
		$this->assign('list', $datas['list']);
		$pager = new Pager($datas['count'], $params['pagesize']);
		$this->assign('pages', $pager->show_pc());
		$this->assign('user_title', "菜谱发布记录");
		$this->display();
	}
	
	//下载记录
	public function downloadAction(){
		$params = array(
				'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
				'pagesize'=>12,
				'user_id'=>session('userid'),
		);
		$datas = $this->materialService()->getDownList($params);
		$this->assign('list', $datas['list']);
		$pager = new Pager($datas['count'], $params['pagesize']);
		$this->assign('pages', $pager->show_pc());
		$this->display();
	}

	public function activityrecordAction()
	{
		$params = array(
			'page' => intval(I('p')) > 0 ? intval(I('p')) : 1,
			'pagesize' => 12,
			'user_id' => session('userid'),
		);
		$datas = $this->activityApplyService()->activityApplyPagerList($params);
		$this->assign('list', $datas['list']);
		$pager = new Pager($datas['count'], $params['pagesize']);
		$this->assign('pages', $pager->show_pc());
		$this->assign('user_title', "活动报名记录");
		$this->display();
	}
	
	//投票记录
	public function voteAction(){
		$params = array(
				'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
				'pagesize'=>12,
				'user_id'=>session('userid'),
		);
		$datas = $this->beautyVoteService()->infoPagerList($params);
		$this->assign('list', $datas['list']);
		$pager = new Pager($datas['count'], $params['pagesize']);
		$this->assign('pages', $pager->show_pc());
		$this->display();
	}
	
	//申请记录
	public function bespeakAction(){
		$params = array(
				'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
				'pagesize'=>12,
				'user_id'=>session('userid'),
		);
		$datas = $this->designerService()->orderPagerList($params);
		$this->assign('list', $datas['list']);
		$pager = new Pager($datas['count'], $params['pagesize']);
		$this->assign('pages', $pager->show_pc());
		$this->display();
	}
	
	//预约记录
	public function design_messageAction(){
		$params = array(
				'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
				'pagesize'=>12,
				'user_id'=>session('userid'),
		);
		$datas = $this->designerMessageService()->infoPagerList($params);
		$this->assign('list', $datas['list']);
		$pager = new Pager($datas['count'], $params['pagesize']);
		$this->assign('pages', $pager->show_pc());
		$this->display();
	}

	private function cookBokService() {
		return D('CookBook', 'Service');
	}

	private function activityApplyService() {
		return D('ActivityApply', 'Service');
	}

	private function feedbackService() {
		return D('Feedback', 'Service');
	}
	
	private function materialService(){
		return D('Material', 'Service');
	}
	
	private function collectService() {
		return D('Collect', 'Service');
	}
	
	private function inquiryService() {
		return D('Inquiry', 'Service');
	}
	
	private function beautyVoteService() {
		return D('BeautyVote', 'Service');
	}
	
	private function designerService(){
		return D('Designer', 'Service');
	}
	
	private function designerMessageService(){
		return D('DesignerMessage', 'Service');
	}
}