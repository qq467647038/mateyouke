<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use think\Db;

class GroupTravel extends Common{
    public function _initialize(){
        parent::_initialize();

        $gongyong = new GongyongMx();
        $result = $gongyong->apivalidate();

        if($result['status'] == 200){
            if($result['user_id']){
                $this->user = $result;
            }
        }
    }

    public static function customTravel(){
        $where['gt.is_show'] = 1;
        $where['g.onsale'] = 1;
        $info = Db::name('GroupTravel')->alias('gt')
                    ->join('sp_goods g','g.id = gt.goods_id','INNER')
                    ->where($where)
                    ->order('gt.status desc,gt.create_time desc')
                    ->field('gt.id, gt.goods_id')
                    ->find();

        return $info;
        return datamsg(WIN,'获取成功',$info);
    }

    public function lst()
    {
        $page = input('page', 1);
        $page_size = input('page_size', 5);
        $status = input('status', -1);
        $start_limit = --$page*$page_size;

        $where['is_show'] = 1;
        $where['g.onsale'] = 1;
        if($status == 0 || $status == 1)$where['gt.status'] = $status;
        $count = model('GroupTravel')->alias('gt')->where($where)->join('goods g', 'g.id = gt.goods_id', 'left')->count();
        $list = model('GroupTravel')->alias('gt')->field('gt.*, g.thumb_url,g.goods_name,g.sale_num')
                ->join('goods g', 'g.id = gt.goods_id', 'left')
                ->where($where)
                ->limit($start_limit, $page_size)
                ->order('start_time desc')
                ->select();
        foreach ($list as $k=>$v){
            $list[$k]['thumb_url'] = $this->webconfig['weburl'].$v['thumb_url'];
        }

        $return['total_page'] = ceil($count/$page_size);
        $return['list'] = $list;
        return datamsg(WIN,'获取成功',$return);
    }

    public function desc($id = 0)
    {
        if($id === 0)return datamsg(LOSE, '参数异常');

        $groups = Db::name('group_travel')->alias('a')
                    ->field('a.*,b.goods_name,b.shop_price,b.min_price,b.max_price,b.thumb_url,c.shop_name,b.goods_desc')
                    ->join('sp_goods b','a.goods_id = b.id','LEFT')->join('sp_shops c','a.shop_id = c.id','LEFT')
                    ->where('a.id',$id)->find();

        if($groups){
            $goods_pic = Db::name('goods_pic')->where('goods_id', $groups['goods_id'])->order('sort desc')->select();
            foreach ($goods_pic as $kp => $vp){
                $goods_pic[$kp]['img_url'] = $this->webconfig['weburl'].'/'.$vp['img_url'];
            }

            $groups['swiper'] = $goods_pic;
            $groups['thumb_url'] = $this->webconfig['weburl'].$groups['thumb_url'];
            $groups['goods_desc'] = img_add_protocal($groups['goods_desc'], $this->webconfig['weburl']);
            if($groups['goods_attr']){
                if($groups['goods_attr'] != '*'){
                    $groups['goods_attr_str'] = '';
                    $gares = Db::name('goods_attr')->alias('a')->field('a.*,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$groups['goods_attr'])->where('a.goods_id',$groups['goods_id'])->where('b.attr_type',1)->select();
                    if($gares){
                        foreach ($gares as $key => $val){
                            if($key == 0){
                                $groups['goods_attr_str'] = $val['attr_name'].':'.$val['attr_value'];
                            }else{
                                $groups['goods_attr_str'] = $groups['goods_attr_str'].' '.$val['attr_name'].':'.$val['attr_value'];
                            }
                        }

                        $shop_price = $groups['shop_price'];
                        foreach ($gares as $v){
                            $shop_price+=$v['attr_price'];
                        }
                        $shop_price=sprintf("%.2f", $shop_price);
                        $groups['shangpin_price'] = $shop_price;

                        $prores = Db::name('product')->where('goods_attr',$groups['goods_attr'])->where('goods_id',$groups['goods_id'])->field('goods_number')->find();
                        if($prores){
                            $goods_number = $prores['goods_number'];
                        }else{
                            $goods_number = 0;
                        }
                        $groups['goods_number'] = $goods_number;
                    }
                }else{
                    $gares = array();
                    $groups['goods_attr_str'] = '';
                    $groups['shangpin_price'] = $groups['min_price'];

                    $prores = Db::name('product')->where('goods_id',$groups['goods_id'])->field('goods_number')->select();
                    if($prores){
                        $goods_number = 0;
                        foreach ($prores as $v){
                            $goods_number+=$v['goods_number'];
                        }
                    }else{
                        $goods_number = 0;
                    }
                    $groups['goods_number'] = $goods_number;
                }
            }else{
                $gares = array();
                $groups['goods_attr_str'] = '';
                $groups['shangpin_price'] = $groups['min_price'];

                $prores = Db::name('product')->where('goods_id',$groups['goods_id'])->field('goods_number')->select();
                if($prores){
                    $goods_number = 0;
                    foreach ($prores as $v){
                        $goods_number+=$v['goods_number'];
                    }
                }else{
                    $goods_number = 0;
                }
                $groups['goods_number'] = $goods_number;
            }

            return datamsg(WIN,'获取成功',$groups);
        }else{
            return datamsg(LOSE, '找不到相关信息');
        }
    }
}
