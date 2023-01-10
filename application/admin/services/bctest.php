<?php
ini_set('default_socket_timeout', -1);  //不超时

require_once 'Common.class.php';       //公用方法类
require_once 'Gateway.php';             //消息发送客户端

\GatewayClient\Gateway::$registerAddress = '127.0.0.1:1239';
$result['type']='notice';

$userInfo = $mysql->findone('sp_member', '', ['id' => $data['userid']]);
$insertarr['room']=$data['room'];
$insertarr['user_id']=$data['userid'];
$insertarr['type']=$data['blockType'];
$insertarr['add_time']=time();
$insertarr['shop_id']=$data['shopid'];
$mysql->insert('sp_alive_room_block',$insertarr);

if($data['blockType'] == 1){
    $blockType = '拉黑';
}
if($data['blockType'] == 2 ){
    $blockType = '单场禁言';
}
if($data['blockType'] == 3){
    $blockType = '永久禁言';
}
$result['msg'] = '用户【'.$userInfo['user_name'].'】违规，已被管理员'.$blockType;

$jsonmsg = json_encode($result);
\GatewayClient\Gateway::sendToGroup($data['room'],$jsonmsg);
?>