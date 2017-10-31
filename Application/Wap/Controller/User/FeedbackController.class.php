<?php
namespace Wap\Controller\User;
use Wap\Controller\WapController;
use Common\Basic\Status;

class FeedbackController extends WapController {	
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
    		$post['client'] = Status::FeedbackClientWeixin;
    		try {
    			$result = $this->feedbackService()->createOrModify($post);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    		$this->success('提交成功', U('user/index/index'));
    	}
    	
    	//品牌列表
    	$brand_list = $this->goodsBrandService()->getAllList();
    	$this->assign('brand_list', $brand_list);
    	 
    	//门店列表
    	$distributor_list = $this->distributorService()->getAllList();
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
}
