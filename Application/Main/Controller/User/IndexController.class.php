<?php
namespace Main\Controller\User;
use Main\Controller\MainController;
use Common\Basic\Status;

class IndexController extends MainController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
		
		$this->assign('page_title', '个人中心');
    }
	
    public function indexAction(){
    	//未读消息
    	$map = array(
    			'type'=>0,
    			'add_time'=>array('gt',$this->user['reg_time'])
    	);
    	$message_status = M('message_status')->where(array('user_id'=>$this->user['user_id']))->getField('id, msg_id');
    	if ($message_status) {
    		$map['msg_id'] = array('not in', $message_status);
    	}
    	$system_count = $this->messageService()->searchMessagesCount($map);
    	$map = array(
    			'type'=>1,
    			'user_id'=>$this->user['user_id'],
    	);
    	if ($message_status) {
    		$map['msg_id'] = array('not in', $message_status);
    	}
    	$private_count = $this->messageService()->searchMessagesCount($map);
    	$noread = $system_count + $private_count;
    	$this->assign('noread', $noread);
    	
    	//订单统计
    	$params = array(
    			'user_id'=>$this->user['user_id']
    	);
    	$uon = $this->orderService()->getOrderCount($params);
    	$this->assign('uon', $uon);
    	
    	//未付款订单
    	$map = array(
    			'pay_status'=>Status::PayStatusNone,
    			'order_status'=>Status::OrderStatusNone,
    			'user_id'=>$this->user['user_id']
    	);
    	$params['map'] = $map;
    	$order_datas = $this->orderService()->getOrderList($params, 1, 3);
    	$this->assign('order_list', $order_datas['list']);
    	
    	//购物车
    	$params = array(
    			'user_id'=>$this->user['user_id']
    	);
    	$cart_list = $this->cartService()->getAllList($params);
    	$this->assign('cart_list', $cart_list);
    	
    	//商品收藏
    	$params = array(
    			'user_id'=>$this->user['user_id'],
    			'page'=>1,
    			'pagesize'=>5
    	);
    	$collect_datas = $this->collectService()->getCollectGoodsList($params);
    	$this->assign('collect_list', $collect_datas['list']);
    	
    	//推荐品类
    	$map = array(
			'a.product_num'=>array('gt', 0)
		);
		$params = array(
				'pagesize'=>4,
				'map'=>$map,
				'orderby'=>'rand()'
		);
    	$datas = $this->distributorGoodsService()->getPagerList($params);
    	$this->assign('recommend_list', $datas['list']);
    	
		$this->display();
    }
	
	public function infoAction(){
		if (IS_POST) {
			$post = I('post.');
			extract($post);
		
			$data = array();
			isset($sex) && $data['sex'] = $sex;
			isset($nick_name) && $data['nick_name'] = $nick_name;
			isset($birthday) && $data['birthday'] = $birthday;
			if($_FILES['user_img']['name']){
			    $upload = new \Think\Upload(); // 实例化上传类
			    $upload->maxSize   =     31457280 ;// 设置附件上传大小
			    $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg',); // 设置附件上传类型
			    $upload->rootPath  =     UPLOAD_PATH; //'./Uploads/'; // 设置附件上传根目录
			    $upload->savePath  =     $savepath; // 设置附件上传（子）目录
				$upload->subName   =     array('date', 'Ym'); 
				$info = $upload->uploadOne($_FILES['user_img']);
				if(!$info) $this->error($upload->getError());
				$data['user_img'] = $info['savepath'].$info['savename'];
			}

			if(empty($data) || $this->userService()->modify($this->user, $data) === false){
				$this->error('操作失败');
			}
			$this->success('资料修改成功', U('user/index'));
		}
		$this->display();
	}
	
	public function recommendAction() {
		$map = array(
    			'a.product_num'=>array('gt', 0),
    			'c.status'=>Status::DistributorStatusNormal,
    			'_string'=>'record_id >= (
		(SELECT MAX(record_id) FROM hy_distributor_goods) - (SELECT MIN(record_id) FROM hy_distributor_goods)
	) * RAND() + (SELECT MIN(record_id) FROM hy_distributor_goods)',
    	);
		//推荐品类
		$params = array(
				'pagesize'=>4,
				'map'=>$map,
				'orderby'=>'rand()'
		);
		$datas = $this->distributorGoodsService()->getPagerList($params);
		if(empty($datas['list'])){
			$clist = '';
		}else{
			$this->assign('recommend_list', $datas['list']);
			$clist = $this->renderPartial('recommend');
		}
		$this->ajaxReturn($clist);
	}
	
	//申请分销员
	public function apply_salemanAction(){
		
		if(IS_POST){
			$params=array(
						'user_id'=>$this->user['user_id'],
						'type'=>2,
					);
			try{
				$this->salemanService()->infoCreateOrModify($params);
			}catch(\Exception $e){
				$this->error($e->getMessage());
			}
			$this->success('提交申请成功');
			die();
		}
		
		//var_dump($this->user);die();
		if($this->user['user_type']!=2){
		//	$this->redirect('user/index/index');
		}
		$this->assign('info',$this->user);
		$apply_info=$this->salemanService()->userGetInfo($this->user['user_id']);
		$this->assign('apply_info',$apply_info);
		$this->display();
	}
	
	private function orderService() {
		return D('Order', 'Service');
	}
	
	private function collectService() {
		return D('Collect', 'Service');
	}
	
	private function messageService() {
		return D('Message', 'Service');
	}
	
	private function distributorGoodsService(){
		return D('Distributor\Goods', 'Service');
	}
	
	private function salemanService(){
		return D('Saleman','Service');
	}
	
}
