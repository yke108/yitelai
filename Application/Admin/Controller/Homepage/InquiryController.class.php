<?php
namespace Admin\Controller\Homepage;
use Admin\Controller\FController;
use Common\Basic\Pager;

class InquiryController extends FController {
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'homepage',
			'ac'=>'homepage_inquiry_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	session('back_url', __SELF__);
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize
    	);
    	if ($get['keyword']) {
    		$params['keyword'] = $get['keyword'];
    	}
    	if ($get['start_time']) {
    		$params['start_time'] = $get['start_time'];
    	}
    	if ($get['end_time']) {
    		$params['end_time'] = $get['end_time'];
    	}
    	$datas = $this->inquiryService()->getPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $this->pagesize);
    	$this->assign('pager', $pager->show());
    	
		$this->display();
    }
    
    public function editAction($inquiry_id = 0){
    	$info = $this->inquiryService()->getInfo($inquiry_id);
    	if(empty($info)){
    		$this->error('数据不存在');
    	}
    	
    	if (IS_POST) {
    		$post = I('post.');
    		try {
    			$this->inquiryService()->createOrModify($post);
    			$this->success('提交成功', session('back_url'));
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    	}
    	
    	$this->assign('info', $info);
    	$this->display();
    }
    
    private function inquiryService(){
    	return D('Inquiry', 'Service');
    }
}