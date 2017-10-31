<?php
namespace Wap\Controller\User;
use Wap\Controller\WapController;
use Common\Basic\PageMore as Pager;

class DrugController extends WapController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
    }
	
    public function indexAction(){
    	$get = I('get.');
		$params = array(
			'page'=>$get['p'],
			'pagesize'=>15,
			'clinic_id'=>$this->user['clinic_id'],
			//'stime'=>$get['stime'],
			//'etime'=>$get['etime'],
		);
		if (IS_POST) {
			$post = I('post.');
			$this->assign('post', $post);
			if (!empty($post['stime'])) {
				$params['stime'] = $post['stime'];
			}
			if (!empty($post['etime'])) {
				$params['etime'] = $post['etime'];
			}
		}
		$result = $this->drugLogService()->logPagerList($params);
		if ($result[count] > 0){
			$this->assign('list', $result['list']);
			$pager = new Pager($result['count'], $params['pagesize']);
			$this->assign('pager', $pager->show());
		}
		$this->assign('get', $get);
		
		if(IS_AJAX){
			if(empty($result['list'])){
				$clist = '';
			}else{
				$clist = $this->renderPartial('_index');
			}
			die($clist);
		}
		
		$this->display();
    }
    
	public function subAction(){
		if(IS_POST){
			$params = I('post.');
			$params['clinic_id'] = $this->user['clinic_id'];
			$params['user_id'] = $this->user['user_id'];
			try {
				$this->drugLogService()->addLog($params);
			} catch (\Exception $e){
				$this->error($e->getMessage());
			}
			$this->success('添加成功', U('index'));
		}
		$this->assign('drug_list', $this->drugService()->drugs());
		$this->display();
	}
	
	public function listAction(){
		
		$p=I('p')?I('p'):I('get.p');
		$p=$p?$p:1;
		$size=12;
		$clinic_id=$this->user['clinic_id'];
		$map=array('clinic_id'=>$clinic_id);
		$params=array('map'=>$map,'page'=>$p,'pagesize'=>$size,'orderby'=>'add_time desc');
		
		$result=$this->DrugOrderService()->agentGetList($params);
		$drug_category_result=D('DrugCategory')->getRecord();
		
		$this->assign('list', $result['list']);
		$this->assign('drug_category',$drug_category_result);
		
		if(IS_AJAX){
			if(empty($result['list'])){
				$clist = '';
			}else{
				$clist = $this->renderPartial('_list');
			}
			die($clist);
		}
		
		$this->display();
	}
	
	private function drugOrderService(){
		return D('DrugOrder', 'Service');
	}
	
	private function drugLogService(){
		return D('DrugLog', 'Service');
	}
	
	private function drugService(){
		return D('Drug', 'Service');
	}
}