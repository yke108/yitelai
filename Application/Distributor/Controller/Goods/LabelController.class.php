<?php
namespace Distributor\Controller\Goods;
use Distributor\Controller\FController;
use Common\Basic\Pager;

class LabelController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'goods',
			'ac'=>'goods_label_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	$params = array(
    			'distributor_id'=>$this->org_id,
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize
    	);
		$datas = $this->goodsLabelService()->getPagerList($params);
		$this->assign('list', $datas['list']);
		$pager = new Pager($datas['count'], $this->pagesize);
		$this->assign('pager', $pager->show());
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$post = I('post.');
			$post['distributor_id'] = $this->org_id;
			try {
				$this->goodsLabelService()->createOrModify($post);
				$this->success('添加成功', U('index'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		$this->display('edit');
	}
	
	public function editAction($label_id = 0){
		$map = array(
				'label_id'=>$label_id,
				'distributor_id'=>$this->org_id
		);
		$info = $this->goodsLabelService()->findInfo($map);
		if(empty($info)){
			$this->error('数据不存在');
		}
		$this->assign('info', $info);
		if(IS_POST){
			$post = I('post.');
			$post['distributor_id'] = $this->org_id;
			try {
				$this->goodsLabelService()->createOrModify($post);
				$this->success('编辑成功', U('index'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		$this->display();
	}
	
	public function delAction($label_id = 0){
		$map = array(
				'label_id'=>$label_id,
				'distributor_id'=>$this->org_id
		);
		try {
			$this->goodsLabelService()->del($map);
			$this->success('删除成功', U('index'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	private function goodsLabelService() {
		return D('GoodsLabel', 'Service');
	}
}