<?php
namespace Common\Payment;
/*
 * 支付宝相关操作
 * hzj 20160516
 */
class Alipay{
                                         
    const MER_ID = ''; //合作者身份(parterID)
    const MER_KEY = ''; //交易安全校验码(key)
    const SELLER_EMAIL = ''; //支付宝账户
    const NOTIFY_URL = '/alipay.php';
    const RETURN_URL = '/Wxpay/made.html';
    protected $fields = array();
    /**
	 * 构造方法
	 * @param null
	 * @return boolean
	 */
    public function __construct(){
        //$this->callback_url = $this->app->base_url(true)."/apps/".basename(dirname(__FILE__))."/".basename(__FILE__);
		$this->notify_url = '/ectools_payment_plugin_alipay_server';
        //ajx  按照相应要求请求接口网关改为一下地址
        $this->submit_url = 'https://mapi.alipay.com/gateway.do?_input_charset=utf-8';
        $this->submit_method = 'POST';
        $this->submit_charset = 'utf-8';
    }

    /**
     * 提交支付信息的接口
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment){

    	$charset = 'utf-8';
    	$service = 'create_direct_pay_by_user';//即时到账接口
    	/*switch ($real_method){
    		case '0':
    			$service = 'trade_create_by_buyer';
    			break;
    		case '1':
    			$service = 'create_partner_trade_by_buyer';
    			break;
    		case '2':
    			$service = 'create_direct_pay_by_user';
    			break;
    	}*/
    	$paybody = strval( str_replace(' ', '', (isset($payment['body']) && $payment['body']) ? $payment['body'] : '商城订单' ) );
    	$extend_param = 'isv^sh22';
    	
    	$parameter = array(
    			'extend_param'      => $extend_param,
    			'service'           => $service,
    			'partner'           => self::MER_ID,
    			//'partner'           => ALIPAY_ID,
    			'_input_charset'    => $charset,
    			'notify_url'        => DK_DOMAIN.self::NOTIFY_URL,
    			//'return_url'        => DK_DOMAIN.self::RETURN_URL,
    			'return_url'        => DK_DOMAIN.U('Wxpay/made',array('orderid'=>$payment['payment_id'])),
    			/* 业务参数 */
    			'subject'           => $paybody,
    			'out_trade_no'      => strval($payment['payment_id']),
    			'price'             => strval($payment['cur_money']),
    			'quantity'          => 1,
    			'payment_type'      => 1,
    			/* 物流参数 */
    			'logistics_type'    => 'EXPRESS',
    			'logistics_fee'     => 0,
    			'logistics_payment' => 'BUYER_PAY_AFTER_RECEIVE',
    			/* 买卖双方信息 */
    			'seller_email'      => self::SELLER_EMAIL
    	);
    	//_p($parameter);
    	//$this->add_field('logistics_type','POST');
    	//$this->add_field('logistics_payment','BUYER_PAY');
    	//$this->add_field('logistics_fee','0.00');
    	ksort($parameter);
    	reset($parameter);
    	
    	$param = '';
    	$sign  = '';
    	
    	foreach ($parameter AS $key => $val)
    	{
    		$param .= "$key=" .urlencode($val). "&";
    		$sign  .= "$key=$val&";
    	}
    	
    	$param = substr($param, 0, -1);
    	$sign  = substr($sign, 0, -1). self::MER_KEY;
    	//$sign  = substr($sign, 0, -1). ALIPAY_AUTH;
    	
    	$url = 'https://mapi.alipay.com/gateway.do?'.$param. '&sign='.md5($sign).'&sign_type=MD5';
    	self::ecs_header("Location: $url\n");
    	exit;
    }

	

    /**
     * 自定义 header 函数，用于过滤可能出现的安全隐患
     *
     * @param   string  string  内容
     *
     * @return  void
     **/
    function ecs_header($string, $replace = true, $http_response_code = 0)
    {
    	if (strpos($string, '../upgrade/index.php') === 0)
    	{
    		echo '<script type="text/javascript">window.location.href="' . $string . '";</script>';
    	}
    	$string = str_replace(array("\r", "\n"), array('', ''), $string);
    
    	if (preg_match('/^\s*location:/is', $string))
    	{
    		@header($string . "\n", $replace);
    
    		exit();
    	}
    
    	if (empty($http_response_code) || PHP_VERSION < '4.3')
    	{
    		@header($string, $replace);
    	}
    	else
    	{
    		@header($string, $replace, $http_response_code);
    	}
    }
    
	/**
	 * 支付后返回后处理的事件的动作
	 * @params array - 所有返回的参数，包括POST和GET
	 * @return null
	 */
    public function callback(&$in)
	{
		if (!empty($_POST))
		{
			foreach($_POST as $key => $data)
			{
				$_GET[$key] = $data;
			}
		}
		
		/* 检查数字签名是否正确 */
		ksort($in);
		reset($in);
		
		$sign = '';
		foreach ($_GET AS $key=>$val)
		{
			if ($key != 'sign' && $key != 'sign_type' && $key != 'code')
			{
				$sign .= "$key=$val&";
			}
		}
		
		$sign = substr($sign, 0, -1) . self::MER_KEY;
		//$sign = substr($sign, 0, -1) . ALIPAY_AUTH;
		if (md5($sign) != $_GET['sign'])
		{
			return false;
		}
		
		/* 检查支付的金额是否相符 */
		//if (!check_money($order_sn, $_GET['total_fee']))
		//{
			//return false;
		//}
		
		if ($in['trade_status'] == 'WAIT_SELLER_SEND_GOODS' || $in['trade_status'] == 'TRADE_FINISHED' || $in['trade_status'] == 'TRADE_SUCCESS')
		{
			/* 改变订单状态 */
			$money   = $in['total_fee'];
			$ret['out_trade_no' ] = $in['out_trade_no'];
			$ret['account']     = $in['seller_id'];
			$ret['bank']        = '支付宝支付';
			$ret['pay_account'] = $in['buyer_logon_id'];
			$ret['currency']    = 'CNY';
			$ret['money']       = $money;
			$ret['paycost']     = '0.000';
			$ret['cur_money']   = $money;
			$ret['trade_no']    = $in['trade_no'];
			$ret['t_payed']     = strtotime($in['notify_time']) ? strtotime($in['notify_time']) : time();
			$ret['pay_app_id']  = "alipay";
			$ret['pay_type']    = 'online';
			$ret['memo']        = $in['body'];
			$ret['status']      = 'succ';
			
			return $ret;
		}
		else
		{
			return false;
		}
		
    }

	

}
