<?php
namespace Main\Controller\User;
use Main\Controller\MainController;
use Common\Basic\Pager;
use Common\Basic\Status;
use Common\Payment\WeixinPay\AppPay;
use Common\Logic\PointLogic;

class CommentController extends MainController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
    }
	
    public function indexAction(){
    	$pagesize = 5;
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$pagesize,
    			'user_id'=>$this->user['user_id']
    	);
    	$datas = $this->goodsCommentService()->getPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $pagesize);
    	$this->assign('pages', $pager->show_pc());
    	
    	$this->assign('page_title', '交易评论');
    	$this->display();
    }
    
    public function activityAction(){
    	$pagesize = 5;
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$pagesize,
    			'user_id'=>$this->user['user_id']
    	);
    	$datas = $this->activityCommentService()->infoPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $pagesize);
    	$this->assign('pages', $pager->show_pc());
    	
    	$this->assign('page_title', '活动评论');
    	$this->display();
    }
    
    public function cookAction(){
    	$pagesize = 5;
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$pagesize,
    			'user_id'=>$this->user['user_id']
    	);
    	$datas = $this->cookCommentService()->infoPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $pagesize);
    	$this->assign('pages', $pager->show_pc());
    	
    	$this->assign('page_title', '菜谱评论');
    	$this->display();
    }
	
	private function goodsCommentService(){
		return D('GoodsComment', 'Service');
	}
	
	private function activityCommentService(){
		return D('ActivityComment', 'Service');
	}
	
	private function cookCommentService(){
		return D('CookComment', 'Service');
	}
}