<?php
namespace Common\Service;
use Common\Basic\Genre;

class StoryService{
	// Cat
	public function findFieldData($map,$field){
		return $this->storyCatDao()->findFieldRecord($map,$field);
	}
	
	public function getFieldList($map,$field){
		return $this->storyCatDao()->findFieldRecord($map,$field);
	}
	
	public function getCat($id){
		if ($id < 1) return false;
		return $this->storyCatDao()->getRecord($id);
	}
	
	public function catCreateOrModify($params){
		// 自动验证
		$rules = array(
			array('cat_name', 'require', '名称是必须的'),
		);
		//参数/
		$data = array(
			'cat_name'=>trim($params['cat_name']),
			'parent_id'=>$params['parent_id'],
			'is_show'=>trim($params['is_show']),
			'sort_order'=>trim($params['sort_order']),
		);
		
		!empty($params['picture']) && $data['picture']=$params['picture'];
		
		if($params['cat_id'] > 0){
			$data['cat_id'] = $params['cat_id'];
		}
		$storyCatDao = $this->storyCatDao();
		if (!$storyCatDao->validate($rules)->create($data)){
			 throw new \Exception($storyCatDao->getError());
		}
		if ($params['cat_id'] > 0){
			$result = $storyCatDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $storyCatDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	public function catDelete($id){
		$result = $this->storyCatDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	public function catPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		//搜索//
		!empty($params['cat_name']) && $map['cat_name'] = array('like','%'.$params['cat_name'].'%');
		
		if(!empty($params['map'])){
			$map=array_merge($map,$params['map']);
		}
		
		$storyCatDao = $this->storyCatDao();
		$count = $storyCatDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order asc' : $params['orderby'];
			$list = $storyCatDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
			
		}
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
		);
	}
	// catList()方法查询cat列表//
	public function catList($params){
		empty($params['orderby']) && $orderBy = 'sort_order asc';
		$list = $this->storyCatDao()->allRecords();
		$list = genTree($list, 'cat_id', 'parent_id');
		return $list;
	}
	
	//获取分类的所有子类（包含自己）
	public function getCatChilds($cat_id){
		$use_category = false;
		$cat_list = $this->storyCatDao()->getField('cat_id, parent_id');
		$cats = array();
		foreach($cat_list as $ck => $cv){
			if($ck == $cat_id) {
				$use_category = true;
			}
			$cats[$cv][] = $ck;
		}
		if($use_category){
			$clist = array();
			child_list($cat_id, $cats, $clist);
		}
	
		return $clist;
	}
	
	public function catname(){
		$l =  $this->storyCatDao()->allRecords();
		foreach ($l as $key => $value) {
			$list[$value['story_id']] = $value['story_name'];
		}
		return $list;
	}

	//调用model
	private function storyCatDao(){
		return D('Common/Story/StoryCat');
	}
	
	//story_info
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->storyInfoDao()->getRecord($id);
	}
	public function findInfo($map){
		return $this->storyInfoDao()->findRecord($map);
	}
	public function searchInfo($map, $orderBy){
		return $this->storyInfoDao()->searchRecord($map, $orderBy);
	}
	public function infoCreateOrModify($params){
		// 自动验证
		$rules = array(
			array('story_title', 'require', '名称是必须的'),
		);
		//参数//
		$data = array(
			'cat_id'=>trim($params['cat_id']),
			'story_title'=>trim($params['story_title']),
			'story_intro'=>trim($params['story_intro']),
			'story_content'=>trim($params['story_content']),
			'story_gallery'=>trim($params['story_gallery']),
			'is_show'=>trim($params['is_show']),
			'sort_order'=>trim($params['sort_order']),
			'view_fake'=>trim($params['view_fake']),
		);
		if(!empty($params['story_image'])){
			$data['story_image']=$params['story_image'];
		}
		
		if($params['story_id'] > 0){
			$data['story_id'] = $params['story_id'];
			//$data['add_time'] = $params['add_time'];
			$data['update_time'] = time();
			//$data['view_num'] = $params['view_num'];
			//$data['good_num'] = $params['good_num'];
			//$data['bad_num'] = $params['bad_num'];
			$data['status'] = $params['status'];
		}else{

			$data['add_time'] = time();
			$data['update_time'] = time();
			$data['view_num'] = 0;
			$data['good_num'] = 0;
			$data['bad_num'] = 0;
			$data['user_id'] = intval($params['user_id']);
			$data['admin_id']=$params['admin_id'];
			if($params['admin_id']>0){
				$data['status']=1;
			}
		}
		

		$storyInfoDao = $this->storyInfoDao();
		if (!$storyInfoDao->validate($rules)->create($data)){
			 throw new \Exception($storyInfoDao->getError());
		}
		if ($params['story_id'] > 0){
			$result = $storyInfoDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$story_id = $storyInfoDao->addRecord($data);
			if ($story_id < 1){
				throw new \Exception('添加失败');
			}
			
			return array('story_id'=>$story_id);
		}
	}
	public function infoDelete($id){
		$result = $this->storyInfoDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	//分页查询
	public function infoPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		$params['user_id'] > 0 && $map['user_id'] = $params['user_id'] ;
		isset($params['status']) && $map['status'] = $params['status'] ;
		isset($params['is_show']) && $map['is_show'] = $params['is_show'] ;
		isset($params['is_top']) && $map['is_top'] = $params['is_top'] ;
		if(!empty($params['map'])){
			$map=array_merge($map,$params['map']);
		}
		//搜索
		!empty($params['keyword']) && $map['story_title'] = array('like','%'.$params['keyword'].'%');
		if(!empty($params['cat_id'])){
			if(is_array($params['cat_id'])){
				$map['cat_id'] = array('in',$params['cat_id']) ;
			}else{
				$map['cat_id']=$params['cat_id'];
			}
		}
		
		$storyInfoDao = $this->storyInfoDao();
		$count = $storyInfoDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order ASC, story_id DESC' : $params['orderby'];
			$list = $storyInfoDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
			foreach($list as $key=>$val){
				$user_id[]=$val['user_id'];
				$cat_ids[]=$val['cat_id'];
				if($val['admin_id']>0){
					$admin_ids[$val['admin_id']]=$val['admin_id'];
				}
			}
			
			$user_list=$this->userInfoDao()->getUsers($user_id);
			
			foreach($list as $key=>$val){
				$list[$key]['nick_name']=$user_list[$val['user_id']]['nick_name'];
				
				//分类
				$map = array('cat_id'=>array('in', $cat_ids));
				$cat_list = $this->storyCatDao()->where($map)->getField('cat_id, cat_name');
				$list[$key]['cat_name']=$cat_list[$val['cat_id']];
			}
			if(!empty($admin_ids) && $params['_needAdmin']==1){	
				$admin_list=$this->adminInfoDao()->getFieldRecord(array('admin_id'=>array('in',$admin_ids)));
			}
			//var_dump($admin_ids);die();
		}
		
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
			'admins'=>$admin_list,
		);
	}
	
	public function infoAllList($map){
		return $this->storyInfoDao()->allRecords($map);
	}
	
	public function infoFieldList($map, $field){
		return $this->storyInfoDao()->getFieldRecords($map, $field);
	}
	
	public function infoLike($params){
		if (empty($params['user_id'])) {
			throw new \Exception('请先登录');
		}
		
		if (!in_array($params['type'], array(1,2))) {
			throw new \Exception('类型不正确');
		}
		
		$story_info=$this->getInfo($params['story_id']);
		if(empty($story_info)){
			throw new \Exception('文章不存在');
		}
		
		if($story_info['user_id']==$params['user_id']){
			throw new \Exception('抱歉，您不能给自己的文章点赞。');
		}
		
		$map = array(
				'user_id'=>$params['user_id'],
				'ref_type'=>4,
				'ref_id'=>$params['story_id'],
				'ref_value'=>$params['type']
		);
		$zan = $this->zanDao()->findRecord($map);
		if ($zan) {
			throw new \Exception('你已经点赞过');
		}
		
		M()->startTrans();
		
		$result = $this->storyInfoDao()->like($params['story_id']);
		if ($result === false) {
			M()->rollback();
			throw new \Exception('点赞失败');
		}
		
		$data = array(
				'user_id'=>$params['user_id'],
				'ref_type'=>4,
				'ref_id'=>$params['story_id'],
				'name'=>$story_info['story_title'],
				'ref_value'=>$params['type']
		);
		if (!$this->zanDao()->create($data)){
			throw new \Exception($this->zanDao()->getError());
		}
		$result = $this->zanDao()->add();
		if ($result === false) {
			M()->rollback();
			throw new \Exception('点赞失败');
		}
		
		M()->commit();
		
		$info = $this->storyInfoDao()->getRecord($params['story_id']);
		return $info['good_num'];
	}
	public function infoClap($params){
		if (empty($params['user_id'])) {
			throw new \Exception('请先登录');
		}
		
		if (!in_array($params['type'], array(1,2))) {
			throw new \Exception('类型不正确');
		}
		
		$story_info=$this->getInfo($params['story_id']);
		if(empty($story_info)){
			throw new \Exception('文章不存在');
		}
		
		if($story_info['user_id']==$params['user_id']){
			throw new \Exception('抱歉，您不能给自己的文章拍砖。');
		}
		
		$map = array(
				'user_id'=>$params['user_id'],
				'ref_type'=>4,
				'ref_id'=>$params['story_id'],
				'ref_value'=>$params['type']
		);
		$zan = $this->zanDao()->findRecord($map);
		if ($zan) {
			throw new \Exception('你已经拍砖过');
		}
		
		M()->startTrans();
		
		$result = $this->storyInfoDao()->clap($params['story_id']);
		if ($result === false) {
			M()->rollback();
			throw new \Exception('拍砖失败');
		}
		
		$data = array(
				'user_id'=>$params['user_id'],
				'ref_type'=>4,
				'ref_id'=>$params['story_id'],
				'name'=>$story_info['story_title'],
				'ref_value'=>$params['type']
		);
		if (!$this->zanDao()->create($data)){
			throw new \Exception($this->zanDao()->getError());
		}
		$result = $this->zanDao()->add();
		if ($result === false) {
			M()->rollback();
			throw new \Exception('拍砖失败');
		}
		
		M()->commit();
		
		$info = $this->storyInfoDao()->getRecord($params['story_id']);
		return $info['bad_num'];
	}
	
	public function infoUpdate($post){
		return $this->storyInfoDao()->saveRecord($post);
	}
	
	//添加文章浏览数
	public function addViewNum($params){
		$story_info = $this->getInfo($params['story_id']);
		if (empty($story_info)) throw new \Exception('文章不存在');
		
		M()->startTrans();
		
		//修改文章浏览数
		$map=array('story_id'=>$params['story_id']);
		$result = $this->storyInfoDao()->where($map)->setInc('view_num');
		if ($result === false) {
			M()->rollback();
			throw new \Exception('修改浏览数失败');
		}
		
		//修改用户浏览数
		if ($params['user_id']) {
			$user_info = $this->userInfoDao()->getRecord($params['user_id']);
			if (empty($user_info)) throw new \Exception('用户不存在');
			
			$map=array('user_id'=>$params['user_id']);
			$result = $this->userInfoDao()->where($map)->setInc('read_count');
			if ($result === false) {
				M()->rollback();
				throw new \Exception('修改浏览数失败');
			}
		}
		
		M()->commit();
	}
	
	//置顶
	public function changeStatus($story_id){
		$info=$this->getInfo($story_id);
		if(empty($info)){throw new \Exception('修改状态失败');}
		
		
		$data=array('story_id'=>$story_id,'is_top'=>($info['is_top']==1?0:1));
		
		$result=$this->storyInfoDao()->save($data);
		
		if($result==false){
			throw new \Exception('修改状态失败');
		}
	}
	
	//推荐首页
	public function storyIsIndex($story_id){
		$info=$this->getInfo($story_id);
		if(empty($info)){throw new \Exception('修改状态失败');}
		$data=array('story_id'=>$story_id,'is_index'=>($info['is_index']==1?0:1));
		
		$result=$this->storyInfoDao()->save($data);
		
		if($result==false){
			throw new \Exception('修改状态失败');
		}
	}
	
	
	//作者排行
	public function userRanking(){
		$story_sql=M('story_info')->field('count(story_id)')->alias('b')->where("b.is_show=1 and b.status=1 and b.user_id=a.user_id")->buildSql();
		$result=M('user_info')
				->alias('a')
				->field("a.*,{$story_sql} story_number")
				->where($map)
				->order("story_number desc")
				->page('1,10')
				->select();
		return $result;
		
	}
	
	//首页使用-返回顶级类还有顶级分类下的文章
	public function IndexCatStory(){
		$cat_map=array('parent_id'=>0);
		$cat_params=array('pagesize'=>3,'map'=>$cat_map);
		$cat_result=$this->catPagerList($cat_params);
		$cat_list=$cat_result['list'];
		$story_params=array('status'=>1,'pagesize'=>5);
		foreach($cat_list as $key=>$val){
			$self_id=array($val['cat_id']);	
			$child_id=$this->storyCatDao()->findFieldRecord(array('parent_id'=>$val['cat_id']),'cat_id',true);
			empty($child_id) && $child_id=array();
			$cat_id_aggregate=array_merge($self_id,$child_id);
			$cat_list[$key]['child_id']=$cat_id_aggregate;
			$story_params['map']['cat_id']=array('in',$cat_id_aggregate);
			$story_params['orderby']='add_time desc';
			$story_result=$this->infoPagerList($story_params);
			$cat_list[$key]['story_list']=$story_result['list'];
		}
		return $cat_list;
	}
	
	//批量通过
	public function modifyStatus($story_id){
		//$info=$this->getInfo($story_id);
		//if(empty($info)){throw new \Exception('修改状态失败');}
		
		if(is_array($story_id)){
			$story_id=array('in',$story_id);
		}
		
		$data=array('story_id'=>$story_id,'status'=>1);
		
		$result=$this->storyInfoDao()->save($data);
		
		if($result==false){
			throw new \Exception('修改状态失败');
		}
	}
	
	public function createReward($params){
		//自动验证
		$rules = array(
				array('pay_id', 'require', '打赏金额不能为空'),
				array('pay_id',array(1,2,3),'支付方式不正确',2,'in'),
				array('reward_amount', 'require', '打赏金额不能为空'),
		);
		//参数/
		$reward_id = date('ymdHis').rand(1000,9999);
		$data = array(
				'reward_id'=>$reward_id,
				'user_id'=>$params['user_id'],
				'story_id'=>$params['story_id'],
				'pay_id'=>$params['pay_id'],
				'reward_amount'=>$params['reward_amount'],
				'add_time'=>NOW_TIME
		);
		
		$storyRewardDao = $this->storyRewardDao();
		if (!$storyRewardDao->validate($rules)->create($data)){
			throw new \Exception($storyRewardDao->getError());
		}
		$reward_id = $storyRewardDao->addRecord($data);
		if ($reward_id < 1){
			throw new \Exception('添加失败');
		}
		return $reward_id;
	}
	
	public function rewardPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		$params['user_id'] > 0 && $map['user_id'] = $params['user_id'] ;
		isset($params['pay_status']) && $map['pay_status'] = $params['pay_status'] ;
		if(!empty($params['map'])){
			$map=array_merge($map,$params['map']);
		}
		if ($params['keyword']) {
			$map['story_title'] = array('like', '%'.$params['keyword'].'%');
			$storys = $this->storyInfoDao()->allRecords($map);
			if (empty($storys)) {
				return array();
			}
			$story_ids = array();
			foreach ($storys as $v) {
				$story_ids[] = $v['story_id'];
			}
			$map['story_id'] = array('in', $story_ids);
		}
		if ($params['cat_id']) {
			$clist = $this->getCatChilds($params['cat_id']);
			$map['cat_id'] = array('in', $clist);
			$storys = $this->storyInfoDao()->allRecords($map);
			if (empty($storys)) {
				return array();
			}
			$story_ids = array();
			foreach ($storys as $v) {
				$story_ids[] = $v['story_id'];
			}
			$map['story_id'] = array('in', $story_ids);
		}
		
		$storyRewardDao = $this->storyRewardDao();
		$count = $storyRewardDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'reward_id DESC' : $params['orderby'];
			$list = $storyRewardDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		$result = $this->_rewardList($list, $params);
		return array(
				'list'=>$result['list'],
				'count'=>$count,
				'admins'=>$result['admins'],
		);
	}
	
	private function _rewardList($list) {
		if (!empty($list)) {
			//文章
			$user_ids = $story_ids = $admin_ids = array();
			foreach ($list as $v) {
				$user_ids[] = $v['user_id'];
				$story_ids[] = $v['story_id'];
				if($v['admin_id'] > 0){
					$admin_ids[$v['admin_id']] = $v['admin_id'];
				}
			}
			
			//打赏者
			$users = $this->userInfoDao()->getUsers($user_ids);
			
			//文章
			$map['story_id'] = array('in', $story_ids);
			$storys = $this->storyInfoDao()->getFieldRecords($map);
			
			//分类
			$catlist = $this->storyCatDao()->getFieldRecords();
			
			//作者
			$author_ids = array();
			foreach ($storys as $v) {
				$author_ids[] = $v['user_id'];
			}
			$authors = $this->userInfoDao()->getUsers($author_ids);
			
			//管理员
			if(!empty($admin_ids)){
				$admins = $this->adminInfoDao()->getFieldRecord(array('admin_id'=>array('in',$admin_ids)));
			}
			
			foreach ($list as $k => $v) {
				$list[$k]['story_title'] = $storys[$v['story_id']]['story_title'];
				$list[$k]['story_image'] = $storys[$v['story_id']]['story_image'];
				$list[$k]['cat_name'] = $catlist[$storys[$v['story_id']]['cat_id']]['cat_name'];
				
				$list[$k]['author_name'] = $authors[$storys[$v['story_id']]['user_id']]['nick_name'];
				$list[$k]['nick_name'] = $users[$v['user_id']]['nick_name'];
			}
		}
		
		return array('list'=>$list, 'admins'=>$admins);
	}
	
	public function rewardInfo($params){
		$map = array('reward_id'=>$params['reward_id']);
		$params['user_id'] > 0 && $map['user_id'] = $params['user_id'] ;
		isset($params['pay_status']) && $map['pay_status'] = $params['pay_status'] ;
		if(!empty($params['map'])){
			$map=array_merge($map,$params['map']);
		}
		
		$storyRewardDao = $this->storyRewardDao();
		$info = $storyRewardDao->findRecord($map);
		
		return $info;
	}
	
	//打赏
	public function payReward($params){
		//打赏人
		$user_info = $this->userInfoDao()->getRecord($params['user_id']);
		//判断余额是否足够
		if ($params['pay_id'] == 1) {
			if ($params['reward_amount'] > $user_info['user_money']) throw new \Exception('余额不足');
		}
		
		M()->startTrans();
		
		//减少账户余额
		$result = $this->userInfoDao()->depleteMoney($user_info['user_id'], $params['reward_amount']);
		if ($result === false) {
			M()->rollback();
			throw new \Exception('支付失败');
		}
		
		//打赏人
		$result = $this->userInfoDao()->increasePayReward($user_info['user_id'], $params['reward_amount']);
		if ($result === false) {
			M()->rollback();
			throw new \Exception('支付失败');
		}
		$params_account = array(
				'user_id'=>$user_info['user_id'],
				'amount_old'=>$user_info['user_money'],
				'amount_change'=>$params['reward_amount'],
				'ref_user_id'=>$params['story_user_id'],
				'ref_id'=>$params['story_id'],
		);
		$result = $this->userAccountDao()->payReward($params_account);
		if ($result === false) {
			M()->rollback();
			throw new \Exception('支付失败');
		}
		
		//支付成功
		try {
			$this->paySuccess($params);
		} catch (\Exception $e) {
			M()->rollback();
			throw new \Exception($e->getMessage());
		}
		
		M()->commit();
	
		return true;
	}
	
	public function paySuccess($params) {
		//打赏记录
		$reward_info = $this->storyRewardDao()->getRecord($params['reward_id']);
		//文章
		$story_info = $this->storyInfoDao()->getRecord($reward_info['story_id']);
		//被打赏人
		$story_user = $this->userInfoDao()->getRecord($story_info['user_id']);
		
		//被打赏人
		$result = $this->userInfoDao()->increaseUserMoney($story_info['user_id'], $reward_info['reward_amount']);
		if ($result === false) {
			return false;
		}
		$result = $this->userInfoDao()->increaseGetReward($story_info['user_id'], $reward_info['reward_amount']);
		if ($result === false) {
			return false;
		}
		$params_account = array(
				'user_id'=>$story_user['user_id'],
				'amount_old'=>$story_user['user_money'],
				'amount_change'=>$reward_info['reward_amount'],
				'ref_user_id'=>$reward_info['user_id'],
				'ref_id'=>$story_info['story_id'],
		);
		$result = $this->userAccountDao()->getReward($params_account);
		if ($result === false) {
			return false;
		}
		
		//修改支付状态
		$map = array('reward_id'=>$params['reward_id']);
		$data = array('pay_status'=>1, 'pay_time'=>NOW_TIME);
		$result = $this->storyRewardDao()->updateRecord($map, $data);
		if ($result ===  false) {
			return false;
		}
		
		//统计打赏数
		$reward_info = $this->storyRewardDao()->getRecord($params['reward_id']);
		$result = $this->storyInfoDao()->increaseRewardNum($reward_info['story_id'], 1);
		if ($result ===  false) {
			return false;
		}
		
		return true;
	}
	
	public function share($params){
		if (empty($params['user_id']) || empty($params['story_id'])) throw new \Exception('缺少参数');
		
		$story_info = $this->getInfo($params['story_id']);
		if (empty($story_info)) throw new \Exception('文章不存在');
		
		M()->startTrans();
		
		//分享记录
		$data = array(
				'user_id'=>$params['user_id'],
				'id_value'=>$params['story_id'],
				'collect_type'=>Genre::CollectTypeShareStory,
				'add_time'=>NOW_TIME,
		);
		$result = $this->collectDao()->addRecord($data);
		if ($result === false) {
			M()->rollback();
			throw new \Exception('分享失败');
		}
		
		//统计分享数
		$result = $this->storyInfoDao()->where(array('story_id'=>$story_info['story_id']))->setInc('share_count');
		if ($result === false) {
			M()->rollback();
			throw new \Exception('分享失败');
		}
		
		M()->commit();
	}
	
	//调用model//
	private function storyInfoDao(){
		return D('Common/Story/StoryInfo');
	}
	
	private function storyRewardDao(){
		return D('Common/Story/StoryReward');
	}
	
	private function storyReadDao(){
		return D('Common/Story/StoryRead');
	}
	
	private function zanDao(){
		return D('Common/Zan/Log');
	}
	
	private function userInfoDao(){
		return D('Common/User/UserInfo');
	}
	
	private function userAccountDao(){
		return D('Common/User/UserAccount');
	}
	
	private function adminInfoDao(){
		return D('Common/Admin/AdminInfo');
	}
	
	private function collectDao(){
		return D('Collect');
	}
}//end HelpService!