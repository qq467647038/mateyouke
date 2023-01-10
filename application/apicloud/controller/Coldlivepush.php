<?php
namespace app\apicloud\controller;
use think\Db;
class Coldlivepush extends Common {
    /**
     * 直播流请求地址
     */
    public function getstream($room){
        $domain=$this->webconfig['pushdomain'];
        $streamName=$room;
        $key = $this->webconfig['apikey'];
        $time = time()+86400;
        $timedate=date('Y-m-d H:i:s',$time);
        return $this->getPushUrl($domain,$streamName,$key,$timedate);
    }


    /**
     * 获取推流地址
     * 如果不传key和过期时间，将返回不含防盗链的url
     * @param domain 您用来推流的域名
     *        streamName 您用来区别不同推流地址的唯一流名称
     *        key 安全密钥
     *        time 过期时间 sample 2016-11-12 12:00:00
     * @return String url
     */
    public function getPushUrl($domain, $streamName, $key = null, $time = null){
        $type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        if(empty($type)){
            $type = "https://";
        }


        if($key && $time){
            $txTime = strtoupper(base_convert(strtotime($time),10,16));
            //txSecret = MD5( KEY + streamName + txTime )
            $txSecret = md5($key.$streamName.$txTime);
            $ext_str = "?".http_build_query(array(
                    "txSecret"=> $txSecret,
                    "txTime"=> $txTime
                ));
        }
        $data = "rtmp://".$domain."/live/".$streamName . (isset($ext_str) ? $ext_str : "");
        return $data;
    }
}