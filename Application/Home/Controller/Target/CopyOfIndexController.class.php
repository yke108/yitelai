<?php
namespace Home\Controller\Target;
use Home\Controller\BaseController;
use Common\Basic\Status;

class IndexController extends BaseController {	
	public function _initialize(){
		parent::_initialize();
		//session('userid',1);
    }
    
    protected function _purviewCheck(){
    	$this->purviewCheck('index');
    }

    public function indexAction() /*工作目标*/
	{
		//天
		$day = $this->workService()->myCurrentwork(session('uid'),1);
		$this->assign('day',$day);
		
		//周
		$week = $this->workService()->myCurrentwork(session('uid'),2);
		$this->assign('week',$week);
		
		//月
		$month = $this->workService()->myCurrentwork(session('uid'),3);
		$this->assign('month',$month);
		
		//年
		$year = $this->workService()->myCurrentwork(session('uid'),4);
		$this->assign('year',$year);
		
		//管理员
		$admin = $this->adminService()->getAdmin(session('uid'));
		$this->assign('admin', $admin);
		
		//店铺
		$distributor = $this->distributorService()->getInfo(array('distributor_id' => session('distributor_id')));
		$this->assign('distributor', $distributor);
		
		$this->assign('page_title','工作目标管理');
		$this->display();
    }

	public function addAction() /*NoPurview*/
	{ 
		if(IS_POST)
		{ 
			if (session('sys_id') != 2) {
				//$this->error('权限不够，无法操作');
			}
			$post = I('post.');
			$post['admin_id'] = session('uid');
			$post['distributor_id'] = session('distributor_id');
			$post['time'] = date('Y-m-d H:i:s');
			
			switch($post['type'])
			{ 
				case 1 : $start = date('Y-m-d H:i:s') ; $end = date('Y-m-d H:i:s',strtotime($start.' + 1 day')) ; break;//天
				case 2 : $start = date('Y-m-d H:i:s') ; $end = date('Y-m-d H:i:s',strtotime($start.' + 1 week')) ; break;//周
				case 3 : $start = date('Y-m-d H:i:s') ; $end = date('Y-m-d H:i:s',strtotime($start.' + 1 month')) ; break;//月
				case 4 : $start = date('Y-m-d H:i:s') ; $end = date('Y-m-d H:i:s',strtotime($start.' + 1 year')) ; break;//年
			}
			$post['start'] = $start;
			$post['end'] = $end;
			$result = $this->workService()->add($post);
			if($result >= 1 )
			{ 				
				$this->redirect('target/index/my/id/'.$result.'/type/'.$post['type']);
			}
		}
		else
		{
			$this->assign('get',I('get.'));
			$this->assign('page_title','添加工作目标');
			$this->display();
		}
	}
	// 往期目标
	public function historyAction() /*NoPurview*/
	{ 
		$list = $this->workService()->getworklist(array('distributor_id' => session('distributor_id')));
		$this->assign('list',$list);
		$this->assign('page_title','往期工作目标');
		$this->display();
	}
	
	// 我的目标
	public function myAction() /*NoPurview*/
	{
		$info = $this->workService()->mywork(session('uid'),I('type'),I('id'));
		
		if(IS_POST)
		{ 
			$data = I('post.');
			
			$time = strtotime($info['time']);
			switch(I('type'))
			{
				case 1 : $can_edit = (date('Ymd') == date('Ymd', $time)) ; break;
				case 2 : $can_edit = BoolWeek(NOW_TIME, $time) ; break;
				case 3 : $can_edit = (date('Ym') == date('Ym', $time)) ; break;
				case 4 : $can_edit = (date('Y') == date('Y', $time)) ; break;
			}
			if (!$can_edit && $info['type'] == Status::WorkTargetDay) $this->error('已过有效期，目标不可编辑');
			
			// 计算每项的完成率和总完成率
			if ($info['type'] == Status::WorkTargetDay) {
				$data['user_percent'] = floor(($data['true_user_add'] / $data['plan_user_add']) * 100);
				$data['consumer_percent'] = floor(($data['true_consumer_add'] / $data['plan_consumer_add']) * 100);
				$data['invitation_percent'] = floor(($data['true_invitation'] / $data['plan_invitation']) * 100);
				$data['sign_percent'] = floor(($data['true_sign_up'] / $data['plan_sign_up']) * 100);
				$data['deal_percent'] = floor(($data['true_deal'] / $data['plan_deal']) * 100);
				$data['sales_percent'] = floor(($data['true_sales'] / $data['plan_sales']) * 100);
				
				$sum_percent = array($data['user_percent'],$data['consumer_percent'],$data['invitation_percent'],$data['sign_percent'],$data['deal_percent'],$data['sales_percent']);
				$data['sum_percent'] = array_sum($sum_percent);
				if ($data['sum_percent'] == 600) {
					$data['finish_status'] = 1;
				}
				if ($data['sum_percent'] > 600) {
					$data['finish_status'] = 2;
				}
			}
			
			//计算周目标
			
			//计算月目标
			
			//计算年目标
			
			if($this->workService()->savemywork($data) === false)
			{ 
				//$this->redirect('target/index/my/id/'.$data['id'].'/type/'.$data['type']);
				$this->error('修改失败');
			}
			$this->success('修改成功', U('index'));
		}
		
		$this->assign('info',$info);
		
		switch(I('type'))
		{ 
			case 1 : $name = '天' ; $view = 'my'; break;
			case 2 : $name = '周' ; $view = 'view'; break;
			case 3 : $name = '月' ; $view = 'view'; break;
			case 4 : $name = '年' ; $view = 'view'; break;
		}
		$this->assign('page_title','我的工作目标-'.$name);
		$this->display($view);
	}
	// 其他人目标分类
	public function otherAction() /*NoPurview*/
	{ 
		$this->assign('page_title','其他人目标');
		$this->display();
	}
	// 其他人目标分类进入
	public function other_listAction() /*NoPurview*/
	{ 
		switch(I('type'))
		{ 
			case 1 : $name = '天' ; break;
			case 2 : $name = '周' ; break;
			case 3 : $name = '月' ; break;
			case 4 : $name = '年' ; break;
		}
		
		$map = array('type' => I('type'));
		if (session('sys_id') == 2) {
			$map['distributor_id'] = session('distributor_id');
		}
		$list = $this->workService()->getworklist($map);
		$this->assign('list',$list);
		
		$this->assign('page_title','其他人目标-'.$name);
		$this->display();
	}
	// 目标详情
	public function detailAction() /*NoPurview*/
	{ 
		$info = $this->workService()->getworkbyid(I('id'));
		$this->assign('info',$info);
		
		//管理员
		$admin = $this->adminService()->getAdmin($info['admin_id']);
		$this->assign('admin', $admin);
		
		//店铺
		$distributor = $this->distributorService()->getInfo(array('distributor_id' => $info['distributor_id']));
		$this->assign('distributor', $distributor);
		
		$this->assign('admin_id', session('uid'));
		
		$this->assign('page_title','工作目标详情');
		$this->display();
	}
	
	protected  function checkBefore(){
		$this->purviewCheck();
	}

	public function checkAction() /*工作目标审核*/
	{ 
		if(IS_POST)
		{ 
			$data = I('post.');
			$data['check_name'] = '店长';
			$data['check_time'] = date('Y-m-d H:i:s');
			$data['check_status'] = $data['check_status'] == 1 ? 1 : 2;
			if($this->workService()->edit($data))
			{ 
				echo 1;
				return;
			}
			else
			{ 
				echo 2;
				return;
			}
		}
	}
	// 工作目标备注
	public function noteAction() /*NoPurview*/
	{ 
		if(IS_POST)
		{ 
			$data = I('post.');
			if($this->workService()->edit($data))
			{ 
				echo 1;
				return;
			}
			else
			{ 
				echo 2;
				return;
			}
		}
	}
	// 排行榜
	public function rankingAction() /*NoPurview*/
	{ 
		// 日
		$day = $this->workService()->getworklist('type = 1','sum_percent desc');
		$this->assign('day',$day);
		
		// 周
		$week = $this->workService()->getworklist('type = 2','sum_percent desc');
		$this->assign('week',$week);
		
		// 月
		$month = $this->workService()->getworklist('type = 3','sum_percent desc');
		$this->assign('month',$month);
		
		// 年
		$year = $this->workService()->getworklist('type = 4','sum_percent desc');
		$this->assign('year',$year);
		
		$this->assign('page_title','排行榜');
		$this->display();
	}
	
	// 工作目标
	protected function workService(){
		return D('Work', 'Service');
	}
	
	protected function distributorService(){
		return D('Distributor', 'Service');
	}
}