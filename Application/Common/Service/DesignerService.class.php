<?php
namespace Common\Service;
class DesignerService{
	//设计师
	public function getInfo($id,$user_id){
		if ($id < 1) return false;
		$info=$this->designerInfoDao()->getRecord($id);
		$info['case_count']=$this->designerCaseDao()->searchRecordsCount(array('designer_id'=>$info['designer_id']));
		$info['follow_count']=$this->designerFollowDao()->searchRecordsCount(array('designer_id'=>$info['designer_id']));
		$region=$this->regionDao()->getRegionNameTwo($info['region_code']);
		
		$all_space_result=$this->designerTypeValueDao()->getFieldRecord(array('type'=>'space'),'id,name');
		$all_space_key=array_keys($all_space_result);
		$type_list=$this->designerTypeValueDao()->getFieldRecord();
		
		
		$region_arr=explode(' ',$region);
		$info['city_district']=$region_arr[1].$region_arr[2];
		$info['city']=$region_arr[1];
		$info['charge_name']=$type_list[$info['charge']];
		$info['demand_name']=str_replace($all_space_key,$all_space_result,$val['demand']);
		$info['tel_hide'] = substr($info['tel'], 0, 3).'****'.substr($info['tel'], -4, 4);
		
		if($user_id!=''){
			$follow_map=array('designer_id'=>$id,'user_id'=>$user_id);
			$follow_info=$this->designerFollowDao()->findRecord($follow_map);
			if(!empty($follow_info)){$info['is_follow']=1;}
		}
		return $info;
	}
	
	public function viewnum($designer_id) {
		return $this->designerInfoDao()->where(array('designer_id'=>$designer_id))->setInc('view_num');
	}
	
	public function infoCreateOrModify($params){
		// 自动验证
		$rules = array(
			array('designer_name', 'require', '名称是必须的'),
		);
		
		$distributor_info=$this->distributorDao()->findRecord(array('distributor_id'=>$params['distributor_id']));
		
		//参数
		$decorate = '';
		if ($params['decorate']) {
			$decorate = implode(',', $params['decorate']);
			$decorate = ','.$decorate.',';
		}
		$data = array(
			'designer_image'=>trim($params['designer_image']),
			'designer_name'=>trim($params['designer_name']),
			'tel'=>trim($params['tel']),
			'designer_intro'=>trim($params['designer_intro']),
			'designer_desc'=>trim($params['designer_desc']),
			'sort_order'=>trim($params['sort_order']),
			'demand'=>trim($params['demand'], ','),
			'charge'=>trim($params['charge']),
			'level'=>trim($params['level']),
			'is_famous'=>trim($params['is_famous']),
			'is_authentication'=>trim($params['is_authentication']),
			'decorate'=>$decorate,
		);
		
		if($params['designer_id'] > 0){
			$data['designer_id'] = $params['designer_id'];
			$data['add_time'] = $params['add_time'];
			$data['update_time'] = time();
		}else{
			$data['distributor_id'] = trim($params['distributor_id']);
			$data['add_time'] = time();
			$data['update_time'] = time();
			$data['region_code']=$distributor_info['region_code'];
		}

		$designerInfoDao = $this->designerInfoDao();
		if (!$designerInfoDao->validate($rules)->create($data)){
			 throw new \Exception($designerInfoDao->getError());
		}
		if ($params['designer_id'] > 0){
			$result = $designerInfoDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $designerInfoDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function infoUpdate($data){
		return $this->designerInfoDao()->saveRecord($data);
	}
	
	//
	public function infoDelete($id){
		//如果设计有案例，提示先删除案例
		$case_info = $this->designerCaseDao()->where(array('designer_id'=>$id))->find();
		if ($case_info) {
			throw new \Exception('请先删除设计师案例');
		}
		
		$result = $this->designerInfoDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	//分页查询
	public function infoPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		//管理员id
		$params['admin_id'] > 0 && $map['admin_id'] = $params['admin_id'];
		//分销商id
		!empty($params['distributor_id']) && $map['distributor_id'] = $params['distributor_id'];
		//认证
		!empty($params['is_authentication']) && $map['is_authentication'] = $params['is_authentication'];
		//搜索
		!empty($params['keyword']) && $map['designer_name|designer_intro'] = array('like','%'.$params['keyword'].'%');
		//推荐页面顶部
		$params['is_page_top']==1 && $map['is_page_top']=1;
		$params['is_index']==1 && $map['is_index']=1;
		//需求空间
		!empty($params['demand']) && $map['demand'] = array('like','%,'.$params['demand'].',%');
		//设计师收费
		!empty($params['charge']) && $map['charge'] = array('like','%'.$params['charge'].'%');
		//城市筛选
		!empty($params['city']) && $map['_string']=" floor(region_code/100)*100={$params['city']} ";
		//风格筛选
		!empty($params['decorate']) && $map['decorate'] = array('like','%,'.$params['decorate'].',%');
		if(!empty($params['store_name'])){
			$search_map=array('distributor_name'=>array('like',"%{$params[store_name]}%"));
			$distributor_ids=$this->distributorInfoDao()->getFieldRecord($search_map,'distributor_id',true);
			if(!empty($distributor_ids)){
				$map['distributor_id']=array('in',$distributor_ids);
			}else{
				$map['distributor_id']=null;
			}
		}
		if (!empty($params['start_time'])) {
			$map['add_time'][] = array('egt', strtotime($params['start_time']));
		}
		if (!empty($params['end_time'])) {
			$map['add_time'][] = array('elt', strtotime($params['end_time']) + 86400);
		}
		if(!empty($params['map'])){
			$map=array_merge($map,$params['map']);
		}
		//var_dump($map);die();
		
		$designerInfoDao = $this->designerInfoDao();
		$count = $designerInfoDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order desc' : $params['orderby'];
			$list = $designerInfoDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
			if(isset($params['get_case'])==true){//获取案例列表
				foreach($list as $key=>$val){
					$region=$this->regionDao()->getRegionNameTwo($val['region_code']);
					$region_arr=explode(' ',$region);
					$case_map=array('designer_id'=>$val['designer_id']);
					$list[$key]['case_list']=$this->designerCaseDao()->searchRecords($case_map,'case_id desc',1,4);
					$list[$key]['case_count']=$this->designerCaseDao()->searchRecordsCount($case_map);
					$region=$this->distributorDao()->getFieldRecord(array('distributor_id'=>$val['distributor_id']),'region_code');
					$list[$key]['region']=$region_arr[0].$region_arr[1];
					$list[$key]['demand']=trim($val['demand'],',');
				}
			}
			foreach($list as $key=>$val){
				$space.=trim($val['demand'],',').',';
				$charge_id[]=$val['charge'];
				//所属店铺
				$distributor_ids[] = $val['distributor_id'];
			}
			
			$all_space_result=$this->designerTypeValueDao()->getFieldRecord(array('type'=>'space'),'id,name');
			$all_space_key=array_keys($all_space_result);
			$type_list=$this->designerTypeValueDao()->getFieldRecord();
			
			//所属店铺
			$map = array('distributor_id'=>array('in', $distributor_ids));
			$distributors = $this->distributorDao()->where($map)->getField('distributor_id, distributor_name');
			foreach($list as $key=>$val){
				//$list[$key]['space_name']=str_replace($all_space_key,$all_space_result,$val['demand']);
				$val['demand'] = $val['demand'] ? explode(',', $val['demand']): array();
				$demand = array();
				if ($val['demand']) {
					foreach ($val['demand'] as $v2) {
						if (in_array($v2, $all_space_key)) {
							$demand[] = $v2;
						}
					}
				}
				$demand = $demand ? implode(',', $demand) : '';
				$list[$key]['space_name']=str_replace($all_space_key,$all_space_result,$demand);
				$list[$key]['charge_name']=$type_list[$val['charge']];
				$list[$key]['case_count']=$this->designerCaseDao()->searchRecordsCount(array('designer_id'=>$val['designer_id']));
				$list[$key]['region_lang']=$this->regionDao()->getRegionNameTwo($val['region_code']);
				$list[$key]['order_count']=$this->orderDao()->searchRecordsCount(array('designer_id'=>$val['designer_id']));
				$list[$key]['follow_count']=$this->designerFollowDao()->searchRecordsCount(array('designer_id'=>$val['designer_id']));
				//所属店铺
				$list[$key]['distributor_name'] = $distributors[$val['distributor_id']];
				//隐藏手机号
				$list[$key]['tel_hide'] = substr($val['tel'], 0, 3).'****'.substr($val['tel'], -4, 4);
			}
		}
		
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
		);
	}
	public function infoList($params){
		empty($params['orderby']) && $orderBy = 'sort_order asc';
		$params['distributor_id'] > 0 && $map['distributor_id'] = $params['distributor_id'];
		$params['admin_id'] > 0 && $map['admin_id'] = $params['admin_id'];
		return  $this->designerInfoDao()->allRecords($map,$orderBy);
	}
	public function infoCount($map){
		return $this->designerInfoDao()->searchRecordsCount($map);
	}
	public function infoname(){
		$l =  $this->designerInfoDao()->allRecords();
		foreach ($l as $key => $value) {
			$list[$value['designer_id']] = $value['designer_name'];
		}
		return $list;
	}
	
	//获取城市列表
	public function getDesignerCity($map,$limit){
		$designer_region=$this->designerInfoDao()->getFieldGroupRecord($map,'designer_id,floor(region_code/100)*100 city','floor(region_code/100)',7);
		
		foreach($designer_region as $key=>$val){
			$region_name=$this->regionDao()->getFieldRecord(array('region_code'=>array('in',$designer_region)),'region_code,region_name');
		}
		
		return $region_name;
		
		
	}
	
	//设计师推荐首页
	public function designerIsIndex($designer_id){
		$info=$this->getInfo($designer_id);
		if(empty($info)){throw new \Exception('推荐首页失败');}
		$data=array('designer_id'=>$designer_id,'is_index'=>($info['is_index']==1?0:1));
		
		$result=$this->designerInfoDao()->save($data);
		
		if($result==false){
			throw new \Exception('推荐首页失败');
		}
	}
	
	//设计师重点推荐首页
	public function designerIsKeyIndex($designer_id){
		$info=$this->getInfo($designer_id);
		if(empty($info)){throw new \Exception('重点推荐首页失败');}
		$data=array('designer_id'=>$designer_id,'is_key_index'=>($info['is_key_index']==1?0:1));
		
		$result=$this->designerInfoDao()->save($data);
		
		if($result==false){
			throw new \Exception('重点推荐首页失败');
		}
		if($data['is_key_index']==1){
			$map="designer_id!={$designer_id} and designer_id>0";
			$save_data=array('is_key_index'=>0);
			$result=$this->designerInfoDao()->where($map)->save($save_data);
		}
	}

	public function designerInfoListService($distributor_id, $content, $params){
		$where = array();
		if($content){
			$where['designer_name'] = array('like','%'.$content.'%');
		}
		if($distributor_id){
			$where['distributor_id'] = $distributor_id;
		}
		$field = array('designer_id','distributor_id','designer_image','designer_name');
		$orderBy = array();
		$count = $this->designerInfoDao()->searchRecordsCount($where);
		$data = $this->designerInfoDao()->searchRecordsList($where, $field, $orderBy, $params['page'], $params['pagesize']);
		$_list = array();
		foreach ($data as $key => $val) {
			$_t = $val;
			$designerOrderCount = D('DesignerOrder')->field(array('designer_id'))->where(array('designer_id' => $val['designer_id']))->count();
			if($val['designer_image']){
				$_t['designer_image'] = domain_name_url.'/upload/'.$val['designer_image'];
			} else {
				$_t['designer_image'] = domain_name_url.'/public/main/images/user_default_img.jpg';
			}
			$_t['designerOrderCount'] = $designerOrderCount;
			$_t['detailUrl'] = U('/Statistics/Designer/designerdetail', array('designer_id' => $val['designer_id']));
			$_list[] = $_t;
		}
		return array(
			'list' => $_list,
			'count' => $count,
		);
	}


	public function designerFindService($designer_id)
	{
		$designerFind = $this->designerInfoDao()->findRecord($designer_id);
		if($designerFind['designer_image']){
			$designerFind['designer_image'] = domain_name_url.'/upload/'.$designerFind['designer_image'];
		} else {
			$designerFind['designer_image'] = domain_name_url.'/public/main/images/user_default_img.jpg';
		}
		return $designerFind;
	}
	
	//添加关注记录
	public function addFollow($params){
		if(empty($params['user_id']) || empty($params['designer_id'])){throw new \Exception('缺少参数');}
		
		$deginser_info=$this->getInfo($params['designer_id']);
		if(empty($deginser_info)){
			throw new \Exception('设计师不存在');
		}
		
		$map=array('user_id'=>$params['user_id'],'designer_id'=>$params['designer_id']);
		$follow_info=$this->designerFollowDao()->findRecord($map);
		if(!empty($follow_info)){
			throw new \Exception('您已关注该设计师');
		}
		
		$data=array(
					'user_id'=>$params['user_id'],
					'designer_id'=>$params['designer_id'],
				);
		$result=$this->designerFollowDao()->addRecord($data);
		if($result==false){throw new \Exception('关注失败');}
	}
	
	//调用model
	private function designerInfoDao(){
		return D('Common/Designer/DesignerInfo');
	}
	private function designerCaseDao(){
		return D('Common/Designer/DesignerCase');
	}
	
	private function designerFollowDao(){
		return D('Common/Designer/designerFollow');
	}
	
	private function distributorDao(){
		return D('Common/Distributor/Info');
	}
	
	private function regionDao(){
		return D('Common/Region');
	}
	
	
	
	//获取一个状态
	public function findStatus($limit){
		return $this->statusDao()->getStatusRecord($limit);
	}
	
	//订单状态
	public function getStatus($id){
		if ($id < 1) return false;
		return $this->statusDao()->getRecord($id);
	}
	public function statusCreateOrModify($params){
		// 自动验证
		$rules = array(
			array('status_name', 'require', '名称是必须的'),
		);
		//参数
		$data = array(
			'status_name'=>trim($params['status_name']),
			'sort_order'=>trim($params['sort_order']),
		);
		if($params['status_id'] > 0){
			$data['status_id'] = $params['status_id'];
		}
		$statusDao = $this->statusDao();
		if (!$statusDao->validate($rules)->create($data)){
			 throw new \Exception($statusDao->getError());
		}
		if ($params['status_id'] > 0){
			$result = $statusDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $statusDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	public function statusDelete($id){
		$result = $this->statusDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	public function statusPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		//搜索
		!empty($params['keyword']) && $map['status_name'] = array('like','%'.$params['keyword'].'%');
		$statusDao = $this->statusDao();
		$count = $statusDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order asc' : $params['orderby'];
			$list = $statusDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
		);
	}
	// statusList()方法查询statusList列表//
	public function statusList($params){
		empty($params['orderby']) && $orderBy = 'sort_order asc';
		$params['distributor_id'] > 0 && $map['distributor_id'] = $params['distributor_id'];
		return  $this->statusDao()->allRecords($map,$orderBy);
	}
	public function statusname(){
		$l =  $this->statusDao()->allRecords();
		foreach ($l as $key => $value) {
			$list[$value['status_id']] = $value['status_name'];
		}
		return $list;
	}
	//调用model
	private function statusDao(){
		return D('Common/Designer/DesignerOrderStatus');
	}

	//订单
	public function getOrder($id){
		if ($id < 1) return false;
		return $this->orderDao()->getRecord($id);
	}
	public function orderCreateOrModify($params){
		// 自动验证
		$rules = array(
			array('customer_name', 'require', '客户名字是必须的'),
			array('customer_mobile', '/^1[34578]\d{9}$/' , '手机号码格式错误' , 1 , 'regex' ,1),
		);
		
		$designer_info=$this->getInfo($params['designer_id']);
		
		$style = $this->designerCaseConfigDao()->getRecord($params['style']);
		
		//参数
		$data = array(
			'designer_id'=>trim($params['designer_id']),
			'customer_name'=>trim($params['customer_name']),
			'customer_mobile'=>trim($params['customer_mobile']),
			'status_id'=>trim($params['status_id']),
			'order_intro'=>trim($params['order_intro']),
			'province'=>trim($params['province']),
			'city'=>trim($params['city']),
			'district'=>trim($params['district']),
			'space_type'=>trim($params['space_type']),
			'style'=>$style['name'],
			'area'=>trim($params['area']),
			'budget'=>trim($params['budget']),
			'user_id'=>trim($params['user_id']),
		);
		if($params['order_id'] > 0){
			$data['order_id'] = $params['order_id'];
			$data['add_time'] = $params['add_time'];
			$data['update_time'] = time();
		}else{
			$data['distributor_id'] = $designer_info['distributor_id']?$designer_info['distributor_id']:$params['store_id'];
			$data['add_time'] = time();
			$data['update_time'] = time();

				$id = $params['admin_id'];
				$info = $this->adminService()->getRecord($id);
				$dats['add_time'] = time();
				$dats['log_content'] = '添加了一条新订单';
				$dats['admin_id'] = $info['admin_id'];
				$orderlogDao = $this->orderlogDao();
				$log = $orderlogDao->addRecord($dats);
		}
		$orderDao = $this->orderDao();
		if (!$orderDao->validate($rules)->create($data)){
			 throw new \Exception($orderDao->getError());
		}
		if ($params['order_id'] > 0){
			$result = $orderDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $orderDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
			
			//添加成功设计书预约数+1
			$this->designerInfoDao()->where(array('designer_id'=>$data['designer_id']))->setInc('bespoke_count');
		}
		
		
		
	}
	public function shexiu($params){
		//参数
		$data = array(
			'designer_id'=>trim($params['designer_id']),
		);
		if($params['order_id'] > 0){
			$data['order_id'] = $params['order_id'];
			$data['add_time'] = $params['add_time'];
			$data['update_time'] = time();

				$id = $params['admin_id'];
				$xiuname = $params['xiuname'];
				$info = $this->adminService()->getRecord($id);
				$dats['add_time'] = time();
				$dats['log_content'] = '把设计师修改为'.$xiuname;
				$dats['order_id'] = $params['order_id'];
				$dats['admin_id'] = $info['admin_id'];
				$orderlogDao = $this->orderlogDao();
				$log = $orderlogDao->addRecord($dats);
		}else{
			$data['distributor_id'] = $params['distributor_id'];		
		}
		$orderDao = $this->orderDao();
		if (!$orderDao->validate($rules)->create($data)){
			 throw new \Exception($orderDao->getError());
		}
		if ($params['order_id'] > 0){
			$result = $orderDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $orderDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
			
		}
		
	}
	public function zhuangxiu($params){
		//参数
		$data = array(
			'status_id'=>trim($params['status_id']),
		);
		if($params['order_id'] > 0){
			$data['order_id'] = $params['order_id'];
			$data['add_time'] = $params['add_time'];
			$data['update_time'] = time();

				$id = $params['admin_id'];
				$zxiuname = $params['zxiuname'];
				$info = $this->adminService()->getRecord($id);
				$dats['add_time'] = time();
				$dats['log_content'] = '把状态修改为'.$zxiuname;
				$dats['order_id'] = $params['order_id'];
				$dats['admin_id'] = $info['admin_id'];
				$orderlogDao = $this->orderlogDao();
				$log = $orderlogDao->addRecord($dats);

		}else{
			$data['distributor_id'] = $params['distributor_id'];		
		}
		$orderDao = $this->orderDao();
		if (!$orderDao->validate($rules)->create($data)){
			 throw new \Exception($orderDao->getError());
		}
		
		M()->startTrans();
		
		if ($params['order_id'] > 0){
			$result = $orderDao->saveRecord($data);
			if ($result === false){
				M()->rollback();
				throw new \Exception('修改失败');
			}
		} else {
			$result = $orderDao->addRecord($data);
			if ($result < 1){
				M()->rollback();
				throw new \Exception('添加失败');
			}
		}
		
		//测量完成后，用户可以参与大转盘抽奖一次
		if ($data['status_id'] == 26) {
			$lottery = $this->lotteryInfoDao()->getRecordForUser();
			$data = array(
					'user_id'=>$params['user_id'],
					'lottery_id'=>$lottery['lottery_id'],
					'chance_type'=>1,
					'chance_brief'=>'获得抽奖',
			);
			$lotteryLogDao = $this->lotteryLogDao();
			$result = $lotteryLogDao->addRecord($data);
			if ($result === false){
				M()->rollback();
				throw new \Exception('操作失败');
			}
		}
		
		M()->commit();
	}
	public function orderDelete($id){
		$result = $this->orderDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	public function orderPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		//管理员id
		$params['admin_id'] > 0 && $map['admin_id'] = $params['admin_id'];
		//分销商id
		!empty($params['distributor_id']) && $map['distributor_id'] = $params['distributor_id'];
		//分销商id
		!empty($params['designer_id']) && $map['designer_id'] = $params['designer_id'];
		//搜索
		$params['status_id'] > 0 && $map['status_id'] = $params['status_id'];
		//会员
		$params['user_id'] > 0 && $map['user_id'] = $params['user_id'];
		if(!empty($params['map'])){
			$map=array_merge($map,$params['map']);
		}
		!empty($params['keyword']) && $map['status_name'] = array('like','%'.$params['keyword'].'%');
		
		$orderDao = $this->orderDao();
		$count = $orderDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'add_time desc' : $params['orderby'];
			$list = $orderDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
			$status_list=$this->statusDao()->getFieldRecord(array(),'status_id,status_name');
			
			foreach($list as $key=>$val){
				$designer_id[$val['designer_id']]=$val['designer_id'];
				$user_id[$val['user_id']]=$val['user_id'];
			}
			
			if(!empty($designer_id)){
				$designer_info=$this->designerInfoDao()->getFieldRecord(array('designer_id'=>array('in',$designer_id)),'designer_id,designer_name,designer_image,tel,distributor_id,region_code');
			}
			if(!empty($user_id)){
				$user_info=$this->userInfoDao()->getFieldRecord(array('user_id'=>array('in',$user_id)),"user_id,nick_name,user_img,headimgurl");
			}
			foreach($list as $key=>$val){
				$region=$this->regionDao()->getRegionNameTwo($designer_info[$val['designer_id']]['region_code']);
				$region_arr=explode(' ',$region);
				$list[$key]['designer_name']=$designer_info[$val['designer_id']]['designer_name'];
				$list[$key]['designer_image']=$designer_info[$val['designer_id']]['designer_image'];
				$list[$key]['designer_tel']=$designer_info[$val['designer_id']]['tel'];
				$list[$key]['region_all']=implode('',$region_arr);
				$list[$key]['region']=$region_arr[0].$region_arr[1];
				$list[$key]['city']=$region_arr[2];
				$list[$key]['status_lang']=$status_list[$val['status_id']];
				$list[$key]['user_nick_name']=$user_info[$val['user_id']]['nick_name'];
				$list[$key]['user_img']=$user_info[$val['user_id']]['user_img'];
				$list[$key]['headimgurl']=$user_info[$val['user_id']]['headimgurl'];
				
				$region=$this->regionDao()->getRegionNameTwo($val['district']);
				$region_arr=explode(' ',$region);
				$list[$key]['c_region_all']=implode('',$region_arr);
				$list[$key]['c_region']=$region_arr[0].$region_arr[1];
				$list[$key]['c_city']=$region_arr[2];
			}
		}
		
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
		);
	}
	
	//查询设计师订单
	public function orderCount($designer_id){
		$map=array('designer_id'=>$designer_id);
		return $this->orderDao()->searchRecordsCount($map);
	}
	
	
	// orderList()方法查询orderList列表//
	public function orderList($params){
		empty($params['orderby']) && $orderBy = 'add_time desc';
		return  $this->orderDao()->allRecords();
	}
	public function ordername(){
		$l =  $this->orderDao()->allRecords();
		foreach ($l as $key => $value) {
			$list[$value['order_id']] = $value['customer_name'];
		}
		return $list;
	}
	// orderList()方法查询orderList列表//
	public function orderlogList($params){
		empty($params['orderby']) && $orderBy = 'add_time desc';
		$params['order_id'] > 0 && $map['order_id'] = $params['order_id'];
		return  $this->orderlogDao()->allRecords($map,$orderby);
	}
	//调用订单model
	private function orderDao(){
		return D('Common/Designer/DesignerOrder');
	}
	//调用订单日志model
	private function orderlogDao(){
		return D('Common/Designer/DesignerOrderLog');
	}
	//调用管理员model
	private function adminService(){
    	return D('Common/Admin/AdminInfo');
    }
	
	private function distributorInfoDao(){
    	return D('Common/Distributor/Info');
    }
	
	private function userInfoDao(){
    	return D('Common/User/UserInfo');
    }
	
	public function spaceGetField($map,$field='space_id,space_name',$page=1,$size=1000){
		return $this->designerSpaceDao()->getFieldRecord($map,$field,$page,$size);
	}
	
	public function spaceGetInfo($id){
		if ($id < 1) return false;
		$info=$this->designerSpaceDao()->getRecord($id);
		return $info;
	}
	public function spaceCreateOrModify($params){
		// 自动验证
		$rules = array(
			array('designer_name', 'require', '名称是必须的'),
		);
		//参数
		$data = array(
			'space_name'=>trim($params['space_name']),
			'sort_order'=>trim($params['sort_order']),
		);
		if($params['space_id']){
			$data['space_id']=$params['space_id'];
		}
		

		$designerInfoDao = $this->designerSpaceDao();
		if (!$designerInfoDao->validate($rules)->create($data)){
			 throw new \Exception($designerInfoDao->getError());
		}
		if ($params['space_id'] > 0){
			$result = $designerInfoDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $designerInfoDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function spaceUpdate($data){
		return $this->designerSpaceDao()->saveRecord($data);
	}
	
	//
	public function spaceDelete($id){
		$result = $this->designerSpaceDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	//分页查询
	public function spacePagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		
		$designerInfoDao = $this->designerSpaceDao();
		$count = $designerInfoDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order desc' : $params['orderby'];
			$list = $designerInfoDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
			
		}
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
		);
	}
	
	//调用model
	private function designerSpaceDao(){
		return D('Common/Designer/designerSpace');
	}
	
	public function chargeGetField($map,$field='charge_id,charge_name',$page=1,$size=1000){
		return $this->designerChargeDao()->getFieldRecord($map,$field,$page,$size);
	}
	
	public function chargeGetInfo($id){
		if ($id < 1) return false;
		$info=$this->designerChargeDao()->getRecord($id);
		return $info;
	}
	public function chargeCreateOrModify($params){
		// 自动验证
		$rules = array(
			array('charge_name', 'require', '名称是必须的'),
		);
		//参数
		$data = array(
			'charge_name'=>trim($params['charge_name']),
			'sort_order'=>trim($params['sort_order']),
		);
		if($params['charge_id']){
			$data['charge_id']=$params['charge_id'];
		}

		$designerInfoDao = $this->designerChargeDao();
		if (!$designerInfoDao->validate($rules)->create($data)){
			 throw new \Exception($designerInfoDao->getError());
		}
		if ($params['charge_id'] > 0){
			$result = $designerInfoDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $designerInfoDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function chargeUpdate($data){
		return $this->designerChargeDao()->saveRecord($data);
	}
	
	//
	public function chargeDelete($id){
		$result = $this->designerChargeDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	//分页查询
	public function chargePagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		
		$designerInfoDao = $this->designerChargeDao();
		$count = $designerInfoDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order desc' : $params['orderby'];
			$list = $designerInfoDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
			
		}
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
		);
	}
	
	private function designerChargeDao(){
		return D('Common/Designer/DesignerCharge');
	}
	
	private function designerTypeValueDao(){
		return D('Common/Designer/DesignerTypeValue');
	}
	
	private function designerCaseConfigDao(){
		return D('Common/Designer/DesignerCaseConfig');
	}
	
	private function lotteryLogDao(){
		return D('Common/Lottery/Log');
	}
	
	private function lotteryInfoDao(){
		return D('Common/Lottery/Info');
	}
	
}//end HelpService!