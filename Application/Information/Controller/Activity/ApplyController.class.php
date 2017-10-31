<?php
namespace Information\Controller\Activity;
use Information\Controller\FController;
use Common\Basic\Pager;

class ApplyController extends FController {
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'activity',
			'ac'=>'activity_apply_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	session('back_url', __SELF__);
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    	);
    	if (!empty($get['keyword'])) {
    		$params['keyword'] = $get['keyword'];
    	}
    	$datas = $this->activityApplyService()->infoPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $this->pagesize);
    	$this->assign('pager', $pager->show());
    	
    	$this->display();
    }
    
    public function addAction(){
    	if(IS_POST){
    		$post = I('post.');
    		try {
    			$result = $this->activityApplyService()->infoCreateOrModify($post);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		$this->success('添加成功', U('index'));
    	}
    	
    	$this->display('edit');
    }
    
    public function editAction($apply_id = 0){
    	$info = $this->activityApplyService()->getInfo($apply_id);
    	if(empty($info)) $this->error('内容不存在');
    	
    	if(IS_POST){
    		$post = I('post.');
    		try {
    			$result = $this->activityApplyService()->infoCreateOrModify($post);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		$this->success('修改成功', session('back_url'));
    	}
    	
    	$this->assign('info', $info);
    	
    	$this->display();
    }
    
    public function delAction($apply_id = 0){
    	try {
    		$result = $this->activityApplyService()->infoDelete($apply_id);
    		$this->success('删除成功', session('back_url'));
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    }
    
    public function recommendAction($apply_id = 0){
    	try {
    		$result = $this->activityApplyService()->infoRecommend($apply_id);
    		$this->success('操作成功', session('back_url'));
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    }
    
    public function openAction($apply_id = 0){
    	try {
    		$result = $this->activityApplyService()->infoOpen($apply_id);
    		$this->success('操作成功', session('back_url'));
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    }
	
	private function activityApplyService(){
		return D('Information\ActivityApply', 'Service');
	}
}