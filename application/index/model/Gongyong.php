<?php
namespace app\index\model;
use think\Model;
use think\Db;

class Gongyong extends Model
{    
   //判断是否是秒杀、团购商品
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
       }

       
       if(!empty($rushs)){
           $rushs['ac_type'] = 1;
           $activity = $rushs;
       }elseif(!empty($groups)){
           $groups['ac_type'] = 2;
           $activity = $groups;
       }
       
       return $activity;
   }
   

}
