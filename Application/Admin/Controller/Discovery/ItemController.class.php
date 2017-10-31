<?php
namespace Admin\Controller\Discovery;
use Admin\Controller\FController;
use Common\Basic\Pager;

class ItemController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'promotion',
			'ac'=>'discovery_item_index',
		);
		$this->sbset($set);
    }
	
	public function indexAction($id = 0){
		$this->purviewCheck('discoveryitem_index');
		$get = I('get.');
		$params = array();
		$result = $this->discoveryService()->itemList($params);
		$this->assign('list', $result['list']);
		$this->assign('get', $get);
		$this->display();
    }
	
	public function editAction($id = 0){
		$this->purviewCheck('discoveryitem_edit');
		if(IS_POST){
			$post = I('post.');
			try {
				$params = array(
					'item_id'=>$id,
					'item_title'=>$post['title'],
					'item_tip'=>$post['brief'],
					'item_icon'=>$post['icon'],
					'is_open'=>$post['is_open'],
					'client_type'=>$post['client_type'],
					'version_min'=>$post['version_min'],
					'version_max'=>$post['version_max'],
					'remark'=>$post['remark'],
				);
				$this->discoveryService()->itemCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('操作成功');
		}
		$info = $this->discoveryService()->getItem($id);
		$this->assign('info', $info);
		$this->display();
	}
	
    private function discoveryService(){
    	return D('Discovery', 'Service');
    }
}