<?php
namespace Common\Service;
use Common\Basic\Status;

class ViewService{
	// 浏览列表
	public function getlist($params)
	{
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 10;
		
		$where = 1;
		if($params['start'] && $params['end'])
		{ 
			$where.= ' and in_time between "'.$params['start'].' 00:00:00" and "'.$params['end'].' 23:59:59"';
		}
		
		$count = $this->ViewDao()->searchRecordsCount($where);
		$list = $this->ViewDao()->searchRecords($where,'id desc', $params['page'], $params['pagesize']);

		if($list)
		{ 
			foreach($list as $k => $v)
			{ 
				$user = $this->UserInfoDao()->findUser(array('openid' => $v['openid']));
				$list[$k]['nick_name'] = $user['nick_name'];
				$list[$k]['mobile'] = $user['mobile'];
			}
		}
		
		return array(
			'list' => $list,
			'count' => $count,
		);
	}
	// 操作日志
	public function getlog($params)
	{
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 10;
		
		$where = 1;
		if($params['start'] && $params['end'])
		{ 
			$where.= ' and time between "'.$params['start'].' 00:00:00" and "'.$params['end'].' 23:59:59"';
		}
		if($params['fullname'])
		{ 
			$where.= ' and fullname = "'.$params['fullname'].'"';
		}
		
		$count = $this->LogDao()->searchRecordsCount($where);
		$list = $this->LogDao()->searchRecords($where,'id desc', $params['page'], $params['pagesize']);

		if($list)
		{ 
			foreach($list as $k => $v)
			{ 
				$user = $this->UserInfoDao()->findUser(array('user_id' => $v['userid']));
				$list[$k]['nick_name'] = $user['nick_name'];
				$list[$k]['mobile'] = $user['mobile'];
			}
		}
		
		return array(
			'list' => $list,
			'count' => $count,
		);
	}
	private function ViewDao()
	{
		return D('Common/View/View');
	}
	private function LogDao()
	{ 
		return D('Common/View/Log');
	}
	private function UserInfoDao()
	{ 
		return D('Common/User/UserInfo');
	}
}