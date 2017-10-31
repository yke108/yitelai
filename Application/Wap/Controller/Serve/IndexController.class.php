<?php
namespace Wap\Controller\Serve;
use Wap\Controller\WapController;
use Common\Basic\Pager;
use Common\Logic\PointLogic;

class IndexController extends WapController {
	public function _initialize(){
		parent::_initialize();
		
		$this->assign('page_title', '家居服务');
    }
	
    public function indexAction(){
    	$get = I('get.');
    	$this->assign('get', $get);
    	$cat = $this->serveService()->getCat($get['cat_id']);
    	$this->assign('cat', $cat);
		$this->assign('cat_id',$get['cat_id']);
    	$p=I('p')?I('p'):I('get.p');
		$p=$p?$p:1;
		$params = array(
    		'page'=>$p,
    		'pagesize'=>6,
    		'cat_id'=>$get['cat_id'],
			'orderby'=>'sort_order desc,add_time desc',
    	);
    	$result = $this->serveService()->infoPagerList($params);
    	if ($result['count'] > 0){
    		$this->assign('list', $result['list']);
    	}
		$this->assign('catlist',$this->serveService()->catlist($params));
		
		if(IS_AJAX){
			$html=$this->renderPartial('Serve/Index/_index');
			$this->ajaxReturn(array('html'=>$html));
		}
		
		$this->display();
    }
	
    public function likeAction(){
    	if (empty($this->user['user_id'])) {
    		$this->error('请先登录', U('index/site/login'));
    	}
    	$post = I('post.');
    	try {
    		$post['type'] = 1;
    		$post['user_id'] = $this->user['user_id'];
    		$good_num = $this->serveService()->infoLike($post);
    			
    		//点赞获得积分
    		$point_config = $this->ConfigService()->findSystemConfigs('point','fkey,fval,ftitle,fdesc,field_type');
    		$params = array(
    				'user_id'=>$this->user['user_id'],
    				'point_old'=>$this->user['pay_points'],
    				'point'=>$point_config['likes']['fval'],
    				'type'=>PointLogic::PointTypeLike,
    				//'ref_user_id'=>$this->user['user_id'],
    				'ref_id'=>$post['serve_id']
    		);
    		try {
    			$this->pointService()->addUserPoint($params);
    		} catch (\Exception $e) {
    			$this->error($e->getMessage());
    		}
    			
    		$this->success('点赞成功', '', array('good_num'=>$good_num));
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    }
    
    public function clapAction(){
    	if (empty($this->user['user_id'])) {
    		$this->error('请先登录', U('index/site/login'));
    	}
    	$post = I('post.');
    	try {
    		$post['type'] = 2;
    		$post['user_id'] = $this->user['user_id'];
    		$bad_num = $this->serveService()->infoClap($post);
    		$this->success('拍砖成功', '', array('bad_num'=>$bad_num));
    	} catch (\Exception $e) {
    		$this->error($e->getMessage());
    	}
    }
    
	private function serveService(){
		return D('Serve', 'Service');
	}
	
	private function pointService(){
		return D('Point', 'Service');
	}
}