<?php
namespace Common\Service;
use Common\Basic\Pager;
class DesignerCaseService{
	
	public function getGroupField($map,$group='region_code',$field='region_code',$orderby='case_id desc'){
		return $this->designerCaseDao()->groupFieldRecord($map,$group,$field,$orderby);
	}
	
	//help_info
	public function getInfo($id){
		if ($id < 1) return false;
		
		$info=$this->designerCaseDao()->getRecord($id);
		$distributor_id=$this->designerInfoDao()->getFieldRecord(array('designer_id'=>$info['designer_id']),'distributor_id');
		$region=$this->distributorDao()->getFieldRecord(array('distributor_id'=>$distributor_id),'region_code');
		$city=$this->regionDao()->getCity($region);
		$info['city']=$city;
		return $info;
	}
	public function infoCreateOrModify($params){
		// 自动验证
		$rules = array(
			// array('article_title', 'require', '名称是必须的'),
		);
		
		$deisnger_info=$this->designerInfoDao()->getRecord($params['designer_id']);
		
		//参数
		$data = array(
			'case_name'=>$params['case_name'],
			'houses'=>$params['houses'],
			'decorate_type'=>$params['decorate_type'],	
			'layout_type'=>$params['layout_type'],	
			'country'=>$params['country'],
			'district'=>$params['district'],
			'custom_type'=>$params['custom_type'],
			'progress'=>$params['progress'],
			'area'=>$params['area'],	
			'budget'=>$params['budget'],	
			'intro'=>$params['intro'],	
			'description'=>$params['description'],	
			'sort_order'=>$params['sort_order'],	
			'designer_id'=>$params['designer_id'],
			'region_code'=>$deisnger_info['region_code'],
			'distributor_id'=>$params['distributor_id'],
		);
		
		$params['picture']!='' && $data['picture']=$params['picture'];	
		$params['gallery']!='' && $data['gallery']=$params['gallery'];	
		
		
		
		if($params['case_id'] > 0){
			$data['case_id'] = $params['case_id'];
			unset($data['designer_id']);
		}else{
			$data['add_time']=time();
		}
		
		$designerCaseDao = $this->designerCaseDao();
		if (!$designerCaseDao->validate($rules)->create($data)){
			 throw new \Exception($designerCaseDao->getError());
		}
		if ($params['case_id'] > 0){
			$result = $designerCaseDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $designerCaseDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}	
	public function infoDelete($id){
		$result = $this->designerCaseDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	//分页查询
	public function infoPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		$params['case_id'] > 0 && $map['case_id'] = $params['case_id'];
		$params['designer_id'] > 0 && $map['designer_id'] = $params['designer_id'];
		$params['layout_type'] > 0 && $map['layout_type'] = $params['layout_type'];
		$params['decorate_type'] > 0 && $map['decorate_type'] = $params['decorate_type'];
		$params['district'] > 0 && $map['district'] = $params['district'];
		$params['country'] > 0 && $map['country'] = $params['country'];
		$params['distributor_id'] > 0 && $map['distributor_id'] = $params['distributor_id'];
		$params['is_hot'] > 0 && $map['is_hot'] = $params['is_hot'];
		$params['keyword'] && $map['case_name'] = array('like', '%'.trim($params['keyword']).'%');
		
		if(!empty($params['map'])){
			$map=array_merge($map,$params['map']);
		}
		
		$designerCaseDao = $this->designerCaseDao();
		$count = $designerCaseDao->searchRecordsCount($map);
		$list = array();
		
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order ASC, case_id DESC' : $params['orderby'];
			$list = $designerCaseDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
			foreach($list as $key=>$val){
				$designer_ids[]=$val['designer_id'];
				$list[$key]['region_name']=$this->regionDao()->getProvinceCity($val['region_code']);
			}
			
			$designers=$this->designerInfoDao()->getIdsRecord($designer_ids);
		}
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
			'designers'=>$designers,
		);
	}
	
	//添加案例浏览记录
	public function caseLogCreate($params){
		$ip=get_client_ip();
		empty($params['user_id']) && $params['user_id']=0;
		
		//找到同个人浏览相同的案例就把之前的案例更新时间
		$map=array("_string"=>"ip='{$ip}' or user_id={$params['user_id']}",'case_id'=>$params['case_id']);
		$case_log_info=$this->designerCaseLogDao()->findRecord($map);
		if(!empty($case_log_info)){
			$save_data=array('add_time'=>NOW_TIME,'log_id'=>$case_log_info['log_id']);
			return $this->designerCaseLogDao()->saveRecord($save_data);die();
		}
		
		$data=array(
					'user_id'=>$params['user_id'],
					'case_id'=>$params['case_id'],
					'designer_id'=>$params['designer_id'],
					'ip'=>get_client_ip(),
					'add_time'=>NOW_TIME,
					);
		return $this->designerCaseLogDao()->addRecord($data);
	}
	
	//浏览记录列表
	public function caseLogPagerList($params){
		$ip=get_client_ip();
		$params['user_id'] || $params['user_id']=0;
		$map=array("_string"=>"ip='{$ip}' or user_id={$params['user_id']}");
		
		$log_result=$this->designerCaseLogDao()->getFieldRecord($map,"log_id,case_id,designer_id",$params['page'],$params['pagesize']);
		
		if(!empty($log_result)){
			foreach($log_result as $key=>$val){
				$case_id[]=$val['case_id'];
				$designer_id[]=$val['designer_id'];
			}
			$design_list=$this->designerInfoDao()->getFieldRecord(array('designer_id'=>array('in',$designer_id)));
			$case_list=$this->designerCaseDao()->searchRecords(array('case_id'=>array('in',$case_id)));
			foreach($case_list as $key=>$val){
				$case_list[$key]['designer_name']=$design_list[$val['designer_id']];
				$case_list[$key]['gallery_array']=$val['gallery']!=''?unserialize($val['gallery']):array();
			}
		}
		return $case_list;
	}
	
	public function caseCount($params){
		$map = array();
		if ($params['distributor_id']) {
			$map['distributor_id'] = $params['distributor_id'];
		}
		return $this->designerCaseDao()->searchRecordsCount($map);
	}
	
	//案例浏览记录+1
	public function caseAddClickNumber($case_id){
		if(!empty($case_id)){
			$map=array('case_id'=>$case_id);
			$this->designerCaseDao()->where($map)->setInc('click_number');
		}

	}
	
	//里推荐首页
	public function caseIsIndex($case_id){
		$info=$this->getInfo($case_id);
		if(empty($info)){throw new \Exception('推荐首页失败');}
		$data=array('case_id'=>$case_id,'is_index'=>($info['is_index']==1?0:1));
		
		$result=$this->designerCaseDao()->save($data);
		
		if($result==false){
			throw new \Exception('推荐首页失败');
		}
	}
	
	public function caseIsHot($case_id){
		$info=$this->getInfo($case_id);
		if(empty($info)){throw new \Exception('推荐首页失败');}
		$data=array('case_id'=>$case_id,'is_hot'=>($info['is_hot']==1?0:1));
	
		$result=$this->designerCaseDao()->save($data);
	
		if($result==false){
			throw new \Exception('设置失败');
		}
	}
	
	private function designerInfoDao(){
		return D('Common/Designer/DesignerInfo');
	}
	
	private function designerCaseDao(){
		return D('Common/Designer/DesignerCase');
	}
	
	private function designerCaseConfigDao(){
		return D('Common/Designer/DesignerCaseConfig');
	}
	
	private function designerCaseLogDao(){
		return D('Common/Designer/DesignerCaseLog');
	}
	
	private function distributorDao(){
		return D('Common/Distributor/Info');
	}
	
	private function regionDao(){
		return D('Common/Region');
	}
	
	
	public function getConfig($id){
		if ($id < 1) return false;
		$info=$this->designerCaseConfigDao()->getRecord($id);
		return $info;
	}
	public function ConfigCreateOrModify($params){
		// 自动验证
		$rules = array(
			// array('article_title', 'require', '名称是必须的'),
		);
		//参数
		$data = array(
			'name'=>$params['name'],
			'sort_order'=>$params['sort_order'],	
			'type'=>$params['type'],		
		);

		
		
		
		if($params['id'] > 0){
			$data['id'] = $params['id'];
		}
		
		$designerCaseConfigDao = $this->designerCaseConfigDao();
		if (!$designerCaseConfigDao->validate($rules)->create($data)){
			 throw new \Exception($designerCaseConfigDao->getError());
		}
		if ($params['id'] > 0){
			$result = $designerCaseConfigDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $designerCaseConfigDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}	
	public function ConfigDelete($id){
		$result = $this->designerCaseConfigDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	//分页查询
	public function configPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		
		if(!empty($params['map'])){
			$map=array_merge($map,$params['map']);
		}
		
		$designerCaseConfigDao = $this->designerCaseConfigDao();
		$count = $designerCaseConfigDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order ASC, id DESC' : $params['orderby'];
			$list = $designerCaseConfigDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
		);
	}
	
	public function configAllList($params){
		$map = array();
		if(!empty($params['map'])){
			$map=array_merge($map,$params['map']);
		}
		$orderby = empty($params['orderby']) ? 'sort_order ASC, id DESC' : $params['orderby'];
		return $this->designerCaseConfigDao()->allRecords($map, $orderby);
	}
	
	//获取案例配置值列表
	public function configGetField($map,$field='id,name',$page=1,$pagesize=1000){
		return $this->designerCaseConfigDao()->getFieldRecord($map,$field,$page,$pagesize);
	}
	
	//案例设置推荐首页
	public function configIsIndex($case_id){
		$info=$this->getConfig($case_id);
		if(empty($info)){throw new \Exception('推荐首页失败');}
		$data=array('id'=>$case_id,'is_index'=>($info['is_index']==1?0:1));
		
		$result=$this->designerCaseConfigDao()->save($data);
		
		if($result==false){
			throw new \Exception('推荐首页失败');
		}
	}
	
	
	//分页查询
	public function typeConfigPagerList($map,$field='type_id,type_name'){
		return $this->designerConfigTypeConfigDao()->getFieldRecord($map,$field);
	}
	
	
	private function designerConfigTypeConfigDao(){
		return D('Common/Designer/DesignerConfigType');
	}
	
}//end HelpService!