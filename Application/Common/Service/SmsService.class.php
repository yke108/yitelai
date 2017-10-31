<?php
namespace Common\Service;
use Common\Basic\CsException;
use Common\Basic\Tool;

class SmsService{
	const UserRegister = 1;
	const UnbindPhone = 2;
	const BindPhone = 3;
	const ModifyPassword = 4;
	const UserLogin = 5;
	const RedpacketReceive = 6;
	const ModifyTradePassword = 7;
	const AddAdmin = 8;
	
	public function sendSms($user, $phone, $type, $params){
		$must_exists = array(
			self::UnbindPhone, self::ModifyPassword, self::UserLogin, self::AddAdmin,
		);
		$canot_exists = array(
			self::UserRegister, self::BindPhone, self::RedpacketReceive,
		);
		if(in_array($type, $must_exists)){
			if(empty($user)) throw new \Exception('用户不存在');
			if(empty($phone)) throw new \Exception('手机号不能为空');
		} elseif(in_array($type, $canot_exists)){
			if($user['password']) return '手机号码已存在';
		}
		$res = $this->createRecordForPhone($phone, $type);
		if(!is_array($res)) return '发送失败';
		
		//过滤
		//return $res;
		$filter = array('13912345678', '13719148993');
		if (in_array($phone, $filter)) {
			return $res;
		}
		
		if($this->send($phone, $res['rnd'], $type, $params) !== 0){
			return '发送失败，请稍后再试';
		}
		return $res;
	}
	
	public function createRecordForPhone($phone, $verify_type){
		//生成验证码
		$rnd = rand(100000,999999);
		//$rnd = 123456;
		//保存验证码
		$expire_time = NOW_TIME + 600;
		$data = array(
			'code'=>$this->psd($rnd),
			'phone'=>$phone,
			'expire_time'=>$expire_time,
			'verify_type'=>$verify_type,
		);
		$sms_id = $this->smsCodeDao()->add($data);
		if($sms_id < 1){
			return false;
		}
		return array(
			'sms_id'=>$sms_id,
			'rnd'=>$rnd,
			'pss'=>$this->pssimple($rnd),
		);
	}
	
	public function getRecordById($id){
		$map = array(
			'id'=>$id,
		);
		return $this->smsCodeDao()->where($map)->find();
	}
	
	public function getCheckedRecord($id, $code, $verify_type){
		$sms = $this->getRecordById($id);
		if(empty($sms)
		|| $sms['expire_time'] < time()
		|| $sms['code'] != $this->psd($code)
		|| $sms['verify_type'] != $verify_type){
			return false;
		} 
		return $sms;
	}
	
	/* public function sendDirect($tel, $content){
		$content = iconv("UTF-8","GB2312",$content);
		$info = array(
			'id'=>'yitelai2016',
			'pwd'=>'yitelai123456',
			'to'=>$tel,
			'content'=>$content,
			'time'=>NOW_TIME,
		);
		$url  = 'http://service.winic.org/sys_port/gateway/?';
		$url .= http_build_query($info);

		if (function_exists ( 'file_get_contents' )) {
			$file_contents = file_get_contents ( $url );
		} else {
			$ch = curl_init ();
			$timeout = 5;
			curl_setopt ( $ch, CURLOPT_URL, $url );
			curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
			$file_contents = curl_exec ($ch);
			curl_close ( $ch );
		}
	
		$res = explode('/', $file_contents);
		return $res[0] == '000';
	} */
	
	public function sendDirect($tel, $content){
		//$content = iconv('GB2312', 'UTF-8', $content);
		$info = array(
				'username'=>'gaj',
				'password'=>'123456',
				'mobile'=>$tel,
				'content'=>$content,
		);
		$url  = 'http://114.215.196.145/sendSmsApi?';
		$url .= http_build_query($info);
		
		if (function_exists ( 'file_get_contents' )) {
			$file_contents = file_get_contents ( $url );
		} else {
			$ch = curl_init ();
			$timeout = 5;
			curl_setopt ( $ch, CURLOPT_URL, $url );
			curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
			$file_contents = curl_exec ($ch);
			curl_close ( $ch );
		}
		
		//M('test')->add(array('txt'=>$file_contents));
		
		//$res = explode('/', $file_contents);
		//return $res[0] == '000';
		return $file_contents == 1;
	}
	
	public function send($tel, $sendsms, $verify_type = 1, $params = ''){
		header("content-Type: text/html; charset=GB2312");
		if(empty($tel)){
			return '发送失败';
		}
	
		if($verify_type == self::UserRegister){
			$content="新用户注册验证码：".$sendsms."，请在10分钟内完成验证"; //短信内容
		} else if($verify_type == self::UnbindPhone){
			$content="修改手机绑定验证码：".$sendsms."，请在10分钟内完成验证";
		} else if($verify_type == self::BindPhone){
			$content="绑定新手机验证码：".$sendsms."，请在10分钟内完成验证"; //短信内容
		} else if($verify_type == self::ModifyPassword){
			$content="密码修改的验证码：".$sendsms."，请在10分钟内完成验证";
		} else if($verify_type == self::ModifyTradePassword){
			$content="支付密码修改的验证码：".$sendsms."，请在10分钟内完成验证";
		} else if($verify_type == self::UserLogin){
			$content="登录验证码：".$sendsms."，请在10分钟内完成验证";
		} else if($verify_type == self::RedpacketReceive){
			$content="验证码：".$sendsms."，请在10分钟内完成验证";
		} else if($verify_type == self::AddAdmin){
			$url = DK_DOMAIN.'/distributor';
			$content="您的店铺管理地址：".$url."。管理员账号：".$params['username']."，密码：".$params['password']."。";
		} else {
			return 2; //内容不能为空
		}
	
		if (!$this->sendDirect($tel, $content)) {
			return '发送失败';
		} else {
			return 0; //OK
		}
	}
	
	public function smsLogSend(){
		//获取记录
		$map = array(
				'send_status'=>0,
		);
		$smsLogDao = $this->smsLogDao();
		$smsLog = $this->smsLogDao()->findRecord($map);
		if (empty($smsLog)) return;
		if ($smsLog['content']){
			$content = $smsLog['content'];
		} else {
			$vals = json_decode($smsLog['sms_vals'], true);
			if ($vals['content']){
				$content = $vals['content'];
				foreach ($vals as $ko => $vo){
					if ($ko == 'content') continue;
					if ($ko == 'short_url') $vo = Tool::getDwz($vo);
					$content = str_replace('{'.$ko.'}', $vo, $content);
				}
			}
		}
	
		if (!empty($content) && $smsLog['mobile']){
			if ($this->sendDirect($smsLog['mobile'], $content)){
				$data = array(
						'log_id'=>$smsLog['log_id'],
						'send_status'=>1,
						'send_time'=>NOW_TIME,
				);
				$smsLogDao->saveRecord($data);
			} else {
				$data = array(
						'log_id'=>$smsLog['log_id'],
						'send_status'=>array('exp', 'send_status+2'),
						'send_time'=>NOW_TIME,
				);
				$smsLogDao->saveRecord($data);
			}
		}
	}
	
	public function getSmsTemplate($id){
		return $this->smsTemplateDao()->getRecord($id);
	}
	
	public function smsTemplateModify($params){
		if ($params['template_id'] < 1) throw new CsException('系统错误');
		if (empty($params['template_intro'])) throw new CsException('模板介绍不能为空');
		if (empty($params['template_sms'])) throw new CsException('模板内容不能为空');
		$data = array(
				'template_id'=>$params['template_id'],
				'template_sms'=>trim($params['template_sms']),
				'template_sms_status'=>$params['template_sms_status'] == 1 ? 1 : 2,
				'template_intro'=>$params['template_intro'],
				'template_sign'=>$params['template_sign'],
				'template_code'=>$params['template_code'],
		);
		$result = $this->smsTemplateDao()->saveRecord($data);
		if ($result === false){
			throw new CsException('修改失败');
		}
	}
	
	public function smsTemplatePagerList($params){
		$params['page'] < 1 && $params['page'] = 1;
		$params['pagesize'] < 1 && $params['pagesize'] = 20;
		$map = array();
		$smsTemplateDao = $this->smsTemplateDao();
		$count = $smsTemplateDao->searchRecordsCount($map);
		$list = array();
		if($count > 0){
			$orderby = empty($params['orderby']) ? 'template_id desc' : $params['orderby'];
			$list = $smsTemplateDao->searchRecords($map, $orderby, $params['page'], $params['pagesize']);
		}
		return array(
				'list'=>$list,
				'count'=>$count,
				'page'=>$params['page'],
				'pagesize'=>$params['pagesize'],
		);
	}
	
	public function sendSmsByTemplate($id, $mobile, $data = array()){
		$template = $this->smsTemplateDao()->getRecord($id);
		if (empty($template)) throw new CsException('模板不存在');
		if ($template['template_sms_status'] != 1) return true;
		$str = $template['template_sms'];
		if(is_array($data)){
			foreach($data as $k => $v){
				$str = str_replace('{'.$k.'}', $v, $str);
			}
		}
		if (empty($str)) throw new CsException('短信内容不能为空');
		$this->sendDirect($mobile, $str);
	}
	
	private function psd($code){
		return md5($code.md5($code.'kzl3eho6qw1eids8'));
	}
	
	private function pssimple($code){
		return md5($code);
	}
	
	private function smsCodeDao(){
		return D('Common/Sms/SmsCode');
	}
	
	private function smsLogDao(){
		return D('Common/Sms/SmsLog');
	}
	
	protected function configDao(){
		return D('Common/Sms/Config');
	}
	
	private function smsTemplateDao(){
		return D('Common/Sms/SmsTemplate');
	}
}
