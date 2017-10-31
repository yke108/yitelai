<?php
namespace Common\Service;

use Common\Basic\CsException;
use Common\Basic\Status;

class FeedbackService{
	
	public function getInfo($id){
		if ($id < 1) return false;
		$info = $this->feedbackDao()->getRecord($id);
		return $this->outPutForInfo($info);
	}
	
	public function findInfo($map){
		$info = $this->feedbackDao()->findRecord($map);
		return $this->outPutForInfo($info);
	}
	
	public function createOrModify($params){
		// 自动验证
		$rules = array(
				array('content', 'require', '内容不能为空'),
				array('type',array(1,2,3),'反馈类型值的范围不正确',2,'in')
		);
		//参数
		$data = array(
				'user_id'=>$params['user_id'],
				'type'=>$params['type'],
				'brand_id'=>$params['brand_id'],
				'distributor_id'=>$params['distributor_id'],
				'goods_id'=>$params['goods_id'],
				'content'=>trim($params['content']),
				'pictures'=>$params['pictures'],
				'client'=>$params['client'],
		);
		$feedbackDao = $this->feedbackDao();
		if (!$feedbackDao->validate($rules)->create($data)){
			 throw new \Exception($feedbackDao->getError());
		}
		if ($params['log_id'] > 0){
			$result = $feedbackDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $feedbackDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
			return $result;
		}
	}
	
	public function remark($params){
		// 自动验证
		$rules = array(
				array('remark', 'require', '备注内容不能为空'),
		);
		//参数
		$data = array(
				'admin_id'=>$params['admin_id'],
				'log_id'=>$params['log_id'],
				'remark'=>trim($params['remark']),
		);
		$feedbackDao = $this->feedbackDao();
		if (!$feedbackDao->validate($rules)->create($data)){
			throw new \Exception($feedbackDao->getError());
		}
		$result = $feedbackDao->saveRecord($data);
		if ($result === false){
			throw new \Exception('修改失败');
		}
	}
	
	public function modify($log_id, $data){
		if ($this->feedbackDao()->where(array('log_id'=>$log_id))->save($data) === false) throw new \Exception('修改失败');
	}
	
	public function feedbackReplyUpdate($map, $data){
		if ($this->feedbackReplyDao()->where($map)->save($data) === false) throw new \Exception('更新失败');
	}
	
	public function feedbackDelete($id){
		$result = $this->feedbackDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function getPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = $params['map'] ? $params['map'] : array();
		if(!empty($params['type'])){
			$map['type'] = $params['type'];
		}
		if (!empty($params['brand_id'])) {
			$map['brand_id'] = $params['brand_id'];
		}
		if (!empty($params['distributor_id'])) {
			$map['distributor_id'] = $params['distributor_id'];
		}
		if(!empty($params['start_time'])){
			$map['add_time'][] = array('egt', strtotime($params['start_time']));
		}
		if(!empty($params['end_time'])){
			$map['add_time'][] = array('elt', strtotime($params['end_time']) + 86400);
		}
		if (!empty($params['user_id'])) {
			$map['user_id'] = $params['user_id'];
		}
		
		if(isset($params['status'])){
			$map['status'] = $params['status'];
		}
		if(isset($params['keyword'])){
			$map['content'] = array('like', '%'.trim($params['keyword']).'%');
		}
		if(isset($params['user_id'])){
			$map['user_id'] = $params['user_id'];
		}
		if(isset($params['client'])){
			$map['client'] = $params['client'];
		}
		if(isset($params['admin_id'])){
			$map['admin_id'] = $params['admin_id'];
		}
		
		$feedbackDao = $this->feedbackDao();
		$count = $feedbackDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'log_id DESC' : $params['orderby'];
			$list = $feedbackDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		
		return array(
			'list'=>$this->outPutForList($list),
			'count'=>$count,
		);
	}
	
	public function stat($params){
		if (isset($params['distributor_id'])) {
			$map['distributor_id'] = $params['distributor_id'];
		}
		$feedbackDao = $this->feedbackDao();
		
		//未解答
		$map['status'] = Status::FeedbackStatusNone;
		$none_count = $feedbackDao->searchRecordsCount($map);
		
		//解答中
		$map['status'] = Status::FeedbackStatusOn;
		$on_count = $feedbackDao->searchRecordsCount($map);
		
		//已解答
		$map['status'] = Status::FeedbackStatusDone;
		$done_count = $feedbackDao->searchRecordsCount($map);
		
		return array(
				'none_count'=>$none_count,
				'on_count'=>$on_count,
				'done_count'=>$done_count,
		);
	}
	
	public function selectDistinctUserid() {
		return $this->feedbackDao()->searchDistinctUserid();
	}
	
	public function selectDistinctAdminid() {
		return $this->feedbackDao()->searchDistinctAdminid();
	}
	
	private function outPutForList($list) {
		if (!empty($list)) {
			$distributor_ids = $admin_ids = array();
			foreach ($list as $v) {
				$user_ids[] = $v['user_id'];
				if ($v['distributor_id']) {
					$distributor_ids[] = $v['distributor_id'];
				}
				if ($v['admin_id']) {
					$admin_ids[] = $v['admin_id'];
				}
			}
			//用户
			$users = $this->userInfoDao()->getUsersByIds($user_ids);
			//店铺
			if ($distributor_ids) {
				$distributors = $this->distributorInfoDao()->getDistributorsByIds($distributor_ids);
			}
			//客服
			if ($admin_ids) {
				$admins = $this->adminInfoDao()->getRecords($admin_ids);
			}
			
			foreach ($list as $k => $v) {
				//反馈类型
				$list[$k]['type_name'] = Status::$feedbackTypeList[$v['type']];
				//状态
				$list[$k]['status_label'] = Status::$feedbackStatusList[$v['status']];
				$list[$k]['client_label'] = Status::$feedbackClientList[$v['client']];
				//用户
				$list[$k]['nick_name'] = $users[$v['user_id']]['nick_name'];
				$list[$k]['mobile'] = $users[$v['user_id']]['mobile'];
				$list[$k]['city'] = $users[$v['user_id']]['city'];
				$list[$k]['avatar'] = $users[$v['user_id']]['user_img'] ? picurl($users[$v['user_id']]['user_img'], 'b90') : $users[$v['user_id']]['headimgurl'];
				$list[$k]['user_name'] = $users[$v['user_id']]['real_name'] ? $users[$v['user_id']]['real_name'] : $users[$v['user_id']]['nick_name'];
				//店铺
				$list[$k]['distributor_name'] = $distributors[$v['distributor_id']];
				//客服
				$list[$k]['admin_name'] = $admins[$v['admin_id']]['admin_name'];
				//上传图片
				if ($v['pictures']) {
					$list[$k]['pictures'] = explode(',', $v['pictures']);
				}
			}
		}
		return $list;
	}
	
	private function outPutForInfo($info) {
		if (!empty($info)) {
			//状态
			$info['status_label'] = Status::$feedbackStatusList[$info['status']];
			$info['client_label'] = Status::$feedbackClientList[$info['client']];
			//用户
			$user = $this->userInfoDao()->getRecord($info['user_id']);
			$info['nick_name'] = $user['nick_name'];
			$info['avatar'] = $user['user_img'] ? picurl($user['user_img'], 'b90') : $user['headimgurl'];
			$info['user_name'] = $user['real_name'] ? $user['real_name'] : $user['nick_name'];
			//上传图片
			if ($info['pictures']) {
				$info['pictures'] = explode(',', $info['pictures']);
			}
		}
		return $info;
	}
	
	public function mbegin($params){
		$info = $this->getInfo($params['log_id']);
		if (empty($info)){
			throw new \Exception('系统错误');
		}
		$data = array(
			'log_id'=>$info['log_id'],
			'mbegin_time'=>NOW_TIME,
		);
		if ($this->feedbackDao()->saveRecord($data) === false){
			throw new \Exception('系统错误');
		}
	}
	
	public function reply($params) {
		$feedback_info = $this->getInfo($params['log_id']);
		
		M()->startTrans();
		
		//修改管理员ID
		if ($feedback_info['admin_id'] == 0 && $params['ref_id'] > 0 
				&& $params['ref_type'] == Status::FeedbackRefTypeAdmin) {
			$data = array(
					'log_id'=>$params['log_id'],
					'admin_id'=>$params['ref_id'],
					'status'=>Status::FeedbackStatusOn,
			);
			if ($this->feedbackDao()->saveRecord($data) === false){
				M()->rollback();
				throw new \Exception('系统错误');
			}
		}
		
		//添加回复
		$data = array(
				'log_id'=>$params['log_id'],
				'content'=>$params['content'],
				'ref_id'=>$params['ref_id'],
				'ref_type'=>$params['ref_type'],
				'is_json'=>intval($params['is_json']),
		);
		$feedbackReplyDao = $this->feedbackReplyDao();
		if (!$feedbackReplyDao->create($data)){
			M()->rollback();
			throw new \Exception($feedbackReplyDao->getError());
		}
		if ($feedbackReplyDao->add() === false){
			M()->rollback();
			throw new \Exception('系统错误');
		}
		
		M()->commit();
		
		return $feedback_info;
	}
	
	public function getNextReply($log_id){
		$map = [
			'log_id'=>$log_id,
			'ref_id'=>['gt', 0],
			'ref_type'=>Status::FeedbackRefTypeAdmin,
			'is_read'=>0,
		];
		$info = $this->feedbackReplyDao()->order('reply_id asc')->where($map)->find();
		if($info){
			$data = [
				'reply_id'=>$info['reply_id'],
				'is_read'=>1,
			];
			$this->feedbackReplyDao()->save($data);
		}
		return $info;
	}
	
	public function getUserNextReply($log_id){
		$map = [
		'log_id'=>$log_id,
		'ref_type'=>Status::FeedbackRefTypeUser,
		'is_read'=>0,
		];
		$info = $this->feedbackReplyDao()->order('reply_id asc')->where($map)->find();
		if($info){
			$data = [
			'reply_id'=>$info['reply_id'],
			'is_read'=>1,
			];
			$this->feedbackReplyDao()->save($data);
		}
		return $info;
	}
	
	public function replyAllList($params){
		$map = $params['map'] ? $params['map'] : array();
		if(isset($params['log_id'])){
			$map['log_id'] = $params['log_id'];
		}
		
		$orderby = empty($params['orderby']) ? 'reply_id ASC' : $params['orderby'];
		$list = $this->feedbackReplyDao()->searchAllRecords($map, $orderby);
		
		if (!empty($list)) {
			$user_ids = $admin_ids = array();
			foreach ($list as $v) {
				if ($v['ref_type'] == Status::FeedbackRefTypeUser) {
					$user_ids[] = $v['ref_id'];
				}else {
					$admin_ids[] = $v['ref_id'];
				}
			}
			//用户
			if ($user_ids) {
				$users = $this->userInfoDao()->getUsersByIds($user_ids);
			}
			//管理员
			if ($admin_ids) {
				$admins = $this->adminInfoDao()->getRecords($admin_ids);
			}
			
			foreach ($list as $k => $v) {
				if ($v['ref_type'] == Status::FeedbackRefTypeUser) {
					$list[$k]['avatar'] = $users[$v['user_id']]['user_img'] ? picurl($users[$v['user_id']]['user_img'], 'b90') : $users[$v['user_id']]['headimgurl'];
				}else {
					$list[$k]['avatar'] = $admins[$v['ref_id']]['avatar'];
				}
			}
		}
		
		return $list;
	}
	
	public function feedbackAppCreate($params){
		if (empty($params['content'])) throw new CsException('内容不能为空', 1001);
		$data = array(
			'admin_id'=>$params['admin_id'],
			'content'=>trim($params['content']),
			'pictures'=>$params['pictures'],
			'client'=>$params['client'],
			'about'=>$params['about'],
		);
		$feedbackAppDao = $this->feedbackAppDao();
		$result = $feedbackAppDao->addRecord($data);
		if ($result < 1){
			throw new \Exception('添加失败');
		}
	}
	
	public function feedbackAppPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		if(!empty($params['start_time'])){
			$map['add_time'][] = array('egt', strtotime($params['start_time']));
		}
		if(!empty($params['end_time'])){
			$map['add_time'][] = array('lt', strtotime($params['end_time']) + 86400);
		}
		$feedbackAppDao = $this->feedbackAppDao();
		$count = $feedbackAppDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'log_id DESC' : $params['orderby'];
			$list = $feedbackAppDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
			foreach ($list as $vo){
				$ids[$vo['admin_id']] = $vo['admin_id'];
			}
			$admins = $this->adminInfoDao()->getRecords($ids);
			foreach ($list as $ko => $vo){
				$pictures = empty($vo['pictures']) ? array() : explode(',', $vo['pictures']);
				$list[$ko]['pictures'] = $pictures;
			}
		}
		return array(
			'list'=>$list,
			'admin_list'=>$admins,
			'count'=>$count,
		);
	}

	public function feedbackPagerList($params){
		$map = array();
		$map['user_id'] = $params['user_id'];
		if($params['type']){
			$map['type'] = $params['type'];
		}
		if($params['content']){
			$map['content'] = array('like', '%'.$params['content'].'%');
		}
		$feedbackDao = $this->feedbackDao();
		$count = $feedbackDao->searchRecordsCount($map);
		$_list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'log_id DESC' : $params['orderby'];
			$field = array();
			$data = $feedbackDao->searchFieldRecords($map, $field, $orderby, $params['page'], $params['pagesize']);
			foreach ($data as $key => $val){
				$_t = $val;
				if($val['brand_id'] > 0){
					$brandFind = $this->goodsBrandService()->getFieldRecord($val['brand_id'], array('brand_name'));
					$_t['brand_name']  = $brandFind['brand_name'];
				} else {
					$_t['brand_name']  = '';
				}
				if($val['distributor_id'] > 0){
					$distributorFind = D('DistributorInfo')->where(array('distributor_id' => $val['distributor_id']))->field(array('distributor_name'))->find();
					$_t['distributor_name']  = $distributorFind['distributor_name'];
				} else {
					$_t['distributor_name']  = '';
				}
				$_t['statusName'] = Status::$feedbackStatusList[$val['status']];
				$_t['detailUrl'] = U('user/history/feedbackdetail', array('log_id' => $val['log_id']));
				$_list[]  = $_t;
			}
		}
		return array(
			'list'=>$_list,
			'count'=>$count,
		);
	}

	public function feedbackFind($log_id)
	{
		$feedbackFind = $this->feedbackDao()->getRecord($log_id);
		if($feedbackFind['brand_id'] > 0){
			$brandFind = $this->goodsBrandService()->getFieldRecord($feedbackFind['brand_id'], array('brand_name'));
			$feedbackFind['brand_name']  = $brandFind['brand_name'];
		} else {
			$feedbackFind['brand_name']  = '';
		}
		if($feedbackFind['distributor_id'] > 0){
			$distributorFind = D('DistributorInfo')->where(array('distributor_id' => $feedbackFind['distributor_id']))->field(array('distributor_name'))->find();
			$feedbackFind['distributor_name']  = $distributorFind['distributor_name'];
		} else {
			$feedbackFind['distributor_name']  = '';
		}
		$feedbackFind['statusName'] = Status::$feedbackStatusList[$feedbackFind['status']];
		$feedbackFind['inputtime'] = date('Y-m-d H:i:s', $feedbackFind['add_time']);
		if($feedbackFind['pictures']){
			$imageList = explode(',', $feedbackFind['pictures']);
			$_img = '';
			foreach($imageList as $k => $v){
				$_img.='<img src="'.domain_name_url.$v.'" style="margin:0 0 10px 0;width:250px;"/><br/>';
			}
			$feedbackFind['pictures_list'] = $_img;
		} else {
			$feedbackFind['pictures_list'] = '';
		}
		$replyList = $this->feedbackReplyDao()->searchAllRecords(array('log_id' => $log_id), array('reply_id' => 'DESC'));
		$_list = array();
		foreach($replyList as $key => $val){
			$_t = $val;
			if($val['ref_type'] == 1){
				$_t['content'] = "<strong style='font-size: 14px;'>提问：</strong>".$val['content'];
			} else {
				$_t['content'] = "<strong style='font-size: 14px;'>回复：</strong>".$val['content'];
			}
			$_t['inputtime'] = date('Y-m-d H:i:s', $val['add_time']);
			$_list[] = $_t;
		}
		return array(
			'feedbackFind' => $feedbackFind,
			'reply_list' => $_list,
		);
	}
	
	//调用model
	private function feedbackDao(){
		return D('Common/Feedback/Feedback');
	}

	private function goodsBrandService() {
		return D('Common/Goods/GoodsBrand');
	}

	private function feedbackReplyDao(){
		return D('Common/Feedback/FeedbackReply');
	}
	
	private function feedbackAppDao(){
		return D('Common/Feedback/FeedbackApp');
	}
	
	private function userInfoDao(){
		return D('Common/User/UserInfo');
	}
	
	private function adminInfoDao(){
		return D('Common/Admin/AdminInfo');
	}
	
	private function distributorInfoDao(){
		return D('Common/Distributor/Info');
	}
}//end FeedbackService!