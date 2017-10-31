<?php
namespace Admin\Controller\Story;
use Admin\Controller\FController;
use Common\Basic\Pager;
use Admin\Basic\Purview;

class InquiryController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'story',
			'ac'=>'story_inquiry_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction($id = 0){
    	$get = I('get.');
    	$params = array(
    		'page'=>$get['p'],
    		'pagesize'=>10,
    		'keyword'=>$get['keyword'],
    		'cat_id'=>$get['cat_id'],
    	);
    	$result = $this->storyinfoService()->infoPagerList($params);
    	if ($result['count'] > 0){
    		$this->assign('list', $result['list']);
    		$pager = new Pager($result['count'], $result['pagesize']);
    		$this->assign('pager', $pager->show());
    	}
		$this->assign('get', $get);
		$this->assign('catlist',$this->storyinfoService()->catlist($params));
		$this->display();
    }
	
	
	
	public function delAction($id = 0){
		$info = $this->storyinfoService()->getInfo($id);
		if(empty($info)) $this->error('内容不存在');
		try {
			$result = $this->storyinfoService()->infoDelete($info['story_id']);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功',U('index'),0);
	}
	
	
	private function storyinfoService(){
		return D('Story', 'Service');
	}
}