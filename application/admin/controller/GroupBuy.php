<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class GroupBuy extends Common{
    
    public function lst(){
        $shop_id = session('shop_id');
        
        $filter = input('filter');
        if(!$filter || !in_array($filter, array(1,2,3,4,5,10))){
            $filter = 10;
        }
        
        $where = array();
        $where['a.shop_id'] = $shop_id;
        $where['a.is_show'] = 1;
        switch($filter){
            //待审核
            case 1:
                $where['a.checked'] = 0;
                break;
            //即将开始
            case 2:
                $where['a.checked'] = 1;
                $where['a.start_time'] = array('gt',time());
                break;
            //团购中
            case 3:
                $where['a.checked'] = 1;
                $where['a.start_time'] = array('elt',time());
                $where['a.end_time'] = array('gt',time());  
                break;
            //已结束
            case 4:
                $where['a.checked'] = 1;
                $where['a.end_time'] = array('elt',time());
                break;
            //平台关闭
            case 5:
                $where['a.checked'] = 2;
                break;
        }
        
        $list = Db::name('group_buy')->alias('a')->field('a.*,b.goods_name')->join('sp_goods b','a.goods_id = b.id','LEFT')->where($where)->order('a.apply_time desc')->paginate(25);
        $page = $list->render();
        
        $listres = $list->toArray();
        $list = $listres['data'];
        
        if($list){
            foreach ($list as $k => $v){
                if($v['checked'] == 0){
                    //待审核
                    $list[$k]['zhuangtai'] = 1;
                }elseif($v['checked'] == 1 && $v['start_time'] > time()){
                    //即将开始
                    $list[$k]['zhuangtai'] = 2;
                }elseif($v['checked'] == 1 && $v['start_time'] <= time() && $v['end_time'] > time()){
                    //团购中
                    $list[$k]['zhuangtai'] = 3;
                }elseif($v['checked'] == 1 && $v['end_time'] <= time()){
                    //已结束
                    $list[$k]['zhuangtai'] = 4;
                }elseif($v['checked'] == 2){
                    //平台关闭
                    $list[$k]['zhuangtai'] = 5;
                }
                
                if($v['goods_attr']){
                    if($v['goods_attr'] != '*'){
                        $list[$k]['goods_attr_str'] = '';
                        $gares = Db::name('goods_attr')->alias('a')->field('a.*,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$v['goods_attr'])->where('a.goods_id',$v['goods_id'])->where('b.attr_type',1)->select();
                        if($gares){
                            foreach ($gares as $key => $val){
                                if($key == 0){
                                    $list[$k]['goods_attr_str'] = $val['attr_name'].':'.$val['attr_value'];
                                }else{
                                    $list[$k]['goods_attr_str'] = $list[$k]['goods_attr_str'].' '.$val['attr_name'].':'.$val['attr_value'];
                                }
                            }
                        }
                    }else{
                        $gares = array();
                        $list[$k]['goods_attr_str'] = '';
                    }
                }else{
                    $gares = array();
                    $list[$k]['goods_attr_str'] = '';
                }
            }
        }
        
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        
        $this->assign('pnum',$pnum);
        $this->assign('page',$page);
        $this->assign('filter',$filter);
        $this->assign('list',$list);// 赋值数据集
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
    
    //获取商品信息
    public function getgoodsinfo(){
        if(request()->isPost()){
            $shop_id = session('shop_id');
            $radioattr = array();
            if(input('post.id')){
                $goods_id = input('post.id');
                $goods = Db::name('goods')->where('id',$goods_id)->where('onsale',1)->where('shop_id',$shop_id)->field('id,max_price,min_price')->find();
                if($goods){
                    $radiores = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_id,a.attr_value,b.attr_name,b.attr_type')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.goods_id',$goods_id)->where('b.attr_type',1)->select();
                    if($radiores){
                        foreach ($radiores as $v){
                            $radioattr[$v['attr_id']][] = $v;
                        }
                    }
                    
                    $prores = Db::name('product')->where('goods_id',$goods_id)->field('goods_number')->select();
                    if($prores){
                        $pronum = 0;
                        foreach ($prores as $v){
                            $pronum+=$v['goods_number'];
                        }
                    }else{
                        $pronum = 0;
                    }
                    
                    $value = array('status'=>1,'radioattr'=>$radioattr,'pronum'=>$pronum,'max_price'=>$goods['max_price'],'min_price'=>$goods['min_price']);
                }else{
                    $value = array('status'=>0);
                }
            }else{
                $value = array('status'=>0);
            }
            return json($value);
        }
    }
    
    //根据商品单选属性计算价格调用对应库存
    public function get_goods_price(){
        if(request()->isPost()){
            $shop_id = session('shop_id');
            $data = input('post.');
            if(!empty($data['goods_id']) && !empty($data['goods_attr'])){
                $goods_id = $data['goods_id'];
                
                $goods = Db::name('goods')->where('id',$goods_id)->where('onsale',1)->where('shop_id',$shop_id)->field('id,shop_price,min_price,max_price')->find();
                if($goods){
                    $radiores = Db::name('goods_attr')->alias('a')->field('a.id,a.attr_id')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.goods_id',$goods_id)->where('b.attr_type',1)->select();
                    if($radiores){
                        if($data['goods_attr'] != '*'){
                            $data['goods_attr'] = trim($data['goods_attr']);
                            $data['goods_attr'] = str_replace('，', ',', $data['goods_attr']);
                            $data['goods_attr'] = rtrim($data['goods_attr'],',');
                            $goods_attr = $data['goods_attr'];
                            
                            $gattr = explode(',', $goods_attr);
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
                            }else{
                                $value = array('status'=>0,'mess'=>'商品属性参数错误');
                                return json($value);
                            }
                            
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
                                $value = array('status'=>1,'mess'=>'获取信息成功','shop_price'=>$shop_price,'goods_number'=>$goods_number);
                            }else{
                                $value = array('status'=>0,'mess'=>'参数错误');
                            }
                        }else{
                            $goods_attr = $data['goods_attr'];

                            if($goods['min_price'] != $goods['max_price']){
                                $value = array('status'=>0,'mess'=>'商品存在价格区间，不能选择全部规格');
                                return json($value);
                            }else{
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
                                $value = array('status'=>1,'mess'=>'获取信息成功','shop_price'=>$shop_price,'goods_number'=>$goods_number);
                            }
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'参数错误');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'商品已下架或不存在');
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少参数');
            }
        }
        return json($value);
    }
    
    public function add(){
        if(request()->isPost()){
            $shop_id = session('shop_id');
            $data = input('post.');
            $data['shop_id'] = $shop_id;
            $data['apply_time'] = time();
            
            $result = $this->validate($data,'GroupBuy');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $goods_id = $data['goods_id'];

                $goods = Db::name('goods')->where('id',$goods_id)->where('onsale',1)->where('shop_id',$shop_id)->field('id,shop_price,min_price,max_price')->find();
                if($goods){
                    $start_time = strtotime($data['start_time']);
                    $end_time = strtotime($data['end_time']);
                    /*if($start_time > time()){

                    }else{
                        $value = array('status'=>0,'mess'=>'开始时间需大于当前时间');
                    }*/
                    if($start_time < $end_time){
                        $rush_activitys = Db::name('rush_activity')->where(function ($query) use ($goods_id,$start_time,$end_time,$shop_id){
                            $query->where('goods_id',$goods_id)->where('checked','in','0,1')->where('is_show',1)->where('start_time','elt',$start_time)->where('end_time','egt',$start_time)->where('shop_id',$shop_id);
                        })->whereOr(function ($query) use ($goods_id,$start_time,$end_time,$shop_id){
                            $query->where('goods_id',$goods_id)->where('checked','in','0,1')->where('is_show',1)->where('start_time','egt',$start_time)->where('start_time','elt',$end_time)->where('shop_id',$shop_id);
                        })->field('goods_id')->find();
                    
                        if(!$rush_activitys){
                            $assembles = Db::name('assemble')->where(function ($query) use ($goods_id,$start_time,$end_time,$shop_id){
                                $query->where('goods_id',$goods_id)->where('checked','in','0,1')->where('is_show',1)->where('start_time','elt',$start_time)->where('end_time','egt',$start_time)->where('shop_id',$shop_id);
                            })->whereOr(function ($query) use ($goods_id,$start_time,$end_time,$shop_id){
                                $query->where('goods_id',$goods_id)->where('checked','in','0,1')->where('is_show',1)->where('start_time','egt',$start_time)->where('start_time','elt',$end_time)->where('shop_id',$shop_id);
                            })->field('goods_id')->find();
                            
                            if(!$assembles){
                                $group_buys = Db::name('group_buy')->where(function ($query) use ($goods_id,$start_time,$end_time,$shop_id){
                                    $query->where('goods_id',$goods_id)->where('checked','in','0,1')->where('is_show',1)->where('start_time','elt',$start_time)->where('end_time','egt',$start_time)->where('shop_id',$shop_id);
                                })->whereOr(function ($query) use ($goods_id,$start_time,$end_time,$shop_id){
                                    $query->where('goods_id',$goods_id)->where('checked','in','0,1')->where('is_show',1)->where('start_time','egt',$start_time)->where('start_time','elt',$end_time)->where('shop_id',$shop_id);
                                })->field('goods_id,goods_attr')->find();
                                
                                if($group_buys){
                                    if(!$group_buys['goods_attr']){
                                        $value = array('status'=>0,'mess'=>'活动时间内该商品全部规格已参与团购，新增失败');
                                        return json($value);
                                    }else{
                                        if(input('post.goods_attr')){
                                            $garr = trim(input('post.goods_attr'));
                                            $garr = str_replace('，', ',', $garr);
                                            $garr = rtrim($garr,',');
                                
                                            if($garr == $group_buys['goods_attr']){
                                                $value = array('status'=>0,'mess'=>'活动时间内商品所选规格已参与团购，新增失败');
                                                return json($value);
                                            }elseif(input('post.goods_attr') == '*'){
                                                $value = array('status'=>0,'mess'=>'活动时间内已有商品相关规格参与团购，请删除后重试');
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
                                    $lastId = Db::name('group_buy')->insert(array(
                                        'group_name'=>$data['group_name'],
                                        'goods_id'=>$data['goods_id'],
                                        'goods_attr'=>$goods_attr,
                                        'price'=>$data['price'],
                                        'start_time'=>$start_time,
                                        'end_time'=>$end_time,
                                        'remark'=>$data['remark'],
                                        'checked'=>1,
                                        'apply_time'=>$data['apply_time'],
                                        'checked_time'=>time(),
                                        'is_show'=>1,
                                        'shop_id'=>$data['shop_id']
                                    ));
                                    if($lastId){
                                        $value = array('status'=>1,'mess'=>'增加成功');
                                    }else{
                                        $value = array('status'=>0,'mess'=>'增加失败');
                                    }
                                }else{
                                    $value = array('status'=>0,'mess'=>'团购价格不得大于商品价格');
                                }
                            }else{
                                $value = array('status'=>0,'mess'=>'活动时间区间内该商品参与了拼团活动，新增失败');
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'活动时间区间内该商品参与了秒杀活动，新增失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'开始时间需小于结束时间');
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
    
    public function info(){
        if(input('id')){
            $shop_id = session('shop_id');
    
            $groups = Db::name('group_buy')->alias('a')->field('a.*,b.goods_name,b.shop_price,b.min_price,b.max_price,b.thumb_url,c.shop_name')->join('sp_goods b','a.goods_id = b.id','LEFT')->join('sp_shops c','a.shop_id = c.id','LEFT')->where('a.id',input('id'))->where('a.shop_id',$shop_id)->find();
            if($groups){
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
    
    
                if($groups['checked'] == 0){
                    //待审核
                    $groups['zhuangtai'] = 1;
                }elseif($groups['checked'] == 1 && $groups['start_time'] > time()){
                    //即将开始
                    $groups['zhuangtai'] = 2;
                }elseif($groups['checked'] == 1 && $groups['start_time'] <= time() && $groups['end_time'] > time()){
                    //团购中
                    $groups['zhuangtai'] = 3;
                }elseif($groups['checked'] == 1 && $groups['end_time'] <= time()){
                    //已结束
                    $groups['zhuangtai'] = 4;
                }elseif($groups['checked'] == 2){
                    //平台关闭
                    $groups['zhuangtai'] = 5;
                }
                $this->assign('groups',$groups);
                return $this->fetch();
            }else{
                $this->error('找不到相关信息');
            }
        }else{
            $this->error('缺少参数');
        }
    }
    
    
    public function delete(){
        $shop_id = session('shop_id');
        $id = input('id');
        if(!empty($id)){
            $groups = Db::name('group_buy')->where('id',$id)->where('shop_id',$shop_id)->where('is_show',1)->find();
            if($groups){
                if($groups['checked'] == 1 && $groups['start_time'] <= time() && $groups['end_time'] > time()){
                    //秒杀活动进行中
                    $value = array('status'=>0,'mess'=>'秒杀活动进行中，不允许删除');
                    return json($value);
                }
                
                // 启动事务
                Db::startTrans();
                try{
                    Db::name('group_buy')->update(array('is_show'=>0,'id'=>$id));
                    if($groups['checked'] == 1 && $groups['end_time'] <= time()){
                        $goods = Db::name('goods')->where('id',$groups['goods_id'])->field('min_price,zs_price,is_activity')->find();
                        if($goods['min_price'] != $goods['zs_price']){
                            Db::name('goods')->update(array('id'=>$groups['goods_id'],'zs_price'=>$goods['min_price']));
                        }
                        if($goods['is_activity'] == 1){
                            Db::name('goods')->update(array('id'=>$groups['goods_id'],'is_activity'=>0));
                        }
                    }
                    // 提交事务
                    Db::commit();
                    $value = array('status'=>1,'mess'=>'删除成功');
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $value = array('status'=>0,'mess'=>'删除失败');
                }
            }else{
                $value = array('status'=>0,'mess'=>'找不到相关信息');
            }
        }else{
            $value = array('status'=>0,'mess'=>'未选中任何数据');
        }
        return json($value);
    }
    
    
    public function search(){
        $shop_id = session('shop_id');
        
        if(input('post.keyword') != ''){
            cookie('group_keyword',input('post.keyword'),7200);
        }else{
            cookie('group_keyword',null);
        }
        
        if(input('post.checked') != ''){
            cookie('group_checked',input('post.checked'),7200);
        }
        
        if(input('post.recommend') != ''){
            cookie('group_recommend',input('post.recommend'),7200);
        }
        
        if(input('post.starttime') != ''){
            $groupstarttime = strtotime(input('post.starttime'));
            cookie('groupstarttime',$groupstarttime,7200);
        }
        
        if(input('post.endtime') != ''){
            $groupendtime = strtotime(input('post.endtime'));
            cookie('groupendtime',$groupendtime,7200);
        }
        
        $where = array();
        
        $where['a.shop_id'] = $shop_id;
        $where['a.is_show'] = 1;
        
        if(cookie('group_keyword')){
            $where['a.activity_name'] = cookie('group_keyword');
        }
        
        if(cookie('group_checked') != ''){
            $group_checked = (int)cookie('group_checked');
            if(!empty($group_checked)){
                switch ($group_checked){
                    //待审核
                    case 1:
                        $where['a.checked'] = 0;
                        break;
                        //即将开始
                    case 2:
                        $where['a.checked'] = 1;
                        $where['a.start_time'] = array('gt',time());
                        break;
                        //团购中
                    case 3:
                        $where['a.checked'] = 1;
                        $where['a.start_time'] = array('elt',time());
                        $where['a.end_time'] = array('gt',time());
                        break;
                        //已结束
                    case 4:
                        $where['a.checked'] = 1;
                        $where['a.end_time'] = array('elt',time());
                        break;
                        //平台关闭
                    case 5:
                        $where['a.checked'] = 2;
                        break;
                }
            }
        }
        
        if(cookie('group_recommend') != ''){
            $group_recommend = (int)cookie('group_recommend');
            if(!empty($group_recommend)){
                switch ($group_recommend){
                    //推荐
                    case 1:
                        $where['a.recommend'] = 1;
                        break;
                        //未推荐
                    case 2:
                        $where['a.recommend'] = 0;
                        break;
                }
            }
        }
        
        if(cookie('groupendtime') && cookie('groupstarttime')){
            $where['a.apply_time'] = array(array('egt',cookie('groupstarttime')), array('elt',cookie('groupendtime')));
        }
        
        if(cookie('groupstarttime') && !cookie('groupendtime')){
            $where['a.apply_time'] = array('egt',cookie('groupstarttime'));
        }
        
        if(cookie('groupendtime') && !cookie('groupstarttime')){
            $where['a.apply_time'] = array('elt',cookie('groupendtime'));
        }


        $list = Db::name('group_buy')->alias('a')->field('a.*,b.goods_name')->join('sp_goods b','a.goods_id = b.id','LEFT')->where($where)->order('a.apply_time desc')->paginate(25);
        $page = $list->render();
        
        $listres = $list->toArray();
        $list = $listres['data'];
        
        if($list){
            foreach ($list as $k => $v){
                if($v['checked'] == 0){
                    //待审核
                    $list[$k]['zhuangtai'] = 1;
                }elseif($v['checked'] == 1 && $v['start_time'] > time()){
                    //即将开始
                    $list[$k]['zhuangtai'] = 2;
                }elseif($v['checked'] == 1 && $v['start_time'] <= time() && $v['end_time'] > time()){
                    //团购中
                    $list[$k]['zhuangtai'] = 3;
                }elseif($v['checked'] == 1 && $v['end_time'] <= time()){
                    //已结束
                    $list[$k]['zhuangtai'] = 4;
                }elseif($v['checked'] == 2){
                    //平台关闭
                    $list[$k]['zhuangtai'] = 5;
                }
        
                if($v['goods_attr']){
                    $list[$k]['goods_attr_str'] = '';
                    $gares = Db::name('goods_attr')->alias('a')->field('a.*,b.attr_name')->join('sp_attr b','a.attr_id = b.id','INNER')->where('a.id','in',$v['goods_attr'])->where('a.goods_id',$v['goods_id'])->where('b.attr_type',1)->select();
                    if($gares){
                        foreach ($gares as $key => $val){
                            if($key == 0){
                                $list[$k]['goods_attr_str'] = $val['attr_name'].':'.$val['attr_value'];
                            }else{
                                $list[$k]['goods_attr_str'] = $list[$k]['goods_attr_str'].' '.$val['attr_name'].':'.$val['attr_value'];
                            }
                        }
                    }
                }else{
                    $gares = array();
                    $list[$k]['goods_attr_str'] = '';
                }
            }
        }
        
        
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        
        $search = 1;
        
        if(cookie('groupstarttime')){
            $this->assign('starttime',cookie('groupstarttime'));
        }
        
        if(cookie('groupendtime')){
            $this->assign('endtime',cookie('groupendtime'));
        }
        
        if(cookie('group_recommend')){
            $this->assign('recommend',cookie('group_recommend'));
        }
        
        if(cookie('group_checked')){
            $this->assign('checked',cookie('group_checked'));
        }
        
        if(cookie('group_keyword')){
            $this->assign('keyword',cookie('group_keyword'));
        }
        
        $this->assign('search',$search);
        $this->assign('pnum', $pnum);
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $page);// 赋值分页输出
        $this->assign('filter',10);
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }        
    }
    
    
}