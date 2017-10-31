<?php
namespace Common\Service;

class CookBookService{
	public function getInfo($id){
		if ($id < 1) return false;
		$info = $this->cookBookDao()->getRecord($id);
		return $this->outputForInfo($info);
	}
	
	public function infoCreateOrModify($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = trim($v);
		}
		
		$data['label_ids'] = $params['label_ids'] ? implode(',', $params['label_ids']) : '';
		$data['is_open'] = ($params['is_open'] == 1) ? $params['is_open'] : 0;
		$data['is_recommend'] = ($params['is_open'] == 1) ? $params['is_recommend'] : 0;
		
		$cookBookDao = $this->cookBookDao();
		if (!$cookBookDao->create($data)){
			 throw new \Exception($cookBookDao->getError());
		}
		
		if ($params['book_id'] > 0){
			$result = $cookBookDao->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $cookBookDao->add();
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function infoDelete($id){
		$result = $this->cookBookDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function infoRecommend($bood_id){
		$info = $this->getInfo($bood_id);
		$is_recommend = $info['is_recommend'] == 0 ? 1 : 0;
		$result = $this->cookBookDao()->where(array('book_id'=>$info['book_id']))->save(array('is_recommend'=>$is_recommend));
		if ($result === false) throw new \Exception('设置失败');
	}
	
	public function infoPopular($bood_id){
		$info = $this->getInfo($bood_id);
		$is_recommend = $info['is_popular'] == 0 ? 1 : 0;
		$result = $this->cookBookDao()->where(array('book_id'=>$info['book_id']))->save(array('is_popular'=>$is_recommend));
		if ($result === false) throw new \Exception('设置失败');
	}
	
	public function infoOpen($bood_id){
		$info = $this->getInfo($bood_id);
		$is_open = $info['is_open'] == 0 ? 1 : 0;
		$result = $this->cookBookDao()->where(array('book_id'=>$info['book_id']))->save(array('is_open'=>$is_open));
		if ($result === false) throw new \Exception('设置失败');
	}
	
	public function readCount($bood_id){
		$this->cookBookDao()->where(array('book_id'=>$bood_id))->setInc('read_count');
	}
	
	//分页查询
	public function infoPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = array();
		if (!empty($params['keyword'])) {
			$map['title'] = array('like', '%'.$params['keyword'].'%');
		}
		if (!empty($params['cat_id'])) {
			$clist = $this->catChilds($params['cat_id']);
			$map['cat_id'] = array('in', $clist);
		}
		if (isset($params['is_open'])) {
			$map['is_open'] = $params['is_open'];
		}
		if (isset($params['is_recommend'])) {
			$map['is_recommend'] = $params['is_recommend'];
		}
		if (isset($params['is_popular'])) {
			$map['is_popular'] = $params['is_popular'];
		}
		if (isset($params['user_id'])) {
			$map['user_id'] = $params['user_id'];
		}
		$cookBookDao = $this->cookBookDao();
		$count = $cookBookDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'sort_order desc, book_id desc' : $params['orderby'];
			$list = $cookBookDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}

	public function infoFieldPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		if (!empty($params['keyword'])) {
			$map['title'] = array('like', '%' . $params['keyword'] . '%');
		}
		$map['user_id'] = $params['user_id'];
		$cookBookDao = $this->cookBookDao();
		$count = $cookBookDao->searchRecordsCount($map);
		$_list = array();
		if ($count > 0) {
			$orderby = empty($params['orderby']) ? 'sort_order desc, book_id desc' : $params['orderby'];
			$field = array('book_id','name', 'picture', 'add_time');
			$data = $cookBookDao->searchFieldRecords($map, $field, $orderby, $params['page'], $params['pagesize']);
			foreach ($data as $key => $val) {
				$_t = $val;
				$_t['picture'] = domain_name_url .'upload/' . $val['picture'];
				$_t['inputtime'] = date('Y-m-d H:i:s', $val['add_time']);
				$_t['detailUrl'] = U('cook/index/info', array('id' => $val['book_id']));
				$_list[] = $_t;
			}
		}
		return array(
			'list' => $_list,
			'count' => $count,
		);
	}

	public function infoAllList($params){
		$map = array();
		if (!empty($params['cat_id'])) {
			$map['cat_id'] = $params['cat_id'];
		}
		$cookBookDao = $this->cookBookDao();
		$orderby = empty($params['orderby']) ? 'sort_order desc' : $params['orderby'];
		return $cookBookDao->searchAllRecords($map, $orderby);
	}
	
	private function outputForList($list){
		if (!empty($list)) {
			$user_ids = array();
			foreach ($list as $v) {
				$user_ids[] = $v['user_id'];
			}
			$users = $this->userInfoDao()->getUsersByIds($user_ids);
			
			foreach ($list as $k => $v) {
				$list[$k]['nick_name'] = $users[$v['user_id']]['nick_name'];
				$list[$k]['avatar'] = $users[$v['user_id']]['user_img'] ? picurl($users[$v['user_id']]['user_img'], 'b90') : $users[$v['user_id']]['headimgurl'];
			}
		}
		
		return $list;
	}
	
	private function outputForInfo($info){
		if (!empty($info)) {
			$info['label_ids'] = $info['label_ids'] ? explode(',', $info['label_ids']) : array();
			$info['material'] = $info['material'] ? unserialize($info['material']) : array();
			$info['steps'] = $info['steps'] ? unserialize($info['steps']) : array();
			
			//用户
			$user_info = $this->userInfoDao()->getRecord($info['user_id']);
			$info['user_img'] = $user_info['user_img'];
			$info['nick_name'] = $user_info ? $user_info['nick_name'] : '伊特莱';
			
			//菜谱数
			$info['book_count'] = $this->cookBookDao()->where(array('user_id'=>$user_info['user_id']))->count();
			
			//关注数
			$info['collect_count'] = 0;
			
			//粉丝数
			$info['fans_count'] = 0;
		}
	
		return $info;
	}
	
	//返回model
	private function cookBookDao(){
		return D('Common/Cook/Book');
	}
	
	private function userInfoDao(){
		return D('Common/User/UserInfo');
	}
}//end HelpService!甜品