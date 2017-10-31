<?php
namespace Admin\Controller\Distributor;
use Admin\Controller\FController;
use Common\Basic\Pager;
use Common\Basic\Status;

class IndexController extends FController {
	private $m_sys_id = 2;
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
			'in'=>'distributor',
			'ac'=>'distributor_index_index',
		);
		$this->sbset($set);
    }
    
    public function testAction($distributor_id = 0) {
    	M()->startTrans();
    	
    	//删除货品
    	$distributor_goods = M('distributor_goods')->where(array('distributor_id'=>$distributor_id))->select();
    	if ($distributor_goods) {
    		$record_ids = array();
    		foreach ($distributor_goods as $v) {
    			$record_ids[] = $v['record_id'];
    		}
    		$result = M('distributor_goods_product')->where(array('record_id'=>array('in', $record_ids)))->delete();
    		if ($result === false) {
    			M()->rollback();
    			die('删除失败');
    		}
    		
    		//删除商品
    		$result = M('distributor_goods')->where(array('distributor_id'=>$distributor_id))->delete();
    		if ($result === false) {
    			M()->rollback();
    			die('删除失败');
    		}
    	}
    	
    	//删除店铺
    	$result = M('distributor_info')->where(array('distributor_id'=>$distributor_id))->delete();
    	if ($result === false) {
    		M()->rollback();
    		die('删除失败');
    	}
    	
    	M()->commit();
    	
    	die('删除成功');
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
    			'is_self_distributor'=>$get['is_self_distributor'],
    			'start_time'=>$get['start_time'],
    			'end_time'=>$get['end_time'],
    	);
    	if ($get['distributor_id']) {
    		$params['distributor_id'] = $get['distributor_id'];
    	}
    	$datas = $this->distributorService()->getPagerList($params);
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
				$this->distributorService()->createOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('添加成功', U('index'));
		}
		
		$this->publicAssign();
		
		$this->display('edit');
	}
	
	public function editAction($distributor_id = 0){
		$info = $this->distributorService()->getInfo($distributor_id);
		if(empty($info)) $this->error('店铺不存在');
		
		if(IS_POST){
			$post = I('post.');
			try {
				$this->distributorService()->createOrModify($post);
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
		//等级列表
		$ranks = $this->distributorRankService()->getFieldList();
		$this->assign('ranks', $ranks);
		
		//地区
		$this->assign('region_list', $this->regionService()->getAllRegions(false));
		
		//所有品牌
		$brand_list = $this->goodsBrandService()->getAllList();
		$this->assign('brand_list', $brand_list);
		
		//所有品牌商
		$brands_list = $this->brandsService()->getAllList();
		$this->assign('brands_list', $brands_list);
		
		//所有区域
		$area_list = $this->AreaService()->getAllList();
		$this->assign('area_list', $area_list);
		
		//店铺状态
		$this->assign('status_list', Status::$distributorStatusList);
	}
	
	public function delAction($distributor_id = 0){
		try {
			$this->distributorService()->delete($distributor_id);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('删除成功');
	}
	
	public function isshowAction($id = 0){
		try {
			$this->distributorService()->isShow($id);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success('操作成功', session('back_url'));
	}
	
	public function fineAction($distributor_id = 0){
		$info = $this->distributorService()->getInfo($distributor_id);
		if(empty($info)){
			$this->error('数据不存在');
		}
	
		if(IS_POST){
			$post = I('post.');
			try {
				$this->distributorService()->createOrModify($post);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('操作成功', session('back_url'));
		}
		
		$this->assign('info', $info);
		
		$this->display();
	}
	
	public function adminAction($distributor_id = 0){
		//店铺超级管理员
		$map = array(
				'org_id'=>$distributor_id,
				'sys_id'=>$this->m_sys_id,
				'is_admin'=>1,
		);
		$admin_info = $this->adminService()->findAdmin($map);
		
		if (IS_POST){
			$params = I('post.');
			$params['org_id'] = $params['distributor_id'];
			$params['sys_id'] = $this->m_sys_id;
			$params['is_admin'] = 1;
			try {
				$this->adminService()->adminCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			
			//发送账号密码到店铺负责人
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
	
	private function distributorService(){
		return D('Distributor', 'Service');
	}
	
	private function regionService(){
		return D('Region', 'Service');
	}
	
	private function areaService(){
		return D('Area', 'Service');
	}
	
	private function goodsBrandService(){
		return D('GoodsBrand', 'Service');
	}
	
	private function distributorRankService(){
		return D('Distributor\DistributorRank', 'Service');
	}
	
	private function merchantService(){
		return D('Merchant', 'Service');
	}
	
	private function smsService(){
		return D('Sms', 'Service');
	}
	
	private function smsapiService(){
		return D('Smsapi', 'Service');
	}
	
	private function brandsService(){
		return D('Brands', 'Service');
	}
}