<?php
namespace app\admin\services;
use think\Model;
use think\Db;
class Upush extends Model{
    //header("Content-Type: text/html; charset=utf-8");
    //http的域名
    protected $HOST = 'http://sdk.open.api.igexin.com/apiex.htm';
    //定义常量, appId、appKey、masterSecret 采用本文档 "第二步 获取访问凭证 "中获得的应用配置               
    // STEP1：获取应用基本信息
    protected $APPKEY = "nbZjAF6u8k7D1JutkZGCq6";
    protected $APPID = "TPLFwUDOaT6rGOZze9sGE1";
    protected $MASTERSECRET = "DUMqEQdpHV8JXPl35x5Z33";

    //群推接口案例
    public function pushAll($data){
        require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/extend/UniPush/' . 'push.php');
        return doPushAll($data);
    }

    //单个消息推送
    public function pushOne($data){
        require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/extend/UniPush/' . 'push.php');
        return doPushOne($data);
    }
}
?>
