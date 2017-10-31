<?php
namespace Admin\Controller\Distributor;
use Admin\Controller\FController;
use Common\Basic\Pager;

class DistributionController extends FController {
	public function _initialize(){
		parent::_initialize();

		$set = array(
			'in'=>'distributor',
			'ac'=>'distributor_distribution_index',
		);
		$this->sbset($set);
		
		//等级列表
		$rank_list = $this->distributorRankService()->getFieldList();
		$this->assign('rank_list', $rank_list);
    }
	
    public function indexAction(){
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    	);
		$datas = $this->distributorDistributionService()->getPagerList($params);
		$this->assign('list', $datas['list']);
		$pager = new Pager($datas['count'], $this->pagesize);
		$this->assign('pager', $pager->show());
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$post = I('post.');
			try {
				$this->distributorDistributionService()->createOrModify($post);
				$this->success('添加成功', U('index'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		$this->display('edit');
	}
	
	public function editAction($distribution_id = 0){
		$info = $this->distributorDistributionService()->getInfo($distribution_id);
		if(empty($info)){
			$this->error('数据不存在');
		}
		
		if(IS_POST){
			$post = I('post.');
			try {
				$this->distributorDistributionService()->createOrModify($post);
				$this->success('编辑成功', U('index'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		
		$this->assign('info', $info);
		
		$this->display();
	}
	
	public function delAction($distribution_id = 0){
		try {
			$this->distributorDistributionService()->delete($distribution_id);
			$this->success('删除成功', U('index'));
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}
	
	private function distributorDistributionService() {
		return D('Distributor\Distribution', 'Service');
	}
	
	private function distributorRankService() {
		return D('Distributor\DistributorRank', 'Service');
	}
}