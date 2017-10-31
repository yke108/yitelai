<?php
namespace Admin\Controller\Serve;
use Admin\Controller\FController;
use Common\Basic\Pager;
use Admin\Basic\Purview;

class ServeinfoController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'serve',
			'ac'=>'serve_serveinfo_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction($id = 0){
    	$get = I('get.');
    	$params = array(
    		'page'=>$get['p'],
    		'pagesize'=>10,
    	);
    	if (!empty($get['serve_name'])) {
    		$params['serve_name'] = $get['serve_name'];
    	}
    	if (!empty($get['cat_id'])) {
    		$params['cat_id'] = $get['cat_id'];
    	}
    	$result = $this->serveinfoService()->infoPagerList($params);
    	if ($result['count'] > 0){
    		$this->assign('list', $result['list']);
    		$pager = new Pager($result['count'], $result['pagesize']);
    		$this->assign('pager', $pager->show());
    	}
		$this->assign('get', $get);
		$catlist = $this->serveinfoService()->catlist($params);
		$newcatlist = array();
		foreach ($catlist as $v) {
			$newcatlist[$v['cat_id']] = $v;
		}
		$this->assign('catlist',$newcatlist);
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$params = I('post.');
			/* if (mb_strlen($params['serve_content'], 'utf-8') > 300) {
				$this->error('简介不能超过150个字');
			} */
			try {
				$result = $this->serveinfoService()->infoCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功',U('index'),0);
		}
		$this->assign('catlist',$this->serveinfoService()->catlist($params));
		$this->display('edit');
	}
	
	public function editAction($id = 0){
		$info = $this->serveinfoService()->getInfo($id);
		if(empty($info)) $this->error('内容不存在');
		if(IS_POST){
			$params = I('post.');
			/* if (mb_strlen($params['serve_content'], 'utf-8') > 300) {
				$this->error('简介不能超过150个字');
			} */
			$params['serve_id'] = $info['serve_id'];
			try {
				$result = $this->serveinfoService()->infoCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功',U('index'),0);
		}
		$this->assign('info', $info);
		$this->assign('catlist',$this->serveinfoService()->catlist($params));
		$this->display();
	}
	
	public function delAction($id = 0){
		$info = $this->serveinfoService()->getInfo($id);
		if(empty($info)) $this->error('内容不存在');
		try {
			$result = $this->serveinfoService()->infoDelete($info['serve_id']);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功',U('index'),0);
	}
	
	private function serveinfoService(){
		return D('Serve', 'Service');
	}
}