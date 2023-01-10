<?php
namespace app\shop\controller;
use app\shop\controller\Common;
use think\Db;
use app\shop\model\Attr as AttrMx;

class Attr extends Common{
    public function ceshi3(){
        /*$data[] = array('customer_name' => '小李', 'money' => 12, 'distance' => 2, 'address' => '长安街C坊');
        $data[] = array('customer_name' => '王晓', 'money' => 30, 'distance' => 10, 'address' => '北大街30号');
        $data[] = array('customer_name' => '赵小雅', 'money' => 89, 'distance' => 6, 'address' => '解放路恒基大厦A座');
        $data[] = array('customer_name' => '小月', 'money' => 150, 'distance' => 5, 'address' => '天桥十字东400米');
        $data[] = array('customer_name' => '李亮亮', 'money' => 45, 'distance' => 26, 'address' => '天山西路198弄');
        $data[] = array('customer_name' => '董娟', 'money' => 67, 'distance' => 17, 'address' => '新大南路2号');
        
        $last_names = array_column($data,'distance');
        array_multisort($last_names,SORT_DESC,$data);
        
        p($data);
        
        $zong = Db::name('th_apply')->where('order_id',20001)->where('thfw_id','in','1,2')->where('apply_status',3)->sum('tui_price');
        echo $zong;*/
    }
    
    public function ceshi5(){
        // 启动事务
        /*Db::startTrans();
        try{
            $bb = Db::name('bang')->lock(true)->select();
            if($bb){
                Db::name('bang')->where('id',2)->update(array('smscode'=>'322222'));
            }else{
                return json(array('mess'=>'出错了，蠢货'));
            }

            sleep(20);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        $aa = Db::name('bang')->field('id,phone,qtime,smscode,password')->group('smscode')->order('qtime asc')->limit(5)->select();
        p($aa);*/
    }
    
    public function ceshi6(){
        $aa = Db::name('bang')->where('id',4)->find();
        p($aa);
    }
    
    public function ceshi2(){
        $nowtime = date('Y-m-d H:i:s',time());
        
        $thetime = '2019-01-01 00:00:00';
        $thetime2 = '2019';
        $thetime = strtotime($thetime);
        $thetime2 = strtotime($thetime2);
        $thetime3 = date('Y-m-d H:i:s','1556453940');
        $thetime4 = date('Y-m-d H:i:s',$thetime);
        echo $nowtime;
        echo '<br/>';
        echo $thetime;
        echo '<br/>';
        echo $thetime2;
        echo '<br/>';
        echo $thetime3;
        echo '<br/>';
        echo $thetime4;
    }
    
    public function ceshi(){
        $cateIds = '41,42';
        $where1 = "cate_id in (".$cateIds.")";
        $where2 = "onsale = 1";
        $where3 = '';
        $where4 = '';
        $where5 = '';
        
        //$where = array();
        //$where['cate_id'] = array('in',$cateIds);
        //$where['onsale'] = 1;
        
        $goods_type = 'activity';
        if($goods_type){
            switch($goods_type){
                case 'ziying':
                    $where3 = 'leixing=1';
                    break;
                case 'activity':
                    $where3 = 'is_activity=1';
                    break;
            }
        }
        
        $brandres = array('10,11,13');
        if($brandres && is_array($brandres)){
            $brandres = implode(',', $brandres);
            $where4 = "brand_id in (".$brandres.")";
        }
        
        $goods_attr = array(
            '6'=>array('S','L'),
            '26'=>array('长裙','短裙'),
        );
        
        if($goods_attr && is_array($goods_attr)){
            $gdattres = array();
            foreach ($goods_attr as $key2 =>$val2){
                foreach ($val2 as $vo){
                    $gdattres[] = $key2.':'.$vo;
                }
            }
        
            if($gdattres){
                foreach($gdattres as $kca => $va){
                    if($kca == 0){
                        $where5 = "find_in_set('".$va."',shuxings)";
                    }else{
                        $where5 = $where5." AND find_in_set('".$va."',shuxings)";
                    }
                }
            }
        }
        
        $goodres = Db::name('goods')->where($where1)->where($where2)->where($where3)->where($where4)->where($where5)->field('id,goods_name,thumb_url,zs_price,leixing,shop_id')->select();
        p($goodres);
    }
    
    //属性列表
    public function lst(){
        if(input('typeid')){
            $shop_id = session('shopsh_id');
            $typeId = input('typeid');
            $good_types = Db::name('type')->where('id',$typeId)->where('shop_id',$shop_id)->field('id,type_name')->find();
            if($good_types){
                $list = Db::name('attr')->alias('a')->field('a.*,b.type_name')->join('sp_type b','a.type_id = b.id','LEFT')->where('a.type_id',$typeId)->where('a.shop_id',$shop_id)->order('a.sort asc')->select();
                $typeres = Db::name('type')->where('shop_id',$shop_id)->order('id asc')->select();

                $this->assign('type_name',$good_types['type_name']);
                $this->assign('typeres',$typeres);
                $this->assign('list',$list);
                $this->assign('typeId',$typeId);
                return $this->fetch();
            }else{
                $this->error('参数错误');
            }
        }else{
            $this->error('缺少参数');
        }
    }
    
    public function getAttrLst(){
        if(request()->isPost()){
            if(input('post.typeid')){
                $shop_id = session('shopsh_id');
                $typeId = input('post.typeid');
                $good_types = Db::name('type')->where('id',$typeId)->where('shop_id',$shop_id)->field('id')->find();
                if($good_types){
                    $attrres = Db::name('attr')->where('type_id',$typeId)->where('shop_id',$shop_id)->order('sort asc')->select();
                }else{
                    $attrres = '';
                }
            }else{
                $attrres = '';
            }
            return json($attrres);
        }
    }
    
    public function checkAttrname(){
        if(request()->isPost()){
            $shop_id = session('shopsh_id');
            $arr = Db::name('attr')->where('shop_id',$shop_id)->where('attr_name',input('post.attr_name'))->find();
            if($arr){
                echo 'false';
            }else{
                echo 'true';
            }
        }
    }
        
    public function add(){
        if(request()->isPost()){
            $data = input('post.');
            $data['shop_id'] = session('shopsh_id');
            $result = $this->validate($data,'Attr');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $good_types = Db::name('type')->where('id',$data['type_id'])->where('shop_id',$data['shop_id'])->field('id')->find();
                if($good_types){
                    $shuxings = Db::name('attr')->where('attr_name',$data['attr_name'])->where('type_id',$data['type_id'])->where('shop_id',$data['shop_id'])->find();
                    if(!$shuxings){
                        if($data['attr_type'] == 1){
                            if(input('post.attr_values')){
                                $data['attr_values'] = str_replace('，', ',', input('post.attr_values'));
                            }else{
                                $value = array('status'=>0,'mess'=>'单选属性属性可选值不能为空');
                                return json($value);
                            }
                        }elseif($data['attr_type'] == 0){
                            if(input('post.attr_values')){
                                $data['attr_values'] = str_replace('，', ',', input('post.attr_values'));
                            }
                        }
                        $attr = new AttrMx();
                        $attr->data($data);
                        $lastId = $attr->allowField(true)->save();
                        if($lastId){
                            $value = array('status'=>1,'mess'=>'增加成功');
                        }else{
                            $value = array('status'=>0,'mess'=>'增加失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'该类型下已存在该属性，增加失败');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'类型错误，增加失败');
                }
            }
            return json($value);
        }else{
            if(input('typeid')){
                $shop_id = session('shopsh_id');
                $good_types = Db::name('type')->where('id',input('typeid'))->where('shop_id',$shop_id)->field('id')->find();
                if($good_types){
                    $typeres = Db::name('type')->where('shop_id',$shop_id)->order('id asc')->select();
                    $this->assign('typeres',$typeres);
                    $this->assign('typeId',input('typeid'));
                    return $this->fetch();
                }else{
                    $this->error('参数错误');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }
    
    public function edit(){
        if(request()->isPost()){
            if(input('post.id')){
                $data = input('post.');
                $data['shop_id'] = session('shopsh_id');
                $result = $this->validate($data,'Attr');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $good_types = Db::name('type')->where('id',$data['type_id'])->where('shop_id',$data['shop_id'])->field('id')->find();
                    if($good_types){
                        $attrinfos = Db::name('attr')->where('id',$data['id'])->where('type_id',$data['type_id'])->where('shop_id',$data['shop_id'])->find();
                        if($attrinfos){
                            $shuxings = Db::name('attr')->where('id','neq',$data['id'])->where('attr_name',$data['attr_name'])->where('type_id',$data['type_id'])->where('shop_id',$data['shop_id'])->find();
                            if(!$shuxings){
                                if($data['attr_type'] == 1){
                                    if(input('post.attr_values')){
                                        $data['attr_values'] = str_replace('，', ',', input('post.attr_values'));
                                    }else{
                                        $value = array('status'=>0,'mess'=>'单选属性属性可选值不能为空');
                                        return json($value);
                                    }
                                }elseif($data['attr_type'] == 0){
                                    if(input('post.attr_values')){
                                        $data['attr_values'] = str_replace('，', ',', input('post.attr_values'));
                                    }
                                }
                                
                                $attr = new AttrMx();
                                $count = $attr->allowField(true)->save($data,array('id'=>$data['id']));
                                if($count !== false){
                                    $value = array('status'=>1,'mess'=>'编辑成功');
                                }else{
                                    $value = array('status'=>0,'mess'=>'编辑失败');
                                }
                            }else{
                                $value = array('status'=>0,'mess'=>'该类型下已存在该属性，编辑失败');
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'找不到相关信息，编辑失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'类型信息错误，编辑失败');
                    }
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少参数，编辑失败');
            }
            return json($value);
        }else{
            if(input('id') && input('typeid')){
                $shop_id = session('shopsh_id');
                $good_types = Db::name('type')->where('id',input('typeid'))->where('shop_id',$shop_id)->field('id')->find();
                if($good_types){
                    $attrs = Db::name('attr')->where('id',input('id'))->where('type_id',input('typeid'))->where('shop_id',$shop_id)->find();
                    if($attrs){
                        $typeres = Db::name('type')->where('shop_id',$shop_id)->order('id asc')->select();
                        $this->assign('typeres',$typeres);
                        $this->assign('attrs',$attrs);
                        $this->assign('typeId',input('typeid'));
                        return $this->fetch();
                    }else{
                        $this->error('参数错误');
                    }
                }else{
                    $this->error('类型参数错误');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }
    
    public function delete(){
        if(input('id') && !is_array(input('id'))){
            $shop_id = session('shopsh_id');
            $id = input('id');
            $type_id = Db::name('attr')->where('id',$id)->where('shop_id',$shop_id)->value('type_id');
            if($type_id){
                $good_types = Db::name('type')->where('id',$type_id)->where('shop_id',$shop_id)->field('id')->find();
                if($good_types){
                    $ga = Db::name('goods_attr')->where('attr_id',$id)->field('id')->limit(1)->find();
                    if($ga){
                        $value = array('status'=>0,'mess'=>'有商品正在使用该属性，删除失败');
                    }else{
                        $count = AttrMX::destroy($id);
                        if($count > 0){
                            $value = array('status'=>1,'mess'=>'删除成功');
                        }else{
                            $value = array('status'=>0,'mess'=>'删除失败');
                        }
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'类型信息错误，删除失败');
                }
            }else{
                $value = array('status'=>0,'mess'=>'找不到相关信息，删除失败');
            }
        }else{
            $value = array('status'=>0,'mess'=>'未选中任何数据');
        }
        return json($value);
    }
    
    public function paixu(){
        if(request()->isAjax()){
            $shop_id = session('shopsh_id');
            if(input('post.ids') && input('post.sort')){
                $ids = input('post.ids');
                $sort = input('post.sort');
                $ids = explode(',', $ids);
                $sort = explode(',', $sort);
                foreach ($ids as $k => $v){
                    $attrs = Db::name('attr')->where('id',$v)->where('shop_id',$shop_id)->find();
                    if($attrs){
                        Db::name('attr')->where('id',$v)->update(array('sort'=>$sort[$k]));
                    }
                }
            }
            $value = array('status'=>1,'mess'=>'排序成功');
            return json($value);
        }
    }
      
}
?>