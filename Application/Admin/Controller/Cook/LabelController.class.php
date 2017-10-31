<?php
namespace Admin\Controller\Cook;
use Admin\Controller\FController;
use Common\Basic\Pager;

class LabelController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'cook',
			'ac'=>'cook_label_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	$params = array(
    			'distributor_id'=>0,
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize
    	);
		$datas = $this->cookLabelService()->getPagerList($params);
		$this->assign('list', $datas['list']);
		$pager = new Pager($datas['count'], $this->pagesize);
		$this->assign('pager', $pager->show());
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$post = I('post.');
			try {
				$this->cookLabelService()->createOrModify($post);
				$this->success('添加成功', U('index'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		$this->display('edit');
	}
	
	public function editAction($label_id = 0){
		$info = $this->cookLabelService()->getInfo($label_id);
		if(empty($info)){
			$this->error('数据不存在');
		}
		$this->assign('info', $info);
		if(IS_POST){
			$post = I('post.');
			try {
				$this->cookLabelService()->createOrModify($post);
				$this->success('编辑成功', U('index'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		$this->display();
	}
	
	public function delAction($label_id = 0){
		try {
			$this->cookLabelService()->delete($label_id);
			$this->success('删除成功', U('index'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	private function cookLabelService() {
		return D('CookLabel', 'Service');
	}
}