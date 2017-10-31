<?php
namespace Common\Service;

use Common\Basic\CsException;
use Common\Basic\MessageConst;
use Common\Basic\SystemConst;

class JPushService{
	public function getInfo($id){
		if ($id < 1) return false;
		return $this->jpushDao()->getRecord($id);
	}
	
	public function infoCreateOrModify($params){
		if (empty($params['message'])) throw new CsException('内容不能为空', 1007);
		$pl = array(
				MessageConst::JpushPlatformAll, 
				MessageConst::JpushPlatformAndroid,
				MessageConst::JpushPlatformIos,
		);
		if (!in_array($params['platform'], $pl)){
			throw new CsException('未知平台', 1008);
		}
		$data = array(
				'message'=>trim($params['message']),
				'platform'=>intval($params['platform']),
				'person_id'=>intval($params['person_id']),
				'pserson_type'=>intval($params['person_type']),
				'admin_id'=>intval($params['admin_id']),
		);
		$jpushDao = $this->jpushDao();
		if ($params['log_id'] > 0){
			$data['log_id'] = $params['log_id'];
			if ($jpushDao->saveRecord($data) === false){
				throw new CsException('修改失败', 1005);
			}
		} else {
			$data['app_type'] = intval($params['app_type']);
			if ($jpushDao->addRecord($data) < 1){
				throw new CsException('添加失败', 1006);
			}
		}
	}
	
	public function infoPagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		isset($params['send_status']) && $map['send_status'] = $params['send_status'];
		isset($params['admin_id']) && $map['admin_id'] = $params['admin_id'];
		$jpushDao = $this->jpushDao();
		$count = $jpushDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'add_time desc' : $params['orderby'];
			$list = $jpushDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
				'list'=>$list,
				'count'=>$count,
				'page'=>$params['page'],
				'pagesize'=>$params['pagesize'],
		);
	}
	
	public function infoDelete($id){
		if($this->jpushDao()->deleteRecord($id) === false){
			throw new CsException('删除失败', 1004);
		}
	}
	
	public function sendInfo($params){
		$map = array(
			'send_status'=>MessageConst::JpushSendStatusNone,
		);
		$map['log_id'] = $params['log_id'];
		$list = $this->jpushDao()->where($map)->select();
		if (count($list) < 1) throw new CsException('没有需要发送的内容', 1003);
		$count_total = count($list);
		$count_ok = 0;
		foreach ($list as $vo){
			$msg_id = $this->push($vo);
			if ($msg_id){
				$data = array(
						'log_id'=>$vo['log_id'],
						'send_status'=>MessageConst::JpushSendStatusOk,
						'message_id'=>$msg_id,
						'send_time'=>NOW_TIME,
				);
				$this->jpushDao()->saveRecord($data);
				$count_ok++;
			} else {
				$data = array(
					'log_id'=>$vo['log_id'],
					'send_status'=>MessageConst::JpushSendStatusFail,
					'message_id'=>0,
					'send_time'=>NOW_TIME,
				);
				$this->jpushDao()->saveRecord($data);
			}
		}
		if ($count_ok < 1) throw new CsException('发送失败', 1009);
		if ($count_ok != $count_total){
			throw new CsException('仅成功发送了'.$count_ok.'/'.$count_total.'条');
		}
	}
	
	public function cancelInfo($id = 0){
		if ($id < 1) throw new CsException('未知错误', 1000);
		$jpushDao = $this->jpushDao();
		$info = $jpushDao->getRecord($id);
		if (empty($info)) throw new CsException('记录不存在', 1004);
		$info['_cancel'] = 1;
		$msg_id = $this->push($info);
		if (empty($msg_id)) throw new CsException('操作失败', 1005); 
		$data = array(
			'log_id'=>$info['log_id'],
			'send_status'=>MessageConst::JpushSendStatusCanceled,
			'cancel_time'=>NOW_TIME,
		);
		$this->jpushDao()->saveRecord($data);
	}
	
	public function notifyCustomerServiceStaff($message, $url, $tag){
		if(!is_array($tag) || count($tag) < 1) return false;
		$dts = [
			'extral'=>[
				'type'=>'t_url',
				'value'=>$url,
			],
			'tag'=>$tag,
		];
		$data = [
			'platform'=>1,
			'message'=>$message,
			'datas'=>$dts,
		];
		return $this->push($data);
	}
	
	private function push($data){
		$config = $this->configDao()->findConfigs(SystemConst::ConfJpush);
		require_once(COMMON_PATH."JPush/JPush.php");
		$pl = array(
				MessageConst::JpushPlatformAll => 'all',
				MessageConst::JpushPlatformAndroid => 'android',
				MessageConst::JpushPlatformIos => 'ios',
		);
		$platform = $pl[$data['platform']];
		if (empty($platform)) return false;
		if (is_array($data['datas'])) {
			$dts = $data['datas'];
		} else {
			$dts = json_decode($data['datas'], true);
		}
		// 初始化
		$client = new \JPush($config['app_key'], $config['master_secret']);
		$is_production = $config['api_test'] == 1 ? true : false;
		$jpush = $client->push()->setPlatform($platform);
		if (is_array($dts['extral']) && count($dts['extral'])){
			$jpush->addIosNotification($data['message'], 'default', null, null, null, $dts['extral']);
			$jpush->addAndroidNotification($data['message'], null, null, $dts['extral']);
		} else {
			$jpush->setNotificationAlert($data['message']);
		}
		
		if (is_array($dts['alias']) && count($dts['alias']) > 0) {
			$jpush->addAlias($dts['alias']);
		} elseif (is_array($dts['tag']) && count($dts['tag']) > 0){
			$jpush->addTag($dts['tag']);
		} else {
			$jpush->addAllAudience();
		}
		if ($data['_cancel']){
			if (empty($data['message_id'])) throw new CsException('消息ID不存在', 9000);
			$jpush->setOptions(null, 0, intval($data['message_id']));
		} else {
			$live_time = null;
			$dts['live_time'] > 0 && $live_time = $dts['live_time'];
			$jpush->setOptions(null, $live_time, null, $is_production);
		}
		$result = $jpush->send();
		$result = json_decode(json_encode($result), true);
		if($result['data']['msg_id'] < 1){
			return false;
		}
		return $result['data']['msg_id'];
	}
	
	protected function jpushDao(){
		return D('Common/temp/Jpush');
	}
	
	protected function configDao(){
		return D('Config');
	}
}
