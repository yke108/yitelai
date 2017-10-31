<?php
namespace Information\Controller\Community;
use Information\Controller\FController;
use Common\Basic\Pager;

class BlockController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'community',
			'ac'=>'community_block_index',
		);
		$this->sbset($set);
    }
	

    public function indexAction(){
    	session('back_url', __SELF__);
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$tr_list = $this->blockService()->blockTrList();
    	$this->assign('tr_list', $tr_list);
		
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$params = I('post.');
			try {
				$result = $this->blockService()->blockCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功', U('index'));
		}
		
		//顶级分类
		$map = array('parent_id'=>0);
		$toplist = $this->blockService()->blockAllList($map);
		$this->assign('toplist', $toplist);
		
		$this->display('edit');
	}
	

	public function editAction($block_id = 0){
		$info = $this->blockService()->getInfo($block_id);
		if(empty($info)) $this->error('内容不存在');
		if(IS_POST){
			$params = I('post.');
			$params['block_id'] = $info['block_id'];
			try {
				$result = $this->blockService()->blockCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功', session('back_url'));
		}
		
		$this->assign('info', $info);
		
		//顶级分类
		$map = array('parent_id'=>$info['block_id']);
		$child = $this->blockService()->getInfo($map);
		if (empty($child)) {
			$map = array('parent_id'=>0);
			$toplist = $this->blockService()->blockAllList($map);
			$newtoplist = array();
			foreach ($toplist as $v) {
				if ($v['block_id'] != $info['block_id']) {
					$newtoplist[] = $v;
				}
			}
			$this->assign('toplist', $newtoplist);
		}
		
		$this->display();
	}
	
	public function delAction($block_id = 0){
		try {
			$result = $this->blockService()->blockDelete($block_id);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功');
	}
	
	private function blockService(){
		return D('Information\Block', 'Service');
	}
}