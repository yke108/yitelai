<?php
namespace News\Controller\User;
use News\Controller\WapController;

class PointgiftController extends WapController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
		
		$this->assign('page_title', '积分商城');
		
		//获取省份
		$province=$this->regionService()->getChildList();
		$this->assign('province',$province);
    }
	
    public function indexAction(){
		$point=$this->userService()->getFieldData(array('user_id'=>session('userid')),'pay_points');
		$this->assign('point',$point);
		
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$params = array(
    			'page'=>I('p')?I('p'):1,
    			'pagesize'=>$this->pagesize,
				'is_going'=>1,
    	);
    	$datas = $this->pointGiftService()->infoPagerList($params);
    	$this->assign('list', $datas['list']);
		
		//收货地址
		$address_list = $this->userAddressService()->getUserAddressList($this->user['user_id'], 'is_default DESC');
		$this->assign('address_list', $address_list);
		
		//默认地址
		$address = $this->userAddressService()->findDefaultAddress($this->user['user_id']);
		$this->assign('address', $address);
		
    	$this->display();
    }
    
	//生成积分兑换订单
	public function add_gift_orderAction(){
		$user_id=session('userid');
		$gift_id=I('gift_id')?I('gift_id'):I('get.gift_id');
		$address_id=I('address_id')?I('address_id'):I('get.address_id');
		$params=array('gift_id'=>$gift_id,'user_id'=>$user_id,'address_id'=>$address_id);
		try{
			$this->pointGiftService()->orderCreateOrModify($params);
		}catch(\Exception $e){
			$this->ajaxReturn(array('error'=>1,'msg'=>$e->getMessage(),'error_code'=>$e->getCode()));
		}
		$this->ajaxReturn(array('error'=>0,'msg'=>'积分兑换商品成功','url'=>U('order')));
	}
	
	public function orderAction(){
		$page=I('p')?I('p'):I('get.p');
		$page=$page?$page:1;
		$size=6;
		$map=array('user_id'=>session('userid'));
		$params=array('page'=>$page,'pagesize'=>$size,'map'=>$map);
		$result=$this->pointGiftService()->orderPagerList($params);
		$this->assign('list',$result['list']);
		
		if(IS_AJAX){
			$html=$this->renderPartial('User/Pointgift/_order');
			$this->ajaxReturn(array('html'=>$html));
		}
		
		$this->display();
	}
	
	public function add_addressAction(){
		if(IS_AJAX){
			$type=I('type')?I('type'):I('get.type');
			$address_id=I('address_id')?I('address_id'):I('get.address_id');
			if($type=='edit'){
				$info=$this->userAddressService()->getAddressById($address_id,$this->user['user_id']);
				$this->assign('info',$info);
				$province_code=intval($info['region_code']/10000)*10000;
				$city_code=intval($info['region_code']/100)*100;
				$province_list=$this->regionService()->getChildList();
				$city_list=$this->regionService()->getChildList($province_code);
				$district_list=$this->regionService()->getChildList($city_code);
				
				$this->assign('province',$province_list);
				$this->assign('city',$city_list);
				$this->assign('district',$district_list);
				$this->assign('info',$info);
				$this->assign('province_code',$province_code);
				$this->assign('city_code',$city_code);
				
			}
			$html=$this->renderPartial('User/Pointgift/_address');
			$this->ajaxReturn(array('html'=>$html));
		}
	}
	
	public function load_address_listAction(){
		//收货地址
		$address_list = $this->userAddressService()->getUserAddressList($this->user['user_id'], 'is_default DESC');
		$this->assign('list', $address_list);
		$this->assign('exchange_back_url',session('exchange_back_url'));
		$this->display('_address_list');	
	}
	
	public function exchangeAction(){
		$id=I('id')?I('id'):I('get.id');
		$address_id=I('address_id')?I('address_id'):I('get.address_id');
		$info=$this->pointGiftService()->getInfo($id);
		if(empty($info)){
			$this->error('礼品不存在');
		}
		$this->assign('info',$info);
		
		//添加地址返回
		session('exchange_back_url', __SELF__);
		
		//默认地址
		if ($address_id) {
			$address = $this->userAddressService()->getAddressById($this->user['user_id']);
		}else {
			$address = $this->userAddressService()->findDefaultAddress($this->user['user_id']);
			if (empty($address)) {
				$address = $this->userAddressService()->getAddress($this->user['user_id']);
			}
		}
		$this->assign('address', $address);
		
		$this->display();
	}
	
	private function pointGiftService(){
		return D('Information\PointGift', 'Service');
	}
	
	private function userAddressService(){
		return D('Information\UserAddress','Service');
	}
	
	private function regionService(){
		return D('Region','Service');
	}
}