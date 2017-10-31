<?php
namespace Main\Controller\User;
use Main\Controller\MainController;
use Common\Basic\Status;

class FeedbackController extends MainController {	
	public function _initialize(){
		$this->login_check = true;
		parent::_initialize();
    }
	
    public function indexAction(){
    	if (IS_POST) {
    		$post = I('post.');
    		//处理上传图片
    		if (count($post['image_datas']) > 3) {
    			$this->error('最多可上传3张图片');
    		}
    		$images = createBase64Image($post['image_datas']);
    		if ($images) {
    			$post['pictures'] = implode(',', $images);
    		}
    		$post['user_id'] = session('userid');
    		$post['client'] = Status::FeedbackClientPc;
    		try {
    			$result = $this->feedbackService()->createOrModify($post);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		
    		//用户投诉店铺要扣积分（OA端需要审核）
    		if ($post['distributor_id'] && $post['type'] == 1) {
    			$params = array(
    					'point'=>1,
    					'distributor_id'=>$post['distributor_id'],
    					'ref_id'=>$post['user_id'],
    					'ref_type'=>Status::RefTypeUser,
    					'type_id'=>3,
    			);
    			if ($images) {
    				$params['pictures'] = implode(',', $images);
    			}
    			try {
    				$this->distributorFineService()->createFine($params);
    			} catch (\Exception $e) {
    				$this->error($e->getMessage());
    			}
    		}
    		
    		$this->success('提交成功');
    	}
    	
    	//反馈类型
    	$this->assign('type_list', Status::$feedbackTypeList);
    	
    	//品牌列表
    	$brand_list = $this->goodsBrandService()->getAllList();
    	$this->assign('brand_list', $brand_list);
    	
    	//门店列表
		$map = array('status'=>Status::DistributorStatusNormal);
    	$distributor_list = $this->distributorService()->getAllList($map);
    	$this->assign('distributor_list', $distributor_list);
    	
    	$this->assign('wx_title', '投诉建议');
		$this->display();
    }
    
    private function feedbackService() {
    	return D('Feedback', 'Service');
    }
    
    private function goodsBrandService() {
    	return D('GoodsBrand', 'Service');
    }
    
    private function distributorService() {
    	return D('Distributor', 'Service');
    }
    
    private function distributorFineService(){
    	return D('Distributor\Fine', 'Service');
    }
}
