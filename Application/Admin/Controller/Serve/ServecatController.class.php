<?php
namespace Admin\Controller\Serve;
use Admin\Controller\FController;
use Common\Basic\Pager;
use Admin\Basic\Purview;

class ServecatController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'serve',
			'ac'=>'serve_servecat_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction($id = 0){
    	$get = I('get.');
    	$params = array(
    		'page'=>$get['p'],
    		'pagesize'=>10,
    	);
    	$result = $this->servecatService()->catPagerList($params);
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
				$result = $this->servecatService()->catCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功',U('index'),0);
		}
		$this->display('edit');
	}
	
	public function editAction($id = 0){
		$info = $this->servecatService()->getCat($id);
		if(empty($info)) $this->error('内容不存在');
		if(IS_POST){
			$params = I('post.');
			$params['cat_id'] = $info['cat_id'];
			try {
				$result = $this->servecatService()->catCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功',U('index'),0);
		}
		$this->assign('info', $info);
		$this->display();
	}
	
	public function delAction($id = 0){
		$info = $this->servecatService()->getCat($id);
		if(empty($info)) $this->error('内容不存在');
		try {
			$result = $this->servecatService()->catDelete($info['cat_id']);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功',U('index'),0);
	}
	
	private function servecatService(){
		return D('Serve', 'Service');
	}
}