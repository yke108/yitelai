<?php
namespace Common\Service;
use Common\Basic\Pager;
use Common\Basic\Status;
class DesignerMessageService{

	//help_info
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->designerMessageDao()->getRecord($id);
	}
	
	public function remark($params) {
		// 自动验证
		$rules = array(
				array('remark', 'require', '备注内容不能为空'),
		);
		//参数
		$data = array(
				'remark'=>$params['remark'],
				'id'=>$params['id']
		);
		if (!$this->DesignerMessageDao()->validate($rules)->create($data)){
			throw new \Exception($this->DesignerMessageDao()->getError());
		}
		$result = $this->DesignerMessageDao()->saveRecord($data);
		if ($result === false){
			throw new \Exception('修改失败');
		}
	}
	
	public function infoCreateOrModify($params){
		//自动验证
		$rules = array(
				array('nick_name', 'require', '名称是必须的'),
				array('mobile', 'require', '电话是必须的'),
				array('district', 'require', '地区是必须的'),
		);
		
		//参数
		$data = array(
			'nick_name'=>$params['nick_name'],	
			'mobile'=>$params['mobile'],	
			'user_id'=>$params['user_id'],
			'province'=>$params['province'],	
			'city'=>$params['city'],	
			'district'=>$params['district'],
			'content'=>$params['content'],
			'type_id'=>$params['type_id'],
			'add_time'=>time(),
			'ip'=>get_client_ip(),
		);
		$params['content']!='' && $data['content']=$params['content'];	
		
		//根据ip判断提交是否太过频繁
		$space_time=60*5;
		$check_map=array('ip'=>get_client_ip());
		$msg_info=$this->DesignerMessageDao()->findRecord($check_map,'add_time desc');
		if(!empty($msg_info) && (NOW_TIME-$msg_info['add_time'])<$space_time){
			throw new \Exception('提交太过频繁');
		}
		
		$designerMessageDao = $this->designerMessageDao();
		if (!$designerMessageDao->validate($rules)->create($data)){
			 throw new \Exception($designerMessageDao->getError());
		}
		
		M()->startTrans();
		
		if ($params['id'] > 0){
			$result = $designerMessageDao->saveRecord($data);
			if ($result === false){
				M()->rollback();
				throw new \Exception('修改失败');
			}
		} else {
			$result = $designerMessageDao->addRecord($data);
			if ($result < 1){
				M()->rollback();
				throw new \Exception('添加失败');
			}
		}
		
		//添加到客户池
		$map = array('mobile'=>$params['mobile']);
		$user_info = $this->userInfoDao()->findUser($map);
		if (empty($user_info)) {
			$province = $this->regionDao()->getDistrictOfProvince($params['district']);
			$data = array(
					'real_name'=>$params['nick_name'],
					'mobile'=>$params['mobile'],
					'province'=>current($province),
					'city'=>$this->regionDao()->getCity($params['city']),
					'from'=>Status::FromSign,
					'from_url'=>$params['from_url'],
					'reg_time'=>NOW_TIME,
			);
			if ($params['content']) {
				$data['demand'] = $params['content'];
			}
			if ($params['type_id']) {
				$type_info = $this->designerMessageTypeDao()->getRecord($params['type_id']);
				$data['demand'] = $type_info['type_name'];
			}
			$result = $this->userInfoDao()->addRecord($data);
			if ($result === false) {
				M()->rollback();
				throw new \Exception('系统错误');
			}
		}
		
		M()->commit();
		
		return array(
				'message_count'=>$this->getCount()
		);
	}	
	public function infoDelete($id){
		$result = $this->designerMessageDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	//分页查询
	public function infoPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		$params['id'] > 0 && $map['id'] = $params['id'];
		if (!empty($params['keyword'])) {
			$map['nick_name|mobile'] = array('like', '%'.trim($params['keyword']).'%');
		}
		if (!empty($params['start_time'])) {
			$map['add_time'][] = array('egt', strtotime($params['start_time']));
		}
		if (!empty($params['end_time'])) {
			$map['add_time'][] = array('elt', strtotime($params['end_time']) + 86400);
		}
		
		$designerMessageDao = $this->designerMessageDao();
		$count = $designerMessageDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? ' id DESC' : $params['orderby'];
			$list = $designerMessageDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
			
			$types = $this->designerMessageTypeDao()->searchFieldRecords();
			foreach($list as $key=>$val){
				$list[$key]['region']=$this->regionDao()->getRegionName($val['district']);
				$list[$key]['type_name'] = $types[$val['type_id']]['type_name'];
			}
		}
		
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
		);
	}
	
	public function getCount(){
		return $this->designerMessageDao()->searchRecordsCount();
	}
	
	private function designerMessageDao(){
		return D('Common/Designer/DesignerMessage');
	}
	
	private function designerMessageTypeDao(){
		return D('Common/Designer/DesignerMessageType');
	}
	
	private function regionDao(){
		return D('Common/Region');
	}
	
	private function userInfoDao(){
		return D('Common/User/UserInfo');
	}
}//end HelpService!