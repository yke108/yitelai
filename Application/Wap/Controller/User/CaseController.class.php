<?php
namespace Wap\Controller\User;
use Wap\Controller\WapController;
use Common\Basic\PageMore as Pager;

class CaseController extends WapController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
		$office_result=$this->clinicCaseOfficeService()->PagerList($office_params);
		$type_result=$this->clinicCaseTypeService()->PagerList($office_params);
		$this->assign('office_list',$office_result['list']);
		$this->assign('type_list',$type_result['list']);
    }
	
    public function indexAction(){
    	$get = I('get.');
    	$this->assign('get', $get);
    	
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
		
		$result = $this->clinicService()->casePagerList($params);
		if ($result[count] > 0){
			$this->assign('list', $result['list']);
			$pager = new Pager($result['count'], $params['pagesize']);
			$this->assign('pager', $pager->show());
		}
		
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
    
    public function infoAction($id = 0){
    	$info = $this->clinicService()->caseInfo($id);
    	if (empty($info)){
    		$this->error('数据不存在');
    	}
    	
    	if (!empty($info['images'])) {
    		$images = explode(',', $info['images']);
    		foreach ($images as $v) {
    			$img = picurl(trim($v,'/'));
    			$aa = getimagesize($img);
    			$gallery[] = array(
    					'width'=>$aa[0],
    					'height'=>$aa[1],
    					'img'=>$img
    			);
    		}
    		$info['gallery'] = $gallery;
    	}
    	
    	$this->assign('info', $info);
    	$this->display();
    }
    
	public function subAction(){
		//var_dump($this->user);die();
		if(IS_POST){
			$params = I('post.');
			$params['clinic_id'] = $this->user['clinic_id'];
			$params['user_id'] = $this->user['user_id'];
			try {
				$this->clinicService()->addCase($params);
			} catch (\Exception $e){
				$this->error($e->getMessage());
			}
			$this->success('添加成功', U('index'));
		}
		$office_params=array('map'=>array(),'page'=>1,'pagesize'=>100);
		$office_result=$this->clinicCaseOfficeService()->PagerList($office_params);
		$type_result=$this->clinicCaseTypeService()->PagerList($office_params);
		$this->assign('office_list',$office_result['list']);
		$this->assign('type_list',$type_result['list']);
		$this->display();
	}
	
	private function clinicService(){
		return D('Clinic', 'Service');
	}
	private function clinicCaseOfficeService(){
		return D('ClinicCaseOffice', 'Service');
	}
	private function clinicCaseTypeService(){
		return D('ClinicCaseType', 'Service');
	}
}