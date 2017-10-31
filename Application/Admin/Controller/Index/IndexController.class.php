<?php
namespace Admin\Controller\Index;
use Admin\Controller\FController;
use Common\Basic\Pager;
use Common\Basic\Status;

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
	
    public function indexAction(){
    	//总收入
    	$order_map = array('pay_status'=>Status::PayStatusPaid);
    	$params = array('map'=>$order_map);
    	$order_total_amount = $this->orderService()->getOrderAmount($params);
    	$this->assign('order_total_amount', $order_total_amount);
    	
    	//月总收入
    	$order_map = array('pay_status'=>Status::PayStatusPaid);
    	$order_map['_string'] = "FROM_UNIXTIME(add_time,'%Y-%m')=date_format(now(),'%Y-%m')";
    	$params = array('map'=>$order_map);
    	$order_month_amount = $this->orderService()->getOrderAmount($params);
    	$this->assign('order_month_amount', $order_month_amount);
    	
    	//年总收入
    	$order_map = array('pay_status'=>Status::PayStatusPaid);
    	$order_map['_string'] = "FROM_UNIXTIME(add_time,'%Y')=date_format(now(),'%Y')";
    	$params = array('map'=>$order_map);
    	$order_year_amount = $this->orderService()->getOrderAmount($params);
    	$this->assign('order_year_amount', $order_year_amount);
    	
    	//今天注册用户数
    	$user_map = array();
    	$user_map['_string'] = "FROM_UNIXTIME(reg_time,'%Y-%m-%d')=date_format(now(),'%Y-%m-%d')";
    	$today_users_count = $this->userService()->searchUsersCount($user_map);
    	$this->assign('today_users_count', $today_users_count);
    	
    	//活跃用户数
    	$user_map = array();
    	$user_map['_string'] = "FROM_UNIXTIME(last_login,'%Y-%m')=date_format(now(),'%Y-%m')";
    	$active_users_count = $this->userService()->searchUsersCount($user_map);
    	$this->assign('active_users_count', $active_users_count);
    	
    	//总用户数
    	$total_users_count = $this->userService()->searchUsersCount();
    	$this->assign('total_users_count', $total_users_count);
    	
    	//总订单数
    	$order_total_count = $this->orderService()->getOrdersCount();
    	$this->assign('order_total_count', $order_total_count);
    	
    	//月订单数
    	$order_map = array();
    	$order_map['_string'] = "FROM_UNIXTIME(add_time,'%Y-%m')=date_format(now(),'%Y-%m')";
    	$order_month_count = $this->orderService()->getOrdersCount($order_map);
    	$this->assign('order_month_count', $order_month_count);
    	
    	//订单统计图
    	$order_map = array();
    	$order_map['_string'] = "FROM_UNIXTIME(add_time,'%Y-%m')=date_format(now(),'%Y-%m')";
    	$datas = $this->orderService()->orderStatistics($order_map);
    	$data2 = $data3 = '';
    	$max_num = 0;
    	//补全月份
    	$list = $pay_list = array();
    	foreach ($datas['list'] as $v) {
    		$list[$v['shj']] = $v;
    	}
    	foreach ($datas['pay_list'] as $v) {
    		$pay_list[$v['shj']] = $v;
    	}
    	$days = date("t");
    	for($i=1;$i<=$days;$i++){
    		$i = $i < 10 ? '0'.$i : $i;
    		$day = date("Y/m").'/'.$i;
    		if (empty($list[$day])) {
    			$list[$day] = array(
    					'shj'=>$day,
    					'amount'=>0,
    					'num'=>0,
    					'add_time'=>strtotime($day)
    			);
    		}
    		if (empty($pay_list[$day])) {
    			$pay_list[$day] = array(
    					'shj'=>$day,
    					'amount'=>0,
    					'num'=>0,
    					'add_time'=>strtotime($day)
    			);
    		}
    	}
    	ksort($list);
    	ksort($pay_list);
    	
    	foreach ($list as $v) {
    		$max_num = ($v['num'] > $max_num) ? $v['num'] : $max_num;
    		$data3 .= '[gd('.date('Y, m, d', $v['add_time']).'), '.$v['num'].'],';
    	}
    	foreach ($pay_list as $v) {
    		$data2 .= '[gd('.date('Y, m, d', $v['add_time']).'), '.$v['num'].'],';
    	}
    	$data2 = '['.trim($data2, ',').']';
    	$data3 = '['.trim($data3, ',').']';
    	
    	$this->assign('data2',$data2);
    	$this->assign('data3',$data3);
    	$this->assign('max_num',$max_num);
    	
    	//订单列表
    	$params = array();
    	$datas = $this->orderService()->getOrderList($params, 1, $this->pagesize);
    	$this->assign('order_list', $datas['list']);
    	
    	$this->display();
    }
	
   public function columnAction(){
   		$get = I('get.');
   		session('column_id', $get['column_id']);
   		$menulist= $this->systemService()->menuList($this->sys_id, session('action_list'), session('column_id'));
   		reset($menulist);
   		$curmenu = current($menulist);
   		$curmenu = current($curmenu['itm']);
   		$this->redirect($curmenu['url']);
   }
    
}