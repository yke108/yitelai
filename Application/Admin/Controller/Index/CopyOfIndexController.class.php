<?php
namespace Admin\Controller\Index;
use Admin\Controller\FController;
use Common\Basic\Pager;

class IndexController extends FController {
	public function _initialize(){
		$this->purviewCheck(false);
		parent::_initialize();

		$set = array(
			'in'=>'index',
			'ac'=>'index_index_index',
		);
		$this->sbset($set);
    }
	
    public function indexAction($id = 0){
    	/* $store=M('store')->where()->select();
    	 $this->assign('store',$store); */
    	$day = strtotime(date('Y-m-d'));
    	//根据分析获取每日的订单数
    	$map = array(
    			'pay_status'=>1,
    			'pay_time'=>array('gt', $day - 86400 * 60),
    	);
    	$post = I('post.');
    	$this->assign('post',$post);
    	if (!empty($post['distributor_id'])) {
    		$map['distributor_id'] = $post['distributor_id'];
    	}
    	if (!empty($post['start_time'])) {
    		$map['pay_time'][] = array('egt', strtotime($post['start_time']));
    	}
    	if (!empty($post['end_time'])) {
    		$map['pay_time'][] = array('elt', strtotime($post['end_time']) + 86400);
    	}
    	$order_amount=M('order_info')->where($map)->getField("SUM(order_amount)");
    	$order_count_c=M('order_info')->where($map)->count();
    	$list = M('order_info')
    	->field('FROM_UNIXTIME(pay_time,"%Y/%m/%d") shj, sum(order_amount) as amount, COUNT(*) as num,pay_time,sum(shipping_fee) shipping_fee')
    	->where($map)->group('shj')->order('pay_time desc')->select();
    	
    	foreach($list as $key=>$val){
    		$return_str="[\"".$val['shj']."\",".$val['amount'].']'.','.trim($return_str,',');
    		$return_price_amount="[\"".$val['shj']."\",".$val['amount'].']'.','.trim($return_price_amount,',');
    		$new_list[$val['shj']]=$val;
    		$new_list[$val['shj']]['pay_time_format']=date("Y-m-d",$val['pay_time']);
    	}
    	$this->assign('order_count_c',$order_count_c);
    	$this->assign('order_amount',($order_amount?$order_amount:0));
    	$this->assign('return_str',$return_str);
    	$this->assign('return_price_amount',$return_price_amount);
    	$this->assign('list', $list);
    	$this->assign('new_list',$new_list);
    	
    	//var_dump(json_encode($list));die();
    	
    	//店铺
    	$distributor_list = $this->distributorService()->getAllList();
    	$this->assign('distributor_list', $distributor_list);
    	
    	$this->display();
    }
	
    private function distributorService(){
    	return D('Distributor', 'Service');
    }
}