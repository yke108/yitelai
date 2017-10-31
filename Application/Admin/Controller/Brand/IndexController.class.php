<?php
namespace Admin\Controller\Brand;
use Admin\Controller\FController;
use Common\Basic\Pager;
use Common\Basic\Status;

class IndexController extends FController {
	private $m_sys_id = Status::SysIdBrand;
	
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'brand',
			'ac'=>'brand_index_index',
		);
		$this->sbset($set);
    }
    
    public function indexAction(){
    	session('back_url', __SELF__);
    	
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'keyword'=>trim($get['keyword']),
    			'status'=>$get['status'],
    			'start_time'=>$get['start_time'],
    			'end_time'=>$get['end_time'],
    	);
    	if ($get['brands_id']) {
    		$params['brands_id'] = $get['brands_id'];
    	}
    	$datas = $this->brandsService()->getPagerList($params);
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $this->pagesize);
    	$this->assign('pager', $pager->show());
    	$this->assign('area_list', $datas['area_list']);
    	
    	$this->publicAssign();
    	
    	$this->display();
    }
	
	public function addAction(){
		if(IS_POST){			
			$post = I('post.');
			try {
				$this->brandsService()->createOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功', U('index'));
		}
		
		$this->publicAssign();
		
		$this->display('edit');
	}
	
	public function editAction($brands_id = 0){
		$info = $this->brandsService()->getInfo($brands_id);
		if(empty($info)) $this->error('品牌商不存在');
		
		if(IS_POST){
			$post = I('post.');
			try {
				$this->brandsService()->createOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('编辑成功', session('back_url'));
		}
		
		$this->assign('info', $info);
		
		$this->publicAssign();
		
		$this->display();
	}
	
	private function publicAssign() {
		//地区
		$this->assign('region_list', $this->regionService()->getAllRegions(false));
		
		//所有区域
		$area_list = $this->AreaService()->getAllList();
		$this->assign('area_list', $area_list);
		
		//品牌商状态
		$this->assign('status_list', Status::$brandStatusList);
	}
	
	public function delAction($brands_id = 0){
		try {
			$this->brandsService()->delete($brands_id);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功');
	}
	
	public function isshowAction($id = 0){
		try {
			$this->brandsService()->isShow($id);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('操作成功', session('back_url'));
	}
	
	public function fineAction($brands_id = 0){
		$info = $this->brandsService()->getInfo($brands_id);
		if(empty($info)){
			$this->error('数据不存在');
		}
	
		if(IS_POST){
			$post = I('post.');
			try {
				$this->brandsService()->createOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('操作成功', session('back_url'));
		}
		
		$this->assign('info', $info);
		
		$this->display();
	}
	
	public function adminAction($brands_id = 0){
		//品牌商超级管理员
		$map = array(
				'org_id'=>$brands_id,
				'sys_id'=>$this->m_sys_id,
				'is_admin'=>1,
		);
		$admin_info = $this->adminService()->findAdmin($map);
		
		if (IS_POST){
			$params = I('post.');
			$params['org_id'] = $params['brands_id'];
			$params['sys_id'] = $this->m_sys_id;
			$params['is_admin'] = 1;
			try {
				$this->adminService()->adminCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			
			//发送账号密码到品牌商负责人
			if (empty($admin_info)) {
				$type = 8;
				$params_sms = array(
						'username'=>$params['username'],
						'password'=>$params['password']
				);
				try {
					$result = $this->smsapiService()->sendSms($params, $params['mobile'], $type, $params_sms);
				} catch (\Exception $e) {
					$this->error($e->getMessage());
				}
			}
			
			$this->success('保存成功', session('back_url'));
		}
		
		$get = I('get.');
		$this->assign('get', $get);
	
		$this->assign('info', $admin_info);
	
		$this->display();
	}
	
	private function brandsService(){
		return D('Brands', 'Service');
	}
	
	private function regionService(){
		return D('Region', 'Service');
	}
	
	private function areaService(){
		return D('Area', 'Service');
	}
	
	private function smsService(){
		return D('Sms', 'Service');
	}
	
	private function smsapiService(){
		return D('Smsapi', 'Service');
	}
}