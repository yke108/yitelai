<?php
namespace Wap\Controller\User;
use Wap\Controller\WapController;
use Common\Basic\Pager;

class PointgiftController extends WapController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
		
		$this->assign('page_title', '个人中心');
		
		//获取省份
		$province=$this->regionService()->getChildList();
		$this->assign('province',$province);
    }

    public function indexAction(){
		
		$point=$this->userService()->getFieldData(array('user_id'=>session('userid')),'pay_points');
    	$get = I('get.');
    	$this->assign('get', $get);
    	$p=I('p')?I('p'):I('get.p');
		$p=$p?$p:1;
    	$pagesize = 12;
    	$params = array(
    			'page'=>$p,
    			'pagesize'=>$pagesize,
				'map'=>$map,
				'is_going'=>1,
    	);
    	$datas = $this->pointGiftService()->infoPagerList($params);  
    	$this->assign('list', $datas['list']);
    	$pager = new Pager($datas['count'], $pagesize);
    	$this->assign('page', $pager->show());
		
		//收货地址
		$address_list = $this->userAddressService()->getUserAddressList($this->user['user_id'], 'is_default DESC');
		$this->assign('address_list', $address_list);
		
		
		//默认地址
		$address = $this->userAddressService()->findDefaultAddress($this->user['user_id']);
		$this->assign('address', $address);
		
		
		$this->assign('point',$point);
    	$this->display();
    }
    
	//生成积分兑换订单
	public function add_gift_orderAction(){
		$user_id=session('userid');
		$gift_id=I('gift_id')?I('gift_id'):I('get.gift_id');
		$params=array('gift_id'=>$gift_id,'user_id'=>$user_id);
		try{
			$this->pointGiftService()->orderCreateOrModify($params);
		}catch(\Exception $e){
			$this->ajaxReturn(array('error'=>1,'msg'=>$e->getMessage(),'error_code'=>$e->getCode()));
		}
		$this->ajaxReturn(array('error'=>0,'msg'=>'积分兑换商品成功'));
	}
	
	public function orderAction(){
		$page=I('p')?I('p'):I('get.p');
		$page=$page?$page:1;
		$size=6;
		$map=array('user_id'=>session('userid'));
		$params=array('page'=>$page,'pagesize'=>$size,'map'=>$map);
		$result=$this->pointGiftService()->orderPagerList($params);
		$pager=new Pager($result['count'],$size);
		$pager->setConfig('header','');
		$this->assign('list',$result['list']);
		$this->assign('page',$pager->show());
		
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
		$info=$this->pointGiftService()->getInfo($id);
		if(empty($info)){
			$this->error('礼品不存在');
		}
		$this->assign('info',$info);
		
		//添加地址返回
		session('exchange_back_url', __SELF__);
		
		//默认地址
		$address = $this->userAddressService()->findDefaultAddress($this->user['user_id']);
		$this->assign('address', $address);
		
		
		
		$this->display();
	}
	
	
	private function pointGiftService(){
		return D('PointGift', 'Service');
	}
	
	private function userAddressService(){
		return D('UserAddress','Service');
	}
	
	private function regionService(){
		return D('Region','Service');
	}
	
	
	
	
}