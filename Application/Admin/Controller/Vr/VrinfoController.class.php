<?php
namespace Admin\Controller\Vr;
use Admin\Controller\FController;
use Common\Basic\Pager;
use Admin\Basic\Purview;

class VrinfoController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'goods',
			'ac'=>'vr_vrinfo_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction($id = 0){
    	$get = I('get.');
    	$params = array(
    		'page'=>$get['p'],
    		'pagesize'=>10,
    	);
    	$result = $this->vrService()->infoPagerList($params);
    	if ($result['count'] > 0){
    		$this->assign('list', $result['list']);
    		$pager = new Pager($result['count'], $result['pagesize']);
    		$this->assign('pager', $pager->show());
    	}
		$this->assign('get', $get);

		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$params = I('post.');
			try {
				$result = $this->vrService()->infoCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功',U('index'),0);
		}
		// $this->assign('catlist',$this->vrService()->catlist($params));
		$this->display('edit');
	}
	
	public function editAction($id = 0){
		$info = $this->vrService()->getInfo($id);
		if(empty($info)) $this->error('内容不存在');
		if(IS_POST){
			$params = I('post.');
			$params['vr_id'] = $info['vr_id'];
			try {
				$result = $this->vrService()->infoCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功',U('index'),0);
		}
		$this->assign('info', $info);
		// $this->assign('catlist',$this->vrService()->catlist($params));
		$this->display();
	}
	
	public function delAction($id = 0){
		$info = $this->vrService()->getInfo($id);
		if(empty($info)) $this->error('内容不存在');
		try {
			$result = $this->vrService()->infoDelete($info['vr_id']);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功',U('index'),0);
	}
	
	private function vrService(){
		return D('Vr', 'Service');
	}
}