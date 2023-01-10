<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\Member as MemberMx;

class Creditshop extends Common{
    public function goods(){
        $shop_id = session('shop_id');


        $where = array();
        $where['a.shop_id'] = $shop_id;
        $where['a.is_recycle'] = 0;
        $where['p.is_show'] = 1;
        $where['a.onsale'] = 1;
        $list = Db::name('goods')
            ->alias('a')
            ->field('p.status,p.id as credit_id,a.id,a.goods_name,a.thumb_url,a.market_price,a.shop_price,a.onsale,b.cate_name,CONCAT(\'需消耗\',credit,\'积分，\',\'金额：\',price) as content')
            ->join('sp_creditshop p','a.id = p.goods_id','right')
            ->join('sp_category b', 'a.cate_id = b.id', 'LEFT')
            ->where($where)->order('a.addtime desc')->paginate(25);
        $page = $list->render();

        if (input('page')) {
            $pnum = input('page');
        } else {
            $pnum = 1;
        }

        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('pnum', $pnum);

        return $this->fetch();
    }

    public function add(){
        if(request()->isPost()){
            $shop_id = session('shop_id');
            $data = input('post.');
            $data['shop_id'] = $shop_id;
            $result = $this->validate($data,'creditshop');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $goods_id = $data['goods_id'];
                $goods = Db::name('goods')->where('id',$goods_id)->where('onsale',1)->where('shop_id',$shop_id)->field('id,shop_price,min_price,max_price')->find();
                if($goods){
                        $creditshops = Db::name('creditshop')->where(function ($query) use ($goods_id,$shop_id){
                            $query->where('goods_id',$goods_id)->where('is_show',1)->where('shop_id',$shop_id);
                        })->whereOr(function ($query) use ($goods_id,$shop_id){
                            $query->where('goods_id',$goods_id)->where('is_show',1)->where('shop_id',$shop_id);
                        })->field('goods_id,goods_attr')->find();
                        if($creditshops){
                            if(!$creditshops['goods_attr']){
                                $value = array('status'=>0,'mess'=>'活动时间内该商品全部规格已参与积分，新增失败');
                                return json($value);
                            }else{
                                if(input('post.goods_attr')){
                                    $garr = trim(input('post.goods_attr'));
                                    $garr = str_replace('，', ',', $garr);
                                    $garr = rtrim($garr,',');

                                    if($garr == $creditshops['goods_attr']){
                                        $value = array('status'=>0,'mess'=>'活动时间内商品所选规格已参与积分活动，新增失败');
                                        return json($value);
                                    }elseif(input('post.goods_attr') == '*'){
                                        $value = array('status'=>0,'mess'=>'活动时间内已有商品相关规格参与积分活动，请删除后重试');
                                        return json($value);
                                    }
                                }else{
                                    $value = array('status'=>0,'mess'=>'参数错误，新增失败');
                                    return json($value);
                                }
                            }
                        }
                        $radiores = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_id')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.goods_id',$goods_id)->where('b.attr_type',1)->select();
                        if($radiores){
                            if(input('post.goods_attr')){
                                if(input('post.goods_attr') != '*'){
                                    $gattr = trim(input('post.goods_attr'));
                                    $gattr = str_replace('，', ',', $gattr);
                                    $gattr = rtrim($gattr,',');
                                    $gattr = explode(',', $gattr);

                                    if($gattr && is_array($gattr)){
                                        $radioattr = array();
                                        foreach ($radiores as $va){
                                            $radioattr[$va['attr_id']][] = $va['id'];
                                        }

                                        $gattres = array();

                                        foreach ($gattr as $ga){
                                            if(!empty($ga)){
                                                $goodsxs = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_id')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id',$ga)->where('a.goods_id',$goods_id)->where('b.attr_type',1)->find();
                                                if($goodsxs){
                                                    $gattres[$goodsxs['attr_id']] = $goodsxs['id'];
                                                }else{
                                                    $value = array('status'=>0,'mess'=>'商品属性参数错误');
                                                    return json($value);
                                                }
                                            }else{
                                                $value = array('status'=>0,'mess'=>'商品属性参数错误');
                                                return json($value);
                                            }
                                        }

                                        foreach ($radioattr as $key => $val){
                                            if(empty($gattres[$key]) || !in_array($gattres[$key],$val)){
                                                $value = array('status'=>0,'mess'=>'请选择商品属性');
                                                return json($value);
                                            }
                                        }

                                        foreach ($gattres as $key2 => $val2){
                                            if(empty($radioattr[$key2]) || !in_array($val2, $radioattr[$key2]) ){
                                                $value = array('status'=>0,'mess'=>'商品属性参数错误');
                                                return json($value);
                                            }
                                        }

                                        $goods_attr = implode(',',$gattr);

                                        $gares = Db::name('goods_attr')->where('id','in',$goods_attr)->where('goods_id',$goods_id)->field('id,attr_price')->select();
                                        if($gares){
                                            $shop_price = $goods['shop_price'];
                                            foreach ($gares as $v){
                                                $shop_price+=$v['attr_price'];
                                            }
                                            $shop_price=sprintf("%.2f", $shop_price);

                                            $prores = Db::name('product')->where('goods_attr',$goods_attr)->where('goods_id',$goods_id)->field('goods_number')->find();
                                            if($prores){
                                                $goods_number = $prores['goods_number'];
                                            }else{
                                                $goods_number = 0;
                                            }
                                        }else{
                                            $value = array('status'=>0,'mess'=>'参数错误');
                                            return json($value);
                                        }

                                    }else{
                                        $value = array('status'=>0,'mess'=>'商品属性参数错误');
                                        return json($value);
                                    }
                                }else{
                                    if($goods['min_price'] != $goods['max_price']){
                                        $value = array('status'=>0,'mess'=>'商品存在价格区间，不能选择全部规格');
                                        return json($value);
                                    }else{
                                        $shop_price = $goods['min_price'];
                                        $goods_attr = '';
                                        $prores = Db::name('product')->where('goods_id',$goods_id)->field('goods_number')->select();
                                        if($prores){
                                            $goods_number = 0;
                                            foreach ($prores as $v){
                                                $goods_number+=$v['goods_number'];
                                            }
                                        }else{
                                            $goods_number = 0;
                                        }
                                    }
                                }
                            }else{
                                $value = array('status'=>0,'mess'=>'请选择商品属性');
                                return json($value);
                            }
                        }else{
                            if(!input('post.goods_attr')){
                                $goods_attr = '';
                                $shop_price = $goods['min_price'];

                                $prores = Db::name('product')->where('goods_id',$goods_id)->field('goods_number')->select();
                                if($prores){
                                    $goods_number = 0;
                                    foreach ($prores as $v){
                                        $goods_number+=$v['goods_number'];
                                    }
                                }else{
                                    $goods_number = 0;
                                }
                            }else{
                                $value = array('status'=>0,'mess'=>'参数错误');
                                return json($value);
                            }
                        }

                        if($data['price'] <= $shop_price){
                            $lastId = Db::name('creditshop')->insert(array(
                                'goods_id'=>$data['goods_id'],
                                'goods_attr'=>$goods_attr,
                                'price'=>$data['price'],
                                'credit'=>$data['credit'],
                                'is_show'=>1,
                                'shop_id'=>$data['shop_id']
                            ));
                            if($lastId){
                                $value = array('status'=>1,'mess'=>'增加成功');
                            }else{
                                $value = array('status'=>0,'mess'=>'增加失败');
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'积分价格不得大于商品价格');
                        }
                }else{
                    $value = array('status'=>0,'mess'=>'商品已下架或不存在');
                }
            }
            return json($value);
        }else{
            return $this->fetch();
        }
    }

    public function edit(){
        if (input('id')) {
            $info = Db::name('creditshop')
                ->alias('c')
                ->join('goods g','c.goods_id=g.id','left')
                ->where(['is_show'=>1,'is_recycle'=>0,'onsale'=>1])
                ->select();
            $this->assign('info',$info);
            return $this->fetch();
            if (empty($info)){
                $this->error('该商品已删除或下架');
            }
        }else{
            $this->error('缺少参数');
        }
    }

    //修改状态
    public function gaibian(){
        $id = input('post.id');
        $shop_id = session('shop_id');
        $name = input('post.name');
        $value = input('post.value');
        $rushs = Db::name('creditshop')->where('id',$id)->where('shop_id',$shop_id)->find();
        if($rushs){
            $data[$name] = $value;
            $data['id'] = $id;
            $count = Db::name('creditshop')->update($data);
            if($count > 0){
                ys_admin_logs('修改积分商品状态','rush_activity',$id);
                $result = 1;
            }else{
                $result = 0;
            }
        }else{
            $result = 0;
        }
        return $result;
    }


    public function log(){

    }

    public function del(){
        if (input('id')) {
            $id = input('post.id');
            $shop_id = session('shop_id');
            $rushs = Db::name('creditshop')->where('id',$id)->where('shop_id',$shop_id)->find();
            if ($rushs){
                $res =Db::name('creditshop')
                    ->where('id',$id)
                    ->where('shop_id',$shop_id)->update(['is_show'=>0]);
                if ($res){
                    return json(['code'=>200,'msg'=>'删除成功']);
                }else{
                    $this->error('删除失败');
                }
            }
        }else{
            $this->error('缺少参数');
        }
    }

}

?>