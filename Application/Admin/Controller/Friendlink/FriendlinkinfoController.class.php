<?php
namespace Admin\Controller\Friendlink;
use Admin\Controller\FController;
use Common\Basic\Pager;
use Admin\Basic\Purview;

class FriendlinkinfoController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'friendlink',
			'ac'=>'friendlink_friendlinkinfo_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction($id = 0){
    	$get = I('get.');
    	$params = array(
    		'page'=>$get['p'],
    		'pagesize'=>10,
    		'cat_id'=>$get['cat_id'],
    	);
    	$result = $this->friendlinkinfoService()->infoPagerList($params);
    	if ($result['count'] > 0){
    		$this->assign('list', $result['list']);
    		$pager = new Pager($result['count'], $result['pagesize']);
    		$this->assign('pager', $pager->show());
    	}
		$this->assign('get', $get);
		$this->assign('catlist',$this->friendlinkinfoService()->catlist($params));
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$params = I('post.');
			try {
				$result = $this->friendlinkinfoService()->infoCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功',U('index'),0);
		}
		$this->assign('catlist',$this->friendlinkinfoService()->catlist($params));
		$this->display('edit');
	}
	
	public function editAction($id = 0){
		$info = $this->friendlinkinfoService()->getInfo($id);
		if(empty($info)) $this->error('内容不存在');
		if(IS_POST){
			$params = I('post.');
			$params['friendlink_id'] = $info['friendlink_id'];
			try {
				$result = $this->friendlinkinfoService()->infoCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功',U('index'),0);
		}
		$this->assign('info', $info);
		$this->assign('catlist',$this->friendlinkinfoService()->catlist($params));
		$this->display();
	}
	
	public function delAction($id = 0){
		$info = $this->friendlinkinfoService()->getInfo($id);
		if(empty($info)) $this->error('内容不存在');
		try {
			$result = $this->friendlinkinfoService()->infoDelete($info['friendlink_id']);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功',U('index'),0);
	}
	
	private function friendlinkinfoService(){
		return D('FriendLink', 'Service');
	}
}