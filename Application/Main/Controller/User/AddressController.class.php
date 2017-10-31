<?php
namespace Main\Controller\User;
use Main\Controller\MainController;
use Common\Basic\Pager;

class AddressController extends MainController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
		
		$this->assign('page_title', '个人中心');
    }
	
    public function indexAction(){
    	session('back_url', null);
		$get = I('get.');
		$this->assign('get', $get);
		$pagesize = 10;
		$params = array(
				'page'=>intval(I('p')) ? intval(I('p')) : 1,
				'pagesize'=>$pagesize,
				'user_id'=>$this->user['user_id']
		);
		$datas = $this->addressService()->getPagerList($params);
		$this->assign('list', $datas['list']);
		$pager = new Pager($datas['count'], $pagesize);
		$this->assign('pages', $pager->show_pc());
		$this->display();
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
			
			$redirect_url = session('back_url') ? session('back_url') : U('index');
			$this->success('地址添加成功', $redirect_url);
		}
		
		$regionService = $this->regionService();
		$plist = $regionService->getProvinceList();
		$this->assign('plist', $plist);
		
		//省市区
		$this->assign('region_list', $this->regionService()->getAllRegions(false));
		
		
		$this->	display('edit');
	}
	
	public function editAction($id = 0){
		$addressService = $this->addressService();
		$info = $addressService->getAddressById($id);
		if(empty($info) || $info['user_id'] != $this->user['user_id']){
			$this->error('地址不存在');
		}
		$this->assign('info', $info);
		
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
		
		//省市区
		$this->assign('region_list', $this->regionService()->getAllRegions(false));
		
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
	
	private function ddata(){
		$post = I('post.');
		$zone = $this->regionService()->getDistrictFullName($post['district']);
		if(empty($zone)){
			$this->error('地址修改失败');
		}
		$data = array(
			'consignee'=>$post['consignee'],
			'region_code'=>$post['district'],
			'address'=>$post['address'],
			'zipcode'=>$post['zipcode'],
			'mobile'=>$post['mobile'],
			'zone'=>$zone,
		);
		return $data;
	}
	
	private function regionService(){
		return D('Region', 'Service');
	}
	
	private function addressService(){
		return D('UserAddress', 'Service');
	}
}