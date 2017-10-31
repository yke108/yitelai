<?php
namespace Common\Service;
use Common\Basic\Pager;
class DesignerTypeService{
	
	public function getTypeField($map,$field='type_id,type_name'){
		return $this->designerTypeDao()->getFieldRecord($map,$field);
	}
	
	//help_info
	public function getType($id){
		if ($id < 1) return false;
		return $this->designerTypeDao()->getRecord($id);
	}
	
	public function typeCreateOrModify($params){
		// 自动验证
		$rules = array(
			// array('article_title', 'require', '名称是必须的'),
		);
		//参数
		$data = array(
			'type_name'=>$params['type_name'],			
			'key'=>$params['key'],
		);
		
		if($params['type_id']>0){
			$data['type_id']=$params['type_id'];
		}		
		
		
		$designerTypeDao = $this->designerTypeDao();
		if (!$designerTypeDao->validate($rules)->create($data)){
			 throw new \Exception($designerTypeDao->getError());
		}
		if($params['type_id']>0){
			$result = $designerTypeDao->saveRecord($data);
			if ($result < 1){
				throw new \Exception('编辑失败');
			}
		}else{
			$result = $designerTypeDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
		
	}	
	public function typeDelete($id){
		$result = $this->designerTypeDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	//分页查询
	public function typePagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		$designerTypeDao = $this->designerTypeDao();
		$count = $designerTypeDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? ' type_id DESC' : $params['orderby'];
			$list = $designerTypeDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
		);
	}
	
	public function getTypeValueField($map,$field='id,name'){
		return $this->designerTypeValueDao()->getFieldRecord($map,$field);
	}
	
	public function getTypeValue($id){
		if ($id < 1) return false;
		return $this->designerTypeValueDao()->getRecord($id);
	}
	
	public function typeValueCreateOrModify($params){
		// 自动验证
		$rules = array(
			// array('article_title', 'require', '名称是必须的'),
		);
		//参数
		$data = array(
			'name'=>$params['name'],			
			'is_index'=>$params['is_index'],
			'sort_order'=>$params['sort_order'],
			'type'=>$params['type'],
		);
		
		if($params['id']>0){
			$data['id']=$params['id'];
		}		
		
		
		$designerTypeValueDao = $this->designerTypeValueDao();
		if (!$designerTypeValueDao->validate($rules)->create($data)){
			 throw new \Exception($designerTypeValueDao->getError());
		}
		if($params['id']>0){
			$result = $designerTypeValueDao->saveRecord($data);
			if ($result < 1){
				throw new \Exception('编辑失败');
			}
		}else{
			$result = $designerTypeValueDao->addRecord($data);
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
		
	}	
	public function typeValueDelete($id){
		$result = $this->designerTypeValueDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	//分页查询
	public function typeValuePagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		if($params['map']){
			$map=array_merge($map,$params['map']);
		}
		$designerTypeValueDao = $this->designerTypeValueDao();
		$count = $designerTypeValueDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? ' id DESC' : $params['orderby'];
			$list = $designerTypeValueDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		
		return array(
			'list'=>$list,
			'count'=>$count,
			'pagesize'=>$params['pagesize'],
		);
	}
	
	//推荐首页
	public function typeValueIsIndex($id){
		$info=$this->getTypeValue($id);
		if(empty($info)){throw new \Exception('推荐首页失败');}
		$data=array('id'=>$id,'is_index'=>($info['is_index']==1?0:1));
		
		$result=$this->designerTypeValueDao()->save($data);
		
		if($result==false){
			throw new \Exception('推荐首页失败');
		}
	}
	
	private function designerTypeDao(){
		return D('Common/Designer/DesignerType');
	}
	
	private function designerTypeValueDao(){
		return D('Common/Designer/DesignerTypeValue');
	}
	
	
	
	
}//end HelpService!