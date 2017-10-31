<?php
namespace Common\Payment\Yeepay;
/**
 * 所有接口的基类
 */
abstract class CommonUtil{
	//身份的唯一标识
	protected $APP_KEY;
	//受理商ID，身份标识
	protected $MERCHANT_NO;
	//商户支付密钥Key
	protected $KEY;
	
	protected $CUSTOMER_NO;

	protected $SSLCERT_PATH;
	
	protected $SSLKEY_PATH;
	
	protected $NOTIFY_URL = '';

	protected $CURL_TIMEOUT = 30;
	
	
	public $parameters; //请求参数，类型为关联数组
	protected $url = 'https://open.yeepay.com/yop-center'; //接口链接
	protected $method;
	
	public $state;
	public $response; //返回的响应
	public $result; //返回参数，类型为关联数组
	public $error;

	function __construct($config) {
		$this->APP_KEY = $config['app_key'];
		$this->MERCHANT_NO = $config['merchant_no'];
		$this->KEY = $config['key'];
		$this->CUSTOMER_NO = $config['customer_no'];
		$this->SSLCERT_PATH = $config['sslcert_path'];
		$this->SSLKEY_PATH = $config['sslkey_path'];
		$this->NOTIFY_URL = $config['notify_url'];
		$this->method = $config['method'];
	}

	function trimString($value){
		$ret = null;
		if (null != $value){
			$ret = $value;
			if (strlen($ret) == 0){
				$ret = null;
			}
		}
		return $ret;
	}
	
	/**
	 * 	作用：格式化参数，签名过程需要使用
	 */
	function formatBizQueryParaMap($paraMap, $urlencode){
		$buff = "";
		ksort($paraMap);
		foreach ($paraMap as $k => $v){
		    if($urlencode){
			   $v = urlencode($v);
			}
			$buff .= $k.$v;
		}
		return $buff;
	}
	
	/**
	 * 	作用：生成签名
	 */
	public function getSign($Obj){
		foreach ($Obj as $k => $v){
			$Parameters[$k] = $v;
		}
		//签名步骤一：按字典序排序参数
		ksort($Parameters);
		$String = $this->formatBizQueryParaMap($Parameters, false);
		//echo '【string1】'.$String.'</br>';
		//签名步骤二：在string后加入KEY
		$String = $this->KEY.$String.$this->KEY;
		//echo "【string2】".$String."</br>";
		//签名步骤三：MD5加密
		$String = sha1($String);
		//echo "【string3】 ".$String."</br>";
		//签名步骤四：所有字符转为大写
		$result_ = strtolower($String);
		//echo "【result】 ".$result_."</br>";
		return $result_;
	}

	/**
	 * 	作用：以post方式提交xml到对应的接口url
	 */
	public function postXmlCurl($xml,$url,$second=30){		
        //初始化curl        
       	$ch = curl_init();
		//设置超时
		curl_setopt($ch, CURLOP_TIMEOUT, $second);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
		//设置header
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		//post提交方式
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->parameters);
		//运行curl
        $data = curl_exec($ch);
		$error = curl_errno($ch);
		curl_close($ch);
		if(empty($data)){
			throw new \Exception('curl出错',$error);
		}
		return $data;
	}

	/**
	 * 	作用：使用证书，以post方式提交xml到对应的接口url
	 */
	function postXmlSSLCurl($xml,$url,$second=30){
		$ch = curl_init();
		//超时时间
		curl_setopt($ch,CURLOPT_TIMEOUT,$second);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
		//设置header
		curl_setopt($ch,CURLOPT_HEADER,FALSE);
		//要求结果为字符串且输出到屏幕上
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
		//设置证书
		//使用证书：cert 与 key 分别属于两个.pem文件
		//默认格式为PEM，可以注释
		curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
		curl_setopt($ch,CURLOPT_SSLCERT, $this->SSLCERT_PATH);
		//默认格式为PEM，可以注释
		curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
		curl_setopt($ch,CURLOPT_SSLKEY, $this->SSLKEY_PATH);
		//post提交方式
		curl_setopt($ch,CURLOPT_POST, true);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$this->parameters);
		$data = curl_exec($ch);
		$error = curl_errno($ch);
		curl_close($ch);
		if(empty($data)){
			throw new \Exception('curl出错',$error);
		}
		return $data;
	}
	
	
	
	/**
	 * 	作用：设置请求参数
	 */
	function setParameter($parameter, $parameterValue){
		if(empty($parameterValue)) return;
		$this->parameters[$this->trimString($parameter)] = $this->trimString($parameterValue);
	}
	
	/**
	 * 	作用：设置标配的请求参数，生成签名，生成接口参数xml
	 */
	function createData(){
		$this->setParameter('appKey', $this->APP_KEY);
		$this->setParameter('customerNo', $this->CUSTOMER_NO);
		$this->setParameter('merchantNo', $this->MERCHANT_NO);
		//$this->setParameter('customerNo', $this->APP_KEY);
		$this->setParameter('method', $this->method);
	    $this->parameters["ts"] = intval(microtime(true) * 1000);
		$this->parameters["v"] = '6';
		$this->parameters['format'] = 'json';
		$this->parameters['signRet'] = 'true';
	    $this->parameters["sign"] = $this->getSign($this->parameters);//签名
		unset($this->parameters['method']);
	}
	
	/**
	 * 	作用：post请求xml
	 */
	function postData($method, $data = array(), $with_ssl = false){
		$this->method = $method;
		foreach($data as $k => $v){
			if(empty($v)) continue;
			$this->parameters[$k] = $v;
		}
	    $xml = $this->createData();
		$url = $this->url.$this->method;
		if($with_ssl){
			$this->response = $this->postXmlSSLCurl($xml,$url,$this->CURL_TIMEOUT);
		} else {
			$this->response = $this->postXmlCurl($xml,$url,$this->CURL_TIMEOUT);
		}
		$result = json_decode($this->response, true);
		$this->state = ($result['state'] == 'SUCCESS');
		$this->result = $result['result'];
		$this->error = $result['error'];
	}
}