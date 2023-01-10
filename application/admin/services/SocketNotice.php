<?php
namespace app\admin\services;
use think\Model;
use think\Db;
class SocketNotice extends Model{
    public function send($data){
        ini_set('default_socket_timeout', -1);  //不超时
        require_once 'Common.class.php';       //公用方法类
        require_once 'Gateway.php';             //消息发送客户端
        \GatewayClient\Gateway::$registerAddress = '127.0.0.1:1240';
        $result['type']='alive_notice';
        $result['content']=$data['notice_content'];
        //$client_data = db("member_clientid")->where(['user_id'=>$data['id']])->find();
        $jsonmsg = json_encode($result);
        \GatewayClient\Gateway::sendToClient($data['clientid'],$jsonmsg);
    }
}