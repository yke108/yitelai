<?php
namespace Common\Service;
use Common\Basic\Genre;

class QuestionService{
	// Cat
	public function findFieldData($map,$field){
		return $this->questionCatDao()->findFieldRecord($map,$field);
	}
	
	public function getFieldList($map,$field){
		return $this->questionCatDao()->findFieldRecord($map,$field);
	}
	
	public function getCat($id){
		if ($id < 1) return false;
		return $this->questionCatDao()->getRecord($id);
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
		$questionCatDao = $this->questionCatDao();
		if (!$questionCatDao->validate($rules)->create($data)){
			 throw new \Exception($questionCatDao->getError());
		}
		if ($params['cat_id'] > 0){
			$result = $questionCatDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $questionCatDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	public function catDelete($id){
		$result = $this->questionCatDao()->delRecord($id);
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
		
		$questionCatDao = $this->questionCatDao();
		$count = $questionCatDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order asc' : $params['orderby'];
			$list = $questionCatDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
			
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
		$list = $this->questionCatDao()->allRecords();
		$list = genTree($list, 'cat_id', 'parent_id');
		return $list;
	}
	
	//获取分类的所有子类（包含自己）
	public function getCatChilds($cat_id){
		$use_category = false;
		$cat_list = $this->questionCatDao()->getField('cat_id, parent_id');
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
		$l =  $this->questionCatDao()->allRecords();
		foreach ($l as $key => $value) {
			$list[$value['story_id']] = $value['story_name'];
		}
		return $list;
	}
	
	//story_info
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->questionInfoDao()->getRecord($id);
	}
	public function findInfo($map){
		return $this->questionInfoDao()->findRecord($map);
	}
	public function searchInfo($map, $orderBy){
		return $this->questionInfoDao()->searchRecord($map, $orderBy);
	}
	public function infoCreateOrModify($params){
		// 自动验证
		$rules = array(
			array('title', 'require', '名称是必须的'),
			array('keywords', 'require', '关键字不能为空'),
		);
		//参数//
		$data = array(
			'cat_id'=>trim($params['cat_id']),
			'title'=>trim($params['title']),
			'keywords'=>trim($params['keywords']),
			'content'=>trim($params['content']),
			'is_show'=>trim($params['is_show']),
			'sort_order'=>trim($params['sort_order']),
		);
		
		if($params['question_id'] > 0){
			$data['question_id'] = $params['question_id'];
		}else{
			$data['add_time'] = time();
		}
		$questionInfoDao = $this->questionInfoDao();
		if (!$questionInfoDao->validate($rules)->create($data)){
			 throw new \Exception($questionInfoDao->getError());
		}
		if ($params['question_id'] > 0){
			$result = $questionInfoDao->saveRecord($data);
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$data['distributor_id'] = $params['distributor_id'];
			$story_id = $questionInfoDao->addRecord($data);
			if ($story_id < 1){
				throw new \Exception('添加失败');
			}
			
			return array('story_id'=>$story_id);
		}
	}
	public function infoDelete($id){
		$result = $this->questionInfoDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function infoList($map){
		return $this->questionInfoDao()->where($map)->select();
	}
	
	//分页查询
	public function infoPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		$params['user_id'] > 0 && $map['user_id'] = $params['user_id'] ;
		isset($params['is_show']) && $map['is_show'] = $params['is_show'] ;
		if(!empty($params['map'])){
			$map=array_merge($map,$params['map']);
		}
		//搜索
		!empty($params['keyword']) && $map['title'] = array('like','%'.$params['keyword'].'%');
		if(!empty($params['cat_id'])){
			if(is_array($params['cat_id'])){
				$map['cat_id'] = array('in',$params['cat_id']) ;
			}else{
				$map['cat_id']=$params['cat_id'];
			}
		}
		
		isset($params['distributor_id']) && $map['distributor_id'] = $params['distributor_id'];
		
		$questionInfoDao = $this->questionInfoDao();
		$count = $questionInfoDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order ASC, question_id DESC' : $params['orderby'];
			$list = $questionInfoDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
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
				$cat_list = $this->questionCatDao()->where($map)->getField('cat_id, cat_name');
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
		return $this->questionInfoDao()->allRecords($map);
	}
	
	public function infoFieldList($map, $field){
		return $this->questionInfoDao()->getFieldRecords($map, $field);
	}
	
	public function infoUpdate($post){
		return $this->questionInfoDao()->saveRecord($post);
	}
	
	//添加文章浏览数
	public function addViewNum($params){
		$story_info = $this->getInfo($params['story_id']);
		if (empty($story_info)) throw new \Exception('文章不存在');
		
		M()->startTrans();
		
		//修改文章浏览数
		$map=array('story_id'=>$params['story_id']);
		$result = $this->questionInfoDao()->where($map)->setInc('view_num');
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
		
		$result=$this->questionInfoDao()->save($data);
		
		if($result==false){
			throw new \Exception('修改状态失败');
		}
	}
	
	//推荐首页
	public function storyIsIndex($story_id){
		$info=$this->getInfo($story_id);
		if(empty($info)){throw new \Exception('修改状态失败');}
		$data=array('story_id'=>$story_id,'is_index'=>($info['is_index']==1?0:1));
		
		$result=$this->questionInfoDao()->save($data);
		
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
			$child_id=$this->questionCatDao()->findFieldRecord(array('parent_id'=>$val['cat_id']),'cat_id',true);
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
	
	
	private function questionInfoDao(){
		return D('Common/Question/Info');
	}
	
	private function questionCatDao(){
		return D('Common/Question/Cat');
	}
	
	private function userInfoDao(){
		return D('Common/User/UserInfo');
	}
	
	private function adminInfoDao(){
		return D('Common/Admin/AdminInfo');
	}
}