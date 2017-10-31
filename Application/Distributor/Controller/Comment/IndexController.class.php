<?php
namespace Distributor\Controller\Comment;
use Distributor\Controller\FController;
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
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'distributor_id'=>$this->org_id,
    			'map'=>$map
    	);
    	$datas = $this->goodsCommentService()->getPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $this->pagesize);
    	$this->assign('pager', $pager->show());
    	 
    	$this->display('index');
    }
    
	private function goodsCommentService(){
		return D('GoodsComment', 'Service');
	}
}