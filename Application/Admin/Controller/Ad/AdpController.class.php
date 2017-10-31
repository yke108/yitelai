<?php
namespace Admin\Controller\Ad;
use Admin\Controller\FController;
use Common\Basic\Pager;

class AdpController extends FController {
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'ad',
			'ac'=>'ad_adp_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	$params = array(
    			'position_type'=>0,
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>100,
    	);
		$datas = $this->adService()->positionPagerList($params);
		$this->assign('list', $datas['list']);
		$pager = new Pager($datas['count'], 100);
		$this->assign('pager', $pager->show());
		$this->display();
    }
    
    public function addAction() {
    	if (IS_POST) {
    		$post = I('post.');
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
    	$info = $this->adService()->getPosition($position_id);
    	if (empty($info)) {
    		$this->error('数据不存在');
    	}
    	$this->assign('info', $info);
    	
    	if (IS_POST) {
    		$post = I('post.');
    		try {
    			$this->adService()->positionCreateOrModify($post);
    			$this->success('编辑成功', U('index'));
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    	}
    	$this->display();
    }
	
	//编辑或删除的记录条件
	protected function chkMap($id = 0){
		$map = array(
			'advt_id'=>$id,
		);
		return $map;
	}
	
	//保存的数据格式要求
	protected function dataRules(){
		return array();
	}
	
	//需要保存的数据预处理
	protected function dataPost($id = 0){
		$post = I('post.');
		$data = array(
			'advt_name'=>trim($post['advt_name']),
			'advt_key'=>trim($post['advt_key']),
		);
		if($id > 0){
			$data['advt_id'] = $id;
		}
		return $data;
	}
	
	private function adService() {
		return D('Ad', 'Service');
	}
}