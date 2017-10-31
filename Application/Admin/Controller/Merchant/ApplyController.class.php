<?php
namespace Admin\Controller\Merchant;
use Admin\Controller\FController;
use Common\Basic\Pager;
use Common\Basic\Status;
use Common\Basic\MessageConst;

class ApplyController extends FController {
	private $m_sys_id = 2;
	
	public function _initialize(){
		parent::_initialize();
		
		$set = array(
				'in'=>'merchant',
				'ac'=>'merchant_apply_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction(){
    	session('back_url', __SELF__);
    	
    	$this->listDisplay();
    }
    
    private function listDisplay($map = array()){
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    			'map'=>$map,
    	);
    	$result = $this->merchantService()->getPagerList($params);
    	if ($result['count'] > 0){
    		$this->assign('list', $result['list']);
    		$pager = new Pager($result['count'], $params['pagesize']);
    		$this->assign('pager', $pager->show());
    	}
    	
    	$this->display('index');
    }
	
	public function checkAction($id = 0){
		$info = $this->merchantService()->getInfo($id);
		if(empty($info)) $this->error('内容不存在');
		
		if(IS_POST){
			$params = I('post.');
			
			if ($params['status'] == 2 && empty($params['remark'])) {
				$this->error('请填写备注');
			}
			
			$params['merchant_id'] = $info['merchant_id'];
			try {
				$result = $this->merchantService()->createOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			
			if ($params['status'] == 1) {
				$title = '商家入驻申请审核通过';
				$result_content = '商家入驻申请审核通过';
			}else {
				$title = '商家入驻申请审核不通过';
				$result_content = '商家入驻申请审核不通过，原因：'.$params['remark'];
			}
			
			//申请日志
			$params = array(
					'merchant_id'=>$info['merchant_id'],
					'user_id'=>$info['user_id'],
					'content'=>$title,
					'result_content'=>$result_content,
					'add_time'=>NOW_TIME
			);
			try {
				$this->merchantLogService()->createOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			
			//发送短信
			$user_info = $this->userService()->getUserInfo($info['user_id']);
			$data = array(
					'UserId'=>$user_info['user_id'],
					'Title'=>$title,
					'MsgType'=>Status::MsgTypeMerchantApplyPass,
			);
			try {
				$result = $this->smsLogic()->smsLogByTemplate(MessageConst::SmsTpOnMerchantApplyPass ,$user_info['mobile'], $data);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			
			$this->success('审核成功', session('back_url'));
		}
		$this->assign('info', $info);
		$this->display();
	}
	
	public function viewAction($id = 0){
		$info = $this->merchantService()->getInfo($id);
		if(empty($info)) $this->error('内容不存在');
		$this->assign('info', $info);
		
		//品牌列表
		$map = array('merchant_id'=>$info['merchant_id']);
		$brand_list = $this->merchantBrandService()->getAllList($map);
		$this->assign('brand_list', $brand_list);
		
		//已添加的分类
		$map = array('merchant_id'=>$info['merchant_id']);
		$merchant_cat_list = $this->merchantCatService()->getAllList($map);
		$this->assign('merchant_cat_list', $merchant_cat_list);
		
		$this->display();
	}
	
	public function brand_viewAction($id = 0){
		$brand = $this->merchantBrandService()->getInfo($id);
		$this->assign('brand', $brand);
		
		$this->display();
	}
	
	public function distributorAction($distributor_id = 0){
		$get = I('get.');
		
		if(IS_POST){
			$post = I('post.');
			try {
				$this->distributorService()->createOrModify($post);
				$this->success('添加成功', U('index'));
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		
		$this->assign('region_list', $this->regionService()->getAllRegions(false));
		
		//所有品牌
		$brand_list = $this->goodsBrandService()->getAllList();
		$this->assign('brand_list', $brand_list);
		
		//等级列表
		$ranks = $this->distributorRankService()->getFieldList();
		$this->assign('ranks', $ranks);
		
		//店铺
		$distributor_info = $this->distributorService()->getInfo($distributor_id);
		$this->assign('info', $distributor_info);
		
		//申请信息
		$merchant_info = $this->merchantService()->getInfo($get['merchant_id']);
		$this->assign('merchant_info', $merchant_info);
		
		$this->display('distributor');
	}
	
	public function adminAction($distributor_id = 0){
		$map = array(
				'org_id'=>$distributor_id,
				'sys_id'=>$this->m_sys_id,
				'is_admin'=>1,
		);
		$admin_info = $this->adminService()->findAdmin($map);
		$this->assign('info', $admin_info);
	
		$get = I('get.');
		$this->assign('get', $get);
	
		if (IS_POST){
			$params = I('post.');
			$params['sys_id'] = $this->m_sys_id;
			//$params['org_id'] = $distributor_id;
			//$params['admin_id'] = $admin_info['admin_id'];
			$params['is_admin'] = 1;
			try {
				$this->adminService()->adminCreateOrModify($params);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
			$this->success('保存成功', U('index'));
		}
	
		$this->display();
	}
	
	private function merchantService(){
		return D('Merchant', 'Service');
	}
	
	private function merchantLogService(){
		return D('MerchantLog', 'Service');
	}
	
	private function distributorService(){
		return D('Distributor', 'Service');
	}
	
	private function regionService(){
		return D('Region', 'Service');
	}
	
	private function goodsBrandService(){
		return D('GoodsBrand', 'Service');
	}
	
	private function distributorRankService(){
		return D('Distributor\DistributorRank', 'Service');
	}
	
	private function merchantBrandService(){
		return D('MerchantBrand', 'Service');
	}
	
	private function merchantCatService(){
		return D('MerchantCat', 'Service');
	}
	
	private function smsLogic(){
		return D('Sms', 'Logic');
	}
}