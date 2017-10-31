<?php
namespace Common\Service;
use Think\Model;
use Common\Basic\Genre;

class CollectService{
	function getInfo($collect_id) {
		return $this->collectDao()->getRecord($collect_id);
	}
	
	function getCollectInfo($params) {
		$map = array(
				'user_id'=>$params['user_id'],
				'id_value'=>$params['record_id'],
				'collect_type'=>Genre::CollectTypeGoods,
		);
		return $this->collectDao()->getRecordInfo($map);
	}
	
	function findInfo($params) {
		$map = array(
				'user_id'=>$params['user_id'],
				'id_value'=>$params['id_value'],
				'collect_type'=>$params['collect_type'],
		);
		return $this->collectDao()->getRecordInfo($map);
	}
	
	function add($params){
		if (empty($params['user_id'])) throw new \Exception('请先登录');
		$distributor_goods = $this->distributorGoodsDao()->getRecord($params['id_value']);
		if (!$distributor_goods) throw new \Exception('商品不存在');
		$map = array(
				'user_id'=>$params['user_id'],
				'id_value'=>$params['id_value'],
				'collect_type'=>$params['collect_type'],
		);
		$info = $this->collectDao()->getRecordInfo($map);
		if ($info) {
			throw new \Exception('已关注');
		}
		
		M()->startTrans();
		
		//收藏记录
		$data = $map;
		$data['add_time'] = NOW_TIME;
		$res = $this->collectDao()->addRecord($data);
		if (!$res) {
			M()->rollback();
			throw new \Exception('关注失败');
		}
		
		//统计收藏数
		$res = $this->goodsDao()->where(array('goods_id'=>$distributor_goods['goods_id']))->setInc('collect_count');
		if (!$res) {
			M()->rollback();
			throw new \Exception('关注失败');
		}
		
		$res = $this->distributorGoodsDao()->where(array('record_id'=>$distributor_goods['record_id']))->setInc('collect_count');
		if (!$res) {
			M()->rollback();
			throw new \Exception('关注失败');
		}
		
		M()->commit();
		
		return true;
	}
	
	public function collect($params){
		$map = array(
				'user_id'=>$params['user_id'],
				'id_value'=>$params['id_value'],
				'collect_type'=>$params['collect_type'],
				'system'=>$params['system'],
				'browser'=>$params['browser'],
				'version'=>$params['version'],
		);
		$info = $this->collectDao()->getRecordInfo($map);
		if ($info) {
			throw new \Exception('已收藏');
		}
		
		$data = $map;
		$data['add_time'] = NOW_TIME;
		$res = $this->collectDao()->addRecord($data);
		if ($res === false) {
			throw new \Exception('收藏失败');
		}
	}
	
	function addAll($params){
		if (empty($params['user_id'])) {
			throw new \Exception('请先登录');
		}
		foreach ($params['cart_ids'] as $v) {
			//购物车商品
			$cart_goods = $this->cartDao()->find($v);
			//分销商货品
			$distributor_product = $this->distributorGoodsProductDao()->getRecord($cart_goods['goods_id']);
			if (!$distributor_product) {
				throw new \Exception('商品不存在');
			}
			
			M()->startTrans();
			
			$map = array(
					'user_id'=>$params['user_id'],
					'id_value'=>$distributor_product['record_id'],
					'collect_type'=>$params['collect_type'],
			);
			$info = $this->collectDao()->getRecordInfo($map);
			if (!$info) {
				$data = $map;
				$data['add_time'] = NOW_TIME;
				$res = $this->collectDao()->addRecord($data);
				if (!$res) {
					M()->rollback();
					throw new \Exception('关注失败');
				}
			}
			
			$res = $this->goodsDao()->where(array('goods_id'=>$distributor_product['goods_id']))->setInc('collect_count');
			if (!$res) {
				M()->rollback();
				throw new \Exception('关注失败');
			}
		}
		
		M()->commit();
		
		return true;
	}
	
	function addStore($params){
		if (empty($params['user_id'])) {
			throw new \Exception('请先登录');
		}
		$info = $this->distributorInfoDao()->getRecord($params['id_value']);
		if (!$info) {
			throw new \Exception('店铺不存在');
		}
		$map = array(
				'user_id'=>$params['user_id'],
				'id_value'=>$params['id_value'],
				'collect_type'=>$params['collect_type'],
		);
		$info = $this->collectDao()->getRecordInfo($map);
		if ($info) {
			throw new \Exception('已收藏');
		}
		$data = $map;
		$data['add_time'] = NOW_TIME;
		$res = $this->collectDao()->addRecord($data);
		if (!$res) {
			throw new \Exception('收藏失败');
		}
		return true;
	}
	
	function del($params) {
		$map = array(
				'collect_id'=>$params['collect_id'],
				'user_id'=>$params['user_id']
		);
		$res = $this->collectDao()->delRecord($map);
		if (!$res) {
			throw new \Exception('删除失败');
		}
		return true;
	}
	
	function delCollect($map) {
		$res = $this->collectDao()->delRecord($map);
		if (!$res) {
			throw new \Exception('删除失败');
		}
		return true;
	}
	
	function getCollectGoodsList($params) {
		$map = array();
		if ($params['keyword']) {
			$where['goods_name'] = array('like', '%'.$params['keyword'].'%');
			$goods_list = $this->goodsDao()->where($where)->select();
			if (empty($goods_list)) {
				return array();
			}
			$goods_ids = array();
			foreach ($goods_list as $v) {
				$goods_ids[] = $v['goods_id'];
			}
			$where = array('goods_id'=>array('in', $goods_ids));
			$distributor_goods_list = $this->distributorGoodsDao()->where($where)->select();
			if (empty($distributor_goods_list)) {
				return array();
			}
			$record_ids = array();
			foreach ($distributor_goods_list as $v) {
				$record_ids[] = $v['record_id'];
			}
			$map['id_value'] = array('in', $record_ids);
		}
		if ($params['user_id']) {
			$map['user_id'] = $params['user_id'];
		}
		if (isset($params['collect_type'])) {
			$map['collect_type'] = $params['collect_type'];
		}
		if (!empty($params['start_time'])) {
			$map['a.add_time'][] = array('egt', strtotime($params['start_time']));
		}
		if (!empty($params['end_time'])) {
			$map['a.add_time'][] = array('elt', strtotime($params['end_time']) + 86400);
		}
		
		$count = $this->collectDao()->searchRecordCount($map);
		$list = array();
		if ($count) {
			$orderBy = 'collect_id DESC';
			$list = $this->collectDao()->searchRecordList($map, $orderBy, $params['page'], $params['pagesize']);
			
			$user_ids = array();
			foreach ($list as $v) {
				$user_ids[] = $v['user_id'];
			}
			$users = $this->userInfoDao()->getUsersByIds($user_ids);
			
			foreach ($list as $k => $v) {
				$goods = $this->distributorGoodsDao()->getRecord($v['id_value']);
				$list[$k]['goods_id'] = $goods['goods_id'];
				$list[$k]['goods_name'] = $goods['goods_name'];
				$list[$k]['goods_image'] = $goods['goods_image'];
				
				//默认货品
				$product = $this->distributorGoodsProductService()->getDefaultProduct($goods['record_id']);
				$list[$k]['goods_price'] = $product['product_price'];
				
				//用户
				$list[$k]['user_name'] = $users[$v['user_id']]['real_name'] ? $users[$v['user_id']]['real_name'] : $users[$v['user_id']]['nick_name'];
				$list[$k]['avatar'] = $users[$v['user_id']]['user_img'] ? picurl( $users[$v['user_id']]['user_img'], 'b90') : $users[$v['user_id']]['headimgurl'];
			}
		}
		
		return array(
				'list'=>$list,
				'count'=>$count,
		);
	}

	function collectStoryList($params) {
		$map = array();
		if ($params['user_id']) {
			$map['user_id'] = $params['user_id'];
		}
		if (isset($params['collect_type'])) {
			$map['collect_type'] = $params['collect_type'];
		}
		$count = $this->collectDao()->searchRecordCount($map);
		$_list = array();
		if ($count) {
			$orderBy = 'collect_id DESC';
			$data = $this->collectDao()->searchRecordList($map, $orderBy, $params['page'], $params['pagesize']);
			foreach ($data as $key => $val) {
				$_t = $val;
				$storyFind = D('StoryInfo')->field(array('story_title','story_image'))->where(array('story_id' => $val['id_value']))->find();
				$_t['story_title'] = $storyFind['story_title'];
				$_t['story_image'] = domain_name_url.'/upload/'.$storyFind['story_image'];
				$_list[] = $_t;
			}
		}
		return array(
			'list' => $_list,
			'count' => $count,
		);
	}

	function getPagerList($params) {
		$map = array();
		if ($params['user_id']) {
			$map['user_id'] = $params['user_id'];
		}
		if (isset($params['collect_type'])) {
			$map['collect_type'] = $params['collect_type'];
		}
		
		$count = $this->collectDao()->searchDistributorRecordCount($map);
		$list = array();
		if ($count) {
			$orderBy = 'collect_id DESC';
			$list = $this->collectDao()->searchDistributorRecordList($map, $orderBy, $params['page'], $params['pagesize']);
			foreach ($list as $k => $v) {
				//店铺收藏数
				$map = array(
						'id_value'=>$v['distributor_id'],
						'collect_type'=>Genre::CollectTypeStore
				);
				$list[$k]['collect_count'] = $this->collectDao()->searchDistributorRecordCount($map);
				
				//店铺推荐商品
				$params = array(
						'distributor_id'=>$v['distributor_id'],
						'pagesize'=>5,
						//'is_hot'=>1
				);
				$datas = $this->distributorGoodsService()->getPagerList($params);
				$list[$k]['hot_list'] = $datas['list'];
			}
		}
		
		return array(
				'list'=>$list,
				'count'=>$count,
		);
	}

	//访客访问记录
	function collectVisitorList($params) {
		$map = $params['map'] ? $params['map'] : array();
		if ($params['user_id']) {
			$map['user_id'] = $params['user_id'];
		}
		if (isset($params['collect_type'])) {
			$map['collect_type'] = $params['collect_type'];
		}
		if (isset($params['collect_type'])) {
			$map['collect_type'] = $params['collect_type'];
		}
		if($params['starTime'] && $params['endTime']){
			$addTime = toStartEntTime($params['starTime'], $params['endTime']);
			$map['add_time'] = array('between', $addTime['starTime'] . ',' . $addTime['endTime']);
		}
		$list = array();
		$orderBy = 'collect_id DESC';
		$list = $this->collectDao()->where($map)->order($orderBy)->select();
		return array(
			'list' => $this->outPutForList($list),
		);
	}

	
	function getCollectList($params) {
		$map = $params['map'] ? $params['map'] : array();
		if ($params['user_id']) {
			$map['user_id'] = $params['user_id'];
		}
		if (isset($params['collect_type'])) {
			$map['collect_type'] = $params['collect_type'];
		}
		if ($params['keyword']) {
			//商品
			$where = array('goods_name'=>array('like', '%'.$params['keyword'].'%'));
			$goods_list = $this->goodsDao()->where($where)->select();
			$record_ids = array();
			if (!empty($goods_list)) {
				$goods_ids = array();
				foreach ($goods_list as $v) {
					$goods_ids[] = $v['goods_id'];
				}
				$where = array('goods_id'=>array('in', $goods_ids));
				$distributor_goods_list = $this->distributorGoodsDao()->where($where)->select();
				if (!empty($distributor_goods_list)) {
					foreach ($distributor_goods_list as $v) {
						$record_ids[] = $v['record_id'];
					}
				}
			}
			//文章
			$where = array('story_title'=>array('like', '%'.$params['keyword'].'%'));
			$story_list = $this->storyInfoDao()->getFieldRecords($where);
			$story_ids = array();
			if (!empty($story_list)) {
				$story_ids = array_keys($story_list);
			}
			if (empty($goods_ids) && empty($story_ids)) {
				return array();
			}
			
			$id_values = array_merge($record_ids, $story_ids);
			$map['id_value'] = array('in', $id_values);
		}
		//店铺
		if ($params['distributor_id']) {
			$where = array('distributor_id'=>$params['distributor_id']);
			$user_list = $this->userInfoDao()->searchFieldRecords($where);
			if (empty($user_list)) return array();
			$user_ids = array_keys($user_list);
			$map['user_id'] = array('in', $user_ids);
		}
		
		$count = $this->collectDao()->searchRecordsCount($map);
		$list = array();
		if ($count) {
			$orderBy = 'collect_id DESC';
			$list = $this->collectDao()->searchRecords($map, $orderBy, $params['page'], $params['pagesize']);
		}
		
		return array(
				'list'=>$this->outPutForList($list),
				'count'=>$count,
		);
	}
	
	function getNewsList($params) {
		$map = array();
		if ($params['user_id']) {
			$map['user_id'] = $params['user_id'];
		}
		if (isset($params['collect_type'])) {
			$map['collect_type'] = $params['collect_type'];
		}
		
		$count = $this->collectDao()->searchRecordsCount($map);
		$list = array();
		if ($count) {
			$orderBy = 'collect_id DESC';
			$list = $this->collectDao()->searchRecords($map, $orderBy, $params['page'], $params['pagesize']);
			if (!empty($list)) {
				//新闻
				$news_ids = array();
				foreach ($list as $v) {
					$news_ids[] = $v['id_value'];
				}
				$map['news_id'] = array('in', $news_ids);
				$news = $this->newsInfoDao()->searchFieldRecords($map);
					
				foreach ($list as $k => $v) {
					//新闻
					$list[$k]['picture'] = $news[$v['id_value']]['picture'];
					$list[$k]['title'] = $news[$v['id_value']]['title'];
					$list[$k]['type_show'] = $news[$v['id_value']]['type_show'];
					$list[$k]['content'] = $news[$v['id_value']]['content'];
					$list[$k]['read_count'] = $news[$v['id_value']]['read_count'];
					$list[$k]['comment_count'] = $news[$v['id_value']]['comment_count'];
					//时间
					$add_time = round((NOW_TIME - $v['add_time']) / 3600);
					if ($add_time < 1) {
						$add_time = round((NOW_TIME - $v['add_time']) / 60);
						$list[$k]['date_time'] = $add_time.'分钟前';
					}elseif ($add_time < 24) {
						$list[$k]['date_time'] = $add_time.'小时前';
					}else {
						$list[$k]['date_time'] = date('Y-m-d H:i', $v['add_time']);
					}
				}
			}
		}
		return array(
				'list'=>$list,
				'count'=>$count,
		);
	}
	
	function getAllList($params) {
		$map = array();
		if ($params['user_id']) {
			$map['user_id'] = $params['user_id'];
		}
		if (isset($params['collect_type'])) {
			$map['collect_type'] = $params['collect_type'];
		}
		if (isset($params['id_values'])) {
			$map['id_value'] = array('in', $params['id_values']);
		}
		
		return $this->collectDao()->searchAllRecords($map);
	}
	
	function collectCount($params) {
		$map = array();
		if ($params['user_id']) {
			$map['user_id'] = $params['user_id'];
		}
		if (isset($params['collect_type'])) {
			$map['collect_type'] = $params['collect_type'];
		}
		if (isset($params['id_value'])) {
			$map['id_value'] = $params['id_value'];
		}
		return $this->collectDao()->searchRecordsCount($map);
	}
	
	//查询用户是否已经收藏
	public function check($params){
		if(empty($params['user_id']) || empty($params['goods_id'])){return false;}
		$map=array('user_id'=>$params['user_id'],'id_value'=>$params['goods_id']);
		$info=$this->collectDao()->getRecordInfo($map);
		if(!empty($info)){
			return 1;
		}else{
			return false;
		}
	}
	
	public function selectDistinctUserid() {
		return $this->collectDao()->searchDistinctUserid();
	}
	
	//根据商品id删除收藏
	function mapDel($params){
		if (empty($params['user_id'])) throw new \Exception('请先登录');
		$distributor_goods = $this->distributorGoodsDao()->getRecord($params['record_id']);
		if (!$distributor_goods) throw new \Exception('商品不存在');
		
		M()->startTrans();
		
		//删除收藏记录
		$map = array(
				'id_value'=>$params['record_id'],
				'user_id'=>$params['user_id']
		);
		$res = $this->collectDao()->delRecord($map);
		if (!$res) {
			M()->rollback();
			throw new \Exception('删除失败');
		}
		
		//统计收藏数
		$res = $this->goodsDao()->where(array('goods_id'=>$distributor_goods['goods_id']))->setDec('collect_count');
		if (!$res) {
			M()->rollback();
			throw new \Exception('关注失败');
		}
		
		$res = $this->distributorGoodsDao()->where(array('record_id'=>$distributor_goods['record_id']))->setDec('collect_count');
		if (!$res) {
			M()->rollback();
			throw new \Exception('关注失败');
		}
		
		M()->commit();
		
		return true;
	}
	
	private function outPutForList($list) {
		if (!empty($list)) {
			foreach ($list as $v) {
				$user_ids[] = $v['user_id'];
			}
			$users = $this->userInfoDao()->getUsersByIds($user_ids);
			
			$new_list = array();
			foreach ($list as $k => $v) {
				//用户
				$v['user_name'] = $users[$v['user_id']]['real_name'] ? $users[$v['user_id']]['real_name'] : $users[$v['user_id']]['nick_name'];
				$v['avatar'] = $users[$v['user_id']]['user_img'] ? picurl( $users[$v['user_id']]['user_img'], 'b90') : $users[$v['user_id']]['headimgurl'];
				//商品
				$goods = $story_info = array();
				if (in_array($v['collect_type'], array(Genre::CollectTypeGoods, Genre::CollectTypeGoodsFoot, Genre::CollectTypeShareGoods))) {
					$goods = $this->distributorGoodsDao()->getRecord($v['id_value']);
					$v['name'] = $goods['goods_name'];
					$v['image'] = $goods['goods_image'];
				}
				//粉丝故事会
				if (in_array($v['collect_type'], array(Genre::CollectTypeShareStory, Genre::CollectTypeStoryFoot))) {
					$story_info = $this->storyInfoDao()->getRecord($v['id_value']);
					$v['name'] = $story_info['story_title'];
					$v['image'] = $story_info['story_image'];
				}
				
				if ($goods || $story_info) {
					$new_list[$k] = $v;
				}
			}
		}
		return $new_list;
	}
	
	private function collectDao(){
		return D('Collect');
	}
	
	private function distributorGoodsService(){
		return D('Distributor\Goods', 'Service');
	}
	
	private function distributorGoodsProductService(){
		return D('Distributor\GoodsProduct', 'Service');
	}
	
	private function distributorGoodsDao(){
		return D('Common/Distributor/Goods');
	}
	
	private function distributorGoodsProductDao(){
		return D('Common/Distributor/GoodsProduct');
	}
	
	private function goodsDao(){
		return D('Common/Goods/Goods');
	}
	
	private function goodsProductDao(){
		return D('Common/Goods/GoodsProduct');
	}
	
	private function storyInfoDao(){
		return D('Common/Story/StoryInfo');
	}
	
	private function distributorInfoDao(){
		return D('Common/Distributor/Info');
	}
	
	private function cartDao(){
		return D('Cart');
	}
	
	private function newsInfoDao(){
		return D('Common/Information/News/Info');
	}
	
	private function userInfoDao(){
		return D('Common/User/UserInfo');
	}
}