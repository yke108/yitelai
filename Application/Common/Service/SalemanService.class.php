<?php
namespace Common\Service;
class SalemanService{
	//page_info
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->userApplyDao()->getRecord($id);
	}
	
	public function userGetInfo($user_id){
		$map=array('user_id'=>$user_id);
		return $this->userApplyDao()->findRecord($map);
	}
	
	public function infoCreateOrModify($params){
		if(empty($params['user_id'])){
			throw new \Exception('缺少参数');
		}
		// 自动验证
		$rules = array(
				array('city', 'require', '所在城市不能为空'),
				array('name', 'require', '姓名不能为空'),
				array('tel', 'require', '手机号码不能为空'),
				array('tel','/^((\(\d{2,3}\))|(\d{3}\-))?13\d{9}$/','手机号码格式不正确'),
				array('card_no', 'require', '银行卡号不能为空'),
				array('card_no','/^[1-9][0-9]{15,18}/','银行卡号格式不正确'),
				array('id_no', 'require', '身份证号不能为空'),
				array('id_no','/(^\d{15}$)|(^\d{17}([0-9]|X)$)/','身份证号格式不正确'),
				array('weixin_account', 'require', '微信号不能为空'),
				array('brand', 'require', '分销品牌不能为空'),
				array('reason', 'require', '申请理由不能为空'),
		);
		
		$check_map=array('user_id'=>$params['user_id']);
		$check_info=$this->userApplyDao()->findRecord($check_map);
		if(!empty($check_info)){
			throw new \Exception('您已经提交过申请。');
		}
		
		$distributor_id=$this->userInfoDao()->where(array('user_id'=>$params['user_id']))->getField('distributor_id');
		
		$params['type']=$params['type']?$params['type']:1;
		//参数
		$data = array(
			'user_id'=>trim($params['user_id']),
			'city'=>trim($params['city']),
			'name'=>trim($params['name']),
			'tel'=>trim($params['tel']),
			'weixin_account'=>$params['weixin_account'],
			'brand'=>$params['brand'],
			'reason'=>$params['reason'],
			'proposal'=>$params['proposal'],
			'status'=>$params['type']==1?1:0,
			'distributor_id'=>$params['type']==1?0:$distributor_id,
			'notice'=>$params['notice'],			
			'type'=>$params['type'],		
			'add_time'=>time(),	
			// 'sort_order'=>$params['sort_order'],		
		);
		
		if($params['apply_id'] > 0){
			$data['apply_id'] = $params['apply_id'];
		}
		$userApplyDao = $this->userApplyDao();
		if (!$userApplyDao->validate($rules)->create($data)){
			 throw new \Exception($userApplyDao->getError());
		}
		
		$result = $userApplyDao->addRecord($data);
		if ($result < 1){
			throw new \Exception('添加失败');
		}
		
	}
	
	public function infoDelete($id){
		$result = $this->userApplyDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	//分页查询
	public function infoPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		//搜索
		$map = array();
		$params['status'] && $map['status']=$params['status'];
		if(!empty($params['map'])){
			$map=array_merge($map,$params['map']);
		}
		if(!empty($params['keyword'])){
			$user_map=array('_string'=>"mobile like '%{$params[keyword]}%' or nick_name like '%{$params[keyword]}%'");
			$user_ids=$this->userInfoDao()->getFieldRecord($user_map,"user_id",true);
			empty($user_ids) && $user_ids=array();
			
			$apply_user_map=array('_string'=>"tel like '%{$params[keyword]}%' or name like '%{$params[keyword]}%'");
			$apply_user_ids=$this->userApplyDao()->getFieldRecord($apply_user_map,"user_id",true);
			
			empty($apply_user_ids) && $apply_user_ids=array();
			$merge_user_ids=array_merge($user_ids,$apply_user_ids);
			$merge_user_ids=array_unique($merge_user_ids);
			if(!empty($merge_user_ids)){
				$map['user_id']=array('in',$merge_user_ids);
			}else{
				$map['user_id']=null;
			}
		}
		//!empty($params['keyword']) && $map['names'] = array('like','%'.trim($params['keyword']).'%');
		!empty($params['distributor_id']) && $map['distributor_id'] = $params['distributor_id'];
		!empty($params['brand']) && $map['brand'] = array('like','%'.$params['brand'].'%');
		if (!empty($params['start_time'])) {
			$map['add_time'][] = array('egt', strtotime($params['start_time']));
		}
		if (!empty($params['end_time'])) {
			$map['add_time'][] = array('elt', strtotime($params['end_time']) + 86400);
		}
		
		$userApplyDao = $this->userApplyDao();
		$count = $userApplyDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'add_time desc' : $params['orderby'];
			$list = $userApplyDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		
		$result = $this->outPutForList($list);
		return array(
			'list'=>$result['list'],
			'count'=>$count,
			'users'=>$result['users'],
		);
	}
	
	public function check($data, $map){
		$info=$this->getInfo($data['apply_id']);
		if(empty($info)){
			throw new \Exception('审核失败');
		}
		
		$userApplyDao=$this->userApplyDao();
		$userApplyDao->startTrans();
		if($data['status']==3){
			$user_data=array('user_type'=>2,'status'=>3,'user_id'=>$info['user_id']);
			if($info['type']==1){
				if($data['distributor_id']==''){
					throw new \Exception('请选择所属运营商');
				}
				$user_data['distributor_id']=$data['distributor_id'];
			}
			$user_result=$this->userInfoDao()->saveRecord($user_data);
			
			if($user_result==false){
				$userApplyDao->rollback();
				throw new \Exception('审核失败');
			}
		}
		$apply_data=array(
						'status'=>$data['status'],
						'feedback'=>$data['feedback'],
						'apply_id'=>$info['apply_id'],
						'set_distributor_id'=>$data['distributor_id'],
					);
		$result=$userApplyDao->saveRecord($apply_data);
		if($result==false){
			$userApplyDao->rollback();
			throw new \Exception('审核失败');
		}
		$userApplyDao->commit();
	}
	
	private function outPutForList($list) {
		if (!empty($list)) {
			$user_ids = $distributor_ids = array();
			foreach($list as $k => $v){
				$user_ids[] = $v['user_id'];
				$distributor_ids[] = $v['distributor_id'];
			}
			$users = $this->userInfoDao()->getUsers($user_ids);
			$distributors = $this->distributorInfoDao()->getDistributorsByIds($distributor_ids);
			
			foreach($list as $k => $v){
				$list[$k]['region_name'] = $this->regionDao()->getProvinceCity($v['city']);
				$list[$k]['distributor_name'] = $distributors[$v['distributor_id']];
			}
		}
		
		return array(
				'list'=>$list,
				'users'=>$users,
		);
	}
	
	//调用model
	private function userApplyDao(){
		return D('Common/User/UserApply');
	}
	
	private function userInfoDao(){
		return D('Common/User/UserInfo');
	}
	
	private function regionDao(){
		return D('Common/Region');
	}
	
	private function distributorInfoDao(){
		return D('Common/Distributor/Info');
	}
}//end HelpService!