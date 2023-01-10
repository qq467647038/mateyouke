<?php
namespace app\apicloud\model;
use think\Model;
use think\Db;

class Gongyong extends Model
{    
    
   //接口验证
   public function apivalidate($isToken = 1){
    // var_dump(input());exit;
       if(input('post.client_id') && input('post.api_token')){
           $client_id = input('post.client_id');
           $api_token = input('post.api_token');
           $login_code = input('post.login_code');
           $module = request()->module();
           $controller = request()->controller();
           $action = request()->action(true);
           $secretstr = $module.'/'.$controller.'/'.$action;
        
           $client_secret = Db::name('secret')->where('id',$client_id)->value('client_secret');
           if($client_secret){
               $api_token_server = md5($secretstr.date('Y-m-d', time()).$client_secret);
            // echo $api_token;exit;
            //   if($api_token != $api_token_server){
               if(false){
                   $result = array('status'=>400,'mess'=>'接口请求验证失败','data'=>array('status'=>400));
               }else{
                   //验证个人token
                   if($isToken){
                       //验证设备token
                       $device_token = input('post.device_token');
                       
                        // if(input('post.token')){
                       $token = input('post.token');
                       $valitk = $this->checktokens($token,$device_token);
                    //   var_dump($valitk);exit;
                      if($login_code != $valitk['login_code']){
                          $result = array('status'=>400,'mess'=>'账号已在其他设备上登录，请重新登录','data'=>array('status'=>400));
                          return $result;
                      }
                       
                       if($valitk['status'] != 90001){
                           if($valitk['status'] == 90003){
                            $result = array('status'=>400,'mess'=>'账号已在其他设备上登录，请重新登录','data'=>array('status'=>400));
                           }else{
                            $result = array('status'=>400,'mess'=>'身份验证失败','data'=>array('status'=>400));
                           }
                       }else{
                           $result = array('status'=>200,'mess'=>'接口请求验证成功','user_id'=>$valitk['user_id']);
                       }
                   }else{
                       $device_token = input('post.device_token');
                       $token = input('post.token');
                       $valitk = $this->checktokens($token,$device_token);
                    //   if($login_code != $valitk['login_code']){
                    //       $result = array('status'=>400,'mess'=>'账号已在其他设备上登录，请重新登录','data'=>array('status'=>400));
                    //       return $result;
                    //   }
                       
                       $result = array('status'=>200,'mess'=>'接口请求验证成功','user_id' => $valitk['user_id'],'data'=>array('status'=>200));
                   }
               }
           }else{
               $result = array('status'=>400,'mess'=>'接口请求验证失败','data'=>array('status'=>400));
           }
       }else{
           $result = array('status'=>400,'mess'=>'接口请求验证失败','data'=>array('status'=>400));
       }
       return $result;
   }
   
   
   public function checktokens($token,$device_token=""){
       $rxins = Db::name('rxin')->where('token',$token)->find();
       if (!empty($rxins)){
           $yhinfos = Db::name('member')->where('id',$rxins['user_id'])->where('checked',1)->where('delete', 0)->field('id,appinfo_code,login_code')->find();
           if($yhinfos){
               //查看当前用户表中存储的设备clientid值与传递的device_token值是否一致，不一致提示在其他设备登录，请重新登录
               $valitoken = array('status'=>90001,'user_id'=>$rxins['user_id'], 'login_code'=>$yhinfos['login_code']);
            //    if($device_token && $device_token != $yhinfos['appinfo_code']){
            //         $valitoken = array('status'=>90003,'user_id'=>$rxins['user_id']);
            //    }else{
            //         $valitoken = array('status'=>90001,'user_id'=>$rxins['user_id']);
            //    }
               
           }else{
               $valitoken = array('status'=>90002,'user_id'=>0);
           }
       }else{
           $valitoken = array('status'=>90002,'user_id'=>0);
       }
       return $valitoken;
   }
   
   
   //判断是否是秒杀、团购、拼团活动商品
   public function pdrugp($ruinfo,$ru_attr=''){
       $activity = array();

       //秒杀信息
       if(!$ru_attr){
           $rushs = Db::name('rush_activity')->where('goods_id',$ruinfo['id'])->where('shop_id',$ruinfo['shop_id'])->where('checked',1)->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,goods_id,goods_attr,price,num,xznum,kucun,sold,start_time,end_time')->order('price asc')->find();
       }else{
           $rushs = Db::name('rush_activity')->where('goods_id',$ruinfo['id'])->where('shop_id',$ruinfo['shop_id'])->where('goods_attr',$ru_attr)->where('checked',1)->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,goods_id,goods_attr,price,num,xznum,kucun,sold,start_time,end_time')->find();
           if(!$rushs){
               $rushs = Db::name('rush_activity')->where('goods_id',$ruinfo['id'])->where('shop_id',$ruinfo['shop_id'])->where('goods_attr','')->where('checked',1)->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,goods_id,goods_attr,price,num,xznum,kucun,sold,start_time,end_time')->find();
           }
       }


       $groups = array();
       $assembles = array();
       
       if($rushs){
           if($rushs['goods_attr']){
               $number = Db::name('product')->where('goods_id',$rushs['goods_id'])->where('goods_attr',$rushs['goods_attr'])->field('id,goods_number')->find();
               if(empty($number) || $number['goods_number'] < $rushs['num']){
                   $rushs = array();
               }else{
                   if($rushs['kucun'] <= 0){
                       $pro_price = Db::name('goods')->where('id',$rushs['goods_id'])->value('min_price');
                       Db::name('goods')->update(array('id'=>$rushs['goods_id'],'zs_price'=>$pro_price,'is_activity'=>0));
                       $rushs = array();
                   }
               }
           }else{
               $goods_number = Db::name('product')->where('goods_id',$rushs['goods_id'])->sum('goods_number');
               if(empty($goods_number) || $goods_number < $rushs['num']){
                   $rushs = array();
               }else{
                   if($rushs['kucun'] <= 0){
                       $pro_price = Db::name('goods')->where('id',$rushs['goods_id'])->value('min_price');
                       Db::name('goods')->update(array('id'=>$rushs['goods_id'],'zs_price'=>$pro_price,'is_activity'=>0));
                       $rushs = array();
                   }
               }
           }
       }
       
       if(!$rushs){
           //团购信息
           if(!$ru_attr){
               $groups = Db::name('group_buy')->where('goods_id',$ruinfo['id'])->where('shop_id',$ruinfo['shop_id'])->where('checked',1)->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,goods_id,goods_attr,price,start_time,end_time')->order('price asc')->find();
           }else{
               $groups = Db::name('group_buy')->where('goods_id',$ruinfo['id'])->where('shop_id',$ruinfo['shop_id'])->where('goods_attr',$ru_attr)->where('checked',1)->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,goods_id,goods_attr,price,start_time,end_time')->find();
               if(!$groups){
                   $groups = Db::name('group_buy')->where('goods_id',$ruinfo['id'])->where('shop_id',$ruinfo['shop_id'])->where('goods_attr','')->where('checked',1)->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,goods_id,goods_attr,price,start_time,end_time')->find();
               }
           }
           
           if(!$groups){
               //拼团信息
               if(!$ru_attr){
                   $assembles = Db::name('assemble')->where('goods_id',$ruinfo['id'])->where('shop_id',$ruinfo['shop_id'])->where('checked',1)->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,goods_id,goods_attr,price,pin_num,start_time,end_time')->order('price asc')->find();
               }else{
                   $assembles = Db::name('assemble')->where('goods_id',$ruinfo['id'])->where('shop_id',$ruinfo['shop_id'])->where('goods_attr',$ru_attr)->where('checked',1)->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,goods_id,goods_attr,price,pin_num,start_time,end_time')->find();
                   if(!$assembles){
                       $assembles = Db::name('assemble')->where('goods_id',$ruinfo['id'])->where('shop_id',$ruinfo['shop_id'])->where('goods_attr','')->where('checked',1)->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,goods_id,goods_attr,price,pin_num,start_time,end_time')->find();
                   }
               }
           }

           if(!$assembles){
               //拼团信息

               if(!$ru_attr){
                   $rushsss = Db::name('group_travel')->where('goods_id',$ruinfo['id'])->where('shop_id',$ruinfo['shop_id'])->where('is_show',1)->field('*')->order('price asc')->find();

               }else{
                   $rushsss = Db::name('group_travel')->where('goods_id',$ruinfo['id'])->where('shop_id',$ruinfo['shop_id'])->where('goods_attr',$ru_attr)->where('is_show',1)->field('*')->find();
                   if(!$rushsss){
                       $rushsss = Db::name('group_travel')->where('goods_id',$ruinfo['id'])->where('shop_id',$ruinfo['shop_id'])->where('goods_attr','')->where('is_show',1)->field('*')->find();
                   }
               }
           }
       }


       if(!empty($rushs)){
           $rushs['ac_type'] = 1;
           $activity = $rushs;
       }elseif(!empty($groups)){
           $groups['ac_type'] = 2;
           $activity = $groups;
       }elseif(!empty($assembles)){
           $assembles['ac_type'] = 3;
           $activity = $assembles;
       }elseif(!empty($rushsss)){
           $rushsss['ac_type'] = 4;
           $activity = $rushsss;
       }
       
       return $activity;
   }
   

}
