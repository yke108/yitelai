<?php
namespace Distributor\Controller\Goods;
use Distributor\Controller\FController;
use Common\Basic\Pager;

class ServiceController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'goods',
			'ac'=>'goods_service_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'distributor_id'=>$this->org_id
    	);
		$datas = $this->goodsServiceService()->getPagerList($params);
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
				$this->goodsServiceService()->createOrModify($post);
				$this->success('添加成功', U('index'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		$this->display('edit');
	}
	
	public function editAction($service_id = 0){
		$params = array(
				'service_id'=>$service_id,
				'distributor_id'=>$this->org_id
		);
		$info = $this->goodsServiceService()->findInfo($params);
		if(empty($info)){
			$this->error('数据不存在');
		}
		$this->assign('info', $info);
		if(IS_POST){
			$post = I('post.');
			$post['distributor_id'] = $this->org_id;
			try {
				$this->goodsServiceService()->createOrModify($post);
				$this->success('编辑成功', U('index'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		$this->display();
	}
	
	public function delAction($service_id = 0){
		try {
			$map = array(
					'service_id'=>$service_id,
					'distributor_id'=>$this->org_id
			);
			$this->goodsServiceService()->del($map);
			$this->success('删除成功', U('index'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	private function goodsServiceService() {
		return D('GoodsService', 'Service');
	}
}