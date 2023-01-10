<?php

namespace app\common\util;

/*
 * 小程序使用文件
 */
class MiniUtil{
	
	/**
	 * 获取accesstoken
	 * @param unknown $appid
	 * @return mixed|unknown
	 */
	public static function getAccessToken($appid) {
	    $cachekey = "accesstoken_{$appid}";
	    $cache = cache($cachekey);
	    if (!empty($cache) && !empty($cache['token']) && $cache['expire'] > time()) {
	        return $cache['token'];
	    }

	    $appInfo = Model('cxyapp')->find();
	    if ($appInfo){
	        $secret = $appInfo['appsecret'];
	        $appid = $appInfo['appid'];
	    }else {
	        return HttpUtil::httpError(-1, "在数据库中appid不存在!");
	    }
	    $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";
	    $content = HttpUtil::httpGet($url);
	    if(self::isError($content)) {
	        return self::getAccessToken($appid);
	        //return error('-1', '获取微信公众号授权失败, 请稍后重试！错误详情: ' . $content['message']);
	    }
	    $token = @json_decode($content['content'], true);
	    if(empty($token) || !is_array($token) || empty($token['access_token']) || empty($token['expires_in'])) {
	        /*$errorinfo = substr($content['meta'], strpos($content['meta'], '{'));
	        $errorinfo = @json_decode($errorinfo, true);
	        return error('-1', '获取微信公众号授权失败, 请稍后重试！ 公众平台返回原始数据为: 错误代码-' . $errorinfo['errcode'] . '，错误信息-' . $errorinfo['errmsg']);*/
	        return self::getAccessToken($appid);
	            
	    }
	    $record = array();
	    $rtoken = $token['access_token'];
	    $record['token'] = $token['access_token'];
	    $record['expire'] = time() + $token['expires_in'] - 200;
	    cache($cachekey, $record, $token['expires_in']);
	    return $rtoken;
	}

	/**
	 * 获取公众号accesstoken
	 * @param unknown $appid
	 * @return mixed|unknown
	 */
	public static function getWapAccessToken($appid, $secret) {

	    $cachekey = "accesstoken_{$appid}";
		$cache = cache($cachekey);
	    if (!empty($cache) && !empty($cache['token']) && $cache['expire'] > time()) {
	        return $cache['token'];
	    }
	    $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";
		$content = HttpUtil::httpGet($url);

	    if(empty($content)) {
	        return self::getAccessToken($appid);
	        //return error('-1', '获取微信公众号授权失败, 请稍后重试！错误详情: ' . $content['message']);
	    }
	    $token = @json_decode($content['content'], true);
	    if(empty($token) || !is_array($token) || empty($token['access_token']) || empty($token['expires_in'])) {
	        /*$errorinfo = substr($content['meta'], strpos($content['meta'], '{'));
	        $errorinfo = @json_decode($errorinfo, true);
	        return error('-1', '获取微信公众号授权失败, 请稍后重试！ 公众平台返回原始数据为: 错误代码-' . $errorinfo['errcode'] . '，错误信息-' . $errorinfo['errmsg']);*/
	        return self::getAccessToken($appid);
	            
		}

	    $record = array();
	    $rtoken = $token['access_token'];
	    $record['token'] = $token['access_token'];
	    $record['expire'] = time() + $token['expires_in'] - 200;
	    cache($cachekey, $record, $token['expires_in']);
	    return $rtoken;
	}

	/**
	 * 发送消息
	 * @param unknown $appid
	 * @return mixed|unknown
	 * developer为开发版；trial为体验版；formal为正式版
	 */
	public static function sendTemplateMessage($access_token, $templateid, $data, $miniprogram_state = 'formal'){
	    //$url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=' . $access_token;
	    $url = 'https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token=' . $access_token;
	    $formdata = [
	        'touser' => $data['openid'], 
	        'template_id' => $templateid, 
	        'page' => $data['page'], 
	        'miniprogram_state' => $miniprogram_state, 
	        'data' => $data['data']
	    ];

	    $res = HttpUtil::httpPost($url, json_encode($formdata));
	    return $res['content'];
	}

	/**
	 * 发送公众号消息
	 * @param unknown $appid
	 * @return mixed|unknown
	 */
	public static function sendWapTemplateMessage($access_token, $templateid, $data){
	    $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . $access_token;
	    if(empty($data['linkurl'])){
	    	$linkurl = '';
	    }else{
	    	$linkurl = $data['linkurl'];
	    }
	    $formdata = [
	        'touser' => $data['openid'], 
	        'template_id' => $templateid, 
	        'url' => $linkurl,
	        'data' => $data['data']
	    ];
	    $params = json_encode($formdata);
	    $fp = fsockopen('api.weixin.qq.com', 80, $error, $errstr, 1);
    	$http = "POST /cgi-bin/message/template/send?access_token={$access_token} HTTP/1.1\r\nHost: api.weixin.qq.com\r\nContent-type: application/x-www-form-urlencoded\r\nContent-Length: " . strlen($params) . "\r\nConnection:close\r\n\r\n$params\r\n\r\n";
    	fwrite($fp, $http);
    	fclose($fp);
	    $res = HttpUtil::httpPost($url, json_encode($formdata));
	    return $res['content'];
	    // return true;
	}

	public static function isError($data) {
	    if (empty($data) || !is_array($data) || !array_key_exists('errno', $data) || (array_key_exists('errno', $data) && $data['errno'] == 0)) {
	        return false;
	    } else {
	        return true;
	    }
	}
}

?>