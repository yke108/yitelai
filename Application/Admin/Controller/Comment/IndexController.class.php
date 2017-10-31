<?php
namespace Admin\Controller\Comment;
use Admin\Controller\FController;
use Common\Basic\Pager;
use Common\Basic\Genre;
use Common\Basic\Status;

class IndexController extends FController {
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'comment',
			'ac'=>'comment_index_index',
		);
		$this->sbset($set);
    }
    
    public function indexAction() {
    	session('back_url', __SELF__);
    	$this->listDisplay();
    }
    
    public function nocheckAction() {
    	$map = array('status'=>0);
    	$this->listDisplay($map);
    }
    
    public function passAction() {
    	$map = array('status'=>1);
    	$this->listDisplay($map);
    }
    
    public function nopassAction() {
    	$map = array('status'=>2);
    	$this->listDisplay($map);
    }
    
    private function listDisplay($map = array()) {
    	//分销商列表
    	$distributor_list = $this->distributorService()->getAllList();
    	$this->assign('distributor_list', $distributor_list);
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'map'=>$map
    	);
    	if (!empty($get['goods_name'])) {
    		$params['goods_name'] = $get['goods_name'];
    	}
    	if (!empty($get['nick_name'])) {
    		$params['nick_name'] = $get['nick_name'];
    	}
    	if (!empty($get['distributor_id'])) {
    		$params['distributor_id'] = $get['distributor_id'];
    	}
    	$datas = $this->goodsCommentService()->getPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $this->pagesize);
    	$this->assign('pager', $pager->show());
    	 
    	$this->display('index');
    }
    
    public function setCommentStatusAction($comment_id = 0) {
    	try {
    		$this->goodsCommentService()->setCommentStatus($comment_id);
    		$this->success('设置成功', session('back_url'));
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    }
    
    public function delAction($comment_id = 0) {
    	try {
    		$this->goodsCommentService()->delete($comment_id);
    		$this->success('删除成功', session('back_url'));
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    }
	
	private function goodsCommentService(){
		return D('GoodsComment', 'Service');
	}
	
	private function distributorService(){
		return D('Distributor', 'Service');
	}
}