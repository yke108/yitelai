<?php
namespace Common\Service;
use Common\Basic\Status;
use Common\Basic\ApplyConst;
use Common\Basic\CsException;

class ApplyService{
	// 类型列表
	public function gettype(){ 
		return $this->ApplyTypeDao()->searchRecords();
	}
	// 单个类型
	public function getTypeById($id){ 
		return $this->ApplyTypeDao()->getRecord($id);
	}
	// 添加类型
	public function addtype($data)
	{ 
		return $this->ApplyTypeDao()->addRecord($data);
	}
	// 修改类型
	public function edittype($data)
	{ 
		return $this->ApplyTypeDao()->saveRecord($data);
	}
	// 删除类型
	public function deltype($id)
	{ 
		return $this->ApplyTypeDao()->deleteRecord(array('id' => $id));
	}
	// 列表
	public function getlist($params)
	{
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 10;
		
		$where = 1;
		if($params['keyword'])
		{ 
			$where.= ' and applicant = "'.$params['keyword'].'"';
		}
		if($params['start'] && $params['end'])
		{ 
			$where.= ' and apply_time between "'.$params['start'].' 00:00:00" and "'.$params['end'].' 23:59:59"';
		}
		if($params['type'])
		{ 
			$where.= ' and type = '.$params['type'];
		}
		if($params['apply_type'])
		{ 
			$where.= ' and apply_type = '.$params['apply_type'];
		}
		
		
		$count = $this->applyListDao()->searchRecordsCount($where);
		$list = $this->applyListDao()->searchRecords($where,'id desc', $params['page'], $params['pagesize']);
		if($list)
		{ 
			foreach($list as $k => $v)
			{ 
				$list[$k]['apply_name'] = $this->ApplyTypeDao()->getFieldRecord(array('id' => $v['apply_type']),'apply_name');
			}
		}
		
		return array(
			'list' => $list,
			'count' => $count,
		);
	}
	// 单个列表
	public function getlistsbyid($id)
	{ 
		return $this->applyListDao()->getRecord($id);	
	}
	// 修改列表
	public function editlists($data)
	{ 
		return $this->applyListDao()->saveRecord($data);
	}
	
	// 前端-添加申请
	public function add($params){
		if ($params['admin_id'] < 1) throw new CsException('系统错误');
		if ($params['verify_admin_id'] < 1) throw new CsException('未指定审批人');
		if ($params['admin_id'] == $params['verify_admin_id']) throw new CsException('不能指定自己为审批人');
		//查看申请人是否有特殊申请权限
		$this->checkApplyPurview($params['admin_id'], $params['apply_type']);
		//查看审批人是否有相应的审批权限
		$this->checkVerifyPurview($params['verify_admin_id'], $params['apply_type']);
		//检验数据 TODO
		$data = array(
			'admin_id'=>$params['admin_id'],
			'distributor_id'=>$params['distributor_id'],
			'apply_time'=>NOW_TIME,
			'start'=>strtotime($params['start']),
			'end'=>strtotime($params['end']),
			'content'=>$params['content'],
			'price'=>$params['price'] * 1,
			'amount'=>$params['amount'] * 1,
			'apply_type'=>$params['apply_type'],
			'pics'=>$params['pics'],
			'attachments'=>$params['attachments'],
			'verify_status'=>ApplyConst::VerifyStatusStart,
		);
		$applyListDao = $this->applyListDao();
		$applyListDao->startTrans();
		$apply_id = $applyListDao->addRecord($data);
		if ($apply_id < 1){
			$applyListDao->rollback();
			throw new CsException('申请提交失败');
		}
		$data = [
			'admin_id'=>$params['verify_admin_id'],
			'apply_id'=>$apply_id,
		];
		if ($this->applyVerifyDao()->add($data) < 1){
			$applyListDao->rollback();
			throw new CsException('申请提交失败');
		}
		$applyListDao->commit();
	}
	
	// 前端-申请记录
	public function gethistory($params){
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = [
			'admin_id'=>$params['admin_id'],
		];
		$data = $this->applyListDao()->where($map)->order('apply_time desc')->limit($params['pagesize'])->select();
		if($data){
			foreach($data as $k => $vo){
				$data[$k]['apply_name'] = ApplyConst::$appy_type_list[$vo['apply_type']];
				$data[$k]['status_label'] = ApplyConst::$verify_status_list[$vo['verify_status']];
			}
			return $data;
		}
	}
	
	public function verifyPagerList(&$params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = [
			'admin_id'=>$params['operator_id'],
		];
		$params['verify_status'] && $map['verify_status'] = $params['verify_status'];
		$applyVerifyDao = $this->applyVerifyDao();
		$count = $applyVerifyDao->where($map)->count();
		if ($count){
			$list = $applyVerifyDao->field('*')
			->where($map)->page($params['page'], $params['pagesize'])->select();
			foreach ($list as $vo){
				$ids[] = $vo['apply_id'];
				
			}
			$apply_list = $this->applyListDao()->getRecords($ids);
			foreach ($list as $ko => $vo){
				$apply = $apply_list[$vo['apply_id']];
				$apply['verify_id'] = $vo['verify_id'];
				$apply['verify_time'] = $vo['verify_time'];
				$apply['verify_note'] = $vo['verify_note'];
				$apply['apply_name'] = ApplyConst::$appy_type_list[$apply['apply_type']];
				$apply['status_label'] = ApplyConst::$verify_status_list[$vo['verify_status']];
				$apply['verify_status'] = $vo['verify_status'];
				$list[$ko] = $apply;
				$admin_ids[$vo['admin_id']] = $apply['admin_id'];
			}
			$admins = $this->adminDao()->getRecords($admin_ids);
		}
		return [
			'list'=>$list,
			'admins'=>$admins,
			'count'=>$count,
			'page'=>$params['page'],
			'pagesize'=>$params['pagesize'],
		];
	}
	
	public function cancelApply($params){
		$applyListDao = $this->applyListDao();
		$applyListDao->startTrans();
		$map = [
			'apply_id'=>$params['apply_id'],
		];
		$info = $applyListDao->where($map)->lock(true)->find();
		if ($info['admin_id'] != $params['operator_id']){
			$applyListDao->rollback();
			$this->error('记录不存在', 1003);
		}
		if ($info['verify_status'] != ApplyConst::VerifyStatusStart){
			$applyListDao->rollback();
			$this->error('操作失败', 1004);
		}
		//修改申请状态
		$data = [
			'apply_id'=>$info['apply_id'],
			'verify_status'=>ApplyConst::VerifyStatusCancel,
		];
		if ($applyListDao->save($data) < 1){
			$applyListDao->rollback();
			$this->error('操作失败', 1005);
		}
		//删除审批人记录
		$map = [
			'apply_id'=>$info['apply_id'],
		];
		if ($this->applyVerifyDao()->where($map)->delete() === false){
			$applyListDao->rollback();
			$this->error('操作失败', 1006);
		}
		$applyListDao->commit();
	}
	
	public function retryApply($params){
		$applyListDao = $this->applyListDao();
		$applyListDao->startTrans();
		$map = [
			'apply_id'=>$params['apply_id'],
		];
		$info = $applyListDao->where($map)->lock(true)->find();
		if ($info['admin_id'] != $params['operator_id']){
			$applyListDao->rollback();
			$this->error('记录不存在');
		}
		if ($info['verify_status'] != ApplyConst::VerifyStatusFail){
			$applyListDao->rollback();
			$this->error('删除失败');
		}
		$data = [
			'apply_id'=>$params['apply_id'],
			'verify_status'=>ApplyConst::VerifyStatusWaitSignAgain,
		];
		if ($applyListDao->save($data) < 1){
			$applyListDao->rollback();
			$this->error('删除失败');
		}
		//TODO 指定审批人
		$applyListDao->commit();
	}
	
	//审批
	public function verify($params) {
		$applyListDao = $this->applyListDao();
		$applyListDao->startTrans();
		$applyVerifyDao = $this->applyVerifyDao();
		$verify_info = $applyVerifyDao->lock(true)->find($params['verify_id']);
		if ($verify_info['admin_id'] != $params['operator_id']){
			$applyListDao->rollback();
			throw new CsException('记录不存在', 1003);
		}
		if ($verify_info['verify_status'] != ApplyConst::VerifyStatusStart){
			$applyListDao->rollback();
			throw new CsException('审批失败。', 1002);
		}
		$info = $applyListDao->lock(true)->find($verify_info['apply_id']);
		$vl = [ ApplyConst::VerifyStatusStart, ApplyConst::VerifyStatusWaitSignAgain ];
		$verify_status = $params['status'] == 1 ? ApplyConst::VerifyStatusOk : ApplyConst::VerifyStatusFail;
		if (!in_array($info['verify_status'], $vl)){
			$applyListDao->rollback();
			throw new CsException('异常申请单', 1003);
		}
		$data = [
			'apply_id'=>$verify_info['apply_id'],
			'verify_status'=>$verify_status,
		];
		if ($applyListDao->save($data) === false){
			$applyListDao->rollback();
			throw new CsException('操作失败.', 1003);
		}
		$data = [
			'verify_id'=>$verify_info['verify_id'],
			'verify_status'=>$verify_status,
			'verify_note'=>$params['note'],
			'verify_time'=>NOW_TIME,
		];
		if ($this->applyVerifyDao()->save($data) < 1){
			$applyListDao->rollback();
			throw new CsException('操作失败。', 1004);
		}
		//查看审批人是否有相应的审批权限
		$this->checkVerifyPurview($params['verify_admin_id'], $info['apply_type']);
		$data = [
			'admin_id'=>$params['verify_admin_id'],
			'apply_id'=>$info['apply_id'],
		];
		if ($this->applyVerifyDao()->add($data) < 1){
			$applyListDao->rollback();
			throw new CsException('操作失败');
		}
		$applyListDao->commit();
	}
	
	//待审批数
	public function verifyWaitNumber($params){
		$map = $params;
		return $this->applyVerifyDao()->where($map)->count();
	}
	
	// 前端-申请详情
	public function getdetail($id){
		if ($id < 1) return [];
		$data = $this->applyListDao()->getRecord($id);
		$list = $this->ApplyVerifyDao()->order('verify_id desc')->listOfApply($id);
		foreach ($list as $k => $vo){
			$ids[$vo['admin_id']] = $vo['admin_id'];
			$list[$k]['status_label'] = ApplyConst::$verify_status_list[$vo['verify_status']];
		}
		$admins = $this->adminDao()->getRecords($ids);
		foreach ($admins as $ko => $vo){
			$image = picurl($vo['avatar']);
			if (empty($image)){
				$image = avatarUrl();
			}
			$admins[$ko]['admin_image'] = $image;
		}
		$data['admins'] = $admins;
		$data['verify_list'] = $list;
		return $data;
	}
	
	public function getVerifyDetail($params){
		$map = [
			'verify_id'=>$params['verify_id'],
		];
		$info = $this->applyVerifyDao()->where($map)->find();
		if ($info){
			$apply_info = $this->applyListDao()->find($info['apply_id']);
			$apply_info['apply_name'] = ApplyConst::$appy_type_list[$apply_info['apply_type']];
			$apply_info['pics'] = picurls($apply_info['pics']);
			$info['apply_info'] = $apply_info;
			$admin_ids[$apply_info['admin_id']] = $apply_info['admin_id'];
			$map = [
				'apply_id'=>$info['apply_id'],
			];
			$list = $this->applyVerifyDao()->order('verify_id desc')->where($map)->select();
			foreach ($list as $vo){
				$admin_ids[$vo['admin_id']] = $vo['admin_id'];
			}
			$admins = $this->adminDao()->getRecords($admin_ids);
			foreach ($admins as $ko => $vo){
				$admins[$ko]['avatar'] = admin_avatar($admin['avatar']);
			}
			$info['verify_list'] = $list;
			$info['admins'] = $admins;
		}
		return $info;
	}
	
	private function checkApplyPurview($admin_id, $apply_type){
		$admin = $this->adminDao()->getRecord($admin_id);
		if (empty($admin)) throw new CsException('系统错误', 1003);
		return true; //TODO
	}
	
	private function checkVerifyPurview($admin_id, $apply_type){
		$admin = $this->adminDao()->getRecord($admin_id);
		if (empty($admin)) throw new CsException('系统错误', 1003);
		return true; //TODO
	}
	
	public function transfer($params){
		$applyListDao = $this->applyListDao();
		$applyListDao->startTrans();
		$applyVerifyDao = $this->applyVerifyDao();
		$verify_info = $applyVerifyDao->lock(true)->find($params['verify_id']);
		if ($verify_info['admin_id'] != $params['operator_id']){
			$applyListDao->rollback();
			throw new CsException('记录不存在', 1003);
		}
		if ($verify_info['verify_status'] != ApplyConst::VerifyStatusStart){
			$applyListDao->rollback();
			throw new CsException('操作失败。', 1002);
		}
		$info = $applyListDao->lock(true)->find($verify_info['apply_id']);
		$vl = [ ApplyConst::VerifyStatusStart, ApplyConst::VerifyStatusWaitSignAgain ];
		if (!in_array($info['verify_status'], $vl)){
			$applyListDao->rollback();
			throw new CsException('异常记录', 1003);
		}
		//查看审批人是否有相应的审批权限
		$this->checkVerifyPurview($params['verify_admin_id'], $info['apply_type']);
		$data = [
			'admin_id'=>$params['verify_admin_id'],
			'apply_id'=>$info['apply_id'],
		];
		if ($this->applyVerifyDao()->add($data) < 1){
			$applyListDao->rollback();
			throw new CsException('操作失败');
		}
		$data = [
			'verify_id'=>$verify_info['verify_id'],
			'verify_status'=>ApplyConst::VerifyStatusTransfer,
			'verify_time'=>NOW_TIME,
		];
		if ($this->applyVerifyDao()->save($data) < 1){
			$applyListDao->rollback();
			throw new CsException('操作失败。', 1004);
		}
		$applyListDao->commit();
	}
	
	private function applyTypeDao(){
		return D('Common/Apply/Type');
	}
	
	private function applyListDao(){ 
		return D('Common/Apply/List');
	}
	
	private function adminDao(){
		return D('Common/Admin/AdminInfo');
	}
	
	private function applyVerifyDao(){
		return D('Common/Apply/Verify');
	}
}