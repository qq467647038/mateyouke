<?php


namespace app\index\controller;


use think\Controller;
use think\Db;
use think\Request;
use think\Session;
use app\index\controller\Common;

class Goodslist extends Common
{
    //获取所有列表
    public function index(Request $request){
        $data = $request->param();

        if (isset($data['brandid'])){
            if (!empty($data['brandid'])){
//                $where['g.brand_id'] = $data['brandid'];
//                Session::set('brandid',$data['brandid']);
            }
        }
        if(isset($data['type']) && !empty($data['type'])){
            $type = $data['type'];
            if($type == 1){
                $order = "g.sale_num desc";
            }elseif($type == 2){
                $order = "g.comment_num desc";
            }elseif($type == 3){
                $order = "g.market_price desc";
            }else{
                $type = '';
                $order = "g.sale_num desc";
            }
        }else{
            $type = '';
            $order = "g.sale_num desc";
        }
        $key = Session::get('keyword');
        if (isset($data['keyword']) && !empty($data['keyword'])){
            $where['g.goods_name'] = ['like',"%".$data['keyword']."%"];
            $keyword = $data['keyword'];
        }elseif(!isset($data['keyword']) && !empty($key)){
            $keyword = $key;
            $where['g.goods_name'] = ['like',"%".$keyword."%"];
        }else{

            $keyword ='';
        }

        Session::set('keyword',$keyword);

        if (empty($keyword)){

        }
        $where['g.onsale'] = 1;
        $where['g.is_recycle'] = 0;

//        $where['g.brand_id'] = 14;
//        var_dump($order);die;
        $goodslist = Db::table('sp_goods')->alias('g')
            ->join('shops s','g.shop_id = s.id')
            ->where($where)
            ->order($order)
            ->field('g.id,g.leixing,g.sale_num,g.is_special,g.brand_id,g.goods_name,g.market_price,g.comment_num,g.thumb_url,s.shop_name,g.shop_id')
            ->paginate(12,false,['query'=>request()->param()])
            ->each(function ($item){
                $webconfig = $this->webconfig;
                $gpres = Db::name('goods_pic')->where('goods_id', $item['id'])->field('id,img_url,sort')->order('sort asc')->select();
                foreach ($gpres as $kp => $vp) {
                    $gpres[$kp]['img_url'] = $webconfig['weburl'] . '/' . $vp['img_url'];
                }
                $item['thumb_urls'] = $gpres;
                $item['thumb_url'] = $webconfig['weburl'] . '/' . $item['thumb_url'];
            if ($item['leixing'] == 1){
                $item['leixing'] = '自营';
            }else{
                $item['leixing'] = '商家';
            }
            return $item;
        });

//        var_dump($goodslist);die;

//        foreach ($goodslist->getData() as $k=>$item){
//            $webconfig = $this->webconfig;
//            $goodslist[$k]['thumb_url'] = $webconfig['weburl'] . '/' . $item['thumb_url'];
//        }
        $recommand = Db::table('sp_goods')->where('is_recommend',0)->orderRaw('rand()')->limit(6)->select();
        foreach ($recommand as $k=>$value){
            $webconfig = $this->webconfig;
            $recommand[$k]['thumb_url'] = $webconfig['weburl'] . '/' . $value['thumb_url'];
        }

        $where['b.is_show'] = 1;

//        var_dump($goodslist);die;

            $brand = Db::table('sp_goods')->alias('g')->join('sp_brand b','g.brand_id = b.id')
                ->where($where)->group('g.brand_id')->field('b.id,b.brand_name,b.brand_logo')->select();
            foreach ($brand as $k=>$value){
                $webconfig = $this->webconfig;
                $brand[$k]['brand_logo'] = $webconfig['weburl'] . '/' . $value['brand_logo'];
            }
            $this->assign('brand',$brand);


        $this->assign('goodslist',$goodslist);
        $this->assign('type',$type);
        $this->assign('total',$goodslist->toArray()['total']);
        $this->assign('recommend',$recommand);
//        var_dump();die;
//        var_dump($goodslist,$where,$order,$recommand,$type);die;
        return $this->fetch('list');
    }



}