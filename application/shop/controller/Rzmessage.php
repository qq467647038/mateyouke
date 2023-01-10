<?php
namespace app\shop\controller;
use app\shop\controller\Common;
use think\Db;

class Rzmessage extends Common
{
    public function info(){
        $shop_id = session('shopsh_id');
        $applys = Db::name('apply_info')->alias('a')->field('a.*,b.industry_name,c.pro_name,d.city_name,u.area_name')->join('sp_industry b','a.indus_id = b.id','LEFT')->join('sp_province c','a.pro_id = c.id','LEFT')->join('sp_city d','a.city_id = d.id','LEFT')->join('sp_area u','a.area_id = u.id','LEFT')->where('a.shop_id',$shop_id)->where('a.checked',1)->where('a.qht',1)->where('a.complete',1)->find();
        if($applys){
            // $rz_orders = Db::name('rz_order')->alias('a')->field('a.*,b.industry_name,b.ser_price,b.remind')->join('sp_industry b','a.indus_id = b.id','LEFT')->where('a.apply_id',$applys['id'])->where('a.shop_id',$shop_id)->where('a.state',1)->find();
            // if($rz_orders){
                $manageres = Db::name('manage_apply')->where('apply_id',$applys['id'])->field('cate_id')->select();
                $managearr = array();
                foreach ($manageres as $v){
                    $managearr[] = $v['cate_id'];
                }
                $managearr = implode(',', $managearr);
                $cateres = Db::name('category')->where('id','in',$managearr)->where('pid',0)->field('id,cate_name')->order('sort asc')->select();
                $this->assign('applys',$applys);
                $this->assign('rz_orders',$rz_orders);
                $this->assign('cateres',$cateres);
                return $this->fetch();
            // }else{
            //     $this->error('找不到相关信息','index/index');
            // }
        }else{
            $this->error('找不到相关信息','index/index');
        }
    }


}