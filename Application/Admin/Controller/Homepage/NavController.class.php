<?php
namespace Admin\Controller\Homepage;
use Admin\Controller\FController;
use Common\Basic\Pager;

class NavController extends FController {
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'homepage',
			'ac'=>'homepage_nav_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$params = array(
    			'distributor_id'=>0,
    			'type'=>0,
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize
    	);
    	$datas = $this->navService()->getPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $this->pagesize);
    	$this->assign('pager', $pager->show());
    	
		$this->display();
    }
    
    public function addAction(){
    	if(IS_POST){
    		$post = I('post.');
    		try {
    			$this->navService()->createOrModify($post);
    			$this->success('添加成功', U('index'));
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    	}
    	
    	$this->display('edit');
    }
    
    public function editAction($nav_id = 0){
    	$info = $this->navService()->getInfo($nav_id);
    	if(empty($info)){
    		$this->error('数据不存在');
    	}
    	$this->assign('info', $info);
    
    	if(IS_POST){
    		$post = I('post.');
    		try {
    			$this->navService()->createOrModify($post);
    			$this->success('编辑成功', U('index'));
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    	}
    	
    	$this->display();
    }
    
    public function delAction($nav_id = 0){
    	try {
    		$this->navService()->delete($nav_id);
    		$this->success('删除成功', U('index'));
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    }
    
    private function navService(){
    	return D('Nav', 'Service');
    }
}