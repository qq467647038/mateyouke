<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class SaleQuyu extends Common{
    public function lst(){
        if(input('user_id')){
            $user_id = input('user_id');
            $sales = Db::name('member')->where('id',$user_id)->where('leixing',1)->field('id,user_name')->find();
            if($sales){
                $list = Db::name('sale_quyu')->alias('a')->field('a.id,a.pro_id,a.city_id,a.area_id,b.pro_name,c.city_name,d.area_name,e.user_name,e.phone')->join('sp_province b','a.pro_id = b.id','LEFT')->join('sp_city c','a.city_id = c.id','LEFT')->join('sp_area d','a.area_id = d.id','LEFT')->join('sp_member e','a.user_id = e.id','LEFT')->where('a.user_id',$user_id)->select();
                $this->assign(array(
                    'list'=>$list,
                    'user_name'=>$sales['user_name'],
                    'user_id'=>$user_id
                ));
                return $this->fetch();
            }else{
                $this->error('销售人员不存在');
            }
        }else{
            $this->error('缺少销售人员id');
        }
    }
    
    public function getcitylist(){
        if(request()->isPost()){
            $pro_id = input('post.pro_id');
            if($pro_id){
                $cityres = Db::name('city')->where('pro_id',$pro_id)->where('checked',1)->where('city_zs',1)->field('id,city_name,zm')->order('sort asc')->select();
                if(empty($cityres)){
                    $cityres = 0;
                }
                return $cityres;
            }
        }
    }
    
    public function getarealist(){
        if(request()->isPost()){
            $city_id = input('post.city_id');
            if($city_id){
                $areares = Db::name('area')->where('city_id',$city_id)->field('id,area_name,zm')->order('sort asc')->select();
                if(empty($areares)){
                    $areares = 0;
                }
                return $areares;
            }
        }
    }
    
    public function add(){
        if(request()->isPost()){
            $data = input('post.');
            if(input('post.user_id')){
                $user_id = input('post.user_id');
                $sales = Db::name('member')->where('id',$user_id)->where('leixing',1)->field('id,wz_id')->find();
                if($sales){
                    $positions = Db::name('position')->where('id',$sales['wz_id'])->field('position_name,quyu_level')->find();
                    if($positions){
                        $quyu_level = $positions['quyu_level'];
                        switch($quyu_level){
                            case 0:
                                $pro_id = 0;
                                $city_id = 0;
                                $area_id = 0;
                                break;
                            case 1:
                                if(input('post.pro_id')){
                                    $pro_id = input('post.pro_id');
                                    $pros = Db::name('province')->where('id',$pro_id)->where('checked',1)->where('pro_zs',1)->field('id')->find();
                                    if($pros){
                                        $city_id = 0;
                                        $area_id = 0;
                                    }else{
                                        $value = array('status'=>0,'mess'=>'请选择区域，添加区域失败');
                                        return json($value);
                                    }
                                }else{
                                    $value = array('status'=>0,'mess'=>'请选择区域，添加区域失败');
                                    return json($value);
                                }
                                break;
                            case 2:
                                if(input('post.pro_id') && input('post.city_id')){
                                    $pro_id = input('post.pro_id');
                                    $city_id = input('post.city_id');
                                    if($city_id == 324){
                                        if(input('post.area_id')){
                                            $area_id = input('post.area_id');
                                        }else{
                                            $value = array('status'=>0,'mess'=>'请选择区县');
                                            return json($value);
                                        }
                                    }
                                    
                                    $sale_quyus = Db::name('sale_quyu')->where('user_id',$user_id)->find();
                                    if($sale_quyus){
                                        if($pro_id != $sale_quyus['pro_id']){
                                            $value = array('status'=>0,'mess'=>'请选择该人员对应省份下的城市');
                                            return json($value);
                                        }
                                        
                                        if($city_id == 324){
                                            if($city_id != $sale_quyus['city_id']){
                                                $value = array('status'=>0,'mess'=>'请选择该人员对应城市下的区县');
                                                return json($value);
                                            }
                                        }
                                    }
                        
                                    $pros = Db::name('province')->where('id',$pro_id)->where('checked',1)->where('pro_zs',1)->field('id')->find();
                                    if($pros){
                                        $citys = Db::name('city')->where('id',$city_id)->where('pro_id',$pros['id'])->where('checked',1)->where('city_zs',1)->field('id')->find();
                                        if($citys){
                                            if($city_id == 324){
                                                $areas = Db::name('area')->where('id',$area_id)->where('city_id',$citys['id'])->where('checked',1)->field('id')->find();
                                                if(!$areas){
                                                    $value = array('status'=>0,'mess'=>'请选择区域，添加区域失败');
                                                    return json($value);
                                                }
                                            }else{
                                                $area_id = 0;
                                            }
                                        }else{
                                            $value = array('status'=>0,'mess'=>'请选择区域，添加区域失败');
                                            return json($value);
                                        }
                                    }else{
                                        $value = array('status'=>0,'mess'=>'请选择区域，添加区域失败');
                                        return json($value);
                                    }
                                }else{
                                    $value = array('status'=>0,'mess'=>'请选择区域，添加区域失败');
                                    return json($value);
                                }
                                break;
                            case 3:
                                if(input('post.pro_id') && input('post.city_id') && input('post.area_id')){
                                    $pro_id = input('post.pro_id');
                                    $city_id = input('post.city_id');
                                    $area_id = input('post.area_id');
                        
                                    $sale_quyus = Db::name('sale_quyu')->where('user_id',$user_id)->find();
                                    if($sale_quyus){
                                        if($pro_id != $sale_quyus['pro_id']){
                                            $value = array('status'=>0,'mess'=>'请选择该人员对应省份下的城市');
                                            return json($value);
                                        }
                                        if($city_id != $sale_quyus['city_id']){
                                            $value = array('status'=>0,'mess'=>'请选择该人员对应城市下的区县');
                                            return json($value);
                                        }
                                    }
                        
                                    $pros = Db::name('province')->where('id',$pro_id)->where('checked',1)->where('pro_zs',1)->field('id')->find();
                                    if($pros){
                                        $citys = Db::name('city')->where('id',$city_id)->where('pro_id',$pros['id'])->where('checked',1)->where('city_zs',1)->field('id')->find();
                                        if($citys){
                                            $areas = Db::name('area')->where('id',$area_id)->where('city_id',$citys['id'])->where('checked',1)->field('id')->find();
                                            if(!$areas){
                                                $value = array('status'=>0,'mess'=>'请选择区域，添加区域失败');
                                                return json($value);
                                            }
                                        }else{
                                            $value = array('status'=>0,'mess'=>'请选择区域，添加区域失败');
                                            return json($value);
                                        }
                                    }else{
                                        $value = array('status'=>0,'mess'=>'请选择区域，添加区域失败');
                                        return json($value);
                                    }
                                }else{
                                    $value = array('status'=>0,'mess'=>'请选择区域，添加区域失败');
                                    return json($value);
                                }
                                break;
                        }
                        
                        $quyus = Db::name('sale_quyu')->where('user_id',$user_id)->where('pro_id',$pro_id)->where('city_id',$city_id)->where('area_id',$area_id)->find();
                        if(!$quyus){
                            $xsids = Db::name('sale_quyu')->alias('a')->field('a.id,a.user_id')->join('sp_member b','a.user_id = b.id','INNER')->where('a.pro_id',$pro_id)->where('a.city_id',$city_id)->where('a.area_id',$area_id)->where('b.wz_id',$sales['wz_id'])->find();
                            if(!$xsids){
                                $lastId = Db::name('sale_quyu')->insertGetId(array('user_id'=>$user_id,'pro_id'=>$pro_id,'city_id'=>$city_id,'area_id'=>$area_id));
                                if($lastId){
                                    ys_admin_logs('添加销售员区域','sale_quyu',$lastId);
                                    $value = array('status'=>1,'mess'=>'添加区域成功');
                                }else{
                                    $value = array('status'=>0,'mess'=>'添加区域失败');
                                }
                            }else{
                                $value = array('status'=>0,'mess'=>'该服务区域下已存在'.$positions['position_name']);
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'该服务区域已选择，请勿重复选择');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'职位不存在');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'销售员不存在');
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少销售人员id参数');
            }
            return json($value);
        }else{
            if(input('user_id')){
                $user_id = input('user_id');
                $sales = Db::name('member')->alias('a')->field('a.id,a.user_name,a.phone,a.wz_id,b.position_name')->join('sp_position b','a.wz_id = b.id','LEFT')->where('a.id',input('user_id'))->where('a.leixing',1)->find();
                if($sales){
                    $quyu_level = Db::name('position')->where('id',$sales['wz_id'])->value('quyu_level');
                    $sale_quyus = Db::name('sale_quyu')->where('user_id',$user_id)->find();
                    if($sale_quyus){
                        switch($quyu_level){
                            case 0:
                                $prores = array();
                                break;
                            case 1:
                                $prores = Db::name('province')->where('checked',1)->where('pro_zs',1)->field('id,pro_name,zm')->order('sort asc')->select();
                                break;
                            case 2:
                                $prores = Db::name('province')->where('id',$sale_quyus['pro_id'])->where('checked',1)->where('pro_zs',1)->field('id,pro_name,zm')->order('sort asc')->select();
                                break;
                            case 3:
                                $prores = Db::name('province')->where('id',$sale_quyus['pro_id'])->where('checked',1)->where('pro_zs',1)->field('id,pro_name,zm')->order('sort asc')->select();
                                $cityres = Db::name('city')->where('id',$sale_quyus['city_id'])->where('checked',1)->where('city_zs',1)->field('id,city_name,zm')->order('sort asc')->select();
                                break;
                        }
                    }else{
                        $prores = Db::name('province')->where('checked',1)->where('pro_zs',1)->field('id,pro_name,zm')->order('sort asc')->select();
                    }
                    $this->assign('quyu_level',$quyu_level);
                    $this->assign('prores',$prores);
                    if(isset($cityres) && $cityres){
                        $this->assign('cityres',$cityres);
                    }
                    $this->assign('sales',$sales);
                    return $this->fetch();
                }else{
                    $this->error('找不到相关销售人员信息');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }

    //删除
    public function delete(){
        if(input('post.id')){
            $id = array_filter(explode(',', input('post.id')));
        }else{
            $id = input('id');
        }
        if(!empty($id)){
            if(is_array($id)){
                $delId = implode(',', $id);
                $count = Db::name('sale_quyu')->delete($delId);
            }else{
                $count = Db::name('sale_quyu')->delete($id);
            }
            if($count > 0){
                $value = array('status'=>1,'mess'=>'删除成功');
            }else{
                $value = array('status'=>0,'mess'=>'删除失败');
            }
        }else{
            $value = array('status'=>0,'mess'=>'请选择删除项');
        }
        return json($value);
    }
    
}