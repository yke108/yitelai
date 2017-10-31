<?php
namespace Information\Controller\Gd;
use Information\Controller\FController;
use Common\Basic\Pager;

class AdpController extends FController {
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'gd',
			'ac'=>'gd_adp_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	$params = array(
    			'position_type'=>1,
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    	);
		$datas = $this->adService()->positionPagerList($params);
		$this->assign('list', $datas['list']);
		$pager = new Pager($datas['count'], $this->pagesize);
		$this->assign('pager', $pager->show());
		$this->display();
    }
    
    public function addAction() {
    	if (IS_POST) {
    		$post = I('post.');
    		$post['position_type'] = 1;
    		try {
    			$this->adService()->positionCreateOrModify($post);
    			$this->success('添加成功', U('index'));
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    	}
    	$this->display('edit');
    }
    
    public function editAction($position_id = 0) {
    	$map = array(
    			'position_id'=>$position_id,
    			'position_type'=>1
    	);
    	$info = $this->adService()->findPosition($map);
    	if (empty($info)) {
    		$this->error('数据不存在');
    	}
    	$this->assign('info', $info);
    	
    	if (IS_POST) {
    		$post = I('post.');
    		$post['position_type'] = 1;
    		try {
    			$this->adService()->positionCreateOrModify($post);
    			$this->success('编辑成功', U('index'));
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    	}
    	$this->display();
    }
	
	private function adService() {
		return D('Information\Ad', 'Service');
	}
}