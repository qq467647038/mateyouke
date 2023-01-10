<?php
namespace app\index\controller;
use app\index\controller\Common;
use think\Db;

class ApplyInfo extends Common{
    
    public function index(){
        if(session('user_id')){
            $user_id = session('user_id');
            $zsinduspics = Db::name('apply_zspic')->where('user_id',$user_id)->field('id,img_url')->select();
            if($zsinduspics){
                foreach ($zsinduspics as $v){
                    Db::name('apply_zspic')->delete($v['id']);
                    if($v['img_url'] && file_exists('./'.$v['img_url'])){
                        @unlink('./'.$v['img_url']);
                    }
                }
            }
            
            $applyinfos = Db::name('apply_info')->where('user_id',$user_id)->field('id,checked,qht,state,complete')->order('apply_time desc')->find();
            if(!$applyinfos){
                return $this->fetch();
            }else{
                if($applyinfos['checked'] == 2 && $applyinfos['qht'] == 0 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                    $this->redirect('apply_info/jujue');
                }elseif($applyinfos['checked'] == 0 && $applyinfos['qht'] == 0 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                    $this->redirect('apply_info/waitchecked');
                }elseif($applyinfos['checked'] == 1 && $applyinfos['qht'] == 0 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                    $this->redirect('apply_info/waitqht');
                }elseif($applyinfos['checked'] == 1 && $applyinfos['qht'] == 1 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                    $this->redirect('apply_info/waitpaybzj');
                }elseif($applyinfos['checked'] == 1 && $applyinfos['qht'] == 1 && $applyinfos['state'] == 1 && $applyinfos['complete'] == 0){
                    $this->redirect('apply_info/waitcomplete');
                }elseif($applyinfos['checked'] == 1 && $applyinfos['qht'] == 1 && $applyinfos['state'] == 1 && $applyinfos['complete'] == 1){
                    $this->redirect('apply_info/complete');
                }else{
                    $this->error('信息错误');
                }
            }
        }else{
            $goods_url = '/index/apply_info/index';
            cookie('goods_url',$goods_url,3600);
            $this->redirect('login/index');
        }
    }
    
    public function comapply(){
        if(request()->isPost()){
            if(session('user_id')){
                $user_id = session('user_id');
                $data = input('post.');
                $applyinfos = Db::name('apply_info')->where('user_id',$user_id)->field('id,checked,qht,state,complete')->order('apply_time desc')->find();
    
                $zhuangtai = 0;
    
                if($applyinfos && $applyinfos['checked'] == 2 && $applyinfos['qht'] == 0 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                    $zhuangtai = 1;
                }
    
                if(!$applyinfos || $zhuangtai == 1){
                    $result = $this->validate($data,'ComapplyInfo');
                    if(true !== $result){
                        $value = array('status'=>0,'mess'=>$result);
                    }else{
                        if(!empty($data['indus_id'])){
                            $industrys = Db::name('industry')->where('id',$data['indus_id'])->where('is_show',1)->field('id')->find();
                            if(!$industrys){
                                $value = array('status'=>0,'mess'=>'请选择行业');
                                return json($value);
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'请选择行业');
                            return json($value);
                        }
                        
                        if(!empty($data['goods_id']) && is_array($data['goods_id'])){
                            $goodids = array_unique($data['goods_id']);
                            $info_id = implode(',', $goodids);
                            
                            foreach ($goodids as $v){
                                $cates = Db::name('category')->where('id',$v)->where('pid',0)->where('is_show',1)->field('id')->find();
                                if(!$cates){
                                    $value = array('status'=>0,'mess'=>'经营类目信息有误，申请失败');
                                    return json($value);
                                }
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'请选择经营类目');
                            return json($value);
                        }
    
                        if(!empty($data['sfzz_id'])){
                            $sfzz_pics = Db::name('apply_zspic')->where('id',$data['sfzz_id'])->where('user_id',$user_id)->find();
                            if($sfzz_pics && $sfzz_pics['img_url']){
                                $data['sfzz_pic'] = $sfzz_pics['img_url'];
                            }else{
                                $value = array('status'=>0,'mess'=>'请上传身份证正面照片');
                                return json($value);
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'请上传身份证正面照片');
                            return json($value);
                        }
    
                        if(!empty($data['sfzb_id'])){
                            $sfzb_pics = Db::name('apply_zspic')->where('id',$data['sfzb_id'])->where('user_id',$user_id)->find();
                            if($sfzb_pics && $sfzb_pics['img_url']){
                                $data['sfzb_pic'] = $sfzb_pics['img_url'];
                            }else{
                                $value = array('status'=>0,'mess'=>'请上传身份证背面照片');
                                return json($value);
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'请上传身份证背面照片');
                            return json($value);
                        }
    
                        if(!empty($data['frsfz_id'])){
                            $frsfz_pics = Db::name('apply_zspic')->where('id',$data['frsfz_id'])->where('user_id',$user_id)->find();
                            if($frsfz_pics && $frsfz_pics['img_url']){
                                $data['frsfz_pic'] = $frsfz_pics['img_url'];
                            }else{
                                $value = array('status'=>0,'mess'=>'请上传手持身份证照片');
                                return json($value);
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'请上传手持身份证照片');
                            return json($value);
                        }
    
                        if(!empty($data['zhizhao_id'])){
                            $zhizhao_pics = Db::name('apply_zspic')->where('id',$data['zhizhao_id'])->where('user_id',$user_id)->find();
                            if($zhizhao_pics && $zhizhao_pics['img_url']){
                                $data['zhizhao'] = $zhizhao_pics['img_url'];
                            }else{
                                $value = array('status'=>0,'mess'=>'请上传营业执照照片');
                                return json($value);
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'请上传营业执照照片');
                            return json($value);
                        }
    
                        $pro_id = $data['pro_id'];
                        $city_id = $data['city_id'];
                        $area_id = $data['area_id'];
                        $pros = Db::name('province')->where('id',$pro_id)->where('checked',1)->where('pro_zs',1)->field('id,pro_name')->find();
                        if($pros){
                            $citys = Db::name('city')->where('id',$city_id)->where('pro_id',$pros['id'])->where('checked',1)->where('city_zs',1)->field('id,city_name')->find();
                            if($citys){
                                $areas = Db::name('area')->where('id',$area_id)->where('city_id',$citys['id'])->where('checked',1)->field('id,area_name')->find();
                                if($areas){
                                    $data['com_shengshiqu'] = $data['com_province'].$data['com_city'].$data['com_area'];
                                    $data['latlon'] = str_replace('，', ',', $data['latlon']);
                                    
                                    if(strpos($data['latlon'],',') !== false){
                                        // 启动事务
                                        Db::startTrans();
                                        try{
                                            $apply_id = Db::name('apply_info')->insertGetId(array(
                                                'com_name'=>$data['com_name'],
                                                'nature'=>$data['nature'],
                                                'com_shengshiqu'=>$data['com_shengshiqu'],
                                                'com_address'=>$data['com_address'],
                                                'fixed_phone'=>$data['fixed_phone'],
                                                'com_email'=>$data['com_email'],
                                                'zczj'=>$data['zczj'],
                                                'tyxydm'=>$data['tyxydm'],
                                                'faren_name'=>$data['faren_name'],
                                                'zzstart_time'=>$data['zzstart_time'],
                                                'zzend_time'=>$data['zzend_time'],
                                                'jyfw'=>$data['jyfw'],
                                                'shop_name'=>$data['shop_name'],
                                                'shop_desc'=>$data['shop_desc'],
                                                'indus_id'=>$data['indus_id'],
                                                'contacts'=>$data['contacts'],
                                                'telephone'=>$data['telephone'],
                                                'email'=>$data['email'],
                                                'sfz_num'=>$data['sfz_num'],
                                                'sfzz_pic'=>$data['sfzz_pic'],
                                                'sfzb_pic'=>$data['sfzb_pic'],
                                                'frsfz_pic'=>$data['frsfz_pic'],
                                                'zhizhao'=>$data['zhizhao'],
                                                'pro_id'=>$data['pro_id'],
                                                'city_id'=>$data['city_id'],
                                                'area_id'=>$data['area_id'],
                                                'shengshiqu'=>$pros['pro_name'].$citys['city_name'].$areas['area_name'],
                                                'address'=>$data['address'],
                                                'latlon'=>$data['latlon'],
                                                'apply_type'=>2,
                                                'apply_time'=>time(),
                                                'user_id'=>$user_id
                                            ));
                                            
                                            if($apply_id){
                                                foreach ($goodids as $val){
                                                    Db::name('manage_apply')->insert(array('cate_id'=>$val,'apply_id'=>$apply_id,'apply_time'=>time()));
                                                }
                                                
                                                if($sfzz_pics && $sfzz_pics['img_url']){
                                                    Db::name('apply_zspic')->where('id',$sfzz_pics['id'])->delete();
                                                }
                                                
                                                if($sfzb_pics && $sfzb_pics['img_url']){
                                                    Db::name('apply_zspic')->where('id',$sfzb_pics['id'])->delete();
                                                }
                                                
                                                if($frsfz_pics && $frsfz_pics['img_url']){
                                                    Db::name('apply_zspic')->where('id',$frsfz_pics['id'])->delete();
                                                }
                                                
                                                if($zhizhao_pics && $zhizhao_pics['img_url']){
                                                    Db::name('apply_zspic')->where('id',$zhizhao_pics['id'])->delete();
                                                }
                                            }
                                            // 提交事务
                                            Db::commit();
                                            $value = array('status'=>1,'mess'=>'提交资料成功，请待审核');
                                        } catch (\Exception $e) {
                                            // 回滚事务
                                            Db::rollback();
                                            $value = array('status'=>0,'mess'=>'提交资料失败');
                                        }
                                    }else{
                                        $value = array('status'=>0,'mess'=>'地址坐标参数错误');
                                    }
                                }else{
                                    $value = array('status'=>0,'mess'=>'请选择区域，操作失败');
                                }
                            }else{
                                $value = array('status'=>0,'mess'=>'请选择区域，操作失败');
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'请选择区域，操作失败');
                        }
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'信息有误，提交失败');
                }
            }else{
                $value = array('status'=>2,'mess'=>'身份已过期，请重新登录');
            }
            return json($value);
        }else{
            if(session('user_id')){
                $user_id = session('user_id');
                $zsinduspics = Db::name('apply_zspic')->where('user_id',$user_id)->field('id,img_url')->select();
                if($zsinduspics){
                    foreach ($zsinduspics as $v){
                        Db::name('apply_zspic')->delete($v['id']);
                        if($v['img_url'] && file_exists('./'.$v['img_url'])){
                            @unlink('./'.$v['img_url']);
                        }
                    }
                }
    
                $applyinfos = Db::name('apply_info')->where('user_id',$user_id)->field('id,checked,qht,state,complete')->order('apply_time desc')->find();
                if(!$applyinfos){
                    $prores = Db::name('province')->where('checked',1)->where('pro_zs',1)->field('id,pro_name,zm')->order('sort asc')->select();
                    $industryres = Db::name('industry')->where('is_show',1)->field('id,industry_name')->order('sort asc')->select();
                    $this->assign('industryres',$industryres);
                    $this->assign('prores',$prores);
                    return $this->fetch();
                }else{
                    if($applyinfos['checked'] == 2 && $applyinfos['qht'] == 0 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                        $prores = Db::name('province')->where('checked',1)->where('pro_zs',1)->field('id,pro_name,zm')->order('sort asc')->select();
                        $industryres = Db::name('industry')->where('is_show',1)->field('id,industry_name')->order('sort asc')->select();
                        $this->assign('industryres',$industryres);
                        $this->assign('prores',$prores);
                        return $this->fetch();
                    }elseif($applyinfos['checked'] == 0 && $applyinfos['qht'] == 0 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                        $this->redirect('apply_info/waitchecked');
                    }elseif($applyinfos['checked'] == 1 && $applyinfos['qht'] == 0 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                        $this->redirect('apply_info/waitqht');
                    }elseif($applyinfos['checked'] == 1 && $applyinfos['qht'] == 1 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                        $this->redirect('apply_info/waitpaybzj');
                    }elseif($applyinfos['checked'] == 1 && $applyinfos['qht'] == 1 && $applyinfos['state'] == 1 && $applyinfos['complete'] == 0){
                        $this->redirect('apply_info/waitcomplete');
                    }elseif($applyinfos['checked'] == 1 && $applyinfos['qht'] == 1 && $applyinfos['state'] == 1 && $applyinfos['complete'] == 1){
                        $this->redirect('apply_info/complete');
                    }else{
                        $this->error('信息错误');
                    }
                }
            }else{
                $goods_url = '/index/apply_info/index';
                cookie('goods_url',$goods_url,3600);
                $this->redirect('login/index');
            }
        }
    }
    
    public function personapply(){
        if(request()->isPost()){
            if(session('user_id')){
                $user_id = session('user_id');
                $data = input('post.');
                $applyinfos = Db::name('apply_info')->where('user_id',$user_id)->field('id,checked,qht,state,complete')->order('apply_time desc')->find();
            
                $zhuangtai = 0;
            
                if($applyinfos && $applyinfos['checked'] == 2 && $applyinfos['qht'] == 0 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                    $zhuangtai = 1;
                }
            
                if(!$applyinfos || $zhuangtai == 1){
                    $result = $this->validate($data,'PersonapplyInfo');
                    if(true !== $result){
                        $value = array('status'=>0,'mess'=>$result);
                    }else{
                        if(!empty($data['indus_id'])){
                            $industrys = Db::name('industry')->where('id',$data['indus_id'])->where('is_show',1)->field('id')->find();
                            if(!$industrys){
                                $value = array('status'=>0,'mess'=>'请选择行业');
                                return json($value);
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'请选择行业');
                            return json($value);
                        }
                        
                        
                        if(!empty($data['goods_id']) && is_array($data['goods_id'])){
                            $goodids = array_unique($data['goods_id']);
                            $info_id = implode(',', $goodids);
                        
                            foreach ($goodids as $v){
                                $cates = Db::name('category')->where('id',$v)->where('pid',0)->where('is_show',1)->field('id')->find();
                                if(!$cates){
                                    $value = array('status'=>0,'mess'=>'经营类目信息有误，申请失败');
                                    return json($value);
                                }
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'请选择经营类目');
                            return json($value);
                        }
            
                        if(!empty($data['sfzz_id'])){
                            $sfzz_pics = Db::name('apply_zspic')->where('id',$data['sfzz_id'])->where('user_id',$user_id)->find();
                            if($sfzz_pics && $sfzz_pics['img_url']){
                                $data['sfzz_pic'] = $sfzz_pics['img_url'];
                            }else{
                                $value = array('status'=>0,'mess'=>'请上传身份证正面照片');
                                return json($value);
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'请上传身份证正面照片');
                            return json($value);
                        }
            
                        if(!empty($data['sfzb_id'])){
                            $sfzb_pics = Db::name('apply_zspic')->where('id',$data['sfzb_id'])->where('user_id',$user_id)->find();
                            if($sfzb_pics && $sfzb_pics['img_url']){
                                $data['sfzb_pic'] = $sfzb_pics['img_url'];
                            }else{
                                $value = array('status'=>0,'mess'=>'请上传身份证背面照片');
                                return json($value);
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'请上传身份证背面照片');
                            return json($value);
                        }
            
                        if(!empty($data['frsfz_id'])){
                            $frsfz_pics = Db::name('apply_zspic')->where('id',$data['frsfz_id'])->where('user_id',$user_id)->find();
                            if($frsfz_pics && $frsfz_pics['img_url']){
                                $data['frsfz_pic'] = $frsfz_pics['img_url'];
                            }else{
                                $value = array('status'=>0,'mess'=>'请上传手持身份证照片');
                                return json($value);
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'请上传手持身份证照片');
                            return json($value);
                        }

                        $pro_id = $data['pro_id'];
                        $city_id = $data['city_id'];
                        $area_id = $data['area_id'];
                        $pros = Db::name('province')->where('id',$pro_id)->where('checked',1)->where('pro_zs',1)->field('id,pro_name')->find();
                        if($pros){
                            $citys = Db::name('city')->where('id',$city_id)->where('pro_id',$pros['id'])->where('checked',1)->where('city_zs',1)->field('id,city_name')->find();
                            if($citys){
                                $areas = Db::name('area')->where('id',$area_id)->where('city_id',$citys['id'])->where('checked',1)->field('id,area_name')->find();
                                if($areas){
                                    $data['latlon'] = str_replace('，', ',', $data['latlon']);
                                    if(strpos($data['latlon'],',') !== false){
                                        
                                        // 启动事务
                                        Db::startTrans();
                                        try{
                                            $apply_id = Db::name('apply_info')->insertGetId(array(
                                                'shop_name'=>$data['shop_name'],
                                                'shop_desc'=>$data['shop_desc'],
                                                'indus_id'=>$data['indus_id'],
                                                'contacts'=>$data['contacts'],
                                                'telephone'=>$data['telephone'],
                                                'email'=>$data['email'],
                                                'sfz_num'=>$data['sfz_num'],
                                                'sfzz_pic'=>$data['sfzz_pic'],
                                                'sfzb_pic'=>$data['sfzb_pic'],
                                                'frsfz_pic'=>$data['frsfz_pic'],
                                                'pro_id'=>$data['pro_id'],
                                                'city_id'=>$data['city_id'],
                                                'area_id'=>$data['area_id'],
                                                'shengshiqu'=>$pros['pro_name'].$citys['city_name'].$areas['area_name'],
                                                'address'=>$data['address'],
                                                'latlon'=>$data['latlon'],
                                                'apply_type'=>1,
                                                'apply_time'=>time(),
                                                'user_id'=>$user_id
                                            ));
                                            
                                            if($apply_id){
                                                foreach ($goodids as $v){
                                                    Db::name('manage_apply')->insert(array('cate_id'=>$v,'apply_id'=>$apply_id,'apply_time'=>time()));
                                                }
                                            
                                                if($sfzz_pics && $sfzz_pics['img_url']){
                                                    Db::name('apply_zspic')->where('id',$sfzz_pics['id'])->delete();
                                                }
                                                
                                                if($sfzb_pics && $sfzb_pics['img_url']){
                                                    Db::name('apply_zspic')->where('id',$sfzb_pics['id'])->delete();
                                                }
                                                
                                                if($frsfz_pics && $frsfz_pics['img_url']){
                                                    Db::name('apply_zspic')->where('id',$frsfz_pics['id'])->delete();
                                                }
                                            }
                                            // 提交事务
                                            Db::commit();
                                            $value = array('status'=>1,'mess'=>'提交资料成功，请待审核');
                                        } catch (\Exception $e) {
                                            // 回滚事务
                                            Db::rollback();
                                            $value = array('status'=>0,'mess'=>'提交资料失败');
                                        }
                                    }else{
                                        $value = array('status'=>0,'mess'=>'地址坐标参数错误');
                                    }
                                }else{
                                    $value = array('status'=>0,'mess'=>'请选择区域，操作失败');
                                }
                            }else{
                                $value = array('status'=>0,'mess'=>'请选择区域，操作失败');
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'请选择区域，操作失败');
                        }
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'信息有误，提交失败');
                }
            }else{
                $value = array('status'=>2,'mess'=>'身份已过期，请重新登录');
            }
            return json($value);            
        }else{
            if(session('user_id')){
                $user_id = session('user_id');
                $zsinduspics = Db::name('apply_zspic')->where('user_id',$user_id)->field('id,img_url')->select();
                if($zsinduspics){
                    foreach ($zsinduspics as $v){
                        Db::name('apply_zspic')->delete($v['id']);
                        if($v['img_url'] && file_exists('./'.$v['img_url'])){
                            @unlink('./'.$v['img_url']);
                        }
                    }
                }
            
                $applyinfos = Db::name('apply_info')->where('user_id',$user_id)->field('id,checked,qht,state,complete')->order('apply_time desc')->find();
                if(!$applyinfos){
                    $prores = Db::name('province')->where('checked',1)->where('pro_zs',1)->field('id,pro_name,zm')->order('sort asc')->select();
                    $industryres = Db::name('industry')->where('is_show',1)->field('id,industry_name')->order('sort asc')->select();
                    $this->assign('industryres',$industryres);
                    $this->assign('prores',$prores);
                    return $this->fetch();
                }else{
                    if($applyinfos['checked'] == 2 && $applyinfos['qht'] == 0 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                        $prores = Db::name('province')->where('checked',1)->where('pro_zs',1)->field('id,pro_name,zm')->order('sort asc')->select();
                        $industryres = Db::name('industry')->where('is_show',1)->field('id,industry_name')->order('sort asc')->select();
                        $this->assign('industryres',$industryres);
                        $this->assign('prores',$prores);
                        return $this->fetch();
                    }elseif($applyinfos['checked'] == 0 && $applyinfos['qht'] == 0 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                        $this->redirect('apply_info/waitchecked');
                    }elseif($applyinfos['checked'] == 1 && $applyinfos['qht'] == 0 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                        $this->redirect('apply_info/waitqht');
                    }elseif($applyinfos['checked'] == 1 && $applyinfos['qht'] == 1 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                        $this->redirect('apply_info/waitpaybzj');
                    }elseif($applyinfos['checked'] == 1 && $applyinfos['qht'] == 1 && $applyinfos['state'] == 1 && $applyinfos['complete'] == 0){
                        $this->redirect('apply_info/waitcomplete');
                    }elseif($applyinfos['checked'] == 1 && $applyinfos['qht'] == 1 && $applyinfos['state'] == 1 && $applyinfos['complete'] == 1){
                        $this->redirect('apply_info/complete');
                    }else{
                        $this->error('信息错误');
                    }
                }
            }else{
                $goods_url = '/index/apply_info/index';
                cookie('goods_url',$goods_url,3600);
                $this->redirect('login/index');
            }
        }
    }
    

    
    
    
    
    public function uploadifys(){
        if(session('user_id')){
            $user_id = session('user_id');
            $file = request()->file('filedata');
            $webconfig = $this->webconfig;
            
            $uploadnum = Db::name('upload_num')->where('user_id',$user_id)->find();
            if($uploadnum){
                $jtime = time();
                if($jtime < $uploadnum['time_out']){
                    if($uploadnum['num'] >= $webconfig['user_upload_max']){
                        $value = array('status'=>0,'msg'=>'今天已超出最大上传次数');
                        return json($value);
                    }
                }
            }
            
            if($file){
                $info = $file->validate(['size'=>3145728,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'apply_pic');
                if($info){
                    $original = 'uploads/apply_pic/'.$info->getSaveName();
                    $image = \think\Image::open('./'.$original);
                    $image->thumb(800, 800)->save('./'.$original);
                    // 启动事务
                    Db::startTrans();
                    try{
                        $zspic_id = Db::name('apply_zspic')->insertGetId(array('img_url'=>$original,'user_id'=>$user_id));
                        if($uploadnum){
                            if($jtime < $uploadnum['time_out']){
                                $cishu = $uploadnum['num']+1;
                                Db::name('upload_num')->update(array('num'=>$cishu,'user_id'=>$user_id,'id'=>$uploadnum['id']));
                            }else{
                                $time_out = strtotime(date('Y-m-d',time()))+3600*24;
                                $cishu = 1;
                                Db::name('upload_num')->update(array('time_out'=>$time_out,'num'=>$cishu,'user_id'=>$user_id,'id'=>$uploadnum['id']));
                            }
                        }else{
                            $time_out = strtotime(date('Y-m-d',time()))+3600*24;
                            Db::name('upload_num')->insert(array('time_out'=>$time_out,'num'=>1,'user_id'=>$user_id));
                        }
                        // 提交事务
                        Db::commit();
                        $picarr = array('img_url'=>$original,'pic_id'=>$zspic_id);;
                        $value = array('status'=>1, 'path'=>$picarr);
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        $value = array('status'=>0, 'msg'=>'上传失败');
                    }
                }else{
                    $value = array('status'=>0,'msg'=>$file->getError());
                }
            }else{
                $value = array('status'=>0,'msg'=>'文件不存在');
            }
        }else{
            $value = array('status'=>0,'msg'=>'您的身份已过期，请重新登录');
        }
        return json($value);
    }
    
    public function delfile(){
        if(session('user_id')){
            if(input('post.zspic_id')){
                $user_id = session('user_id');
                $zspic_id = input('post.zspic_id');
                $img_url = Db::name('apply_zspic')->where('id',$zspic_id)->where('user_id',$user_id)->value('img_url');
                if($img_url){
                    $count = Db::name('apply_zspic')->delete($zspic_id);
                    if($count > 0){
                        if($img_url && file_exists('./'.$img_url)){
                            if(unlink('./'.$img_url)){
                                $value = 1;
                            }else{
                                $value = 0;
                            }
                        }else{
                            $value = 0;
                        }
                    }else{
                        $value = 0;
                    }
                }else{
                    $value = 0;
                }
            }else{
                $value = 0;
            }
        }else{
            $value = 0;
        }
        return json($value);
    }
    
    //验证商家名称唯一性
    public function checkShopname(){
        if(request()->isAjax()){
            if(input('post.shop_name')){
                $shop_name = Db::name('shops')->where(array('shop_name' => input('post.shop_name')))->find();
                if($shop_name){
                    echo 'false';
                }else{
                    echo 'true';
                }
            }else{
                echo 'false';
            }
        }
    }
    
    public function waitchecked(){
        if(session('user_id')){
            $user_id = session('user_id');
            $applyinfos = Db::name('apply_info')->where('user_id',$user_id)->field('id,checked,qht,state,complete')->order('apply_time desc')->find();
            if($applyinfos && $applyinfos['checked'] == 0 && $applyinfos['qht'] == 0 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                return $this->fetch();
            }else{
                $this->redirect('index/index');
            }
        }else{
            $this->redirect('login/index');
        }
    }
    
    public function jujue(){
        if(session('user_id')){
            $user_id = session('user_id');
            $applyinfos = Db::name('apply_info')->where('user_id',$user_id)->field('id,checked,qht,state,complete,remarks')->order('apply_time desc')->find();
            if($applyinfos && $applyinfos['checked'] == 2 && $applyinfos['qht'] == 0 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                $this->assign('remarks',$applyinfos['remarks']);
                return $this->fetch();
            }else{
                $this->redirect('index/index');
            }
        }else{
            $this->redirect('login/index');
        }
    }
    
    public function waitqht(){
        if(session('user_id')){
            $user_id = session('user_id');
            $applyinfos = Db::name('apply_info')->where('user_id',$user_id)->field('id,checked,qht,state,complete,remarks')->order('apply_time desc')->find();
            if($applyinfos && $applyinfos['checked'] == 1 && $applyinfos['qht'] == 0 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                $this->assign('remarks',$applyinfos['remarks']);
                return $this->fetch();
            }else{
                $this->redirect('index/index');
            }
        }else{
            $this->redirect('login/index');
        }
    }
    
    
    public function waitpaybzj(){
        if(session('user_id')){
            $user_id = session('user_id');
            $applyinfos = Db::name('apply_info')->where('user_id',$user_id)->field('id,indus_id,checked,qht,state,complete')->order('apply_time desc')->find();
            if($applyinfos && $applyinfos['checked'] == 1 && $applyinfos['qht'] == 1 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                $rzorders = Db::name('rz_order')->where('user_id',$user_id)->where('apply_id',$applyinfos['id'])->field('id,state')->find();
                if(!$rzorders || $rzorders['state'] == 0){
                    $industrys = Db::name('industry')->where('id',$applyinfos['indus_id'])->where('is_show',1)->field('id,industry_name,ser_price,remind')->find();
                    if($industrys){
                        $this->assign('industrys',$industrys);
                        return $this->fetch();
                    }else{
                        $this->redirect('index/index');
                    }
                }else{
                    $this->redirect('index/index');
                }
            }else{
                $this->redirect('index/index');
            }
        }else{
            $this->redirect('login/index');
        }
    }
    
    public function waitcomplete(){
        if(session('user_id')){
            $user_id = session('user_id');
            $applyinfos = Db::name('apply_info')->where('user_id',$user_id)->field('id,checked,qht,state,complete')->order('apply_time desc')->find();
            if($applyinfos && $applyinfos['checked'] == 1 && $applyinfos['qht'] == 1 && $applyinfos['state'] == 1 && $applyinfos['complete'] == 0){
                return $this->fetch();
            }else{
                $this->redirect('index/index');
            }          
        }else{
            $this->redirect('login/index');
        }
    }
    
    public function complete(){
        if(session('user_id')){
            $user_id = session('user_id');
            $applyinfos = Db::name('apply_info')->where('user_id',$user_id)->field('id,checked,qht,state,complete')->order('apply_time desc')->find();
            if($applyinfos && $applyinfos['checked'] == 1 && $applyinfos['qht'] == 1 && $applyinfos['state'] == 1 && $applyinfos['complete'] == 1){
                return $this->fetch();
            }else{
                $this->redirect('index/index');
            }
        }else{
            $this->redirect('login/index');
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
                return json($cityres);
            }
        }
    }
    
    public function getarealist(){
        if(request()->isPost()){
            $city_id = input('post.city_id');
            if($city_id){
                $areares = Db::name('area')->where('city_id',$city_id)->where('checked',1)->field('id,area_name,zm')->order('sort asc')->select();
                if(empty($areares)){
                    $areares = 0;
                }
                return json($areares);
            }
        }
    }
    
    public function addorder(){
        if(request()->isPost()){
            if(session('user_id')){
                $user_id = session('user_id');
                $applyinfos = Db::name('apply_info')->where('user_id',$user_id)->order('apply_time desc')->find();
                if($applyinfos){
                    if($applyinfos['checked'] == 1 && $applyinfos['qht'] == 1 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                        $rzorders = Db::name('rz_order')->where('user_id',$user_id)->where('apply_id',$applyinfos['id'])->field('id,state')->find();
                        if($rzorders){
                            if($rzorders['state'] == 0){
                                $value = array('status'=>1,'mess'=>'成功');
                            }elseif($rzorders['state'] == 1){
                                $value = array('status'=>0,'mess'=>'信息错误，提交订单失败');
                            }
                        }else{
                            $industrys = Db::name('industry')->where('id',$applyinfos['indus_id'])->where('is_show',1)->field('id,ser_price')->find();
                            if($industrys){
                                $ordernumber = 'R'.date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
                                $dingdan = Db::name('rz_order')->where('ordernumber',$ordernumber)->find();
                                if(!$dingdan){
                                    $lastId = Db::name('rz_order')->insert(array(
                                        'ordernumber'=>$ordernumber,
                                        'contacts'=>$applyinfos['contacts'],
                                        'telephone'=>$applyinfos['telephone'],
                                        'shop_name'=>$applyinfos['shop_name'],
                                        'total_price'=>$industrys['ser_price'],
                                        'pro_id'=>$applyinfos['pro_id'],
                                        'city_id'=>$applyinfos['city_id'],
                                        'area_id'=>$applyinfos['area_id'],
                                        'state'=>0,
                                        'user_id'=>$user_id,
                                        'apply_id'=>$applyinfos['id'],
                                        'indus_id'=>$industrys['id'],
                                        'addtime'=>time()
                                    ));
                                    if($lastId){
                                        $value = array('status'=>1,'mess'=>'提交订单成功');
                                    }else{
                                        $value = array('status'=>0,'mess'=>'提交订单失败');
                                    }
                                }else{
                                    $value = array('status'=>0,'mess'=>'提交订单失败');
                                }
                            }else{
                                $value = array('status'=>0,'mess'=>'提交订单失败');
                            }
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'资料审核尚未通过');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'请先提交申请资料');
                }
            }else{
                $value = array('status'=>2,'mess'=>'身份已过期，请重新登录');
            }
            return json($value);
        }
    }
    
}