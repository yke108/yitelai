<?php
namespace Distributor\Controller\Question;
use Distributor\Controller\FController;
use Common\Basic\Pager;

class InfoController extends FController {
	public function _initialize(){
		parent::_initialize();
		$set = array(
			'in'=>'question',
			'ac'=>'question_info_index',
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
    		'distributor_id'=>$this->org_id,
			'_needAdmin'=>1,
    	);
		if($get['status']!='all'){
			$params['status']=$get['status'];
		}
    	$result = $this->questionService()->infoPagerList($params);
		
    	if ($result['count'] > 0){
    		$this->assign('list', $result['list']);
			$this->assign('admins', $result['admins']);
    		$pager = new Pager($result['count'], $result['pagesize']);
    		$this->assign('pager', $pager->show());
    	}
		$this->assign('get', $get);
		$this->assign('catlist',$this->questionService()->catlist($params));
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$params = I('post.');
			$params['distributor_id'] = $this->org_id;
			$params['admin_id'] = session('uid');
			try {
				$result = $this->questionService()->infoCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功',U('index'),0);
		}
		$this->assign('catlist',$this->questionService()->catlist($params));
		$this->display('edit');
	}
	
	public function editAction($id = 0){
		$this->purviewCheck('storyinfo_edit');
		$info = $this->questionService()->getInfo($id);

		if(empty($info)) $this->error('内容不存在');
		if(IS_POST){
			$params = I('post.');
			$params['question_id'] = $info['question_id'];
			$params['add_time'] = $info['add_time'];
			$params['view_num'] = $info['view_num'];
			try {
				$result = $this->questionService()->infoCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('修改成功',U('index'),0);
		}
		$this->assign('info', $info);
		$this->assign('catlist',$this->questionService()->catlist($params));
		$this->display();
	}
	
	public function delAction($id = 0){
		$info = $this->questionService()->getInfo($id);
		if(empty($info)) $this->error('内容不存在');
		try {
			$result = $this->questionService()->infoDelete($info['question_id']);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功',U('index'),0);
	}
	
	private function questionService(){
		return D('Question', 'Service');
	}
}