<?php
namespace Home\Controller\Resource;
use Home\Controller\BaseController;

class DesignController extends BaseController {	
	public function _initialize(){
		parent::_initialize();
    }
    
    protected function _purviewCheck(){
    	$this->purviewCheck('index');
    }
	
    public function indexAction() { /*门店装修 */
    	$result = $this->distributorDesignService()->infoAllList();
    	$list = array();
    	foreach ($result as $k => $v) {
    		$year = date('Y', $v['add_time']);
    		$month = date('m', $v['add_time']);
    		$list[$year][$month][] = $v;
    	}
    	$this->assign('list', $list);
    	
    	$this->assign('page_title', '门店装修');
		$this->display();
    }
    
    public function addAction() { /*NoPurview*/
    	if (IS_POST) {
    		$post = I('post.');
    		try {
    			$this->distributorDesignService()->infoCreateOrModify($post);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		$this->success('添加成功', U('index'));
    	}
    	
    	//业务员
    	//$map = array('distributor_id'=>session('admin_id'));
    	$user_list = $this->userService()->userAllList($map);
    	$this->assign('user_list', $user_list);
    	
    	//设计师
    	//$map = array('distributor_id'=>session('admin_id'));
    	$designer_list = $this->designerService()->infoList($map);
    	$this->assign('designer_list', $designer_list);
    	
    	$this->assign('page_title', '门店装修');
    	$this->display();
    }
    
    public function infoAction($design_id = 0) { /*NoPurview*/
    	$info = $this->distributorDesignService()->getInfo($design_id);
    	if (empty($info)) $this->error('活动不存在');
    	$this->assign('info', $info);
    	
    	$this->assign('page_title', '门店装修-详情');
    	$this->display();
    }
	
	private function distributorDesignService(){
		return D('Distributor\Design', 'Service');
	}
	
	private function designerService(){
		return D('Designer', 'Service');
	}
}