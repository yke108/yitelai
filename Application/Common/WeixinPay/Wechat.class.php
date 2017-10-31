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

class Wechat
{
    private $data = array();
    public function __construct($token)
    {
        $this->auth($token) || die;
        if (IS_GET) {
            echo $_GET['echostr'];
            die;
        } else {
            $xml = file_get_contents('php://input');
            $xml = new \SimpleXMLElement($xml);
            $xml || die;
            foreach ($xml as $key => $value) {
                $this->data[$key] = strval($value);
            }
        }
    }
    public function request()
    {
        return $this->data;
    }
    public function response($content, $type = 'text', $flag = 0)
    {
        $this->data = array('ToUserName' => $this->data['FromUserName'], 'FromUserName' => $this->data['ToUserName'], 'CreateTime' => NOW_TIME, 'MsgType' => $type);
        $this->{$type}($content);
        $this->data['FuncFlag'] = $flag;
        $xml = new \SimpleXMLElement('<xml></xml>');
        $this->data2xml($xml, $this->data);
        die($xml->asXML());
    }
    private function text($content)
    {
        $this->data['Content'] = $content;
    }
    private function music($music)
    {
        list($music['Title'], $music['Description'], $music['MusicUrl'], $music['HQMusicUrl']) = $music;
        $this->data['Music'] = $music;
    }
    private function news($news)
    {
        $articles = array();
        foreach ($news as $key => $value) {
			$articles[] = $value;
            if ($key >= 9) {
                break;
            }
        }
        $this->data['ArticleCount'] = count($articles);
        $this->data['Articles'] = $articles;
    }
    private function data2xml($xml, $data, $item = 'item')
    {
        foreach ($data as $key => $value) {
            is_numeric($key) && ($key = $item);
            if (is_array($value) || is_object($value)) {
                $child = $xml->addChild($key);
                $this->data2xml($child, $value, $item);
            } else {
                if (is_numeric($value)) {
                    $child = $xml->addChild($key, $value);
                } else {
                    $child = $xml->addChild($key);
                    $node = dom_import_simplexml($child);
                    $node->appendChild($node->ownerDocument->createCDATASection($value));
                }
            }
        }
    }
    private function auth($token)
    {
        $data = array($token,$_GET['timestamp'], $_GET['nonce']);
        $sign = $_GET['signature'];
        sort($data,SORT_STRING);
        $signature = sha1(implode($data));
        return $signature === $sign;
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
    
    //https请求（支持GET和POST）
     static public function https_request($url, $data = null)
    {
    	$curl = curl_init();
    	curl_setopt($curl, CURLOPT_URL, $url);
    	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    	if (!empty($data)){
    		curl_setopt($curl, CURLOPT_POST, 1);
    		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    	}
    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    	$output = curl_exec($curl);
    	curl_close($curl);
    	return $output;
    }
    
}

?>