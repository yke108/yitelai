<?php
namespace Home\Controller\Platform;
use Home\Controller\BaseController;

class UserController extends BaseController {	
	public function _initialize(){
		parent::_initialize();
		
		$user_type = \Common\Basic\User::$user_type;
		$this->assign('user_type', $user_type);
    }
    
    protected function _purviewCheck(){
    	$this->purviewCheck('index');
    }
	
    public function indexAction() { /*人员管理 */
    	$result = M()->query("select FROM_UNIXTIME(`add_time`, '%Y%m') months, SUM(goods_amount) goods_amount, SUM(order_amount) order_amount from hy_order_info where pay_status=1 GROUP BY months");
    	$list = array();
    	if ($result) {
    		foreach ($result as $v) {
    			$year = substr($v['months'], 0, 4);
    			$month = substr($v['months'], -2);
    			
    			//购买会员
    			$order_list = M()->query("select distinct user_id from hy_order_info where FROM_UNIXTIME(`add_time`, '%Y%m') = '".$year.$month."'");
    			$user_ids = array();
    			foreach ($order_list as $v2) {
    				$user_ids[] = $v2['user_id'];
    			}
    			$params = array(
    					'page'=>1,
    					'pagesize'=>3,
    					'map'=>array('user_id'=>array('in', $user_ids))
    			);
    			$datas = $this->userService()->userPagerList($params);
    			$user_list = array();
    			foreach ($datas['list'] as $v2) {
    				$user_list[] = $v2['avatar'];
    			}
    			
    			$list[$year][$month] = array(
    					'goods_amount'=>$v['goods_amount'],
    					'order_amount'=>$v['order_amount'],
    					'user_list'=>$user_list,
    			);
    		}
    	}
    	$this->assign('list', $list);
    	
    	$this->assign('page_title', '人员管理');
		$this->display();
    }
    
    public function listAction() { /*NoPurview*/
    	$get = I('get.');
    	$this->assign('get', $get);
    	
    	$params = array(
    			'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
    			'pagesize'=>$this->pagesize,
    	);
    	if ($get['keyword']) {
    		$params['keyword'] = $get['keyword'];
    	}
    	$result = $this->userService()->userPagerList($params);
    	$this->assign('list', $result['list']);
    	$this->assign('count', $result['count']);
    	
    	if (IS_AJAX) {
    		if(empty($result['list'])){
    			$clist = '';
    		}else{
    			$clist = $this->renderPartial('_list');
    		}
    		$this->ajaxReturn(array('html'=>$clist, 'p'=>$params['page']+1));
    	}
    	
    	$this->assign('page_title', '人员列表');
    	$this->display();
    }
    
    public function infoAction($user_id = 0) { /*NoPurview*/
    	$info = $this->userService()->getUserInfo($user_id);
    	if (empty($info)) $this->error('用户不存在');
    	$this->assign('info', $info);
    	
    	$this->assign('page_title', '人员列表-详情');
    	$this->display();
    }
}