<?php
namespace Admin\Controller\Finance;
use Admin\Controller\FController;
use Common\Basic\Pager;

class BankController extends FController {
	public function _initialize(){
		$this->purviewCheck(false);
		parent::_initialize();

		$set = array(
			'in'=>'finance',
			'ac'=>'finance_bank_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	
    	
    	
    	
    	$page = intval(I('p')) ? intval(I('p')) : 1;
		$size=100;
    	$params = array(
    			'page'=>$page,
    			'pagesize'=>$size,
    	);
    	
    	
    	$datas = $this->cashApplyService()->bankPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $size);
    	$this->assign('pager', $pager->show());
    	
		$this->display();
    }
	
	public function addAction(){
		if(IS_POST){
			$post=I('post.');
			try{
				$result = $this->cashApplyService()->bankCreateOrModify($post);
			}catch(\Exception $e){
				$this->error($e->getMessage());
			}
			$this->success('添加成功');
		}
		$this->display('edit');
	}
	
	public function editAction($id){
		if(IS_POST){
			$post=I('post.');
			$post['bank_id']=$id;
			
			try{
				$result = $this->cashApplyService()->bankCreateOrModify($post);
			}catch(\Exception $e){
				$this->error($e->getMessage());
			}
			$this->success('编辑成功');
		}
		$info=$this->cashApplyService()->getBank($id);
		$this->assign('info',$info);
		$this->display();
	}
	
	public function delAction($id){
		try{
			$result = $this->cashApplyService()->bankDelete($id);
		}catch(\Exception $e){
			$this->error($e->getMessage());
		}
		$this->success('删除成功');
	}
	
    
    private function cashApplyService() {
    	return D('CashApply', 'Service');
    }
    
	
   
}