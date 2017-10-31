<?php
/**
 *********************************************************
 * @author : Ethan
 * @contact : touch_789@163.com
 * @time ：2015-3-10 13:26
 * @copyright : (c) 2015 Ethan All rights reserved.
 *********************************************************
 * @name : 
 * @function : 
 *
 */
namespace Common\WeixinPay;
/**
* JSAPI支付——H5网页端调起支付接口
*/
class wxAPI
{
	public $appID;
	public $appSecret;
	public $appToken;
	public $appAESKey;
	public $httpClass;
	private $data = array();
	
	function __construct($appID,$appSecret,$appToken,$appAESKey)
	{
		if(empty($appID) || empty($appSecret) || empty($appToken) || empty($appAESKey))
			$this->error("微信API凭证错误，无法链接微信服务器！");
		$this->appID = $appID;
		$this->appSecret = $appSecret;
		$this->appToken = $appToken;
		$this->appAESKey = $appAESKey;
			
	}
	
	/**
	 * 获取微信推送的数据
	 * @return array 转换为数组后的数据
	 */
	public function request()
	{
		$xml = file_get_contents("php://input");		
		$xml || exit;
		$xml = new \SimpleXMLElement($xml);
		$xml || exit;
		
		foreach ($xml as $key => $value) 
		{
			$this->data[$key] = strval($value);
		}
       	return $this->data;
	}
	
	/**
	 * * 响应微信发送的信息（自动回复）
	 * @param  string $to      接收用户名
	 * @param  string $from    发送者用户名
	 * @param  array  $content 回复信息，文本信息为string类型
	 * @param  string $type    消息类型
	 * @param  string $flag    是否新标刚接受到的信息
	 * @return string          XML字符串
	 */
	public function response($content, $type = 'text', $flag = 0){
		
		/* 基础数据 */
		$this->data = array(
			'ToUserName'   => $this->data['FromUserName'],
			'FromUserName' => $this->data['ToUserName'],
			'CreateTime'   => time(),
			'MsgType'      => $type,
		);

		/* 添加类型数据 */
		$this->$type($content);

		/* 添加状态 */
		$this->data['FuncFlag'] = $flag;

		/* 转换数据为XML */
		$xml = new \SimpleXMLElement('<xml></xml>');
		$this->data2xml($xml, $this->data);
		exit($xml->asXML());
	}

	function response_news($contents,$fromUsername,$toUserName)
	{
		/* 基础数据 */
		$this->data = array(
			'ToUserName'   => $this->data['FromUserName'],
			'FromUserName' => $this->data['ToUserName'],
			'CreateTime'   => time(),
			'MsgType'      => $type,
		);
		$contents = unserialize($contents);
		$textTpl = "<xml>
		<ToUserName><![CDATA[%s]]></ToUserName>
		<FromUserName><![CDATA[%s]]></FromUserName>
		<CreateTime>%s</CreateTime>
		<MsgType><![CDATA[%s]]></MsgType>
		<ArticleCount>%s</ArticleCount>
		<Articles>
		%s
		</Articles>
		</xml>";					
		foreach((array)$contents as $obj)
		{
			if(empty($obj))continue;
			$html = "<item>
				<Title><![CDATA[%s]]></Title>
				<Description><![CDATA[%s]]></Description>
				<PicUrl><![CDATA[%s]]></PicUrl>
				<Url><![CDATA[%s]]></Url>
				</item>";
			$item[] = sprintf($html,$obj['Title'],$obj['Description'],$obj['PicUrl'],$obj['Url']);
		}
		$xml = sprintf($textTpl,$fromUsername,$toUserName,time(),"news",count($contents),join("",$item));
		echo $xml;
		exit;
	}
	/**
	 * 回复文本信息
	 * @param  string $content 要回复的信息
	 */
	private function text($content){
		$this->data['Content'] = $content;
	}

	/**
	 * 回复音乐信息
	 * @param  string $content 要回复的音乐
	 */
	private function music($music){
		list(
			$music['Title'], 
			$music['Description'], 
			$music['MusicUrl'], 
			$music['HQMusicUrl']
		) = $music;
		$this->data['Music'] = $music;
	}

	/**
	 * 回复图文信息
	 * @param  string $news 要回复的图文内容
	 */
	private function news($news){
		$articles = array();
		foreach ($news as $key => $value) {
			list(
				$articles[$key]['Title'],
				$articles[$key]['Description'],
				$articles[$key]['PicUrl'],
				$articles[$key]['Url']
			) = $value;
			if($key >= 9) { break; } //最多只允许10调新闻
		}
		$this->data['ArticleCount'] = count($articles);
		$this->data['Articles'] = $articles;
	}
	private function transfer_customer_service($content){
		$this->data['Content'] = $content;
	}
	
    private function data2xml($xml, $data, $item = 'item') {
        foreach ($data as $key => $value) {
            /* 指定默认的数字key */
            is_numeric($key) && $key = $item;

            /* 添加子元素 */
            if(is_array($value) || is_object($value)){
                $child = $xml->addChild($key);
                $this->data2xml($child, $value, $item);
            } else {
            	if(is_numeric($value)){
            		$child = $xml->addChild($key, $value);
            	} else {
            		$child = $xml->addChild($key);
	                $node  = dom_import_simplexml($child);
				    $node->appendChild($node->ownerDocument->createCDATASection($value));
            	}
            }
        }
    }
	
	/**
	 * 微信API接入验证
	 * 
	 */
	public function auth($token)
	{
		$signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];	
        		
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
	//获取全局票据
	public function get_access_token()
	{
		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->appID."&secret=".$this->appSecret;
		$result = json_decode( $this->curlGet($url) , true);
		return $result;
	}
	
	//新建自定义链接
	public function set_menu_list($btnJSON,$access_token)
	{
		$url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$access_token;
		$result = json_decode($this->curlPost($url,urldecode($btnJSON)),true);  
		return $result;
	}
	//获取用户基本信息
	function get_user_info($code)
	{
		$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$this->appID."&secret=".$this->appSecret."&code={$code}&grant_type=authorization_code";
		$result = json_decode($this->curlGet($url) , true);
		$openid = $result['openid'];
		if($openid != "")
		{
			$token = $result['access_token'];
			$url=  "https://api.weixin.qq.com/sns/userinfo?access_token={$token}&openid={$openid}&lang=zh_CN";
			$info = json_decode($this->curlGet($url),true);
			return $info;
		}
		return array();
	}
	//获取JS SDK的票据
	function get_jssdk_token($token)
	{
		$url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token={$token}&type=jsapi";
		$result = json_decode($this->curlGet($url),true);
		return $result;
	}
	
	//通过curl get数据
	static public function curlGet($url,$timeout=5,$header="") 
	{
		$ch = curl_init();  
		//设置选项，包括URL
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );	
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST , false);
		curl_setopt($ch, CURLOPT_HEADER , false);
		//执行并获取HTML文档内容
		$output = curl_exec($ch); 
		$error = curl_error($ch);
		if(!empty($error))
		{
			print_r($error."<br>");
		}
		//释放curl句柄
		curl_close($ch); 
		return $output;	
	}
	//通过curl post数据
	static public function curlPost($url, $post_data=array(), $timeout=5,$header="") 
	{
		$header=empty($header)?'':$header;
		//$post_string = http_build_query($post_data);  
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , false); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST , false);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array($header));//模拟的header头
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}
	//默认模拟的header头
	static private function defaultHeader()
	{
		$header="User-Agent:Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12\r\n";
		$header.="Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n";
		$header.="Accept-language: zh-cn,zh;q=0.5\r\n";
		$header.="Accept-Charset: GB2312,utf-8;q=0.7,*;q=0.7\r\n";
		return $header;
	}
}

?>