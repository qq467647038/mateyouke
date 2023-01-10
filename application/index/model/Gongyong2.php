<?php
namespace app\apicloud\model;
use think\Model;
use think\Db;

class Gongyong extends Model
{    
    
   //接口验证
   public function apivalidate(){
       if(input('post.client_id') && input('post.api_token')){
           $client_id = input('post.client_id');
           $api_token = input('post.api_token');
           $module = request()->module();
           $controller = request()->controller();
           $action = request()->action();
           $secretstr = $module.'/'.$controller.'/'.$action;
           $client_secret = Db::name('secret')->where('id',$client_id)->value('client_secret');
           if($client_secret){
               $api_token_server = md5($secretstr.date('Y-m-d', time()).$client_secret);
               if($api_token != $api_token_server){
                   $result = array('status'=>400,'mess'=>'接口请求验证失败','data'=>array('status'=>400));
               }else{
                   //验证个人token
                   if(input('post.token')){
                       $token = input('post.token');
                       $valitk = $this->checktokens($token);
                       if($valitk['status'] != 90001){
                           $result = array('status'=>400,'mess'=>'身份验证失败','data'=>array('status'=>400));
                       }else{
                           $result = array('status'=>200,'mess'=>'接口请求验证成功','user_id'=>$valitk['user_id']);
                       }
                   }else{
                       $result = array('status'=>200,'mess'=>'接口请求验证成功','data'=>array('status'=>200));
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
   
   
   public function checktokens($token){
       $rxins = Db::name('rxin')->where('token',$token)->find();
       if (!empty($rxins)){
           $yhinfos = Db::name('member')->where('id',$rxins['user_id'])->where('checked',1)->field('id')->find();
           if($yhinfos){
               $valitoken = array('status'=>90001,'user_id'=>$rxins['user_id']);
           }else{
               $valitoken = array('status'=>90002,'user_id'=>0);
           }
       }else{
           $valitoken = array('status'=>90002,'user_id'=>0);
       }
       return $valitoken;
   }
   
   //判断是否是秒杀或团购活动商品
   public function pdrugp($ruinfo,$ru_attr=''){
       $activity = array();
       
       //活动期间内秒杀库存已抢空
       if(!$ru_attr){
           $prorushs = Db::name('rush_activity')->where('goods_id',$ruinfo['id'])->where('shop_id',$ruinfo['shop_id'])->where('checked',1)->where('hd_bs',1)->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,goods_id,goods_attr,price,num,sold,start_time,end_time')->order('price asc')->find();
       }else{
           $prorushs = Db::name('rush_activity')->where('goods_id',$ruinfo['id'])->where('goods_attr',$ru_attr)->where('shop_id',$ruinfo['shop_id'])->where('checked',1)->where('hd_bs',1)->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,goods_id,goods_attr,price,num,sold,start_time,end_time')->find();
       }
        
       if($prorushs){
           if($prorushs['sold'] >= $prorushs['num']){
               $pro_price = Db::name('goods')->where('id',$prorushs['goods_id'])->value('min_price');
               // 启动事务
               Db::startTrans();
               try{
                   Db::name('rush_activity')->update(array('hd_bs'=>2,id=>$prorushs['id']));
                   Db::name('goods')->update(array('id'=>$prorushs['goods_id'],'zs_price'=>$pro_price,'is_activity'=>0));
                   // 提交事务
                   Db::commit();
               } catch (\Exception $e) {
                   // 回滚事务
                   Db::rollback();
               }
           }
       }
        
        
       //过期秒杀信息
       if(!$ru_attr){
           $end_rushs = Db::name('rush_activity')->where('goods_id',$ruinfo['id'])->where('shop_id',$ruinfo['shop_id'])->where('checked',1)->where('hd_bs',1)->where('is_show',1)->where('end_time','elt',time())->field('id,goods_id')->order('price asc')->find();
       }else{
           $end_rushs = Db::name('rush_activity')->where('goods_id',$ruinfo['id'])->where('goods_attr',$ru_attr)->where('shop_id',$ruinfo['shop_id'])->where('checked',1)->where('hd_bs',1)->where('is_show',1)->where('end_time','elt',time())->field('id,goods_id')->find();
       }
        
       if($end_rushs){
           $rumin_price = Db::name('goods')->where('id',$end_rushs['goods_id'])->value('min_price');
           // 启动事务
           Db::startTrans();
           try{
               Db::name('rush_activity')->update(array('hd_bs'=>2,id=>$end_rushs['id']));
               Db::name('goods')->update(array('id'=>$end_rushs['goods_id'],'zs_price'=>$rumin_price,'is_activity'=>0));
               // 提交事务
               Db::commit();
           } catch (\Exception $e) {
               // 回滚事务
               Db::rollback();
           }
       }
        
       //过期团购信息
       if(!$ru_attr){
           $end_groups = Db::name('group_buy')->where('goods_id',$ruinfo['id'])->where('shop_id',$ruinfo['shop_id'])->where('checked',1)->where('hd_bs',1)->where('is_show',1)->where('end_time','elt',time())->field('id,goods_id')->order('price asc')->find();
       }else{
           $end_groups = Db::name('group_buy')->where('goods_id',$ruinfo['id'])->where('goods_attr',$ru_attr)->where('shop_id',$ruinfo['shop_id'])->where('checked',1)->where('hd_bs',1)->where('is_show',1)->where('end_time','elt',time())->field('id,goods_id')->find();
       }
        
       if($end_groups){
           $acmin_price = Db::name('goods')->where('id',$end_groups['goods_id'])->value('min_price');
           // 启动事务
           Db::startTrans();
           try{
               Db::name('group_buy')->update(array('hd_bs'=>2,id=>$end_groups['id']));
               Db::name('goods')->update(array('id'=>$end_groups['goods_id'],'zs_price'=>$acmin_price,'is_activity'=>0));
               // 提交事务
               Db::commit();
           } catch (\Exception $e) {
               // 回滚事务
               Db::rollback();
           }
       }
       
       
       
       
       //秒杀信息
       if(!$ru_attr){
           $rushs = Db::name('rush_activity')->where('goods_id',$ruinfo['id'])->where('shop_id',$ruinfo['shop_id'])->where('checked',1)->where('hd_bs',0)->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,goods_id,goods_attr,price,num,sold,start_time,end_time')->order('price asc')->find();
       }else{
           $rushs = Db::name('rush_activity')->where('goods_id',$ruinfo['id'])->where('goods_attr',$ru_attr)->where('shop_id',$ruinfo['shop_id'])->where('checked',1)->where('hd_bs',0)->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,goods_id,goods_attr,price,num,sold,start_time,end_time')->find();
       }
       
       $groups = array();
       
       if($rushs){
           if($rushs['goods_attr']){
               $number = Db::name('product')->where('goods_id',$rushs['goods_id'])->where('goods_attr',$rushs['goods_attr'])->field('id,goods_number')->find();
               if(!empty($number) && $number['goods_number'] >= $rushs['num']){
                   // 启动事务
                   Db::startTrans();
                   try{
                       Db::name('rush_activity')->update(array('hd_bs'=>1,id=>$rushs['id']));
                       Db::name('goods')->update(array('id'=>$rushs['goods_id'],'zs_price'=>$rushs['price'],'is_activity'=>1));
                       // 提交事务
                       Db::commit();
                   } catch (\Exception $e) {
                       // 回滚事务
                       Db::rollback();
                       $rushs = array();
                   }
               }else{
                   //给商家后台推送商品实际库存小于秒杀报名库存信息
                   Db::name('rush_activity')->update(array('checked'=>2,id=>$rushs['id']));
                   $rushs = array();
                   
               }
           }else{
               $goods_number = Db::name('product')->where('goods_id',$rushs['goods_id'])->sum('goods_number');
               if(!empty($goods_number) && $goods_number >= $rushs['num']){
                   // 启动事务
                   Db::startTrans();
                   try{
                       Db::name('rush_activity')->update(array('hd_bs'=>1,id=>$rushs['id']));
                       Db::name('goods')->update(array('id'=>$rushs['goods_id'],'zs_price'=>$rushs['price'],'is_activity'=>1));
                       // 提交事务
                       Db::commit();
                   } catch (\Exception $e) {
                       // 回滚事务
                       Db::rollback();
                       $rushs = array();
                   }
               }else{
                   //给商家后台推送商品实际库存小于秒杀报名库存信息
                   Db::name('rush_activity')->update(array('checked'=>2,id=>$rushs['id']));
                   $rushs = array();
                   
               }
           }
       }
       
       if(!$rushs){
           //团购信息
           if(!$ru_attr){
               $groups = Db::name('group_buy')->where('goods_id',$ruinfo['id'])->where('shop_id',$ruinfo['shop_id'])->where('checked',1)->where('hd_bs',0)->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,goods_id,goods_attr,price,sold,start_time,end_time')->order('price asc')->find();
           }else{
               $groups = Db::name('group_buy')->where('goods_id',$ruinfo['id'])->where('goods_attr',$ru_attr)->where('shop_id',$ruinfo['shop_id'])->where('checked',1)->where('hd_bs',0)->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,goods_id,goods_attr,price,sold,start_time,end_time')->find();
           }
           
           if($groups){
               // 启动事务
               Db::startTrans();
               try{
                   Db::name('group_buy')->update(array('hd_bs'=>1,id=>$groups['id']));
                   Db::name('goods')->update(array('id'=>$groups['goods_id'],'zs_price'=>$groups['price'],'is_activity'=>2));
                   // 提交事务
                   Db::commit();
               } catch (\Exception $e) {
                   // 回滚事务
                   Db::rollback();
                   $groups = array();
               }
           }
       }

       
       if(!empty($rushs)){
           $rushs['ac_type'] = 1;
           $cuxiao = $rushs;
       }elseif(!empty($groups)){
           $groups['ac_type'] = 2;
           $cuxiao = $groups;
       }
       
       return $activity;
   }
   

}
