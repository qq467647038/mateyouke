<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
error_reporting(E_ERROR);

use app\apicloud\controller\TencentSms as TecentSms;
use TencentCloud\Common\Credential;
use think\Db;
use think\Cache;
use app\common\service\SmsService;
use OSS\OssClient;
use Qcloud\Cos\Client;
use OSS\Core\OssException;
require_once "damow.php";

function getConfig(){
    $obj = Db::name('config')->field(['ename','value'])->select();
    $array = [];
    foreach ($obj as $key => $value) {
        $array[$value['ename']] = $value['value'];
    }
    return $array;
}

// 应用公共文件

function returnJson($status = 200, $mess = '' , $data = []){
    $array = [
        'status' => $status,
        'mess' => $mess,
        'data' => $data
    ];
    return json($array);
}

/*
 * 打印数组
 */
function p($arr){
    echo '<pre>';
    print_r($arr);
    echo '</pre>';
}

/*
 * 递归实现无限级分类
 *
 */
function recursive($array,$pid=0,$level=0){
    $arr = array();
    foreach ($array as $v){
        if($v['pid'] == $pid){
            $v['level'] = $level;
            $v['html'] = str_repeat('--', $level);
            $arr[] = $v;
            $arr = array_merge($arr, recursive($array, $v['id'],$level+1));
        }
    }
    return $arr;
}

/*
 * 传递一个id获取它的所有子类id
 *
 */
function get_all_child($array,$id){
    $arr = array();
    foreach ($array as $v){
        if($v['pid'] == $id){
            $arr[] = $v['id'];
            $arr = array_merge($arr, get_all_child($array, $v['id']));
        }
    }
    return $arr;
}


/*
 *
 * 传递一个id获取所有父类及它自己
 *
 */
function get_all_parent($array,$id){
    $arr = array();
    foreach($array as $v){
        if($v['id'] == $id){
            $arr[] = $v['id'];
            $arr = array_merge($arr,get_all_parent($array, $v['pid']));
        }
    }
    return $arr;
}

//生成一个不会重复的字符串
function settoken(){
    $str = md5(uniqid(md5(microtime(true)),true));
    $str = sha1($str); //加密
    return $str;
}

//随机生成数字与字母组合
function getRandomString($len, $chars=null){
    if (is_null($chars)) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    }
    mt_srand(10000000*(double)microtime());
    for ($i = 0, $str = '', $lc = strlen($chars)-1; $i < $len; $i++) {
        $str .= $chars[mt_rand(0, $lc)];
    }
    return $str;
}


//生成六位验证码
function createSMSCode($length = 6){
    $min = pow(10 , ($length - 1));
    $max = pow(10, $length) - 1;
    return rand($min, $max);
}


//自定义函数：time2string($second) 输入秒数换算成多少天/多少小时/多少分/多少秒的字符串
function time2string($second){
    $day = floor($second/(3600*24));
    $second = $second%(3600*24);//除去整天之后剩余的时间
    $hour = floor($second/3600);
    $second = $second%3600;//除去整小时之后剩余的时间
    $minute = floor($second/60);
    $second = $second%60;//除去整分钟之后剩余的时间
    //返回字符串
    if($day == 0){
        return $hour.'小时'.$minute.'分';
    }else{
        return $day.'天'.$hour.'小时'.$minute.'分';
    }
}

//下载文件
function downloadWeixinFile($url){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_NOBODY, 0);    //只取body头
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $package = curl_exec($ch);
    $httpinfo = curl_getinfo($ch);
    curl_close($ch);
    $imageAll = array_merge(array('body' =>$package), array('header' =>$httpinfo));
    return $imageAll;
}


/**
 * 对银行卡号进行掩码处理
 * @param  string $bankCardNo 银行卡号
 * @return string             掩码后的银行卡号
 */
function formatBankCardNo($bankCardNo){
    //截取银行卡号后4位
    $suffix = substr($bankCardNo,-4,4);
    $maskBankCardNo = "**** **** **** **** ".$suffix;
    return $maskBankCardNo;
}

function xmlToArray($xml){
    //禁止引用外部xml实体
    libxml_disable_entity_loader(true);
    $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    return $values;
}

/**
 *  发送短信验证码
 * @param
 * @return object
 * @author:Damow
 */
function sendSms($mobiles,$smscode){exit;
    $service = new SmsService();
    $res = $service->sendCode($mobiles,$smscode);
    $res = object_to_array($res);
    $res['msg'] = $res['Code'];
    return $res;
    // import('Ucpaas', EXTEND_PATH);
    // $options['accountsid']='123456';
    // //填写在开发者控制台首页上的Auth Token
    // $options['token']='123456';
    // $Ucpaas = new \Ucpaas($options);
    // //初始化 $options必填
    // $appid = "123456";	//应用的ID，可在开发者控制台内的短信产品下查看
    // $templateid = "123456";    //可在后台短信产品→选择接入的应用→短信模板-模板ID，查看该模板ID
    
    // $uid = "";
    // //70字内（含70字）计一条，超过70字，按67字/条计费，超过长度短信平台将会自动分割为多条发送。分割后的多条短信将按照具体占用条数计费。
    // $acsResponse =  $Ucpaas->SendSms($appid,$templateid,$smscode,$mobiles,$uid);
    // $acsResponse = json_decode($acsResponse);
    // return $acsResponse;
}

/**
 * @function设置图片地址
 * @param $img_url
 * @author Feifan.Chen <1057286925@qq.com>
 * @return string
 */
function setMedia($img_url){
    //查找系统配置
    $weburl = Db::name('config')
        ->where('ename','weburl')
        ->value('value');
    if(strpos($img_url,'http') === false){
        if(strpos($img_url,'uploads/') !== false){
            $img_url = $img_url ? $weburl."/".$img_url : "";
        }else{
            //功能未做
            $domain = config('tengxunyun')['cos_domain'];
            $img_url = $img_url ? $domain."/".$img_url : "";
        }
    }
    return $img_url;
}
/**
 * 对象转数组
 *
 * @param object $obj 对象
 * @return array
 */
function object_to_array($obj) {
    $obj = (array)$obj;
    foreach ($obj as $k => $v) {
        if (gettype($v) == 'resource') {
            return;
        }
        if (gettype($v) == 'object' || gettype($v) == 'array') {
            $obj[$k] = (array)object_to_array($v);
        }
    }
    return $obj;
}



/**
 * 二维数组根据某个字段排序
 * @param array $proszarray 要排序的数组
 * @param string $prokeys   要排序的键字段
 * @param string $prosort  排序类型  SORT_ASC     SORT_DESC
 * @return array 排序后的数组
 */
function arraySort($proszarray, $prokeys, $prosort = SORT_DESC) {
    $keysValue = array_column($proszarray,$prokeys);
    array_multisort($keysValue,$prosort,$proszarray);
    return $proszarray;
}

// 获取用户角色
function getUserRole($userId){
    $userInfo = db('member')->find($userId);
    if($userInfo['shop_id']>0){
        $role = "shop";
    }
    if($userInfo['pid']>0){
        $role = 'service';
    }
    if($userInfo['shop_id']==0 && $userInfo['pid'] ==0){
        $role = 'user';
    }
    return $role;
}

/**
** 截取中文字符串
**/
function msubstr($str, $start=0, $length, $charset="utf-8", $suffix=true){
 if(function_exists("mb_substr")){
 $slice= mb_substr($str, $start, $length, $charset);
 }elseif(function_exists('iconv_substr')) {
 $slice= iconv_substr($str,$start,$length,$charset);
 }else{
 $re['utf-8'] = "/[x01-x7f]|[xc2-xdf][x80-xbf]|[xe0-xef][x80-xbf]{2}|[xf0-xff][x80-xbf]{3}/";
 $re['gb2312'] = "/[x01-x7f]|[xb0-xf7][xa0-xfe]/";
 $re['gbk'] = "/[x01-x7f]|[x81-xfe][x40-xfe]/";
 $re['big5'] = "/[x01-x7f]|[x81-xfe]([x40-x7e]|xa1-xfe])/";
 preg_match_all($re[$charset], $str, $match);
 $slice = join("",array_slice($match[0], $start, $length));
 } 
 $fix='';
 if(strlen($slice) < strlen($str)){
  $fix='...';
 }
 return $suffix ? $slice.$fix : $slice;
}

//获取商品佣金
function getCommissionPrice($price){
    //查找分销配置
    $config = Db::name('distribution')
        ->where('id',1)
        ->find();
    $one_commission = 0.00;
    $two_commission = 0.00;
    if ($config['is_open']){

        $one_commission = sprintf("%.2f",$price*($config['one_profit']/100));
        $two_commission = sprintf("%.2f",$price*($config['two_profit']/100));
    }

    return [
        'commission_one' => $one_commission,
        'commission_two' => $two_commission,
    ];
}

//分销绑定上级通用方法
function bandPid($user_id,$pid){
    $level_arr = [
        '无代理等级', '个人代理', '区县代理', '市级代理', '省级代理'
    ];

    $time = time();
    $user_id = (int)$user_id;

    //记录绑定前参数日志
    Db::name('invite_log')->insert([
        'type'=>2,
        'log' => json_encode(['user_id'=>$user_id,'pid'=>$pid]),
        'create_time'   => $time
    ]);
    if($pid == $user_id){
        return false;
    }

    if (empty($pid) || !isset($pid) || is_null($pid) || empty($user_id) || !isset($user_id) || is_null($user_id)){
        return false;
    }
    //邀请人信息
    $p_info = Db::name('member')
        ->where('id',$pid)
        ->find();

    $user_info = Db::name('member')
        ->where('id',$user_id)
        ->find();


    $data = [];
    $data['title'] = '绑定上级通知';
    $data['cover'] = '';
    $data['create_time'] = time();
    $data['edit_time'] = time();
    $data['cover'] = '';
    $data['type'] = 1;
    $data['status'] = 1;
    $data['user_id'] = $user_info['id'];
    $data['introduce'] = '您有一条新的消息，请注意查收！';

    // 查询是否有从属关系
    $p_team_id = explode(',', $p_info['team_id']);
    if(in_array($user_id, $p_team_id))
    {
        $data['content'] = '绑定上级失败，被绑定人和绑定人存在从属关系   '.$user_info['user_name'].'【'.$level_arr[$user_info['agent_type']].'】 '.$p_info['user_name'].'【'.$level_arr[$p_info['agent_type']].'】';

        Db::name('notification')->insertGetId($data);

        return false;
    }

    if (empty($user_id) || empty($pid) || empty($p_info) || empty($user_info)){

        return false;
    }

    if($user_info['agent_type']>=$p_info['agent_type'] && $user_info['agent_type']>0){
        $data['content'] = '绑定上级失败，被绑定人等级需小于绑定人   '.$user_info['user_name'].'【'.$level_arr[$user_info['agent_type']].'】 '.$p_info['user_name'].'【'.$level_arr[$p_info['agent_type']].'】';

        Db::name('notification')->insertGetId($data);

        return false;
    }

    Db::startTrans();
    try{
        $member_info = Db::name('member')
            ->where('id',$user_id)
            ->find();
        //当他没有上级的时候，绑定上级以及更新上上级
        if (empty($member_info['one_level'])){
            //查出上级的上级
            $up_one_level = $p_info['one_level'];
            //更新该会员的分销id状态
            Db::name('member')
                ->where('id',$user_id)
                ->update(['one_level' => $pid,
                    'two_level' => $up_one_level,
                    'team_id' => $p_info['team_id'].','.$p_info['id']
                ]);

            //进来插入上下级关系 此时为一级关系
            $insert_data_one = [
                'uid' => $pid,
                'fid' => $user_id,
                'level' => 1,
                'addtime'   => $time
            ];
            $data_one = Db::name('member_friend')->where([
                'uid' => $pid,
                'fid' => $user_id,
                'level' => 1,
            ])->find();
            if (empty($data_one)){
                Db::name('member_friend')
                    ->insert($insert_data_one);
            }

            //找出$pid的上级 为 二级关系
            //当pid的上级不为空的时候
            if (!empty($up_one_level)){
                $insert_data_two = [
                    'uid' => $up_one_level,
                    'fid' => $user_id,
                    'level' => 2,
                    'addtime'   => $time
                ];
                $data_two = Db::name('member_friend')->where([
                    'uid' => $up_one_level,
                    'fid' => $user_id,
                    'level' => 2,
                ])->find();
                if (empty($data_two)){
                    Db::name('member_friend')
                        ->insert($insert_data_two);
                }
            }
            Db::commit();
            Db::name('invite_log')->insert([
                'log' => json_encode([
                    'user_id'   => $user_id,
                    'pid'   => $pid
                ]),
                'create_time'   => $time
            ]);

            return true;
        }
    }catch (\Exception $e){
        Db::rollback();
        Db::name('invite_log')->insert([
            'log' => json_encode((array)$e),
            'create_time'   => $time,
            'type'  => 1
        ]);
        return false;
    }


}


// 利润分销
function distribute_profit($uid, $oid)
{
    // 用户信息
    $userInfo = Db::name('member')->where('id', $uid)->find();

    // 订单信息
    $orderInfo = Db::name('order')->where('id', $oid)->where('order_status', 1)->where('fh_status', 1)->find();
    $goods_id = Db::name('order_goods')->where('order_id',$orderInfo['id'])->value('goods_id');
    $orderInfo['distribute_price'] = Db::name('goods')->where('id', $goods_id)->value('distribute_price');

    // 代理利润
    $agentProfitInfo = Db::name('travel_distribute_profit')->where('id', 1)->find();

    // 存在上级进行代理等级验证
    $team_id_str = trim($userInfo['team_id'], ',');

    $team_id_arr = explode(',', $team_id_str);

    $userInfoArr = Db::name('member')->where('id', 'in', $team_id_arr)->column('id, agent_type');

    // 查询用户是否被冻结
    $frozenUserInfoArr = Db::name('member')->where('id', 'in', $team_id_arr)->column('id, frozen');

    $reverse_team_id_arr = array_reverse($team_id_arr);

    if($agentProfitInfo['open'] == 1){
        // 总后台开启利润分层
        $agent_type = $userInfo['agent_type'];
        foreach($reverse_team_id_arr as $k=>$v){
            $exist = Db::name('member')->where('id', $v)->find();
            if(!$exist)
            {
                break;
            }
            if($frozenUserInfoArr[$v] == 2)
            {
                // 长期冻结 - 不进行利润分销
                continue;
            }

            $net_profit = 0;
            if(!in_array($k, [0, 1]) && $agent_type>=4){
                break;
            }

            if($userInfoArr[$v] > $agent_type){
                $agent_type = $userInfoArr[$v];
                $agent_rate = agent_profit($userInfoArr[$v], $agentProfitInfo);
            }else{
                $agent_rate = 0;
                if(!in_array($k, [0, 1])){
                    continue;
                }
            }
            $agent_profit = $agent_rate*$orderInfo['distribute_price'];
            if($k==0){
                // 直推
                $net_profit += $orderInfo['onefen_price'];
            }

            if($k==1){
                // 间推
                $net_profit += $orderInfo['twofen_price'];
            }

            if($net_profit>=0 && $agent_profit>=0){
                $wallet_id = Db::name('wallet')
                    ->where('user_id', $v)
                    ->value('id');
                if($wallet_id){
                    Db::name('wallet')
                        ->where('user_id', $v)
                        ->Inc('price', $net_profit)
                        ->Inc('agent_profit', $agent_profit)
                        ->update();
                    Db::name('detail')
                        ->insert(array('agent_profit'=>$agent_profit,'agent_type'=>$userInfoArr[$v],'de_type'=>1,'sr_type'=>1,'price'=>$net_profit,'order_type'=>1,'order_id'=>$orderInfo['id'],'user_id'=>$v,'wat_id'=>$wallet_id,'time'=>time()));

                }
            }
        }
    }
}

// 代理等级利润
function agent_profit($agent_type, $agentProfitInfo){
    $agent_rate = 0;

    switch ($agent_type){
        // 代理等级0:游客  1:个人代理  2:区县代理  3:市级代理  4省级代理
        case 1:
            $agent_rate = $agentProfitInfo['peoson_profit']/100;
            break;
        case 2:
            $agent_rate = $agentProfitInfo['area_profit']/100;
            break;
        case 3:
            $agent_rate = $agentProfitInfo['city_profit']/100;
            break;
        case 4:
            $agent_rate = $agentProfitInfo['province_profit']/100;
            break;
    }

    return $agent_rate;
}

// 代理升级
function uplevel_agent($uid, $oid)
{
    // 用户信息
    $userInfo = Db::name('member')->where('id', $uid)->find();

    if(trim($userInfo['team_id'], ',') == ''){
        // 没有上级不做操作
    }
    else{
        // 存在上级进行代理等级验证
        $team_id_str = trim($userInfo['team_id'], ',');
        $team_id_arr = explode(',', $team_id_str);
        Db::name('member')->where('id', 'in', $team_id_arr)->setInc('agent_num', 1);

        $reverse_team_id_arr = array_reverse($team_id_arr);

        foreach($reverse_team_id_arr as $k=>$v)
        {// 代理等级0:游客  1:个人代理  2:区县代理  3:市级代理  4省级代理
            // VIP数量
//                $vip_count = Db::name('member')->where('team_id', ['like', '%,'.$v], ['like', '%,'.$v.',%'], 'or')->where(function ($query){
//                    $query->where('is_vip', 1);
//                })->count();

            $agent_type = Db::name('member')->where('id', $v)->value('agent_type');
            switch ($agent_type)
            {
                case 0:
                    // 游客
                    common_agent($v);
                    break;
                case 1:
                    // 个人代理
                    person_agent($v);
                    break;
                case 2:
                    // 区域代理
                    area_agent($v);
                    break;
                case 3:
                    // 市级代理
                    city_agent($v);
                    break;
            }

        }

        return true;

//        distribute_profit($uid, $oid);
    }
}

// 市级代理
function city_agent($uid){
    // 代理等级0:游客  1:个人代理  2:区县代理  3:市级代理  4省级代理
    // 考核
    $examine_count = Db::name('member')->where('team_id', ['like', '%,'.$uid], ['like', '%,'.$uid.',%'], 'or')->where(function ($query){
        $query->where('agent_type', 3);
    })->count();

    if($examine_count>=18)
    {
        // 区县代理成立
        Db::name('member')->where('id', $uid)->update(['agent_type'=>4]);
    }

}

// 区县代理
function area_agent($uid){
    // 代理等级0:游客  1:个人代理  2:区县代理  3:市级代理  4省级代理
    // 考核
    $examine_count = Db::name('member')->where('team_id', ['like', '%,'.$uid], ['like', '%,'.$uid.',%'], 'or')->where(function ($query){
        $query->where('agent_type', 2);
    })->count();

    if($examine_count>=10)
    {
        // 区县代理成立
        Db::name('member')->where('id', $uid)->update(['agent_type'=>3]);
    }

}

// 个人代理
function person_agent($uid){
    // 代理等级0:游客  1:个人代理  2:区县代理  3:市级代理  4省级代理
    // 考核
    $examine_count = Db::name('member')->where('team_id', ['like', '%,'.$uid], ['like', '%,'.$uid.',%'], 'or')->where(function ($query){
        $query->where('agent_type', 1);
    })->count();

    if($examine_count>=6)
    {
        // 区县代理成立
        Db::name('member')->where('id', $uid)->update(['agent_type'=>2]);
    }

}

// 游客
function common_agent($uid){
    // 代理等级0:游客  1:个人代理  2:区县代理  3:市级代理  4省级代理
    // 直推
    $redirect_count = Db::name('member')->where('is_vip', 1)->where('one_level', $uid)->count();

    // 考核
    $examine_count = Db::name('member')->where('team_id', ['like', '%,'.$uid], ['like', '%,'.$uid.',%'], 'or')->where(function ($query){
        $query->where('is_vip', 1);
    })->count();

    if($redirect_count>=3 && $examine_count>=30)
    {
        // 个人代理成立
        Db::name('member')->where('id', $uid)->update(['agent_type'=>1]);
    }

}

/*
 * 递归实现无限级会员
 *
 */
function recursiveMember($array,$pid=0,$level=0){
    // var_dump($pid);exit;
    // var_dump($array);exit;
    $arr = array();
    foreach ($array as $v){
        if($v['one_level'] == $pid){
            $v['level'] = $level;
            $v['count'] = 0;
            $v['html'] = str_repeat('--', $level);
            $arr[] = $v;
            // var_dump($arr);exit;
            $arr = array_merge($arr, recursiveMember($array, $v['id'], $level+1));
        }
        // var_dump($arr);exit;
    }
    // var_dump($arr);exit;
    return $arr;
}

function statistics_count($array){
    foreach ($array as &$v){
        $v['direct_count'] = 0;
        $v['team_count'] = 0;
        $v['effective_team_num'] = 0;
        $v['effective_direct_num'] = 0;
        
        foreach ($array as $v1){
            if($v['id'] == $v1['one_level']){
                $v['direct_count']++;
                if($v1['reg_enable'] == 1){
                    $v['effective_direct_num']++;
                }
            }
            
            $team_id_arr = explode(',', $v1['team_id']);
            if(in_array($v['id'], $team_id_arr)){
                $v['team_count']++;
                
                if($v1['reg_enable'] == 1){
                    $v['effective_team_num']++;
                }
            }
        }
    }
    
    return $array;
}

/**
 * 阿里云OSS
 * @datatime 2021/11/24
 * @author qinggege
 */
function aliyunOSS($file){
    $accessKeyId = "LTAI5tDmZzCXac2QBzpF7x5w";
    $accessKeySecret = "PSjH6EPFql4LfMMcNK3dAYc4Rk2RcP";
    // Endpoint以杭州为例，其它Region请按实际情况填写。
    $endpoint = "oss-cn-hangzhou.aliyuncs.com";
    // 存储空间名称
    $bucket= "mateyouke";
//            yxst.oss-cn-beijing.aliyuncs.com
    // <yourObjectName>上传文件到OSS时需要指定包含文件后缀在内的完整路径，例如abc/efg/123.jpg

    $type = get_extension($file['image']['name'] ?? $file['filedata']['name']);

    // print_r($type);die();
    $object = md5(rand(1000,9999).time()).".".$type;
    $filePath = $file['image']['tmp_name'] ?? $file['filedata']['tmp_name'];
//     print_r($filePath);die();
    try{
        $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
        $ossClient->uploadFile($bucket, $object, $filePath);
        $data['name'] = "https://mateyouke.oss-cn-hangzhou.aliyuncs.com/".$object;
        return $data;
    } catch(OssException $e) {
        printf(__FUNCTION__ . ": FAILED\n");
        printf($e->getMessage() . "\n");
        return;
    }
}

function get_extension($filename){
    return pathinfo($filename,PATHINFO_EXTENSION);
}

function order_sms_info($phone, $smsInfo){
    if(!$phone){
        $value = array('status'=>400,'mess'=>'手机号码不存在','data'=>array('status'=>400));
    }
    else{
        $data = [];

        $rand_num = rand (0, 9).rand (0, 9).rand (0, 9).rand (0, 9).rand (0, 9).rand (0, 9);


        $cred = new Credential("AKIDQpAQafdUErNm4EHC0e7SehdQ5c0GMK9q", "brzynU2QflhVHtNiuyrY4QdjgUAh6MTR");
        $sms = new TecentSms($cred,"ap-guangzhou");
        $res = $sms->send($phone,$rand_num);
        if($res){
//                if(true){
            $value = array('status'=>200,'mess'=>'发送成功！');

            // Db::name('sms')->insert([
            //     'phone'=>$phone,
            //     'code'=>$rand_num,
            //     'addtime'=>time(),
            //     'use'=>0
            // ]);

            //  session($phone, $rand_num);
            //  var_dump(session($phone));exit;
        }else{
            $value = array('status'=>400,'mess'=>'发送失败！');
        }
    }
}

