<?php

namespace Home\Controller\Target;

use Home\Controller\BaseController;
use Common\Basic\Status;

class IndexController extends BaseController {
	protected $p_sys_id = 1;
	protected $p_org_id = 1;
	protected $d_sys_id = 2;
	protected $d_org_id = 2;
	
	public function _initialize() {
		parent::_initialize ();
		// session('userid',1);
	}
	protected function _purviewCheck() {
		$this->purviewCheck ( 'index' );
	}
	public function indexAction() /*工作目标*/
	{
		// 天
		$day = $this->workService ()->myCurrentwork ( session ( 'uid' ), 1 );
		$this->assign ( 'day', $day );
		
		// 周
		$week = $this->workService ()->myCurrentwork ( session ( 'uid' ), 2 );
		$this->assign ( 'week', $week );
		
		// 月
		$month = $this->workService ()->myCurrentwork ( session ( 'uid' ), 3 );
		$this->assign ( 'month', $month );
		
		// 年
		$year = $this->workService ()->myCurrentwork ( session ( 'uid' ), 4 );
		$this->assign ( 'year', $year );
		
		// 管理员
		$admin = $this->adminService ()->getAdmin ( session ( 'uid' ) );
		$this->assign ( 'admin', $admin );
		
		// 店铺
		$map = array (
				'distributor_id' => session ( 'distributor_id' ) 
		);
		$distributor = $this->distributorService ()->getInfo ( $map );
		$this->assign ( 'distributor', $distributor );
		
		$this->assign ( 'page_title', '工作目标管理' );
		$this->display ();
	}
	public function addAction() /*NoPurview*/
	{
		if (IS_POST) {
			if (session ( 'sys_id' ) != 2) {
				// $this->error('权限不够，无法操作');
			}
			$post = I ( 'post.' );
			$post ['admin_id'] = session ( 'uid' );
			$post ['distributor_id'] = session ( 'distributor_id' );
			$post ['time'] = date ( 'Y-m-d H:i:s' );
			
			switch ($post ['type']) {
				case Status::WorkTargetDay :
					$start = strtotime ( date ( 'Y-m-d' ) );
					$end = strtotime ( ' + 1 day' );
					$post ['day'] = date ( 'Ymd' );
					break; // 天
				case Status::WorkTargetWeek :
					// $start = date ( 'Y-m-d H:i:s' );
					// $end = date ( 'Y-m-d H:i:s', strtotime ( $start . ' + 1 week' ) );
					$start = mktime ( 0, 0, 0, date ( 'm' ), date ( 'd' ) - date ( 'w' ) + 1, date ( 'Y' ) );
					$end = mktime ( 23, 59, 59, date ( 'm' ), date ( 'd' ) - date ( 'w' ) + 7, date ( 'Y' ) );
					$post ['week'] = date ( 'W', time () );
					break; // 周
				case Status::WorkTargetMonth :
					// $start = date ( 'Y-m-d H:i:s' );
					// $end = date ( 'Y-m-d H:i:s', strtotime ( $start . ' + 1 month' ) );
					$start = mktime ( 0, 0, 0, date ( 'm' ), 1, date ( 'Y' ) );
					$end = mktime ( 23, 59, 59, date ( 'm' ), date ( 't' ), date ( 'Y' ) );
					$post ['month'] = date ( 'Ym' );
					break; // 月
				case Status::WorkTargetYear :
					// $start = date ( 'Y-m-d H:i:s' );
					// $end = date ( 'Y-m-d H:i:s', strtotime ( $start . ' + 1 year' ) );
					$start = mktime ( 0, 0, 0, 1, 1, date ( "Y", time() ) );
					$end = mktime ( 23, 59, 59, 12, 31, date ( "Y", time() ) );
					$post ['year'] = date ( 'Y' );
					break; // 年
			}
			$post ['start'] = $start;
			$post ['end'] = $end;
			
			M()->startTrans();
			
			$result = $this->workService ()->add ( $post );
			if ($result === false) {
				M()->rollback();
				$this->error('系统错误');
			}
			
			// 计算每项的完成率和总完成率
			if ($post ['type'] != Status::WorkTargetDay) {
				// 计算周目标
				$week_info = $this->workService ()->mywork ( session ( 'uid' ), Status::WorkTargetWeek );
				if ($week_info) {
					//当前周的所有天目标
					$current_week = date('W', time());
					$map = array('type'=>Status::WorkTargetDay);
					$map['_string'] = "WEEKOFYEAR(time) = $current_week";
					$day_list = $this->workService()->getworklist($map);
					$data_week['id'] = $week_info['id'];
					foreach ($day_list as $v) {
						$data_week ['true_user_add'] += $v ['true_user_add'];
						$data_week ['true_consumer_add'] += $v ['true_consumer_add'];
						$data_week ['true_invitation'] += $v ['true_invitation'];
						$data_week ['true_sign_up'] += $v ['true_sign_up'];
						$data_week ['true_deal'] += $v ['true_deal'];
						$data_week ['true_sales'] += $v ['true_sales'];
					}
					$data_week ['user_percent'] = floor ( ($data_week ['true_user_add'] / $week_info ['plan_user_add']) * 100 );
					$data_week ['consumer_percent'] = floor ( ($data_week ['true_consumer_add'] / $week_info ['plan_consumer_add']) * 100 );
					$data_week ['invitation_percent'] = floor ( ($data_week ['true_invitation'] / $week_info ['plan_invitation']) * 100 );
					$data_week ['sign_percent'] = floor ( ($data_week ['true_sign_up'] / $week_info ['plan_sign_up']) * 100 );
					$data_week ['deal_percent'] = floor ( ($data_week ['true_deal'] / $week_info ['plan_deal']) * 100 );
					$data_week ['sales_percent'] = floor ( ($data_week ['true_sales'] / $week_info ['plan_sales']) * 100 );
						
					$sum_percent = array (
							$data_week ['user_percent'],
							$data_week ['consumer_percent'],
							$data_week ['invitation_percent'],
							$data_week ['sign_percent'],
							$data_week ['deal_percent'],
							$data_week ['sales_percent']
					);
					$data_week ['sum_percent'] = array_sum ( $sum_percent );
					if ($data_week ['sum_percent'] == 600) {
						$data_week ['finish_status'] = 1;
					}
					if ($data_week ['sum_percent'] > 600) {
						$data_week ['finish_status'] = 2;
					}
					
					if ($this->workService ()->savemywork ( $data_week ) === false) {
						M()->rollback();
						$this->error ( '系统错误' );
					}
				}
					
				// 计算月目标
				$month_info = $this->workService ()->mywork ( session ( 'uid' ), Status::WorkTargetMonth );
				if ($month_info) {
					//当前月的所有天目标
					$map = array('type'=>Status::WorkTargetDay);
					$map['_string'] = "DAYOFMONTH(NOW()) = DAYOFMONTH(time)";
					$day_list = $this->workService()->getworklist($map);
					$data_month['id'] = $month_info['id'];
					foreach ($day_list as $v) {
						$data_month ['true_user_add'] += $v ['true_user_add'];
						$data_month ['true_consumer_add'] += $v ['true_consumer_add'];
						$data_month ['true_invitation'] += $v ['true_invitation'];
						$data_month ['true_sign_up'] += $v ['true_sign_up'];
						$data_month ['true_deal'] += $v ['true_deal'];
						$data_month ['true_sales'] += $v ['true_sales'];
					}
					$data_month ['user_percent'] = floor ( ($data_month ['true_user_add'] / $month_info ['plan_user_add']) * 100 );
					$data_month ['consumer_percent'] = floor ( ($data_month ['true_consumer_add'] / $month_info ['plan_consumer_add']) * 100 );
					$data_month ['invitation_percent'] = floor ( ($data_month ['true_invitation'] / $month_info ['plan_invitation']) * 100 );
					$data_month ['sign_percent'] = floor ( ($data_month ['true_sign_up'] / $month_info ['plan_sign_up']) * 100 );
					$data_month ['deal_percent'] = floor ( ($data_month ['true_deal'] / $month_info ['plan_deal']) * 100 );
					$data_month ['sales_percent'] = floor ( ($data_month ['true_sales'] / $month_info ['plan_sales']) * 100 );
					
					$sum_percent = array (
							$data_month ['user_percent'],
							$data_month ['consumer_percent'],
							$data_month ['invitation_percent'],
							$data_month ['sign_percent'],
							$data_month ['deal_percent'],
							$data_month ['sales_percent']
					);
					$data_month ['sum_percent'] = array_sum ( $sum_percent );
					if ($data_month ['sum_percent'] == 600) {
						$data_month ['finish_status'] = 1;
					}
					if ($data_month ['sum_percent'] > 600) {
						$data_month ['finish_status'] = 2;
					}
					
					if ($this->workService ()->savemywork ( $data_month ) === false) {
						M()->rollback();
						$this->error ( '系统错误' );
					}
				}
					
				// 计算年目标
				$year_info = $this->workService ()->mywork ( session ( 'uid' ), Status::WorkTargetYear );
				if ($year_info) {
					//当前月的所有天目标
					$map = array('type'=>Status::WorkTargetDay);
					$map['_string'] = "TIMESTAMPDIFF(YEAR,time,NOW()) = 0";
					$day_list = $this->workService()->getworklist($map);
					$data_year['id'] = $year_info['id'];
					foreach ($day_list as $v) {
						$data_year ['true_user_add'] += $v ['true_user_add'];
						$data_year ['true_consumer_add'] += $v ['true_consumer_add'];
						$data_year ['true_invitation'] += $v ['true_invitation'];
						$data_year ['true_sign_up'] += $v ['true_sign_up'];
						$data_year ['true_deal'] += $v ['true_deal'];
						$data_year ['true_sales'] += $v ['true_sales'];
					}
					$data_year ['user_percent'] = floor ( ($data_year ['true_user_add'] / $year_info ['plan_user_add']) * 100 );
					$data_year ['consumer_percent'] = floor ( ($data_year ['true_consumer_add'] / $year_info ['plan_consumer_add']) * 100 );
					$data_year ['invitation_percent'] = floor ( ($data_year ['true_invitation'] / $year_info ['plan_invitation']) * 100 );
					$data_year ['sign_percent'] = floor ( ($data_year ['true_sign_up'] / $year_info ['plan_sign_up']) * 100 );
					$data_year ['deal_percent'] = floor ( ($data_year ['true_deal'] / $year_info ['plan_deal']) * 100 );
					$data_year ['sales_percent'] = floor ( ($data_year ['true_sales'] / $year_info ['plan_sales']) * 100 );
						
					$sum_percent = array (
							$data_year ['user_percent'],
							$data_year ['consumer_percent'],
							$data_year ['invitation_percent'],
							$data_year ['sign_percent'],
							$data_year ['deal_percent'],
							$data_year ['sales_percent']
					);
					$data_year ['sum_percent'] = array_sum ( $sum_percent );
					if ($data_year ['sum_percent'] == 600) {
						$data_year ['finish_status'] = 1;
					}
					if ($data_year ['sum_percent'] > 600) {
						$data_year ['finish_status'] = 2;
					}
					
					if ($this->workService ()->savemywork ( $data_year ) === false) {
						M()->rollback();
						$this->error ( '系统错误' );
					}
				}
			}
			
			M()->commit();
			
			$this->redirect ( 'target/index/my/id/' . $result . '/type/' . $post ['type'] );
		} else {
			$this->assign ( 'get', I ( 'get.' ) );
			$this->assign ( 'page_title', '添加工作目标' );
			$this->display ();
		}
	}
	// 往期目标
	public function historyAction() /*NoPurview*/
	{
		$list = $this->workService ()->getworklist ( array (
				'distributor_id' => session ( 'distributor_id' ) 
		) );
		$this->assign ( 'list', $list );
		$this->assign ( 'page_title', '往期工作目标' );
		$this->display ();
	}
	// 我的目标
	public function myAction() /*NoPurview*/
	{
		$info = $this->workService ()->mywork ( session ( 'uid' ), I ( 'type' ), I ( 'id' ) );
		
		if (IS_POST) {
			$data = I ( 'post.' );
			
			$time = strtotime ( $info ['time'] );
			switch (I ( 'type' )) {
				case 1 :
					$can_edit = (date ( 'Ymd' ) == date ( 'Ymd', $time ));
					break;
				case 2 :
					$can_edit = BoolWeek ( NOW_TIME, $time );
					break;
				case 3 :
					$can_edit = (date ( 'Ym' ) == date ( 'Ym', $time ));
					break;
				case 4 :
					$can_edit = (date ( 'Y' ) == date ( 'Y', $time ));
					break;
			}
			if (! $can_edit && $info ['type'] == Status::WorkTargetDay) $this->error ( '已过有效期，目标不可编辑' );
			if ($info['check_status'] == Status::WorkCheckStatusOn) $this->error ( '目标审核中，不可编辑' );
			if ($info['check_status'] == Status::WorkCheckStatusPass) $this->error ( '目标审核通过，不可编辑' );
			
			M()->startTrans();
			
			// 计算每项的完成率和总完成率
			if ($info ['type'] == Status::WorkTargetDay) {
				$data ['user_percent'] = floor ( ($data ['true_user_add'] / $data ['plan_user_add']) * 100 );
				$data ['consumer_percent'] = floor ( ($data ['true_consumer_add'] / $data ['plan_consumer_add']) * 100 );
				$data ['invitation_percent'] = floor ( ($data ['true_invitation'] / $data ['plan_invitation']) * 100 );
				$data ['sign_percent'] = floor ( ($data ['true_sign_up'] / $data ['plan_sign_up']) * 100 );
				$data ['deal_percent'] = floor ( ($data ['true_deal'] / $data ['plan_deal']) * 100 );
				$data ['sales_percent'] = floor ( ($data ['true_sales'] / $data ['plan_sales']) * 100 );
				
				$sum_percent = array (
						$data ['user_percent'],
						$data ['consumer_percent'],
						$data ['invitation_percent'],
						$data ['sign_percent'],
						$data ['deal_percent'],
						$data ['sales_percent'] 
				);
				$data ['sum_percent'] = array_sum ( $sum_percent );
				if ($data ['sum_percent'] == 600) {
					$data ['finish_status'] = 1;
				}
				if ($data ['sum_percent'] > 600) {
					$data ['finish_status'] = 2;
				}
				
				// 计算周目标
				$week_info = $this->workService ()->mywork ( session ( 'uid' ), Status::WorkTargetWeek );
				if ($week_info) {
					//当前周的所有天目标
					$current_week = date('W', time());
					$map = array('type'=>Status::WorkTargetDay);
					$map['_string'] = "WEEKOFYEAR(time) = $current_week";
					$day_list = $this->workService()->getworklist($map);
					$data_week['id'] = $week_info['id'];
					foreach ($day_list as $v) {
						$data_week ['true_user_add'] += $v ['true_user_add'];
						$data_week ['true_consumer_add'] += $v ['true_consumer_add'];
						$data_week ['true_invitation'] += $v ['true_invitation'];
						$data_week ['true_sign_up'] += $v ['true_sign_up'];
						$data_week ['true_deal'] += $v ['true_deal'];
						$data_week ['true_sales'] += $v ['true_sales'];
					}
					$data_week ['user_percent'] = floor ( ($data_week ['true_user_add'] / $week_info ['plan_user_add']) * 100 );
					$data_week ['consumer_percent'] = floor ( ($data_week ['true_consumer_add'] / $week_info ['plan_consumer_add']) * 100 );
					$data_week ['invitation_percent'] = floor ( ($data_week ['true_invitation'] / $week_info ['plan_invitation']) * 100 );
					$data_week ['sign_percent'] = floor ( ($data_week ['true_sign_up'] / $week_info ['plan_sign_up']) * 100 );
					$data_week ['deal_percent'] = floor ( ($data_week ['true_deal'] / $week_info ['plan_deal']) * 100 );
					$data_week ['sales_percent'] = floor ( ($data_week ['true_sales'] / $week_info ['plan_sales']) * 100 );
					
					$sum_percent = array (
							$data_week ['user_percent'],
							$data_week ['consumer_percent'],
							$data_week ['invitation_percent'],
							$data_week ['sign_percent'],
							$data_week ['deal_percent'],
							$data_week ['sales_percent']
					);
					$data_week ['sum_percent'] = array_sum ( $sum_percent );
					if ($data_week ['sum_percent'] == 600) {
						$data_week ['finish_status'] = 1;
					}
					if ($data_week ['sum_percent'] > 600) {
						$data_week ['finish_status'] = 2;
					}
					
					if ($this->workService ()->savemywork ( $data_week ) === false) {
						M()->rollback();
						$this->error ( '修改失败' );
					}
				}
					
				// 计算月目标
				$month_info = $this->workService ()->mywork ( session ( 'uid' ), Status::WorkTargetMonth );
				if ($month_info) {
					//当前月的所有天目标
					$map = array('type'=>Status::WorkTargetDay);
					$map['_string'] = "DAYOFMONTH(NOW()) = DAYOFMONTH(time)";
					$day_list = $this->workService()->getworklist($map);
					$data_month['id'] = $month_info['id'];
					foreach ($day_list as $v) {
						$data_month ['true_user_add'] += $v ['true_user_add'];
						$data_month ['true_consumer_add'] += $v ['true_consumer_add'];
						$data_month ['true_invitation'] += $v ['true_invitation'];
						$data_month ['true_sign_up'] += $v ['true_sign_up'];
						$data_month ['true_deal'] += $v ['true_deal'];
						$data_month ['true_sales'] += $v ['true_sales'];
					}
					$data_month ['user_percent'] = floor ( ($data_month ['true_user_add'] / $month_info ['plan_user_add']) * 100 );
					$data_month ['consumer_percent'] = floor ( ($data_month ['true_consumer_add'] / $month_info ['plan_consumer_add']) * 100 );
					$data_month ['invitation_percent'] = floor ( ($data_month ['true_invitation'] / $month_info ['plan_invitation']) * 100 );
					$data_month ['sign_percent'] = floor ( ($data_month ['true_sign_up'] / $month_info ['plan_sign_up']) * 100 );
					$data_month ['deal_percent'] = floor ( ($data_month ['true_deal'] / $month_info ['plan_deal']) * 100 );
					$data_month ['sales_percent'] = floor ( ($data_month ['true_sales'] / $month_info ['plan_sales']) * 100 );
						
					$sum_percent = array (
							$data_month ['user_percent'],
							$data_month ['consumer_percent'],
							$data_month ['invitation_percent'],
							$data_month ['sign_percent'],
							$data_month ['deal_percent'],
							$data_month ['sales_percent']
					);
					$data_month ['sum_percent'] = array_sum ( $sum_percent );
					if ($data_month ['sum_percent'] == 600) {
						$data_month ['finish_status'] = 1;
					}
					if ($data_month ['sum_percent'] > 600) {
						$data_month ['finish_status'] = 2;
					}
						
					if ($this->workService ()->savemywork ( $data_month ) === false) {
						M()->rollback();
						$this->error ( '修改失败' );
					}
				}
					
				// 计算年目标
				$year_info = $this->workService ()->mywork ( session ( 'uid' ), Status::WorkTargetYear );
				if ($year_info) {
					//当前月的所有天目标
					$map = array('type'=>Status::WorkTargetDay);
					$map['_string'] = "TIMESTAMPDIFF(YEAR,time,NOW()) = 0";
					$day_list = $this->workService()->getworklist($map);
					$data_year['id'] = $year_info['id'];
					foreach ($day_list as $v) {
						$data_year ['true_user_add'] += $v ['true_user_add'];
						$data_year ['true_consumer_add'] += $v ['true_consumer_add'];
						$data_year ['true_invitation'] += $v ['true_invitation'];
						$data_year ['true_sign_up'] += $v ['true_sign_up'];
						$data_year ['true_deal'] += $v ['true_deal'];
						$data_year ['true_sales'] += $v ['true_sales'];
					}
					$data_year ['user_percent'] = floor ( ($data_year ['true_user_add'] / $year_info ['plan_user_add']) * 100 );
					$data_year ['consumer_percent'] = floor ( ($data_year ['true_consumer_add'] / $year_info ['plan_consumer_add']) * 100 );
					$data_year ['invitation_percent'] = floor ( ($data_year ['true_invitation'] / $year_info ['plan_invitation']) * 100 );
					$data_year ['sign_percent'] = floor ( ($data_year ['true_sign_up'] / $year_info ['plan_sign_up']) * 100 );
					$data_year ['deal_percent'] = floor ( ($data_year ['true_deal'] / $year_info ['plan_deal']) * 100 );
					$data_year ['sales_percent'] = floor ( ($data_year ['true_sales'] / $year_info ['plan_sales']) * 100 );
					
					$sum_percent = array (
							$data_year ['user_percent'],
							$data_year ['consumer_percent'],
							$data_year ['invitation_percent'],
							$data_year ['sign_percent'],
							$data_year ['deal_percent'],
							$data_year ['sales_percent']
					);
					$data_year ['sum_percent'] = array_sum ( $sum_percent );
					if ($data_year ['sum_percent'] == 600) {
						$data_year ['finish_status'] = 1;
					}
					if ($data_year ['sum_percent'] > 600) {
						$data_year ['finish_status'] = 2;
					}
					
					if ($this->workService ()->savemywork ( $data_year ) === false) {
						M()->rollback();
						$this->error ( '修改失败' );
					}
				}
			}
			
			$data ['check_status'] = ($data ['check_status'] == 1) ? 1 : 0;
			if ($this->workService ()->savemywork ( $data ) === false) {
				M()->rollback();
				$this->error ( '修改失败' );
			}
			
			M()->commit();
			
			$this->success ( '修改成功', U ( 'index' ) );
		}
		
		$this->assign ( 'info', $info );
		
		$disabled = (in_array($info['check_status'], array(Status::WorkCheckStatusOn, Status::WorkCheckStatusPass))) ? 'disabled' : '';
		$this->assign ( 'disabled', $disabled );
		
		switch (I ( 'type' )) {
			case 1 :
				$name = '天目标';
				$view = 'my';
				break;
			case 2 :
				$name = '周目标';
				$view = 'view';
				break;
			case 3 :
				$name = '月目标';
				$view = 'view';
				break;
			case 4 :
				$name = '年目标';
				$view = 'view';
				break;
		}
		$this->assign ( 'page_title', '我的工作目标-' . $name );
		$this->display ( $view );
	}
	
	// 往期目标
	public function my_listAction($type = 0) /*NoPurview*/
	{
		$params = array (
				'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
				'page_size'=>$this->pagesize,
				'admin_id'=>session('uid'),
		);
		if (I('type')) {
			$params['type'] = I('type');
		}
		if (I('time')) {
			$params['time'] = I('time');
		}
		if (I('finish_status')) {
			$params['finish_status'] = I('finish_status');
		}
		$result = $this->workService ()->getPagerList ( $params );
		$this->assign ( 'list', $result['list'] );
		
		if (IS_AJAX) {
			if(empty($result['list'])){
				$clist = '';
			}else{
				$clist = $this->renderPartial('_my_list');
			}
			$this->ajaxReturn(array('html'=>$clist, 'p'=>$params['page']+1));
		}
		
		$get = I('get.');
		$this->assign('get', $get);
		
		//目标完成状态
		$this->assign('finish_status_list', Status::$workTargetFinishStatusList);
		
		switch ($type) {
			case 1 :
				$name = '天目标';
				break;
			case 2 :
				$name = '周目标';
				break;
			case 3 :
				$name = '月目标';
				break;
			case 4 :
				$name = '年目标';
				break;
		}
		$this->assign ( 'page_title', '我的目标-' . $name );
		$this->display ();
	}
	// 其他人目标分类
	public function otherAction() /*NoPurview*/
	{
		//排除本人的目标
		$admin_id = session('uid');
		$where = "admin_id != $admin_id";
		//可以查看哪些人的目标
		$params = [
			'admin_id'=>session('uid'),
			'distributor_id'=>session('distributor_id'),
		];
		try {
			$result = $this->oaStaffService()->salaryInfo($params, 'role_ids');
		} catch (\Exception $e) {
			//$this->error($e->getMessage());
		}
		if ($result['department_list'][0]['personnel']) {
			foreach ($result['department_list'][0]['personnel'] as $v) {
				$admin_ids[] = $v['admin_id'];
			}
			$admin_ids = implode(',', $admin_ids);
			$where .= " AND admin_id in ('".$admin_ids."')";
		}
		$map['_string'] = $where;
		$params = array (
				'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
				'pagesize'=>$this->pagesize,
				'map'=>$map,
		);
		if (I('store_id')) {
			$params['distributor_id'] = I('store_id');
		}
		if (I('type')) {
			$params['type'] = I('type');
		}
		if (I('department_id')) {
			$params['department_id'] = I('department_id');
		}
		if (I('time')) {
			$params['time'] = I('time');
		}
		$result = $this->workService ()->getPagerList ( $params );
		$this->assign ( 'list', $result['list'] );
		$this->assign ( 'count', $result['count'] );
		
		if (IS_AJAX) {
			if(empty($result['list'])){
				$clist = '';
			}else{
				$clist = $this->renderPartial('_other');
			}
			$this->ajaxReturn(array('html'=>$clist, 'p'=>$params['page']+1));
		}
		
		$get = I('get.');
		$this->assign('get', $get);
		
		//店铺
		$map = array('status'=>Status::DistributorStatusNormal);
		$distributor_list = $this->distributorService()->getAllList($map);
		$store_list = array();
		$all = array(
				'value'=>U('', array('type'=>$get['type'], 'department_id'=>$get['department_id'], 'time'=>$get['time'])),
				'text'=>'全部',
		);
		$store_list[] = $all;
		if ($distributor_list) {
			foreach ($distributor_list as $v) {
				$store_list[] = array(
						'value'=>U('', array('store_id'=>$v['distributor_id'], 'type'=>$get['type'], 'department_id'=>$get['department_id'], 'time'=>$get['time'])),
						'text'=>$v['distributor_name'],
				);
			}
		}
		$this->assign('store_list', json_encode($store_list));
		
		if ($get['store_id']) {
			$distributor = $this->distributorService()->getInfo($get['store_id']);
			$this->assign('distributor_name', $distributor['distributor_name']);
		}
		
		//目标类型
		$type_list = array();
		$all = array(
				'value'=>U('', array('store_id'=>$get['store_id'], 'department_id'=>$get['department_id'], 'time'=>$get['time'])),
				'text'=>'全部',
		);
		$type_list[] = $all;
		foreach (Status::$workTargetList as $k => $v) {
			$type_list[] = array(
					'value'=>U('', array('type'=>$k, 'store_id'=>$get['store_id'], 'department_id'=>$get['department_id'], 'time'=>$get['time'])),
					'text'=>$v,
			);
		}
		$this->assign('type_list', json_encode($type_list));
		
		if ($get['type']) {
			$type_name = Status::$workTargetList[$get['type']];
			$this->assign('type_name', $type_name);
		}
		
		//部门
		if (empty($get['store_id'])) {
			$params = array(
					'sys_id'=>$this->p_sys_id,
					'org_id'=>$this->p_org_id,
			);
		}else {
			$params = array(
					'sys_id'=>$this->d_sys_id,
					'org_id'=>$this->d_org_id,
			);
		}
		$department_list = $this->adminService()->departmentList($params);
		$this->assign('department_list', $department_list);
		
		$this->assign ( 'page_title', '其他人目标' );
		$this->display ();
	}
	// 其他人目标分类进入
	public function other_listAction() /*NoPurview*/
	{
		switch (I ( 'type' )) {
			case 1 :
				$name = '天';
				break;
			case 2 :
				$name = '周';
				break;
			case 3 :
				$name = '月';
				break;
			case 4 :
				$name = '年';
				break;
		}
		
		$map = array (
				'type' => I ( 'type' ) 
		);
		if (session ( 'sys_id' ) == 2) {
			$map ['distributor_id'] = session ( 'distributor_id' );
		}
		$list = $this->workService ()->getworklist ( $map );
		$this->assign ( 'list', $list );
		
		$this->assign ( 'page_title', '其他人目标-' . $name );
		$this->display ();
	}
	// 目标详情
	public function detailAction() /*NoPurview*/
	{
		$info = $this->workService ()->getworkbyid ( I ( 'id' ) );
		$this->assign ( 'info', $info );
		
		// 管理员
		$admin = $this->adminService ()->getAdmin ( $info ['admin_id'] );
		$this->assign ( 'admin', $admin );
		
		// 上级
		if ($admin['parent_id']) {
			$parent = $this->adminService ()->getAdmin ( $info ['parent_id'] );
			$this->assign ( 'parent', $parent );
		}
		
		// 店铺
		$distributor = $this->distributorService ()->getInfo ( array (
				'distributor_id' => $info ['distributor_id'] 
		) );
		$this->assign ( 'distributor', $distributor );
		
		$this->assign ( 'admin_id', session ( 'uid' ) );
		
		$this->assign ( 'page_title', '工作目标详情' );
		$this->display ();
	}
	protected function checkBefore() {
		$this->purviewCheck ();
	}
	public function checkAction() /*工作目标审核*/
	{
		if (IS_POST) {
			$data = I ( 'post.' );
			_p($data);
			$data ['check_name'] = '店长';
			$data ['check_time'] = date ( 'Y-m-d H:i:s' );
			$data ['check_status'] = $data ['check_status'] == 1 ? 3 : 2;
			if ($data ['check_status'] == 1) {
				$data ['user_percent'] = floor ( ($data ['true_user_add'] / $data ['plan_user_add']) * 100 );
				$data ['consumer_percent'] = floor ( ($data ['true_consumer_add'] / $data ['plan_consumer_add']) * 100 );
				$data ['invitation_percent'] = floor ( ($data ['true_invitation'] / $data ['plan_invitation']) * 100 );
				$data ['sign_percent'] = floor ( ($data ['true_sign_up'] / $data ['plan_sign_up']) * 100 );
				$data ['deal_percent'] = floor ( ($data ['true_deal'] / $data ['plan_deal']) * 100 );
				$data ['sales_percent'] = floor ( ($data ['true_sales'] / $data ['plan_sales']) * 100 );
				
				$sum_percent = array (
						$data ['user_percent'],
						$data ['consumer_percent'],
						$data ['invitation_percent'],
						$data ['sign_percent'],
						$data ['deal_percent'],
						$data ['sales_percent']
				);
				$data ['sum_percent'] = array_sum ( $sum_percent );
				if ($data ['sum_percent'] == 600) {
					$data ['finish_status'] = 1;
				}
				if ($data ['sum_percent'] > 600) {
					$data ['finish_status'] = 2;
				}
			}
			if ($this->workService ()->edit ( $data )) {
				echo 1;
				return;
			} else {
				echo 2;
				return;
			}
		}
	}
	// 工作目标备注
	public function noteAction() /*NoPurview*/
	{
		if (IS_POST) {
			$data = I ( 'post.' );
			if ($this->workService ()->edit ( $data )) {
				echo 1;
				return;
			} else {
				echo 2;
				return;
			}
		}
	}
	// 排行榜
	public function rankingAction() /*NoPurview*/
	{
		//可以查看哪些人的目标
		$params = [
			'admin_id'=>session('uid'),
			'distributor_id'=>session('distributor_id'),
		];
		try {
			$result = $this->oaStaffService()->salaryInfo($params, 'role_ids');
		} catch (\Exception $e) {
			//$this->error($e->getMessage());
		}
		if ($result['department_list'][0]['personnel']) {
			foreach ($result['department_list'][0]['personnel'] as $v) {
				$admin_ids[] = $v['admin_id'];
			}
			$map['admin_id'] = array('in', $admin_ids);
		}
		
		if (IS_AJAX) {
			$params = array (
					'page'=>intval(I('p')) > 0 ? intval(I('p')) : 1,
					'page_size'=>$this->pagesize,
					'type'=>I('type'),
					'map'=>$map,
					'orderby'=>'sum_percent desc',
			);
			$result = $this->workService ()->getPagerList ( $params );
			$this->assign ( 'list', $result['list'] );
			
			if(empty($result['list'])){
				$clist = '';
			}else{
				$clist = $this->renderPartial('_ranking');
			}
			$this->ajaxReturn(array('html'=>$clist, 'p'=>$params['page']+1));
		}
		
		$get = I('get.');
		$this->assign('get', $get);
		
		if ($get['city']) {
			$region_name = $this->regionService()->getRegionNameCity($get['city']);
			$this->assign('region_name', $region_name);
		}
		
		//店铺
		if (session('sys_id') == Status::SysIdPlatform) {
			$map = array('status'=>Status::DistributorStatusNormal);
			$distributor_list = $this->distributorService()->getAllList($map);
			$this->assign('distributor_list', $distributor_list);
		}
		
		// 日
		$map['type'] = Status::WorkTargetDay;
		$day = $this->workService ()->getworklist ( $map, 'sum_percent desc' );
		$this->assign ( 'day', $day );
		
		// 周
		$map['type'] = Status::WorkTargetWeek;
		$week = $this->workService ()->getworklist ( $map, 'sum_percent desc' );
		$this->assign ( 'week', $week );
		
		// 月
		$map['type'] = Status::WorkTargetMonth;
		$month = $this->workService ()->getworklist ( $map, 'sum_percent desc' );
		$this->assign ( 'month', $month );
		
		// 年
		$map['type'] = Status::WorkTargetYear;
		$year = $this->workService ()->getworklist ( $map, 'sum_percent desc' );
		$this->assign ( 'year', $year );
		
		$this->assign ( 'page_title', '排行榜' );
		$this->display ();
	}
	
	// 工作目标
	protected function workService() {
		return D ( 'Work', 'Service' );
	}
	
	protected function distributorService() {
		return D ( 'Distributor', 'Service' );
	}
	
	private function oaStaffService(){
		return D('Common/Oa/Staff', 'Service');
	}
	
	private function regionService(){
		return D('Region', 'Service');
	}
}