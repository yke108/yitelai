<?php
namespace Common\Service;
use Common\Basic\Pager;
class DesignerCustomService{

	//help_info
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->designerCustomDao()->getRecord($id);
	}
	
	public function remark($params) {
		// 自动验证
		$rules = array(
				array('remark', 'require', '备注内容不能为空'),
		);
		//参数
		$data = array(
				'remark'=>$params['remark'],
				'custom_id'=>$params['custom_id']
		);
		if (!$this->designerCustomDao()->validate($rules)->create($data)){
			throw new \Exception($this->designerCustomDao()->getError());
		}
		$result = $this->designerCustomDao()->saveRecord($data);
		if ($result === false){
			throw new \Exception('修改失败');
		}
	}
	
	public function infoCreateOrModify($params){
		// 自动验证
		$rules = array(
			// array('article_title', 'require', '名称是必须的'),
		);
		//参数
		$data = array(
			'user_id'=>$params['user_id'],
			'distributor_id'=>$params['distributor_id'],
			'name'=>$params['name'],	
			'tel'=>$params['tel'],	
			'estate_name'=>$params['estate_name'],
			'house_area'=>$params['house_area'],	
			'ip'=>get_client_ip(),	
			'add_time'=>time(),
		);
		
		
		//根据ip判断提交是否太过频繁
		$space_time=60*5;
		$check_map=array('user_id'=>$params['user_id']);
		$msg_info=$this->designerCustomDao()->findRecord($check_map,'add_time desc');
		
		if(!empty($msg_info) && (NOW_TIME-$msg_info['add_time'])<$space_time){
			throw new \Exception('提交太过频繁');
		}
		
		
		
		$designerCustomDao = $this->designerCustomDao();
		if (!$designerCustomDao->validate($rules)->create($data)){
			 throw new \Exception($designerCustomDao->getError());
		}
		
		$result = $designerCustomDao->addRecord($data);
		if ($result < 1){
			throw new \Exception('添加失败');
		}
		
	}	
	public function infoDelete($id){
		$result = $this->designerCustomDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	//分页查询
	public function infoPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		$params['custom_id'] > 0 && $map['custom_id'] = $params['custom_id'];
		$designerCustomDao = $this->designerCustomDao();
		$count = $designerCustomDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? ' custom_id DESC' : $params['orderby'];
			$list = $designerCustomDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
			foreach($list as $key=>$val){
				$user_id[]=$val['user_id'];
			}
			$user_id=array_unique($user_id);
			$user_list=$this->userInfoDao()->getFieldRecord(array('user_id'=>array('in',$user_id)),'user_id,nick_name');
			foreach($list as $key=>$val){
				$list[$key]['nick_name']=$user_list[$val['user_id']];
			}
		}
		
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
		);
	}
	
	private function userInfoDao(){
		return D('Common/User/UserInfo');
	}
	
	private function designerCustomDao(){
		return D('Common/Designer/DesignerCustom');
	}
	
	private function regionDao(){
		return D('Common/Region');
	}
	
	
}//end HelpService!