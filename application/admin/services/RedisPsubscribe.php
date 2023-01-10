<?php
ini_set('default_socket_timeout', -1);
ini_set('date.timezone','Asia/Shanghai');

 echo 1234567;
//redis配置
$redisconfig = [
    'host'=>'127.0.0.1',
    'port'=>6379,
    'auth'=>'',
];
$redis = new \Redis();
$res = $redis->pconnect($redisconfig['host'],$redisconfig['port']);
var_dump($res);
//$key = 'chat';
$redis->subscribe(array('chat','msg'),'callback');
function callback($redis, $channel, $msg){
    print_r($msg);
    require_once 'DbUnitl.class.php';       //数据库链接类
    require_once 'Common.class.php';       //公用方法类
    require_once 'Gateway.php';   //消息发送客户端
    // require_once 'WxMini.class.php';
    // $wx = new \Weixin\WxMini();
    \GatewayClient\Gateway::$registerAddress = '127.0.0.1:1241';
    $data=json_decode($msg,true);
    $config = array(
        'type' => 'mysql',
        'host' => '139.224.208.107',
        'username' => 'tour',
        'password' => '5879504DFE201107',
        'database' => 'tour',
        'port' => '3306'
    );
    $mysql = new \DbUnitl();
    $mysql->connect($config);
    var_dump($mysql);
    print_r('this is dataaaaaaa');
    print_r($data);

    switch ($data['type']){
        case 'say':
            print_r($data['data']['toid']);
            print_r(array('week'=>'周六','time'=>'1'));
            if (is_array($data['data']['message'])){
                $data['data']['message'] = json_encode($data['data']['message']);
            }
            $insertarr['fromid']=$data['data']['fromid'];
            $insertarr['toid']=$data['data']['toid'];
            $insertarr['usertype']=$data['data']['userType'];
            $insertarr['message']=$data['data']['message'];
            $insertarr['messagetype']=$data['data']['message_type'];
            $insertarr['createtime']=time();
            $insertreturnt = $mysql->insert('sp_chat_message',$insertarr);
            $from_rxin = $mysql->findone('sp_rxin',['user_id'],['token'=>$data['data']['fromid']]);
            $from_user = $mysql->findone('sp_member','',['id'=>$from_rxin['user_id']]);
            $to_rxin = $mysql->findone('sp_rxin',['user_id'],['token'=>$data['data']['toid']]);
            $to_user = $mysql->findone('sp_member','',['id'=>$to_rxin['user_id']]);
            $weburl = $mysql->findone('sp_config','',['ename'=>'weburl']);
            $headimgurl = $weburl['value'].'/static/images/default_100px.jpg';
            $data['data']['from_username'] = empty($from_user['user_name']) ? '匿名' : $from_user['user_name'];
            $data['data']['from_headimgurl'] = empty($from_user['headimgurl']) ? $headimgurl : $weburl['value'].'/'.$from_user['headimgurl'];
            $data['data']['to_username'] = empty($to_user['user_name']) ? '匿名' : $to_user['user_name'];
            $data['data']['to_headimgurl'] = empty($from_user['headimgurl']) ? $headimgurl : $weburl['value'].'/'.$to_user['headimgurl'];
            \GatewayClient\Gateway::sendToUid($data['data']['toid'],json_encode($data));
            break;
        case 'history':
            print_r(array('week'=>'周六','time'=>'2'));
            $where['fromid']=$data['data']['fromid'];
            $where['toid']=$data['data']['toid'];
//            $sqlstr="SELECT * FROM `sp_chat_message` WHERE fromid IN('".$where['fromid']."','".$where['toid']."') AND toid IN('".$where['toid']."','".$where['fromid']."') ORDER BY createtime DESC limit 15";
            $sqlstr="SELECT * FROM `sp_chat_message` WHERE fromid IN('".$where['fromid']."') AND toid IN('".$where['toid']."') or fromid IN('".$where['toid']."') AND toid IN('".$where['fromid']."') ORDER BY createtime DESC limit 15";
            $result = $mysql->selectsql($sqlstr);
            if(!empty($result)) {
                foreach ($result as $key => &$value) {
                    if (is_array(json_decode($value['message'],1))){
                        $value['message'] = json_decode($value['message'],1);
                    }
                    $from_rxin = $mysql->findone('sp_rxin', ['user_id'], ['token' => $value['fromid']]);
                    $from_user = $mysql->findone('sp_member', '', ['id' => $from_rxin['user_id']]);
                    $to_rxin = $mysql->findone('sp_rxin', ['user_id'], ['token' => $value['toid']]);
                    $to_user = $mysql->findone('sp_member', '', ['id' => $to_rxin['user_id']]);
                    $weburl = $mysql->findone('sp_config', '', ['ename' => 'weburl']);
                    $headimgurl = $weburl['value'].'/static/images/default_100px.jpg';
                    $value['from_username'] = empty($from_user['user_name']) ? '匿名' : $from_user['user_name'];
                    $value['from_headimgurl'] = empty($from_user['headimgurl']) ? $headimgurl : $weburl['value'] .'/'. $from_user['headimgurl'];
                    $value['to_username'] = empty($to_user['user_name']) ? '匿名' : $to_user['user_name'];
                    $value['to_headimgurl'] = empty($from_user['headimgurl']) ? $headimgurl : $weburl['value'] .'/'. $to_user['headimgurl'];
                    $value['message_type'] = $value['messagetype'];
                    $value['userType'] = $value['usertype'];
                    unset($value['messagetype']);
                    unset($value['usertype']);
                }
            }else{
                $result = [];
            }
            $newresult['type']='history';
            $newresult['data']=$result;
            \GatewayClient\Gateway::sendToUid($data['data']['fromid'],json_encode($newresult));
            break;
        case 'chatlist':
            $where['fromid']=$data['data']['fromid'];
            //记录请求文本日志
            print_r($data['data']['fromid']);
            print_r(array('week'=>'周六','time'=>'3'));
            $uid_arr = [];
            //$sqlstr="SELECT * FROM `sp_chat_message` WHERE toid='".$where['fromid']."'  GROUP BY fromid ORDER BY createtime DESC";
            $sqlstr="SELECT * FROM `sp_chat_message` WHERE toid='".$where['fromid']."' ORDER BY createtime DESC";

            // $sqlstr="SELECT * FROM `sp_chat_message` WHERE fromid='".$where['fromid']."'  WHERE toid ORDER BY createtime ASC";
            $result = $mysql->selectsql($sqlstr);


            //找出我发送了消息但是客服并没有回复我的数组
            //1.1查找出我发送的消息并且按照发送对象分组
            $sql_1 = "SELECT * from sp_chat_message WHERE fromid = '".$where['fromid']."' GROUP BY toid ";
            $result_1 = $mysql->selectsql($sql_1);
            $arr_1 = [];
            foreach ($result_1 as $a=>$b){
                $arr_1[] = $b['toid'];
            }
            $str = "'" . join("','", array_values($arr_1) ) . "'";
            //1.2按照查找出来的结果中的发送对象+toid = 我 并且以fromid发送对象分组
            $sql_2 = "select * from sp_chat_message WHERE  fromid in ($str) and toid = '".$where['fromid']."' group by fromid";
            $result_2 = $mysql->selectsql($sql_2);
            $result_arr_2 = [];
            foreach ($result_2 as $key => $value) {
                $result_arr_2[] = $value['fromid'];

            }
            echo 'this is result1';
            print_r($result_1);
            echo 'this is result2';
            print_r($result_2);
            if (count($result_1) != count($result_2)){
                echo '进入';
                $result_arr_1 = [];
                foreach ($result_1 as $a=>$b){
                    if (!in_array($b['toid'], $result_arr_2)){
                        $result_arr_1[] = $b['id'];
                    }
                }
                echo 'this is $result_arr_1';

                if (!empty($result_arr_1)){
                    $result_str_2 = implode(',',$result_arr_1);
                    $result_sql_2 = "select*from sp_chat_message where id in ($result_str_2) order by id desc";
                    $result_sql_2 =  $mysql->selectsql($result_sql_2);
                    print_r($result_sql_2);
                    $result = array_merge($result,$result_sql_2);

                }
            }
            echo 88887732;
            print_r($result);
            if(!empty($result)) {
                foreach ($result as $key => &$value) {
                    if(in_array($value['fromid'],$uid_arr)){
                        unset($result[$key]);
                    }else{  //加入返回数组中
                        //排序计算
                        $orders = $value['createtime'];
                        //查出我与此用户的 我发的消息记录的最新一条  如果比他人发我的消息时间大，排序的值为他人发我的时间值
                        $aaa = $mysql->findone('sp_chat_message','',['fromid'=>$value['fromid'],'toid'=>$value['toid']], 'ORDER BY id DESC');
                        print_r($aaa);
                        echo 'this is aaadata';
                        print_r($aaa['createtime']);
                        echo 'this is orders time';
                        print_r($orders);
                        if($aaa['createtime'] > $orders){
                            $orders = $aaa['createtime'];
                        }
                        $uid_arr[] = $value['fromid'];
                    }
                    $from_rxin = $mysql->findone('sp_rxin', ['user_id'], ['token' => $value['fromid']]);
                    $from_user = $mysql->findone('sp_member', '', ['id' => $from_rxin['user_id']]);
                    $to_rxin = $mysql->findone('sp_rxin', ['user_id'], ['token' => $value['toid']]);
                    $to_user = $mysql->findone('sp_member', '', ['id' => $to_rxin['user_id']]);
                    $weburl = $mysql->findone('sp_config', '', ['ename' => 'weburl']);
                    $headimgurl = $weburl['value'].'/static/images/default_100px.jpg';
                    $value['from_username'] = empty($from_user['user_name']) ? '匿名' : $from_user['user_name'];
                    $value['from_headimgurl'] = empty($from_user['headimgurl']) ? $headimgurl : $weburl['value'] .'/'. $from_user['headimgurl'];
                    $value['to_username'] = empty($to_user['user_name']) ? '匿名' : $to_user['user_name'];
                    $value['to_headimgurl'] = empty($to_user['headimgurl']) ? $headimgurl : $weburl['value'] .'/'. $to_user['headimgurl'];
                    $value['message_type'] = $value['messagetype'];
                    $value['userType'] = $value['usertype'];
                    $value['orders'] = $orders;
                    $value['dates'] = date('Y-m-d H:i:s',$orders);
                    unset($value['messagetype']);
                    unset($value['usertype']);
                    //$findone = $mysql->findone('sp_chat_message', '', ['fromid' => $value['fromid']], 'ORDER BY id DESC');

//                    $find = "SELECT * FROM `sp_chat_message` WHERE fromid in('".$value['fromid']."','".$value['toid']."'".") and toid in('".$value['fromid']."','".$value['toid']."'".") ORDER BY id DESC limit 1";
                    $find = "SELECT * FROM `sp_chat_message` WHERE fromid in('".$value['fromid']."'".") and toid in('".$value['toid']."'".") or fromid in('".$value['toid']."'".") and toid in('".$value['fromid']."'".") ORDER BY id DESC limit 1";

                    $findone = $mysql->selectsql($find);
                    //if(empty($findone)){
                    //$findtwo = $mysql->findone('sp_chat_message', '', ['fromid' => $value['fromid']], 'ORDER BY id DESC');
                    //$value['msg'] = $findtwo['message'];
                    //$value['msgtype'] = $findone['messagetype'];
                    //$value['msgtime'] = $findone['createtime'];
                    //}
//                    if (is_array(json_decode($findone[0]['message'],1))){
//                        $findone[0]['message'] = '【图文消息】';
//                    }
                    $value['is_read']=$findone[0]['is_read'];
                    $value['msg'] = $findone[0]['message'];
                    $value['msgtype'] = $findone[0]['messagetype'];
                    $value['msgtime'] = $findone[0]['createtime'];
                    $value['dates'] = date('Y-m-d H:i:s',$value['msgtime']);
                    //$countsql = "SELECT COUNT(*) as msgcount FROM `sp_chat_message` WHERE fromid='" . $value['fromid'] . "'AND toid='" . $value['toid'] . "' AND is_read=0";
                    //$msgcount = $mysql->selectsql($countsql);
                    //$countsql = "SELECT COUNT(*) as msgcount FROM `sp_chat_message` WHERE fromid in('".$value['fromid']."','".$value['toid']."'".") and toid in('".$value['fromid']."','".$value['toid']."'".") AND is_read=0";
                    $countsql = "SELECT COUNT(*) as msgcount FROM `sp_chat_message` WHERE fromid = '".$value['fromid']."' and toid = '".$value['toid']."' AND is_read=0";
                    $msgcount = $mysql->selectsql($countsql);
                    $value['msgcount'] = (int)$msgcount[0]['msgcount'];
                }
                $date = array_column($result, 'orders');
                echo 2222;
                print_r($date);
                array_multisort($date,SORT_DESC,$result);
            }else{
                $result = [];
            }
            $newresult['type']='chatlist';
            $newresult['data']=$result;
            echo 1111111111;
            print_r($newresult);
            \GatewayClient\Gateway::sendToUid($data['data']['fromid'],json_encode($newresult));
            break;
        case 'readmsg':
            $where['fromid']=$data['data']['toid'];
            $where['toid']=$data['data']['fromid'];
            echo 'this is where';
            print_r($where);
            $insertarr['is_read']=1;
            $mysql->update('sp_chat_message',$insertarr,$where);
            $where1['fromid'] = $data['data']['fromid'];
            $where['toid']=$data['data']['toid'];
            $mysql->update('sp_chat_message',$insertarr,$where1);
            /*
            $where['toid']=$data['data']['toid'];
            $where['fromid']=$data['data']['fromid'];
            $insertarr['is_read']=1;
            $mysql->update('sp_chat_message',$insertarr,$where);
            */
            break;
        case 'bindalive_id':
            \GatewayClient\Gateway::$registerAddress = '127.0.0.1:1240';
            $comein = $mysql->findone('sp_alive_comein', '', ['room' => $data['room'],'token'=>$data['id']]);
            if(empty($comein)){
                $insertarr['room']=$data['room'];
                $insertarr['token']=$data['id'];
                $insertarr['createtime']=time();
                $mysql->insert('sp_alive_comein',$insertarr);
            }else{
                //$updatearr['room']=$data['room'];
                //$updatearr['token']=$data['id'];
                //$insertarr['createtime']=time();
                //$result = $mysql->update('sp_alive_comein',$insertarr,$updatearr);
            }
            $from_rxin = $mysql->findone('sp_rxin', ['user_id'], ['token' => $data['id']]);
            $from_user = $mysql->findone('sp_member', '', ['id' => $from_rxin['user_id']]);

            //给此用户增加client_id绑定，每次绑定client_id 的时候记录 最新client_id 的值
            $user_client = $mysql->findone('sp_member_clientid', '', ['user_id' => $from_rxin['user_id']]);
            if(empty($user_client)){  //创建记录
                $cidarr = [
                    'user_id' => $from_rxin['user_id'], 'client_id' => $data['client_id'] , 'created' => date('Y-m-d H:i:s',time())
                ];
                $mysql->insert('sp_member_clientid',$cidarr);
            }else{
                //print_r($user_client);
                if($user_client['client_id'] != $data['client_id']){
                    //更改此用户的新连接client_id
                    $cupdatearr['client_id'] = $data['client_id'];
                    $cupdatearr['created'] = date('Y-m-d H:i:s',time());
                    $wherearr['id'] = $user_client['id'];
                    $mysql->update('sp_member_clientid',$cupdatearr,$wherearr);
                }
            }

            if($from_user['shop_id'] >0 ){
                $result['role']='shop';
            }
            if($from_user['pid'] >0 ){
                $shop =  $mysql->findone('sp_member', '', ['id' => $from_user['pid']]);
                $result['role']='service';
                $result['serviceShopId'] = $shop['shop_id'];
            }
            if($from_user['shop_id'] ==0 && $from_user['pid'] ==0 ){
                $result['role']='user';
            }
            $weburl = $mysql->findone('sp_config', '', ['ename' => 'weburl']);
            $headimgurl = $weburl['value'].'/static/images/default_100px.jpg';
            $map['username'] = empty($from_user['user_name']) ? '匿名' : $from_user['user_name'];
            $map['headimgurl'] = empty($from_user['headimgurl']) ? $headimgurl : $weburl['value'].'/'.$from_user['headimgurl'];
            $result['type']='notice';
            $result['data']=$map;
            $result['msg']='欢迎【'.$map['username'].'】进入直播间';
            $jsonmsg = json_encode($result);
            print_r($result);
            \GatewayClient\Gateway::sendToGroup($data['room'],$jsonmsg);
            break;

        case 'alivesay':
            \GatewayClient\Gateway::$registerAddress = '127.0.0.1:1240';
            $from_rxin = $mysql->findone('sp_rxin', ['user_id'], ['token' => $data['id']]);
            $from_user = $mysql->findone('sp_member', '', ['id' => $from_rxin['user_id']]);
            $weburl = $mysql->findone('sp_config', '', ['ename' => 'weburl']);
            $headimgurl = $weburl['value'].'/static/images/default_100px.jpg';
            $alive_fans_sql = "SELECT * from sp_alive_fans WHERE user_id = ".$from_rxin['user_id'];
            $alive_fans_data = $mysql->selectsql($alive_fans_sql);
            $integral = isset($alive_fans_data[0]['integral'])?(int)$alive_fans_data[0]['integral']:0;
            $mintegral = (int) $from_user['integral'];
            $rankSelectSql = "SELECT * FROM sp_fans_level WHERE points_min <='".$integral."' AND points_max >='".$integral."' limit 1";
            $rank = $mysql->selectsql($rankSelectSql);

            //查出会员的等级信息
            $mrankSelectSql = "SELECT * FROM sp_member_level WHERE points_min <='".$mintegral."' AND points_max >='".$mintegral."' limit 1";
            $mrank = $mysql->selectsql($mrankSelectSql);


            //获取发言配置条数
            $config_sql = "select * from sp_config where ename = 'fans_msg_10min_max'";
            $config_data = $mysql->selectsql($config_sql);
            //统计今日已发言条数
            $today_time = strtotime(date('Y-m-d 00:00:00',time()));
            $count_sql = "select count(*) as allcount from sp_alive_chat_message where fromid = '".$data['id']."' and chat_room_id = ".$data['room'].' and createtime > '.$today_time;
            $count_data = $mysql->selectsql($count_sql);
            if($count_data[0]['allcount'] <= $config_data[0]['value']*10){   //如果发言总条数小于配置中可参与积分增加的条数  则处理   否则不处理
                if($count_data[0]['allcount'] % 10 == 0){   //如果总条数满足10的倍数  则加配置中配置的积分
                    $add_points = (int)$mysql->selectsql("select * from sp_config where ename = 'fans_msg_10min'")[0]['value'];
                    //会员发言增加积分
                    $idataarr = [
                        'user_id' => $from_rxin['user_id'] , 'room' => $data['room'] , 'integral' => $add_points , 'type'=> 4, 'addtime'=> time()
                    ];
                    $mysql->insert('sp_fans_integral',$idataarr);
                }
            }

            $map['username'] = empty($from_user['user_name']) ? '匿名' : $from_user['user_name'];
            $map['headimgurl'] = empty($from_user['headimgurl']) ? $headimgurl : $weburl['value'].'/'.$from_user['headimgurl'];
            $map['user_id'] = $from_user['id'];
            $map['msg'] = $data['msg'];
            $map['rank'] = $rank[0]['level_name'];
            $map['member_rank'] = $mrank[0]['level_name'];
            $map['integral'] = $integral;
            $result['type']='alivesay';
            $result['msg']= $map['rank'].$map['username'].'：'.$data['msg'];
            if($from_user['shop_id'] >0 ){
                $result['role']='shop';
                $map['role']='shop';
            }
            if($from_user['pid'] >0 ){
                $shop =  $mysql->findone('sp_member', '', ['id' => $from_user['pid']]);
                $map['role']='service';
                $map['serviceShopId'] = $shop['shop_id'];
                $result['role']='service';
                $result['serviceShopId'] = $shop['shop_id'];
            }
            if($from_user['shop_id'] ==0 && $from_user['pid'] ==0 ){
                $map['role']='user';
                $result['role']='user';
            }
            $result['data']=$map;
            // print_r('12345678');
            //print_r($rank);

            $insertarr['fromid']=$data['id'];
            $insertarr['usertype']='home';
            $insertarr['message']=$data['msg'];
            $insertarr['messagetype']='';
            $insertarr['chat_room_id']=$data['room'];
            $insertarr['createtime']=time();
            $mysql->insert('sp_alive_chat_message',$insertarr);
            // 正常聊天消息
            $jsonmsg = json_encode($result);



            $blockType = $mysql->findone('sp_alive_room_block', '', ['user_id' => $from_user['id'],'room'=>$data['room']]);
            // print_r($blockType);
            if(empty($blockType)){
                // $result = json_decode($wx->checkMsg($data['msg']), true);
                print_r($result);
                print_r('9999');
                // if($result["errmsg"]=="ok"){
                \GatewayClient\Gateway::sendToGroup($data['room'],$jsonmsg);
                // }
            }elseif($blockType['type'] == 1){
                // print_r(111);
                // 拉黑消息
                $data['msg'] = '你已被拉黑，无法发消息';
                $map['msg'] = $data['msg'];
                $result['msg']=$map['username'].'：'.$data['msg'];
                $result['data']=$map;
                $blockmsg = json_encode($result);
                print_r($result);
                // \GatewayClient\Gateway::sendToUid($from_user['id'],$blockmsg);
                \GatewayClient\Gateway::sendToClient($data['client_id'],$blockmsg);

                // \GatewayClient\Gateway::sendToGroup($data['room'],$blockmsg);
            }elseif($blockType['type'] == 2){
                if(time()> ($blockType['add_time']+24*60*60))
                {
                    $mysql->delete('sp_alive_room_block',['id' => $blockType['id']]);
                    // $result = json_decode($wx->checkMsg($data['msg']), true);
                    if($result["errmsg"]=="ok"){
                        \GatewayClient\Gateway::sendToGroup($data['room'],$jsonmsg);
                    }

                }
                else{
                    // 拉黑消息
                    $data['msg'] = '你已被禁言24小时，无法发消息';
                    $map['msg'] = $data['msg'];
                    $result['msg']=$map['username'].'：'.$data['msg'];
                    $result['data']=$map;
                    $blockmsg = json_encode($result);
                    \GatewayClient\Gateway::sendToClient($data['client_id'],$blockmsg);
                }
            }elseif($blockType['type'] == 3){
                print_r(333);
                // 拉黑消息
                $data['msg'] = '你已被永久禁言，无法发消息';
                $map['msg'] = $data['msg'];
                $result['msg']=$map['username'].'：'.$data['msg'];
                $blockmsg = json_encode($result);
                \GatewayClient\Gateway::sendToClient($data['client_id'],$blockmsg);
            }

            break;

        case 'blockUser':
            \GatewayClient\Gateway::$registerAddress = '127.0.0.1:1240';
            print_r($data);
            // $from_rxin = $mysql->findone('sp_rxin', ['user_id'], ['token' => $data['id']]);
            // $from_user = $mysql->findone('sp_member', '', ['id' => $from_rxin['user_id']]);
            // $weburl = $mysql->findone('sp_config', '', ['ename' => 'weburl']);
            // $headimgurl = $weburl['value'].'/static/images/default_100px.jpg';

            // $map['username'] = empty($from_user['user_name']) ? '匿名' : $from_user['user_name'];
            // $map['headimgurl'] = empty($from_user['headimgurl']) ? $headimgurl : $weburl['value'].'/'.$from_user['headimgurl'];
            // $map['user_id'] = $from_user['id'];
            // $map['msg'] = $data['msg'];

            $result['type']='blockUser';
            // $result['data']=$map;
            // $result['msg']=$map['username'].'：'.$data['msg'];

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
            break;


        case 'alivegifts':
            \GatewayClient\Gateway::$registerAddress = '127.0.0.1:1240';
            $from_rxin = $mysql->findone('sp_rxin', ['user_id'], ['token' => $data['id']]);
            $from_user = $mysql->findone('sp_member', '', ['id' => $from_rxin['user_id']]);
            $weburl = $mysql->findone('sp_config', '', ['ename' => 'weburl']);
            $headimgurl = $weburl['value'].'/static/images/default_100px.jpg';
            $gifts = $mysql->findone('sp_alive_gifts', '' , ['id' => $data['giftid']]);
            $map['username'] = empty($from_user['user_name']) ? '匿名' : $from_user['user_name'];
            $map['headimgurl'] = empty($from_user['headimgurl']) ? $headimgurl : $weburl['value'].'/'.$from_user['headimgurl'];
            if($from_user['shop_id'] >0 ){
                $map['role']='shop';
            }
            if($from_user['pid'] >0 ){
                $shop =  $mysql->findone('sp_member', '', ['id' => $from_user['pid']]);
                $map['role']='service';
                $map['serviceShopId'] = $shop['shop_id'];
            }
            if($from_user['shop_id'] ==0 && $from_user['pid'] ==0 ){
                $map['role']='user';
            }
            //发送说话显示
            $result['type']='alivesay';
            $result['data']=$map;
            $result['msg']=$map['username'].'：给你送了【'.$gifts['name'].'】';
            $jsonmsg = json_encode($result);
            \GatewayClient\Gateway::sendToGroup($data['room'],$jsonmsg);

            //发送礼物显示
            $map['pic']=$weburl['value'] .'/'. $gifts['pic'];
            $map['picgif']=$weburl['value'] .'/'. $gifts['picgif'];
            $map['id']=$data['id'];
            $giftsto['type']='alivegifts';
            $giftsto['data']=$map;
            $giftsto['msg'] = $map['username'].'：给你送了【'.$gifts['name'].'】';

            $insertarr['uid']=$from_user['id'];
            $insertarr['shop_id']=$data['shop_id'];
            $insertarr['gid']=$gifts['id'];
            $insertarr['redbi']=$gifts['point'];
            $insertarr['createtime']=time();
            $mysql->insert('sp_alive_givegift',$insertarr);

            $giftstostr = json_encode($giftsto);
            \GatewayClient\Gateway::sendToGroup($data['room'],$giftstostr);
            break;

        case 'addCart' :
            \GatewayClient\Gateway::$registerAddress = '127.0.0.1:1240';
            print_r('addCart');
            $from_rxin = $mysql->findone('sp_rxin', ['user_id'], ['token' => $data['id']]);
            $from_user = $mysql->findone('sp_member', '', ['id' => $from_rxin['user_id']]);
            //$to_rin = $mysql->findone('sp_rxin', ['token'], ['user_id' => $data['userid']]);
            $to_client_data = $mysql->findone('sp_member_clientid','', ['user_id' => $data['userid']]);
            //print_r($data);
            $result = [];
            $newresult['type']='addCart';
            $newresult['data']=$data;
            $newresult['msg']= "向此".$to_client_data['client_id'].'用户发送了一条通知';
            print_r($newresult);
            //\GatewayClient\Gateway::sendToUid($to_rin['token'],json_encode($newresult));
            \GatewayClient\Gateway::sendToClient($to_client_data['client_id'],json_encode($newresult));
            //\GatewayClient\Gateway::sendToClient($data['client_id'],json_encode($newresult));
            //\GatewayClient\Gateway::sendToClient($data['client_id'],json_encode($newresult));
            break;
    }






    return true;
}
?>