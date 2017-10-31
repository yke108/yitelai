<?php
namespace News\Controller\User;
use News\Controller\WapController;

class AddressController extends WapController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
    }
	
    public function indexAction(){
    	session('back_url', null);
		$this->listDisplay();
    }
    
    public function selectAddressAction(){
    	$get = I('get.');
    	$this->assign('get', $get);
    	 
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>10,
    			'user_id'=>$this->user['user_id']
    	);
    	$datas = $this->addressService()->getPagerList($params);
    	$this->assign('list', $datas['list']);
    	
    	if(IS_AJAX){
    		if(empty($datas['list'])){
    			$clist = '';
    		}else{
    			$clist = $this->renderPartial('_selectaddress');
    		}
    		die($clist);
    	}
    	
    	$this->display();
    }
    
    private function listDisplay(){
    	$get = I('get.');
    	$this->assign('get', $get);
    	$p=I('p')?I('p'):I('get.p');
		$p=$p?$p:1;
    	$params = array(
    			'page'=>$p,
    			'pagesize'=>100,
    			'user_id'=>$this->user['user_id']
    	);
    	$datas = $this->addressService()->getPagerList($params);
    	$this->assign('list', $datas['list']);
    	
    	$this->display();
    }
	
	public function address_listAction(){
		$get = I('get.');
    	$this->assign('get', $get);
    	
    	$params = array(
    			'page'=>intval(I('p')) ? intval(I('p')) : 1,
    			'pagesize'=>10,
    			'user_id'=>$this->user['user_id']
    	);
    	$datas = $this->addressService()->getPagerList($params);
    	$this->assign('list', $datas['list']);
    	
    	$this->display('index');
	}
	
	public function addAction(){
		$addressService = $this->addressService();
		if(IS_POST){
			$post = I('post.');
			$data = $this->ddata();
			$address_id = $addressService->addAddressToUser($this->user['user_id'], $data);
			if($address_id < 1){
				$this->error('地址添加失败');
			}
			if($post['is_default'] == 1){
				$addressService->setDefaultAddress($address_id, $this->user['user_id']);
			}
			$redirect_url = session('exchange_back_url') ? session('exchange_back_url') : U('index');
			$this->success('地址添加成功', $redirect_url);
		}
		$regionService = $this->regionService();
		$plist = $regionService->getProvinceList();
		$this->assign('plist', $plist);
		$this->	display('edit');
	}
	
	public function editAction($id = 0){
		$addressService = $this->addressService();
		$info = $addressService->getAddressById($id);
		if(empty($info) || $info['user_id'] != $this->user['user_id']){
			$this->error('地址不存在');
		}
		if(IS_POST){
			$post = I('post.');
			$data = $this->ddata();
			
			if($addressService->updateAddressInfo($info['address_id'], $data) === false){
				$this->error('地址修改失败');
			}
			
			if($post['is_default'] == 1){
				$addressService->setDefaultAddress($info['address_id'], $this->user['user_id']);
			}
			
			$redirect_url = session('back_url') ? session('back_url') : U('index');
			$this->success('地址修改成功', $redirect_url);
		}
		
		$info['province'] = intval($info['district'] / 10000) * 10000;
		$info['city'] = intval($info['district'] / 100) * 100;
		$this->assign('info', $info);
		
		//获得省列表
		$regionService = $this->regionService();
		$plist = $regionService->getProvinceList();
		$this->assign('plist', $plist);
		//获得市列表
		$list = $this->regionService()->getCityListOfProvince($info['province']);
		$this->assign('clist', $list);
		//获得区县列表
		$list = $this->regionService()->getDistrictOfCity($info['city']);
		$this->assign('dlist', $list);
		$this->	display();
	}
	
	public function delAction($id = 0){
		$addressService = $this->addressService();
		$info = $addressService->getAddressById($id);
		if(empty($info) || $info['user_id'] != $this->user['user_id']){
			$this->error('地址不存在');
		}
		if($addressService->deleteAddress($id) < 1){
			$this->error('删除失败');
		}
		$this->success('删除成功', U('index'));
	}
	
	public function subrAction(){
		$code = I('post.region');
		if($code % 100 > 0) $list = array();
		elseif($code % 10000 == 0) 
			$list = $this->regionService()->getCityListOfProvince($code);
		elseif($code % 100 == 0) 
			$list = $this->regionService()->getDistrictOfCity($code);
		$rl = array(
			'code'=>$code,
			'list'=>$list,
		);
		$this->success('Success', '', $rl);
	}
	
	public function addressDefAction($id = 0) {
		$addressService = $this->addressService();
		$info = $addressService->getAddressById($id);
		if(empty($info) || $info['user_id'] != $this->user['user_id']){
			$this->error('地址不存在');
		}
		if($addressService->setDefaultAddress($id, $this->user['user_id']) < 1){
			$this->error('设置失败');
		}
		$back_url = session('exchange_back_url') ? session('exchange_back_url') : '';
		$this->success('设置成功', $back_url);
	}
	
	private function ddata(){
		$post = I('post.');
		if (empty($post['consignee'])) {
			$this->error('联系人不能为空');
		}
		if (empty($post['mobile'])) {
			$this->error('联系电话不能为空');
		}
		if (empty($post['sex'])) {
			$this->error('性别不能为空');
		}
		if (empty($post['district'])) {
			$this->error('请选择收货地址');
		}
		if (empty($post['address'])) {
			$this->error('详细地址不能为空');
		}
		$zone = $this->regionService()->getDistrictFullName($post['district']);
		if(empty($zone)){
			$this->error('地区不存在');
		}
		$data = array(
			'consignee'=>$post['consignee'],
			'region_code'=>$post['district'],
			'address'=>$post['address'],
			'sex'=>$post['sex'],
			'mobile'=>$post['mobile'],
			'zone'=>$zone,
		);
		return $data;
	}
	
	private function regionService(){
		return D('Region', 'Service');
	}
	
	private function addressService(){
		return D('Information\UserAddress', 'Service');
	}
}