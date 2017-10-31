<?php
namespace Common\Service;

class StatisticsService{
	public function createTotalAsk($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = $v;
		}
		if (!$this->totalAskDao()->create($data)){
			throw new \Exception($this->totalAskDao()->getError());
		}
		$total_ask_id = $this->totalAskDao()->add();
		if ($total_ask_id < 1){
			throw new \Exception('添加失败');
		}
	}
	
	public function createTouristAsk($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = $v;
		}
		if (!$this->touristAskDao()->create($data)){
			throw new \Exception($this->touristAskDao()->getError());
		}
		$tourist_ask_id = $this->touristAskDao()->add();
		if ($tourist_ask_id < 1){
			throw new \Exception('添加失败');
		}
	}
	
	public function createUserAsk($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = $v;
		}
		if (!$this->userAskDao()->create($data)){
			throw new \Exception($this->userAskDao()->getError());
		}
		
		M()->startTrans();
		
		$user_ask_id = $this->userAskDao()->add();
		if ($user_ask_id < 1){
			M()->rollback();
			throw new \Exception('添加失败');
		}
		
		$map = array(
				'user_id'=>$params['user_id'],
				'inputtime'=>array('egt', strtotime(date('Y-m-d')))
		);
		$visitor_info = $this->visitorAskDao()->where($map)->find();
		if (empty($visitor_info)) {
			if (!$this->visitorAskDao()->create($data)){
				M()->rollback();
				throw new \Exception($this->visitorAskDao()->getError());
			}
			$visitor_ask_id = $this->visitorAskDao()->add();
			if ($visitor_ask_id < 1){
				M()->rollback();
				throw new \Exception('添加失败');
			}
		}
		
		M()->rollback();
	}
	
	public function createWebAsk($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = $v;
		}
		if (!$this->webAskDao()->create($data)){
			throw new \Exception($this->webAskDao()->getError());
		}
		$web_ask_id = $this->webAskDao()->add();
		if ($web_ask_id < 1){
			throw new \Exception('添加失败');
		}
	}
	
	public function createWechatAsk($params){
		//接收到的参数
		$data = array();
		foreach ($params as $k => $v) {
			$data[$k] = $v;
		}
		if (!$this->wechatAskDao()->create($data)){
			throw new \Exception($this->wechatAskDao()->getError());
		}
		$wechat_ask_id = $this->wechatAskDao()->add();
		if ($wechat_ask_id < 1){
			throw new \Exception('添加失败');
		}
	}
	
	private function totalAskDao(){
		return D('Common/statistics/TotalAsk');
	}
	
	private function touristAskDao(){
		return D('Common/statistics/TouristAsk');
	}
	
	private function userAskDao(){
		return D('Common/statistics/UserAsk');
	}
	
	private function webAskDao(){
		return D('Common/statistics/WebAsk');
	}
	
	private function wechatAskDao(){
		return D('Common/statistics/WechatAsk');
	}
	
	private function visitorAskDao(){
		return D('Common/statistics/VisitorAsk');
	}
}
