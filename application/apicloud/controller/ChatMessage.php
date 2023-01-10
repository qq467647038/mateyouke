<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use think\Db;

class ChatMessage extends Common{
   
    //获取聊天分页数
    public function chatlist(){
        $ret_arr = [
            'code' => 0 , 'data' => [], 'message' => '操作失败'
        ];
        $pages = 15;
        if(input('post.page')){
            $start_page = input('post.page') - 1;
            $start = $start_page * $pages;
        }else{
            $start_page = 1;
            $start = 0;
        }
        
        //$start = $start_page * $pages;
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $data = input('post.');
                    $touserdata = Db::name("rxin")->where("token = '".$data['toid']."'")->find();
//                    $chat_list = Db::name("chat_message")->where("fromid IN('".$data['token']."','".$data['toid']."') AND toid IN('".$data['toid']."','".$data['token']."')")
//                    ->order(array('createtime' => 'desc'))
//                    ->limit($start,$pages)
//                    ->select();

                    $chat_list = Db::name("chat_message")->where("fromid IN('".$data['token']."') AND toid IN('".$data['toid']."')")
                        ->whereOr("fromid IN('".$data['toid']."') AND toid IN('".$data['token']."')")
                        ->order(array('createtime' => 'desc'))
                        ->limit($start,$pages)
                        ->select();
                    $weburl = Db::name('config')->where("ename = 'weburl'")->find();
                    if($chat_list){
                        foreach ($chat_list as $key => &$value) {
                            $from_rxin = Db::name('rxin')->where("token = '".$value['fromid']."'")->find();
                            $from_user = Db::name('member')->find($from_rxin['user_id']);
                            $to_user = Db::name('member')->find($value['toid']);

                            $headimgurl = $weburl['value'] . '/uploads/default.jpg';
                            $value['from_username'] = empty($from_user['user_name']) ? '匿名' : $from_user['user_name'];
                            $value['from_headimgurl'] = empty($from_user['headimgurl']) ? $headimgurl : $weburl['value'] .'/'. $from_user['headimgurl'];
                            $value['to_username'] = empty($to_user['user_name']) ? '匿名' : $to_user['user_name'];
                            $value['to_headimgurl'] = empty($from_user['headimgurl']) ? $headimgurl : $weburl['value'] .'/'. $to_user['headimgurl'];
                            $value['message_type'] = $value['messagetype'];
                            $value['userType'] = $value['usertype'];
                            $value['times'] = date('Y-m-d H:i:s',$value['createtime']);
                            unset($value['messagetype']);
                            unset($value['usertype']);
                        }
                    }
                    $ret_arr = [
                        'status' => 200 , 'data' => $chat_list, 'message' => '获取成功'
                    ];
                }else{
                    $ret_arr = [
                        'status' => 400 , 'data' => [], 'message' => '登录验证失败，请重新登录'
                    ];
                }
            }else{
                $ret_arr = [
                    'status' => 400 , 'data' => [], 'message' => '请登录后查看'
                ];
            }
        }
        echo json_encode($ret_arr,JSON_UNESCAPED_UNICODE);
        exit();
    }    
}