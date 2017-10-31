<?php
namespace Admin\Controller\Page;
use Admin\Controller\FController;
use Common\Basic\Pager;
use Admin\Basic\Purview;
use Common\Basic\Status;

class PageinfoController extends FController {
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
				'in'=>'content',
				'ac'=>'page_pageinfo_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	session('back_url', __SELF__);
    	
    	$map['page_type'] = array('in', array(Status::PageTypeNormal, Status::PageTypeSystem));
    	$this->listDisplay($map);
    }
    
    private function listDisplay($map = array()){
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'keyword'=>$get['keyword'],
    			'map'=>$map,
    	);
    	$result = $this->pageService()->infoPagerList($params);
    	if ($result['count'] > 0){
    		$this->assign('list', $result['list']);
    		$pager = new Pager($result['count'], $params['pagesize']);
    		$this->assign('pager', $pager->show());
    	}
    	
    	$this->display('index');
    }
	
	public function addAction(){
		if(IS_POST){
			$params = $_POST;
			try {
				$result = $this->pageService()->infoCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功', U('index'));
		}
		// $this->assign('catlist',$this->pageService()->catlist($params));
		$this->display('edit');
	}
	
	public function editAction($id = 0){
		$info = $this->pageService()->getInfo($id);
		if(empty($info)) $this->error('内容不存在');
		if(IS_POST){
			$params = $_POST;
			$params['page_id'] = $info['page_id'];
			try {
				$result = $this->pageService()->infoCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功', session('back_url'));
		}
		$this->assign('info', $info);
		// $this->assign('catlist',$this->pageService()->infolist($params));
		$this->display();
	}
	
	public function delAction($id = 0){
		try {
			$result = $this->pageService()->infoDelete($id);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功', session('back_url'));
	}
	
	private function pageService(){
		return D('Page', 'Service');
	}
}