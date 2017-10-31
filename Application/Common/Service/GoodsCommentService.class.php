<?php
namespace Common\Service;

class GoodsCommentService{
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->goodsCommentDao()->getRecord($id);
	}
	
	public function createOrModify($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = $v;
		}
		
		if (!$this->goodsCommentDao()->create($data)){
			 throw new \Exception($this->goodsCommentDao()->getError());
		}
		
		if ($params['comment_id'] > 0){
			$result = $this->goodsCommentDao()->save();
			if ($result === false){
				throw new \Exception('修改失败');
			}
		} else {
			$result = $this->goodsCommentDao()->add();
			if ($result < 1){
				throw new \Exception('添加失败');
			}
		}
	}
	
	public function delete($id){
		$result = $this->goodsCommentDao()->delRecord($id);
		if ($result === false) throw new \Exception('删除失败');
	}
	
	public function setCommentStatus($id){
		$info = $this->goodsCommentDao()->find($id);
		if (empty($info)) throw new \Exception('数据不存在');
		$status = $info['status'] == 1 ? 2 : 1;
		$result = $this->goodsCommentDao()->where(array('comment_id'=>$info['comment_id']))->save(array('status'=>$status));
		if ($result === false) throw new \Exception('设置失败');
	}
	
	public function getPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		
		$map = $params['map'] ? $params['map'] : array();
		if (isset($params['goods_name'])) {
			$map['goods_name'] = array('like', '%'.$params['goods_name'].'%');
		}
		if (isset($params['nick_name'])) {
			$map['nick_name'] = array('like', '%'.$params['nick_name'].'%');
		}
		if (isset($params['distributor_id'])) {
			$map['distributor_id'] = $params['distributor_id'];
		}
		if (isset($params['user_id'])) {
			$map['user_id'] = $params['user_id'];
		}
		if (isset($params['goods_id'])) {
			$map['goods_id'] = $params['goods_id'];
		}
		if (isset($params['status'])) {
			$map['status'] = $params['status'];
		}
		
		$count = $this->goodsCommentDao()->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'comment_id DESC' : $params['orderby'];
			$list = $this->goodsCommentDao()->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		
		return array(
			'list'=>$this->outputForList($list),
			'count'=>$count,
		);
	}
	
	public function getAllList($map = array()){
		return $this->goodsCommentDao()->searchAllRecords($map);
	}
	
	private function outputForList($list) {
		if (!empty($list)) {
			foreach ($list as $v) {
				$user_ids[] = $v['user_id'];
				$distributor_ids[] = $v['distributor_id'];
			}
			$users = $this->userInfoDao()->getUsersByIds($user_ids);
			$distributors = $this->distributorInfoDao()->getDistributorsByIds($distributor_ids);
			
			foreach ($list as $k => $v) {
				$list[$k]['pictures'] = $v['pictures'] ? explode('#', $v['pictures']) : array();
				//订单商品
				$order_goods = $this->orderGoodsDao()->find($v['order_goods_id']);
				$list[$k]['order_goods'] = $order_goods;
				//订单
				$order_info = $this->orderInfoDao()->find($order_goods['order_id']);
				//购买后几天评价
				$list[$k]['days'] = ceil(($v['add_time'] - $order_info['add_time']) / 86400);
				//用户
				$list[$k]['user'] = $users[$v['user_id']];
				$list[$k]['avatar'] = $users[$v['user_id']]['user_img'] ? picurl($users[$v['user_id']]['user_img'], 'b90') : $users[$v['user_id']]['headimgurl'];
				//店铺
				$list[$k]['distributor'] = $distributors[$v['distributor_id']];
			}
		}
		
		return $list;
	}
	
	public function CommentByUser($user, $post){
		// 接收到的参数
		$data = array();
		foreach ($post as $k => $v) {
			$data[$k] = $v;
		}
		
		//检查提交的数据
		if (count($post['pictures']) > 3) {
			throw new \Exception('最多可上传3张图片');
		}
		$data['pictures'] = $post['pictures'] ? implode('#', $post['pictures']) : '';
		$data['user_id'] = $user['user_id'];
		$data['nick_name'] = $user['nick_name'];
		
		//订单商品
		$info = $this->orderGoodsDao()->find($post['id']);
		$map = array(
				'order_id'=>$info['order_id'],
				'user_id'=>$user['user_id']
		);
		if ($info['comment_status'] == 1) {
			throw new \Exception('商品已评价');
		}
		$data['order_goods_id'] = $info['id'];
		$data['goods_id'] = $info['goods_id'];
		$data['goods_name'] = $info['goods_name'];
		
		//订单
		$order_info = $this->orderInfoDao()->where($map)->find();
		if (empty($order_info)) {
			throw new \Exception('订单不存在');
		}
		$data['distributor_id'] = $order_info['distributor_id'];
		
		M()->startTrans();
		
		//更新订单商品评价状态
		$map = array('id'=>$info['id']);
		$ordergoods = array('comment_status'=>1);
		if ($this->orderGoodsDao()->where($map)->save($ordergoods) === false) {
			M()->rollback();
			throw new \Exception('更新评价状态失败');
		}
		
		//更新订单评价状态
		$map = array('order_id'=>$info['order_id'], 'comment_status'=>0);
		$notcomment_goods_count = $this->orderGoodsDao()->where($map)->count();
		$map = array('order_id'=>$info['order_id']);
		$comment_status = ($notcomment_goods_count > 0) ? 1: 2;
		$data_comment = array('comment_status'=>$comment_status);
		if ($this->orderInfoDao()->where($map)->save($data_comment) === false) {
			M()->rollback();
			throw new \Exception('系统错误');
		}
		
		//添加评论记录
		if (!$this->goodsCommentDao()->create($data)){
			M()->rollback();
			throw new \Exception($this->goodsCommentDao()->getError());
		}
		$comment_id = $this->goodsCommentDao()->add();
		if ($comment_id < 1){
			M()->rollback();
			throw new \Exception('添加失败');
		}
		
		//五星好评累加
		if ($data['stars'] == 5) {
			$map = array('record_id'=>$info['goods_id']);
			if ($this->distributorGoodsDao()->where($map)->setInc('stars_count', $data['stars']) === false){
				throw new \Exception('添加失败');
			}
		}
	
		M()->commit();
	
		return array('comment_id'=>$comment_id);
	}
	
	public function CommentDelByUser($user, $comment_id){
		$map = array(
				'comment_id'=>$comment_id,
				'user_id'=>$user['user_id']
		);
		return $this->goodsCommentDao()->where($map)->delete();
	}
	
	private function goodsCommentDao() {
		return D('Common/Goods/GoodsComment');
	}
	
	private function orderInfoDao() {
		return D('Common/Order/OrderInfo');
	}
	
	private function orderGoodsDao() {
		return D('Common/Order/OrderGoods');
	}
	
	private function userInfoDao() {
		return D('Common/User/UserInfo');
	}
	
	private function distributorInfoDao() {
		return D('Common/Distributor/Info');
	}
	
	private function distributorGoodsDao() {
		return D('Common/Distributor/Goods');
	}
}//end HelpService!甜品