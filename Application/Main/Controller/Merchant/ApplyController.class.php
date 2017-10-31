<?php
namespace Main\Controller\Merchant;
use Main\Controller\MainController;
use Common\Basic\Status;
use Common\Payment\WeixinPay\AppPay;

class ApplyController extends MainController {
	public $merchant_info;
	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
		
		//商家入驻设置
		$merchant_config = $this->configService()->getConfigs('merchant');
		$this->assign('merchant_config', $merchant_config);
		
		$map = array(
				'user_id'=>$this->user['user_id'],
				//'status'=>Status::MerchantStatusOn,
		);
		$this->merchant_info = $this->merchantService()->searchInfo($map);
		$this->assign('info', $this->merchant_info);
    }
    
    public function step1Action(){
    	$map = array('page_type'=>Status::PageTypeMerchant);
    	$pages = $this->pageService()->infoAllList($map);
    	$this->assign('pages', $pages);
    	
    	$this->display();
    }
	
    public function step2_1Action(){
    	if (IS_POST) {
    		$post = I('post.');
    		$post['user_id'] = $this->user['user_id'];
    		$post['merchant_id'] = $post['id'];
    		$post['step'] = '2_2';
    		try {
    			$this->merchantService()->createOrModify($post);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		$this->success('保存成功，继续下一步', U('step2_2'));
    	}
    	
    	//省市区
    	$this->assign('region_list', $this->regionService()->getAllRegions(false));
    	
    	$this->display();
    }
    
    public function step2_2Action(){
    	if (IS_POST) {
    		$post = I('post.');
    		$post['user_id'] = $this->user['user_id'];
    		$post['merchant_id'] = $post['id'];
    		$post['step'] = '2_3';
    		try {
    			$this->merchantService()->createOrModify($post);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		$this->success('保存成功，继续下一步', U('step2_3'));
    	}
    	
    	//省市区
    	$this->assign('region_list', $this->regionService()->getAllRegions(false));
    	
    	$this->display();
    }
    
    public function step2_3Action(){
    	if (IS_POST) {
    		$post = I('post.');
    		$post['user_id'] = $this->user['user_id'];
    		$post['merchant_id'] = $post['id'];
    		$post['step'] = '3_1';
    		try {
    			$this->merchantService()->createOrModify($post);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		$this->success('保存成功，继续下一步', U('step3_1'));
    	}
    	
    	$this->display();
    }
    
    public function step3_1Action(){
    	if (IS_POST) {
    		//类目不能为空
    		$map = array('merchant_id'=>$this->merchant_info['merchant_id'], 'user_id'=>$this->user['user_id']);
    		$cat_info = $this->merchantCatService()->findInfo($map);
    		if (empty($cat_info)) {
    			$this->error('请添加类目');
    		}
    		
    		$post = I('post.');
    		$post['user_id'] = $this->user['user_id'];
    		$post['merchant_id'] = $post['id'];
    		$post['step'] = '3_2';
    		try {
    			$this->merchantService()->createOrModify($post);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		$this->success('保存成功，继续下一步', U('step3_2'));
    	}
    	 
    	//店铺类型
    	$type_list = $this->distributorTypeService()->getFieldList();
    	$this->assign('type_list', $type_list);
    	
    	//当前二级
    	$child = array();
    	if ($this->merchant_info['type_id'] > 0) {
    		foreach ($type_list as $v) {
    			foreach ($v['children'] as $v2) {
    				if ($v2['type_id'] == $this->merchant_info['type_id']) {
    					$current_type = $v;
    					$child = $v2;
    					break;
    				}
    			}
    		}
    	}else {
    		$current_type = current($type_list);
    		$child = $current_type['children'][0];
    	}
    	$this->assign('current_type', $current_type);
    	$this->assign('child', $child);
    	
    	//顶级分类
    	$map = array('parent_id'=>0);
    	$cat_top_list = $this->goodsCatService()->selectAllList($map);
    	$this->assign('cat_top_list', $cat_top_list);
    	
    	if ($this->merchant_info['cat_id']) {
    		$sub_list = $this->goodsCatService()->getChildsData($this->merchant_info['cat_id']);
    	}else {
    		$current_cat = current($this->cat_list);
    		$sub_list = $this->goodsCatService()->getChildsData($current_cat['cat_id']);
    	}
    	$this->assign('sub_list', $sub_list);
    	
    	//已添加的分类
    	$map = array('merchant_id'=>$this->merchant_info['merchant_id'], 'user_id'=>$this->user['user_id']);
    	$merchant_cat_list = $this->merchantCatService()->getAllList($map);
    	$this->assign('merchant_cat_list', $merchant_cat_list);
    	
    	if ($merchant_cat_list) {
    		$merchant_cat_ids = array();
    		foreach ($merchant_cat_list as $v) {
    			$merchant_cat_ids[] = $v['cat_id'];
    		}
    		$this->assign('merchant_cat_ids', $merchant_cat_ids);
    	}
    	
    	$this->display();
    }
    
    public function step3_2Action(){
    	if (IS_POST) {
    		//品牌不能为空
    		$map = array('merchant_id'=>$this->merchant_info['merchant_id'], 'user_id'=>$this->user['user_id']);
    		$brand_info = $this->merchantBrandService()->findInfo($map);
    		if (empty($brand_info)) {
    			$this->error('请添加品牌');
    		}
    		
    		$post = I('post.');
    		$post['user_id'] = $this->user['user_id'];
    		$post['merchant_id'] = $post['id'];
    		$post['step'] = '3_4';
    		try {
    			$this->merchantService()->createOrModify($post);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		$this->success('保存成功，继续下一步', U('step3_4'));
    	}
    	
    	//品牌列表
    	$map = array('merchant_id'=>$this->merchant_info['merchant_id']);
    	$brand_list = $this->merchantBrandService()->getAllList($map);
    	$this->assign('brand_list', $brand_list);
    	
    	//省市区
    	$this->assign('region_list', $this->regionService()->getAllRegions(false));
    
    	$this->display();
    }
    
    public function step3_3Action($id = 0){
    	$brand = $this->merchantBrandService()->getInfo($id);
    	
    	if (IS_POST) {
    		$post = I('post.');
    		$post['user_id'] = $this->user['user_id'];
    		$post['merchant_id'] = $post['id'];
    		try {
    			$this->merchantBrandService()->createOrModify($post);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		$this->success('保存成功', U('step3_2'));
    	}
    	
    	$this->assign('brand', $brand);
    	
    	$this->display();
    }
    
    public function step3_4Action(){
    	if (IS_POST) {
    		$post = I('post.');
    		$post['user_id'] = $this->user['user_id'];
    		$post['merchant_id'] = $post['id'];
    		$post['status'] = 0;
    		$post['step'] = 4;
    		try {
    			$this->merchantService()->createOrModify($post);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		
    		//申请日志
    		$map = array(
    				'merchant_id'=>$post['id'],
    				'user_id'=>$this->user['user_id'],
    		);
    		$merchant_log = $this->merchantLogService()->findInfo($map);
    		if (empty($merchant_log)) {
    			$params = array(
    					'merchant_id'=>$post['id'],
    					'user_id'=>$this->user['user_id'],
    					'content'=>'商家提交审核申请',
    					'result_content'=>$this->user['nick_name'].'-提交资质审核',
    					'add_time'=>NOW_TIME
    			);
    			try {
    				$this->merchantLogService()->createOrModify($params);
    			} catch (\Exception $e) {
    				$this->error($e->getMessage());
    			}
    		}else {
    			//修改资料日志
    			$params = array(
    					'merchant_id'=>$post['id'],
    					'user_id'=>$this->user['user_id'],
    					'content'=>'商家提交审核申请',
    					'result_content'=>$this->user['nick_name'].'-修改资料',
    					'add_time'=>NOW_TIME
    			);
    			try {
    				$this->merchantLogService()->createOrModify($params);
    			} catch (\Exception $e) {
    				$this->error($e->getMessage());
    			}
    		}
    		
    		$this->success('保存成功，继续下一步', U('step4'));
    	}
    	
    	//伊特莱健康家居用户协议
    	$page_info = $this->pageService()->getInfo(38);
    	$this->assign('page_info', $page_info);
    	
    	$this->display();
    }
    
    public function step4Action(){
    	if (IS_POST) {
    		$post = I('post.');
    		$post['user_id'] = $this->user['user_id'];
    		$post['merchant_id'] = $post['id'];
    		$post['step'] = '5';
    		try {
    			$this->merchantService()->createOrModify($post);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		
    		$this->success('提交成功', U('step5'));
    	}
    	
    	//最新的申请
    	$map = array('user_id'=>$this->user['user_id']);
    	$this->merchant_info = $this->merchantService()->searchInfo($map);
    	$this->assign('info', $this->merchant_info);
    	
    	//审核日志
    	$map = array('merchant_id'=>$this->merchant_info['merchant_id'], 'user_id'=>$this->user['user_id']);
    	$log_list = $this->merchantLogService()->getAllList($map);
    	$this->assign('log_list', $log_list);
    	
    	$this->display();
    }
    
    public function step5Action(){
    	$this->getPassMerchant();
    	
    	$this->assign('background_url', DK_DOMAIN.'/distributor');
    	$this->display();
    }
    
    public function step6Action(){
    	$this->getPassMerchant();
    	
    	$this->assign('background_url', DK_DOMAIN.'/distributor');
    	$this->display();
    }
    
    public function branddelAction($id = 0){
    	$map = array('brand_id'=>$id, 'user_id'=>$this->user['user_id']);
    	try {
    		$this->merchantBrandService()->delete($map);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	$this->success('删除成功');
    }
    
    public function cancelAction($id = 0){
    	$map = array(
    			'merchant_id'=>$id,
    			'user_id'=>$this->user['user_id'],
    	);
    	$data = array(
    			'status'=>Status::MerchantStatusCancel
    	);
    	try {
    		$this->merchantService()->updateInfo($map, $data);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	
    	//申请日志
    	$params = array(
    			'merchant_id'=>$id,
    			'user_id'=>$this->user['user_id'],
    			'content'=>'商家撤消申请',
    			'result_content'=>$this->user['nick_name'].'-撤消申请',
    			'add_time'=>NOW_TIME
    	);
    	try {
    		$this->merchantLogService()->createOrModify($params);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	
    	$this->success('撤消成功');
    }
    
    public function sub_catAction($cat_id = 0){
    	$list = $this->goodsCatService()->getChildsData($cat_id);
    	if(empty($list)){
    		$clist = '';
    	}else{
    		$this->assign('sub_list', $list);
    		$clist = $this->renderPartial('sub_cat');
    	}
    	$this->ajaxReturn($clist);
    }
    
    public function add_catAction($cat_id = 0){
    	$post = I('post.');
    	$post['user_id'] = $this->user['user_id'];
    	$post['merchant_id'] = $this->merchant_info['merchant_id'];
    	$post['cat_id'] = $this->merchant_info['cat_id'];
    	try {
    		$this->merchantCatService()->addCat($post);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	$this->success('添加成功');
    }
    
    public function catdelAction($id = 0){
    	$map = array('id'=>$id, 'user_id'=>$this->user['user_id']);
    	try {
    		$this->merchantCatService()->delete($map);
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    	$this->success('删除成功');
    }
    
    public function pay_depositAction(){
    	$this->getPassMerchant();
    	
    	if (IS_POST) {
    		$post = I('post.');
    		
    		if (empty($this->user)) {
    			$this->error('请先登录', U('index/site/login'));
    		}
    		if ($post['pay_id'] == '') {
    			$this->error('请选择支付方式');
    		}
    		if (!in_array($post['pay_id'], array(0,1,2))) {
    			$this->error('支付方式不正确');
    		}
    		
    		$post['merchant_id'] = $this->merchant_info['merchant_id'];
    		$post['deposit'] = $this->merchant_info['deposit'];
    		$post['user_id'] = $this->user['user_id'];
    		if($post['pay_id'] == 1) { //余额支付
    			try {
    				$result = $this->merchantService()->payDeposit($post);
    			} catch (\Exception $e) {
    				$this->error($e->getMessage());
    			}
    			$this->success('支付成功', U('pay_deposit_success'));
    		} elseif ($post['pay_id'] == 2) { //微信支付
    			$this->success('正在转向微信支付页面', U('depositpay'));
    		} elseif ($post['pay_id'] == 3) { //支付宝支付
    		   
    		}
    	}
    	
    	$this->display();
    }
    
    //微信支付
    public function depositpayAction($id = 0){
    	$this->getPassMerchant();
    	
    	header("Content-type:text/html;charset=utf-8");
    	//$wxconf = \Common\ApiConfig\Weixin::jsapiConfig();
    	$wxconf = $this->configService()->findWeixinConfigs();
    	$unifiedOrder = new AppPay($wxconf['js_app_id'], $wxconf['mchid'], $wxconf['key']);
    	$unifiedOrder->setParameter("body", "支付保证金");//商品描述
    	$unifiedOrder->setParameter("out_trade_no", $this->merchant_info['merchant_id']);//商户订单号
    	$unifiedOrder->setParameter("total_fee", $this->merchant_info['deposit'] * 100);//总金额
    	$unifiedOrder->setParameter("notify_url", DK_DOMAIN.'/weixin/service.php');//通知地址
    	$unifiedOrder->setParameter("trade_type","NATIVE");//交易类型
    	$unifiedOrder->setParameter("spbill_create_ip", get_client_ip());//交易类型
    	$result = $unifiedOrder->getResult();
    	$code_url = DK_DOMAIN.'/qrc/?data='.$result['code_url'];
    	$this->assign('code_url', $code_url);
    	
    	$this->display();
    }
    
    public function depositpaychkAction() {
    	$this->getPassMerchant();
    	
    	if ($this->merchant_info['deposit_pay'] == 1) {
    		$this->success('支付成功', U('pay_deposit_success'));
    	}
    	$this->error('未支付');
    }
	
    public function pay_deposit_successAction() {
    	$this->getPassMerchant();
    	 
    	$this->display();
    }
    
    public function pay_serviceAction(){
    	$this->getPassMerchant();
    	 
    	if (IS_POST) {
    		$post = I('post.');
    
    		if (empty($this->user)) {
    			$this->error('请先登录', U('index/site/login'));
    		}
    		if ($post['pay_id'] == '') {
    			$this->error('请选择支付方式');
    		}
    		if (!in_array($post['pay_id'], array(0,1,2))) {
    			$this->error('支付方式不正确');
    		}
    
    		$post['merchant_id'] = $this->merchant_info['merchant_id'];
    		$post['service_charge'] = $this->merchant_info['service_charge'];
    		$post['user_id'] = $this->user['user_id'];
    		if($post['pay_id'] == 1) { //余额支付
    			try {
    				$result = $this->merchantService()->payService($post);
    			} catch (\Exception $e) {
    				$this->error($e->getMessage());
    			}
    			$this->success('支付成功', U('pay_service_success'));
    		} elseif ($post['pay_id'] == 2) { //微信支付
    			$this->success('正在转向微信支付页面', U('servicepay'));
    		} elseif ($post['pay_id'] == 3) { //支付宝支付
    
    		}
    	}
    	 
    	$this->display();
    }
    
    //微信支付
    public function servicepayAction($id = 0){
    	$this->getPassMerchant();
    	
    	header("Content-type:text/html;charset=utf-8");
    	//$wxconf = \Common\ApiConfig\Weixin::jsapiConfig();
    	$wxconf = $this->configService()->findWeixinConfigs();
    	$unifiedOrder = new AppPay($wxconf['js_app_id'], $wxconf['mchid'], $wxconf['key']);
    	$unifiedOrder->setParameter("body", "支付技术服务费");//商品描述
    	$unifiedOrder->setParameter("out_trade_no", $this->merchant_info['merchant_id']);//商户订单号
    	$unifiedOrder->setParameter("total_fee", $this->merchant_info['service_charge'] * 100);//总金额
    	$unifiedOrder->setParameter("notify_url", DK_DOMAIN.'/weixin/deposit.php');//通知地址
    	$unifiedOrder->setParameter("trade_type","NATIVE");//交易类型
    	$unifiedOrder->setParameter("spbill_create_ip", get_client_ip());//交易类型
    	$result = $unifiedOrder->getResult();
    	$code_url = DK_DOMAIN.'/qrc/?data='.$result['code_url'];
    	$this->assign('code_url', $code_url);
    	
    	$this->display();
    }
    
    public function servicepaychkAction() {
    	$this->getPassMerchant();
    	
    	if ($this->merchant_info['deposit_pay'] == 1) {
    		$this->success('支付成功', U('pay_service_success'));
    	}
    	$this->error('未支付');
    }
    
    public function pay_service_successAction() {
    	$this->getPassMerchant();
    	
    	$this->display();
    }
    
    public function failAction() {
    	$this->getPassMerchant();
    	
    	$this->display();
    }
    
    private function getPassMerchant() {
    	$map = array('user_id'=>$this->user['user_id'], 'status'=>Status::MerchantStatusPass);
    	$this->merchant_info = $this->merchantService()->searchInfo($map);
    	if (empty($this->merchant_info)) {
    		$this->error('请先等待审核通过');
    	}
    	$this->assign('info', $this->merchant_info);
    }
    
    public function downloadAction($id = 0){
    	$info=D('Guide','Service')->getInfo($id);
    	if(empty($info) || $info['file_path']==''){
    		exit('文件不存在！');
    	}
    	$file = UPLOAD_PATH.$info['file_path'];
    	$file_name = $info['file_name'];
    
    	if(is_file($file)){
    		$length = filesize($file);
    		$file_info=pathinfo($file);
    		$file_name=($file_name==''?time().'.'.$file_info['extension']:$file_name);
    		$showname = $file_name; //ltrim(strrchr($file,'/'),'/');
    		header("Content-Description: File Transfer");
    		header('Content-type: ' . $file_info['type']);
    		header('Content-Length:' . $length);
    		if (preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT'])) { //for IE
    			header('Content-Disposition: attachment; filename="' . rawurlencode($showname) . '"');
    		} else {
    			header('Content-Disposition: attachment; filename="' . $showname . '"');
    		}
    		readfile($file);
    		exit;
    	} else {
    		exit('文件不存在！');
    	}
    }
    
    private function pageService(){
    	return D('Page', 'Service');
    }
    
    private function regionService(){
    	return D('Region', 'Service');
    }
    
    private function merchantService(){
    	return D('Merchant', 'Service');
    }
    
    private function merchantBrandService(){
    	return D('MerchantBrand', 'Service');
    }
    
    private function merchantLogService(){
    	return D('MerchantLog', 'Service');
    }
    
    private function distributorTypeService(){
    	return D('Distributor\Type', 'Service');
    }
    
    private function merchantCatService(){
    	return D('MerchantCat', 'Service');
    }
}