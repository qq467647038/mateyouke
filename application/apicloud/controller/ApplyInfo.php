<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use think\Db;

class ApplyInfo extends Common{
    
    public function panduan(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    
                    $members = Db::name('member')->where('id',$user_id)->field('phone,password')->find();
                    
                    if($members['phone'] && $members['password']){
                        $applyinfos = Db::name('apply_info')->where('user_id',$user_id)->field('id,checked,qht,state,complete')->order('apply_time desc')->find();
                        if(!$applyinfos){
                            $zhuangtai = 1;
                        }else{
                            $zhuangtai = 2;
                        }
                    }else{
                        $zhuangtai = 4;
                    }
                    
                    $value = array('status'=>200,'mess'=>'获取判断信息成功','data'=>array('zhuangtai'=>$zhuangtai));
                }else{
                    $value = $result;
                }
            }else{
                $value = array('status'=>400,'mess'=>'缺少用户令牌','data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    //申请入驻获取相关信息
    public function ruzhuinfo(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    
                    $members = Db::name('member')->where('id',$user_id)->field('phone,password')->find();
                    
                    if($members['phone'] && $members['password']){
                        $applyinfos = Db::name('apply_info')->where('user_id',$user_id)->field('id,checked,qht,state,complete')->order('apply_time desc')->find();
                        if(!$applyinfos){
                            $prores = Db::name('province')->where('checked',1)->where('pro_zs',1)->field('id,pro_name,zm')->order('sort asc')->select();
                            $industryres = Db::name('industry')->where('is_show',1)->field('id,industry_name')->order('sort asc')->select();
                            $cominfos = array('industryres'=>$industryres,'prores'=>$prores,'zhuangtai'=>1);
                        }else{
                            if($applyinfos['checked'] == 2 && $applyinfos['qht'] == 0 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                                $prores = Db::name('province')->where('checked',1)->where('pro_zs',1)->field('id,pro_name,zm')->order('sort asc')->select();
                                $industryres = Db::name('industry')->where('is_show',1)->field('id,industry_name')->order('sort asc')->select();
                                $cominfos = array('industryres'=>$industryres,'prores'=>$prores,'zhuangtai'=>2);
                            }else{
                                $cominfos = array('industryres'=>array(),'prores'=>array(),'zhuangtai'=>3);
                            }
                        }
                    }else{
                        $cominfos = array('industryres'=>array(),'prores'=>array(),'zhuangtai'=>4);
                    }
                    $value = array('status'=>200,'mess'=>'获取信息成功','data'=>$cominfos);
                }else{
                    $value = $result;
                }
            }else{
                $value = array('status'=>400,'mess'=>'缺少用户令牌','data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);        
    }
    
    //通过行业获取类目
    public function getcates(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
        
                    $where = array();
                    if(input('post.indus_id')){
                        $indus_id = input('post.indus_id');
                        $industrys = Db::name('industry')->where('id',$indus_id)->where('is_show',1)->field('id,cate_id_list')->find();
                        if($industrys){
                            $goodsids = explode(',', $industrys['cate_id_list']);
                    
                            $where['id'] = array('in',$goodsids);
                            $where['pid'] = 0;
                            $where['is_show'] = 1;
                    
                            $list = Db::name('category')->where($where)->field('id,cate_name')->order('sort asc')->select();
                    
                            $value = array('status'=>200,'mess'=>'获取经营类目信息成功','data'=>$list);
                        }else{
                            $value = array('status'=>400,'mess'=>'找不到相关行业','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'请选择主营行业','data'=>array('status'=>400));
                    }
                }else{
                    $value = $result;
                }
            }else{
                $value = array('status'=>400,'mess'=>'缺少用户令牌','data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    //个人入驻申请
    public function personapply(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    
                    $members = Db::name('member')->where('id',$user_id)->field('phone,password')->find();
                    
                    if($members['phone'] && $members['password']){
                        $data = input('post.');
                        
                        $applyinfos = Db::name('apply_info')->where('user_id',$user_id)->field('id,checked,qht,state,complete')->order('apply_time desc')->find();
                        
                        $zhuangtai = 0;
                        
                        if($applyinfos && $applyinfos['checked'] == 2 && $applyinfos['qht'] == 0 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                            $zhuangtai = 1;
                        }
                        
                        if(!$applyinfos || $zhuangtai == 1){
                            $yzresult = $this->validate($data,'PersonapplyInfo');
                            if(true !== $yzresult){
                                $value = array('status'=>400,'mess'=>$yzresult,'data'=>array('status'=>400));
                            }else{
                                if(!empty($data['apply_type']) && $data['apply_type'] == 1){
                                    if(!empty($data['indus_id'])){
                                        $industrys = Db::name('industry')->where('id',$data['indus_id'])->where('is_show',1)->field('id,cate_id_list')->find();
                                        if(!$industrys){
                                            $value = array('status'=>400,'mess'=>'请选择行业','data'=>array('status'=>400));
                                            return json($value);
                                        }
                                    }else{
                                        $value = array('status'=>400,'mess'=>'请选择行业','data'=>array('status'=>400));
                                        return json($value);
                                    }
                        
                                    if(!empty($data['cate_ids'])){
                                        $cate_ids = $data['cate_ids'];
                                        $cate_ids = trim($cate_ids);
                                        $cate_ids = str_replace('，', ',', $cate_ids);
                                        $cate_ids = rtrim($cate_ids,',');
                        
                                        if($cate_ids){
                                            $cateids = explode(',', $cate_ids);
                                            if($cateids && is_array($cateids)){
                                                $cateids = array_unique($cateids);
                        
                                                foreach ($cateids as $v){
                                                    if(!empty($v)){
                                                        if(strpos(','.$industrys['cate_id_list'].',',','.$v.',') !== false){
                                                            $cates = Db::name('category')->where('id',$v)->where('pid',0)->where('is_show',1)->field('id')->find();
                                                            if(!$cates){
                                                                $value = array('status'=>400,'mess'=>'经营类目信息有误，申请失败','data'=>array('status'=>400));
                                                                return json($value);
                                                            }
                                                        }else{
                                                            $value = array('status'=>400,'mess'=>'经营类目信息有误，申请失败','data'=>array('status'=>400));
                                                            return json($value);
                                                        }
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>'经营类目信息有误，申请失败','data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }
                                            }else{
                                                $value = array('status'=>400,'mess'=>'经营类目信息有误，申请失败','data'=>array('status'=>400));
                                                return json($value);
                                            }
                                        }else{
                                            $value = array('status'=>400,'mess'=>'经营类目信息有误，申请失败','data'=>array('status'=>400));
                                            return json($value);
                                        }
                                    }else{
                                        $value = array('status'=>400,'mess'=>'请选择经营类目','data'=>array('status'=>400));
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
                                                $zlpicres = array();
                        
                                                $filelogo = request()->file('imageres0');
                                                if($filelogo){
                                                    $infologo = $filelogo->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'shop_logo');
                                                    if($infologo){
                                                        $orilogo = 'uploads/shop_logo/'.$infologo->getSaveName();
                                                        $imagelogo = \think\Image::open('./'.$orilogo);
                                                        $imagelogo->thumb(300, 300)->save('./'.$orilogo,null,90);
                                                        $data['logo'] = $orilogo;
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>$filelogo->getError(),'data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }else{
                                                    $value = array('status'=>400,'mess'=>'请上传店铺logo图片','data'=>array('status'=>400));
                                                    return json($value);
                                                }
                        
                                                $filesfzz = request()->file('imageres1');
                                                if($filesfzz){
                                                    $infosfzz = $filesfzz->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'apply_pic');
                                                    if($infosfzz){
                                                        $orisfzz = 'uploads/apply_pic/'.$infosfzz->getSaveName();
                                                        $imagesfzz = \think\Image::open('./'.$orisfzz);
                                                        $imagesfzz->thumb(800, 800)->save('./'.$orisfzz,null,90);
                                                        $data['sfzz_pic'] = $orisfzz;
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>$filesfzz->getError(),'data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }else{
                                                    $value = array('status'=>400,'mess'=>'请上传经营者身份证正面照片','data'=>array('status'=>400));
                                                    return json($value);
                                                }
                        
                                                $filesfzb = request()->file('imageres2');
                                                if($filesfzb){
                                                    $infosfzb = $filesfzb->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'apply_pic');
                                                    if($infosfzb){
                                                        $orisfzb = 'uploads/apply_pic/'.$infosfzb->getSaveName();
                                                        $imagesfzb = \think\Image::open('./'.$orisfzb);
                                                        $imagesfzb->thumb(800, 800)->save('./'.$orisfzb,null,90);
                                                        $data['sfzb_pic'] = $orisfzb;
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>$filesfzb->getError(),'data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }else{
                                                    $value = array('status'=>400,'mess'=>'请上传经营者身份证背面照片','data'=>array('status'=>400));
                                                    return json($value);
                                                }
                        
                                                $filefrsfz = request()->file('imageres3');
                                                if($filefrsfz){
                                                    $infofrsfz = $filefrsfz->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'apply_pic');
                                                    if($infofrsfz){
                                                        $orifrsfz = 'uploads/apply_pic/'.$infofrsfz->getSaveName();
                                                        $imagefrsfz = \think\Image::open('./'.$orifrsfz);
                                                        $imagefrsfz->thumb(800, 800)->save('./'.$orifrsfz,null,90);
                                                        $data['frsfz_pic'] = $orifrsfz;
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>$filefrsfz->getError(),'data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }else{
                                                    $value = array('status'=>400,'mess'=>'请上传经营者手持身份证正面照片','data'=>array('status'=>400));
                                                    return json($value);
                                                }
                        
//                                                $fileres4 = request()->file('imageres4');
//                                                if($fileres4){
//                                                    $infoxinxi4 = $fileres4->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'apply_pic');
//                                                    if($infoxinxi4){
//                                                        $yuantu4 = 'uploads/apply_pic/'.$infoxinxi4->getSaveName();
//                                                        $images = \think\Image::open('./'.$yuantu4);
//                                                        $images->thumb(800, 800)->save('./'.$yuantu4,null,90);
//                                                        $zlpicres[] = $yuantu4;
//                                                    }else{
//                                                        $value = array('status'=>400,'mess'=>$fileres4->getError(),'data'=>array('status'=>400));
//                                                        return json($value);
//                                                    }
//                                                }
                        
                                                $fileres5 = request()->file('imageres5');
                                                if($fileres5){
                                                    $infoxinxi5 = $fileres5->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'apply_pic');
                                                    if($infoxinxi5){
                                                        $yuantu5 = 'uploads/apply_pic/'.$infoxinxi5->getSaveName();
                                                        $images = \think\Image::open('./'.$yuantu5);
                                                        $images->thumb(800, 800)->save('./'.$yuantu5,null,90);
                                                        $zlpicres[] = $yuantu5;
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>$fileres5->getError(),'data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }
                        
                                                $fileres6 = request()->file('imageres6');
                                                if($fileres6){
                                                    $infoxinxi6 = $fileres6->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'apply_pic');
                                                    if($infoxinxi6){
                                                        $yuantu6 = 'uploads/apply_pic/'.$infoxinxi6->getSaveName();
                                                        $images = \think\Image::open('./'.$yuantu6);
                                                        $images->thumb(800, 800)->save('./'.$yuantu6,null,90);
                                                        $zlpicres[] = $yuantu6;
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>$fileres6->getError(),'data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }
                        
                                                $fileres7 = request()->file('imageres7');
                                                if($fileres7){
                                                    $infoxinxi7 = $fileres7->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'apply_pic');
                                                    if($infoxinxi7){
                                                        $yuantu7 = 'uploads/apply_pic/'.$infoxinxi7->getSaveName();
                                                        $images = \think\Image::open('./'.$yuantu7);
                                                        $images->thumb(800, 800)->save('./'.$yuantu7,null,90);
                                                        $zlpicres[] = $yuantu7;
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>$fileres7->getError(),'data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }
                        
                                                $fileres8 = request()->file('imageres8');
                                                if($fileres8){
                                                    $infoxinxi8 = $fileres8->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'apply_pic');
                                                    if($infoxinxi8){
                                                        $yuantu8 = 'uploads/apply_pic/'.$infoxinxi8->getSaveName();
                                                        $images = \think\Image::open('./'.$yuantu8);
                                                        $images->thumb(800, 800)->save('./'.$yuantu8,null,90);
                                                        $zlpicres[] = $yuantu8;
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>$fileres8->getError(),'data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }
                        
                                                /*$filelogo = request()->file('logo');
                                                 if($filelogo){
                                                 $infologo = $filelogo->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'shop_logo');
                                                 if($infologo){
                                                 $orilogo = 'uploads/shop_logo/'.$infologo->getSaveName();
                                                 $imagelogo = \think\Image::open('./'.$orilogo);
                                                 $imagelogo->thumb(300, 300)->save('./'.$orilogo);
                                                 $data['logo'] = $orilogo;
                                                 }else{
                                                 $value = array('status'=>400,'mess'=>$filelogo->getError(),'data'=>array('status'=>400));
                                                 return json($value);
                                                 }
                                                 }else{
                                                 $value = array('status'=>400,'mess'=>'请上传店铺logo图片','data'=>array('status'=>400));
                                                 return json($value);
                                                 }
                        
                                                 $filesfzz = request()->file('sfzz_pic');
                                                 if($filesfzz){
                                                 $infosfzz = $filesfzz->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'apply_pic');
                                                 if($infosfzz){
                                                 $orisfzz = 'uploads/apply_pic/'.$infosfzz->getSaveName();
                                                 $imagesfzz = \think\Image::open('./'.$orisfzz);
                                                 $imagesfzz->thumb(800, 800)->save('./'.$orisfzz);
                                                 $data['sfzz_pic'] = $orisfzz;
                                                 }else{
                                                 $value = array('status'=>400,'mess'=>$filelogo->getError(),'data'=>array('status'=>400));
                                                 return json($value);
                                                 }
                                                 }else{
                                                 $value = array('status'=>400,'mess'=>'请上传经营者身份证正面照片','data'=>array('status'=>400));
                                                 return json($value);
                                                 }
                        
                                                 $filesfzb = request()->file('sfzb_pic');
                                                 if($filesfzb){
                                                 $infosfzb = $filesfzb->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'apply_pic');
                                                 if($infosfzb){
                                                 $orisfzb = 'uploads/apply_pic/'.$infosfzb->getSaveName();
                                                 $imagesfzb = \think\Image::open('./'.$orisfzb);
                                                 $imagesfzb->thumb(800, 800)->save('./'.$orisfzb);
                                                 $data['sfzb_pic'] = $orisfzb;
                                                 }else{
                                                 $value = array('status'=>400,'mess'=>$filelogo->getError(),'data'=>array('status'=>400));
                                                 return json($value);
                                                 }
                                                 }else{
                                                 $value = array('status'=>400,'mess'=>'请上传经营者身份证背面照片','data'=>array('status'=>400));
                                                 return json($value);
                                                 }
                        
                                                 $filefrsfz = request()->file('frsfz_pic');
                                                 if($filefrsfz){
                                                 $infofrsfz = $filefrsfz->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'apply_pic');
                                                 if($infofrsfz){
                                                 $orifrsfz = 'uploads/apply_pic/'.$infofrsfz->getSaveName();
                                                 $imagefrsfz = \think\Image::open('./'.$orifrsfz);
                                                 $imagefrsfz->thumb(800, 800)->save('./'.$orifrsfz);
                                                 $data['frsfz_pic'] = $orifrsfz;
                                                 }else{
                                                 $value = array('status'=>400,'mess'=>$filelogo->getError(),'data'=>array('status'=>400));
                                                 return json($value);
                                                 }
                                                 }else{
                                                 $value = array('status'=>400,'mess'=>'请上传经营者手持身份证正面照片','data'=>array('status'=>400));
                                                 return json($value);
                                                 }
                        
                                                 $fileres = request()->file('imageres');
                                                 if($fileres){
                                                 if(count($fileres) <= 5){
                                                 $zlpicres = array();
                                                 foreach($fileres as $key => $filexinxi){
                                                 $infoxinxi = $filexinxi->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'apply_pic');
                                                 if($infoxinxi){
                                                 $yuantu = 'uploads/apply_pic/'.$infoxinxi->getSaveName();
                                                 $images = \think\Image::open('./'.$yuantu);
                                                 $images->thumb(800, 800)->save('./'.$yuantu);
                                                 $zlpicres[] = $yuantu;
                                                 }else{
                                                 $value = array('status'=>400,'mess'=>$infoxinxi->getError(),'data'=>array('status'=>400));
                                                 return json($value);
                                                 }
                                                 }
                                                 }else{
                                                 $value = array('status'=>400,'mess'=>'补充资料图片最多允许上传5张','data'=>array('status'=>400));
                                                 return json($value);
                                                 }
                                                 }*/
                        
                        
                                                /*$fileres = request()->file('imageres');
                                                 if($fileres){
                                                 if(count($fileres) <= 9){
                                                 if(empty($fileres[0])){
                                                 $value = array('status'=>400,'mess'=>'请上传logo','data'=>array('status'=>400));
                                                 return json($value);
                                                 }
                        
                                                 if(empty($fileres[1])){
                                                 $value = array('status'=>400,'mess'=>'请上传经营者身份证正面照片','data'=>array('status'=>400));
                                                 return json($value);
                                                 }
                        
                                                 if(empty($fileres[2])){
                                                 $value = array('status'=>400,'mess'=>'请上传经营者身份证背面照片','data'=>array('status'=>400));
                                                 return json($value);
                                                 }
                        
                                                 if(empty($fileres[3])){
                                                 $value = array('status'=>400,'mess'=>'请上传经营者手持身份证正面面照片','data'=>array('status'=>400));
                                                 return json($value);
                                                 }
                        
                                                 foreach($fileres as $key => $file){
                                                 //移动到框架应用根目录/public/uploads/目录下
                                                 if($key == 0){
                                                 $mulu = 'logo';
                                                 }else{
                                                 $mulu = 'apply_pic';
                                                 }
                                                 $info = $file->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . $mulu);
                                                 if($info){
                                                 $original = 'uploads/'.$mulu.'/'.$info->getSaveName();
                                                 $image = \think\Image::open('./'.$original);
                                                 if($key == 0){
                                                 $image->thumb(300, 300)->save('./'.$original);
                                                 }else{
                                                 $image->thumb(800, 800)->save('./'.$original);
                                                 }
                                                 if($key == 0){
                                                 $data['logo'] = $original;
                                                 }elseif($key == 1){
                                                 $data['sfzz_pic'] = $original;
                                                 }elseif($key == 2){
                                                 $data['sfzb_pic'] = $original;
                                                 }elseif($key == 3){
                                                 $data['frsfz_pic'] = $original;
                                                 }elseif($key >= 4){
                                                 $zlpicres[] = $original;
                                                 }
                                                 }else{
                                                 $value = array('status'=>400,'mess'=>$file->getError(),'data'=>array('status'=>400));
                                                 return json($value);
                                                 }
                                                 }
                                                 }else{
                                                 $value = array('status'=>400,'mess'=>'补充资料最多上传5张图片','data'=>array('status'=>400));
                                                 return json($value);
                                                 }
                                                 }else{
                                                 $value = array('status'=>400,'mess'=>'请上传相关证件','data'=>array('status'=>400));
                                                 return json($value);
                                                 }*/
                        
                                                // 启动事务
                                                Db::startTrans();
                                                try{
                                                    $apply_id = Db::name('apply_info')->insertGetId(array(
                                                        'indus_id'=>$data['indus_id'],
                                                        'shop_name'=>$data['shop_name'],
                                                        'shop_desc'=>$data['shop_desc'],
                                                        'logo'=>$data['logo'],
                                                        'contacts'=>$data['contacts'],
                                                        'telephone'=>$data['telephone'],
                                                        'email'=>$data['email'],
                                                        'pro_id'=>$data['pro_id'],
                                                        'city_id'=>$data['city_id'],
                                                        'area_id'=>$data['area_id'],
                                                        'shengshiqu'=>$pros['pro_name'].$citys['city_name'].$areas['area_name'],
                                                        'address'=>$data['address'],
                                                        'sfz_num'=>$data['sfz_num'],
                                                        'sfzz_pic'=>$data['sfzz_pic'],
                                                        'sfzb_pic'=>$data['sfzb_pic'],
                                                        'frsfz_pic'=>$data['frsfz_pic'],
                                                        'apply_type'=>1,
                                                        'apply_time'=>time(),
                                                        'user_id'=>$user_id
                                                    ));
                        
                                                    if($apply_id){
                                                        foreach ($cateids as $val){
                                                            Db::name('manage_apply')->insert(array('cate_id'=>$val,'apply_id'=>$apply_id,'apply_time'=>time()));
                                                        }
                                                        if(!empty($zlpicres)){
                                                            foreach ($zlpicres as $v){
                                                                Db::name('apply_ziliaopic')->insert(array('img_url'=>$v,'apply_id'=>$apply_id));
                                                            }
                                                        }
                                                    }
                                                    // 提交事务
                                                    Db::commit();
                                                    $value = array('status'=>200,'mess'=>'提交资料成功，请待审核','data'=>array('status'=>200));
                                                } catch (\Exception $e) {
                                                    // 回滚事务
                                                    Db::rollback();
                                                    $value = array('status'=>400,'mess'=>'提交资料失败','data'=>array('status'=>400));
                                                }
                                            }else{
                                                $value = array('status'=>400,'mess'=>'请选择区域，操作失败','data'=>array('status'=>400));
                                            }
                                        }else{
                                            $value = array('status'=>400,'mess'=>'请选择区域，操作失败','data'=>array('status'=>400));
                                        }
                                    }else{
                                        $value = array('status'=>400,'mess'=>'请选择区域，操作失败','data'=>array('status'=>400));
                                    }
                                }else{
                                    $value = array('status'=>400,'mess'=>'缺少入驻类型参数','data'=>array('status'=>400));
                                }
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'信息有误，提交失败','data'=>array('status'=>400));
                        }                    
                    }else{
                        $value = array('status'=>400,'mess'=>'请先完成账号安全设置，提交失败','data'=>array('status'=>400));
                    }
                }else{
                    $value = $result;
                }
            }else{
                $value = array('status'=>400,'mess'=>'缺少用户令牌','data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    //实体店申请
    public function comapply(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
    
                    $members = Db::name('member')->where('id',$user_id)->field('phone,password')->find();
                    
                    if($members['phone'] && $members['password']){
                        $data = input('post.');
                        $applyinfos = Db::name('apply_info')->where('user_id',$user_id)->field('id,checked,qht,state,complete')->order('apply_time desc')->find();
                        
                        $zhuangtai = 0;
                        
                        if($applyinfos && $applyinfos['checked'] == 2 && $applyinfos['qht'] == 0 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                            $zhuangtai = 1;
                        }
                        
                        if(!$applyinfos || $zhuangtai == 1){
                            $yzresult = $this->validate($data,'ComapplyInfo');
                            if(true !== $yzresult){
                                $value = array('status'=>400,'mess'=>$yzresult,'data'=>array('status'=>400));
                            }else{
                                if(!empty($data['apply_type']) && $data['apply_type'] == 2){
                                    if(!empty($data['indus_id'])){
                                        $industrys = Db::name('industry')->where('id',$data['indus_id'])->where('is_show',1)->field('id,cate_id_list')->find();
                                        if(!$industrys){
                                            $value = array('status'=>400,'mess'=>'请选择行业','data'=>array('status'=>400));
                                            return json($value);
                                        }
                                    }else{
                                        $value = array('status'=>400,'mess'=>'请选择行业','data'=>array('status'=>400));
                                        return json($value);
                                    }
                        
                                    if(!empty($data['cate_ids'])){
                                        $cate_ids = $data['cate_ids'];
                                        $cate_ids = trim($cate_ids);
                                        $cate_ids = str_replace('，', ',', $cate_ids);
                                        $cate_ids = rtrim($cate_ids,',');
                        
                                        if($cate_ids){
                                            $cateids = explode(',', $cate_ids);
                                            if($cateids && is_array($cateids)){
                                                $cateids = array_unique($cateids);
                        
                                                foreach ($cateids as $v){
                                                    if(!empty($v)){
                                                        if(strpos(','.$industrys['cate_id_list'].',',','.$v.',') !== false){
                                                            $cates = Db::name('category')->where('id',$v)->where('pid',0)->where('is_show',1)->field('id')->find();
                                                            if(!$cates){
                                                                $value = array('status'=>400,'mess'=>'经营类目信息有误，申请失败','data'=>array('status'=>400));
                                                                return json($value);
                                                            }
                                                        }else{
                                                            $value = array('status'=>400,'mess'=>'经营类目信息有误，申请失败','data'=>array('status'=>400));
                                                            return json($value);
                                                        }
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>'经营类目信息有误，申请失败','data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }
                                            }else{
                                                $value = array('status'=>400,'mess'=>'经营类目信息有误，申请失败','data'=>array('status'=>400));
                                                return json($value);
                                            }
                                        }else{
                                            $value = array('status'=>400,'mess'=>'经营类目信息有误，申请失败','data'=>array('status'=>400));
                                            return json($value);
                                        }
                                    }else{
                                        $value = array('status'=>400,'mess'=>'请选择经营类目','data'=>array('status'=>400));
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
                                                $zlpicres = array();
                        
                                                $filelogo = request()->file('imageres0');
                                                if($filelogo){
                                                    $infologo = $filelogo->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'shop_logo');
                                                    if($infologo){
                                                        $orilogo = 'uploads/shop_logo/'.$infologo->getSaveName();
                                                        $imagelogo = \think\Image::open('./'.$orilogo);
                                                        $imagelogo->thumb(300, 300)->save('./'.$orilogo,null,90);
                                                        $data['logo'] = $orilogo;
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>$filelogo->getError(),'data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }else{
                                                    $value = array('status'=>400,'mess'=>'请上传店铺logo图片','data'=>array('status'=>400));
                                                    return json($value);
                                                }
                        
                                                $filesfzz = request()->file('imageres1');
                                                if($filesfzz){
                                                    $infosfzz = $filesfzz->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'apply_pic');
                                                    if($infosfzz){
                                                        $orisfzz = 'uploads/apply_pic/'.$infosfzz->getSaveName();
                                                        $imagesfzz = \think\Image::open('./'.$orisfzz);
                                                        $imagesfzz->thumb(800, 800)->save('./'.$orisfzz,null,90);
                                                        $data['sfzz_pic'] = $orisfzz;
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>$filesfzz->getError(),'data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }else{
                                                    $value = array('status'=>400,'mess'=>'请上传经营者身份证正面照片','data'=>array('status'=>400));
                                                    return json($value);
                                                }
                        
                                                $filesfzb = request()->file('imageres2');
                                                if($filesfzb){
                                                    $infosfzb = $filesfzb->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'apply_pic');
                                                    if($infosfzb){
                                                        $orisfzb = 'uploads/apply_pic/'.$infosfzb->getSaveName();
                                                        $imagesfzb = \think\Image::open('./'.$orisfzb);
                                                        $imagesfzb->thumb(800, 800)->save('./'.$orisfzb,null,90);
                                                        $data['sfzb_pic'] = $orisfzb;
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>$filesfzb->getError(),'data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }else{
                                                    $value = array('status'=>400,'mess'=>'请上传经营者身份证背面照片','data'=>array('status'=>400));
                                                    return json($value);
                                                }
                        
                                                $filefrsfz = request()->file('imageres3');
                                                if($filefrsfz){
                                                    $infofrsfz = $filefrsfz->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'apply_pic');
                                                    if($infofrsfz){
                                                        $orifrsfz = 'uploads/apply_pic/'.$infofrsfz->getSaveName();
                                                        $imagefrsfz = \think\Image::open('./'.$orifrsfz);
                                                        $imagefrsfz->thumb(800, 800)->save('./'.$orifrsfz,null,90);
                                                        $data['frsfz_pic'] = $orifrsfz;
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>$filefrsfz->getError(),'data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }else{
                                                    $value = array('status'=>400,'mess'=>'请上传经营者手持身份证正面照片','data'=>array('status'=>400));
                                                    return json($value);
                                                }
                        
                                                $filezhizhao = request()->file('imageres4');
                                                if($filezhizhao){
                                                    $infozhizhao = $filezhizhao->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'apply_pic');
                                                    if($infozhizhao){
                                                        $orizhizhao = 'uploads/apply_pic/'.$infozhizhao->getSaveName();
                                                        $imagezhizhao = \think\Image::open('./'.$orizhizhao);
                                                        $imagezhizhao->thumb(800, 800)->save('./'.$orizhizhao,null,90);
                                                        $data['zhizhao'] = $orizhizhao;
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>$filezhizhao->getError(),'data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }else{
                                                    $value = array('status'=>400,'mess'=>'请上传法人手持身份证正面照片','data'=>array('status'=>400));
                                                    return json($value);
                                                }
                        
                                                $fileres5 = request()->file('imageres5');
                                                if($fileres5){
                                                    $infoxinxi5 = $fileres5->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'apply_pic');
                                                    if($infoxinxi5){
                                                        $yuantu5 = 'uploads/apply_pic/'.$infoxinxi5->getSaveName();
                                                        $images = \think\Image::open('./'.$yuantu5);
                                                        $images->thumb(800, 800)->save('./'.$yuantu5,null,90);
                                                        $zlpicres[] = $yuantu5;
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>$fileres5->getError(),'data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }
                        
                                                $fileres6 = request()->file('imageres6');
                                                if($fileres6){
                                                    $infoxinxi6 = $fileres6->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'apply_pic');
                                                    if($infoxinxi6){
                                                        $yuantu6 = 'uploads/apply_pic/'.$infoxinxi6->getSaveName();
                                                        $images = \think\Image::open('./'.$yuantu6);
                                                        $images->thumb(800, 800)->save('./'.$yuantu6,null,90);
                                                        $zlpicres[] = $yuantu6;
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>$fileres6->getError(),'data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }
                        
                                                $fileres7 = request()->file('imageres7');
                                                if($fileres7){
                                                    $infoxinxi7 = $fileres7->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'apply_pic');
                                                    if($infoxinxi7){
                                                        $yuantu7 = 'uploads/apply_pic/'.$infoxinxi7->getSaveName();
                                                        $images = \think\Image::open('./'.$yuantu7);
                                                        $images->thumb(800, 800)->save('./'.$yuantu7,null,90);
                                                        $zlpicres[] = $yuantu7;
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>$fileres7->getError(),'data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }
                        
                                                $fileres8 = request()->file('imageres8');
                                                if($fileres8){
                                                    $infoxinxi8 = $fileres8->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'apply_pic');
                                                    if($infoxinxi8){
                                                        $yuantu8 = 'uploads/apply_pic/'.$infoxinxi8->getSaveName();
                                                        $images = \think\Image::open('./'.$yuantu8);
                                                        $images->thumb(800, 800)->save('./'.$yuantu8,null,90);
                                                        $zlpicres[] = $yuantu8;
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>$fileres8->getError(),'data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }
                        
                                                $fileres9 = request()->file('imageres9');
                                                if($fileres9){
                                                    $infoxinxi9 = $fileres9->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'apply_pic');
                                                    if($infoxinxi9){
                                                        $yuantu9 = 'uploads/apply_pic/'.$infoxinxi9->getSaveName();
                                                        $images = \think\Image::open('./'.$yuantu9);
                                                        $images->thumb(800, 800)->save('./'.$yuantu9,null,90);
                                                        $zlpicres[] = $yuantu9;
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>$fileres9->getError(),'data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }
                        
                                                /*$filelogo = request()->file('logo');
                                                 if($filelogo){
                                                 $infologo = $filelogo->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'shop_logo');
                                                 if($infologo){
                                                 $orilogo = 'uploads/shop_logo/'.$infologo->getSaveName();
                                                 $imagelogo = \think\Image::open('./'.$orilogo);
                                                 $imagelogo->thumb(300, 300)->save('./'.$orilogo);
                                                 $data['logo'] = $orilogo;
                                                 }else{
                                                 $value = array('status'=>400,'mess'=>$filelogo->getError(),'data'=>array('status'=>400));
                                                 return json($value);
                                                 }
                                                 }else{
                                                 $value = array('status'=>400,'mess'=>'请上传店铺logo图片','data'=>array('status'=>400));
                                                 return json($value);
                                                 }
                        
                                                 $filesfzz = request()->file('sfzz_pic');
                                                 if($filesfzz){
                                                 $infosfzz = $filesfzz->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'apply_pic');
                                                 if($infosfzz){
                                                 $orisfzz = 'uploads/apply_pic/'.$infosfzz->getSaveName();
                                                 $imagesfzz = \think\Image::open('./'.$orisfzz);
                                                 $imagesfzz->thumb(800, 800)->save('./'.$orisfzz);
                                                 $data['sfzz_pic'] = $orisfzz;
                                                 }else{
                                                 $value = array('status'=>400,'mess'=>$filesfzz->getError(),'data'=>array('status'=>400));
                                                 return json($value);
                                                 }
                                                 }else{
                                                 $value = array('status'=>400,'mess'=>'请上传法人身份证正面照片','data'=>array('status'=>400));
                                                 return json($value);
                                                 }
                        
                                                 $filesfzb = request()->file('sfzb_pic');
                                                 if($filesfzb){
                                                 $infosfzb = $filesfzb->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'apply_pic');
                                                 if($infosfzb){
                                                 $orisfzb = 'uploads/apply_pic/'.$infosfzb->getSaveName();
                                                 $imagesfzb = \think\Image::open('./'.$orisfzb);
                                                 $imagesfzb->thumb(800, 800)->save('./'.$orisfzb);
                                                 $data['sfzb_pic'] = $orisfzb;
                                                 }else{
                                                 $value = array('status'=>400,'mess'=>$filesfzb->getError(),'data'=>array('status'=>400));
                                                 return json($value);
                                                 }
                                                 }else{
                                                 $value = array('status'=>400,'mess'=>'请上传法人身份证背面照片','data'=>array('status'=>400));
                                                 return json($value);
                                                 }
                        
                                                 $filefrsfz = request()->file('frsfz_pic');
                                                 if($filefrsfz){
                                                 $infofrsfz = $filefrsfz->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'apply_pic');
                                                 if($infofrsfz){
                                                 $orifrsfz = 'uploads/apply_pic/'.$infofrsfz->getSaveName();
                                                 $imagefrsfz = \think\Image::open('./'.$orifrsfz);
                                                 $imagefrsfz->thumb(800, 800)->save('./'.$orifrsfz);
                                                 $data['frsfz_pic'] = $orifrsfz;
                                                 }else{
                                                 $value = array('status'=>400,'mess'=>$filefrsfz->getError(),'data'=>array('status'=>400));
                                                 return json($value);
                                                 }
                                                 }else{
                                                 $value = array('status'=>400,'mess'=>'请上传法人手持身份证正面照片','data'=>array('status'=>400));
                                                 return json($value);
                                                 }
                        
                                                 $filezhizhao = request()->file('zhizhao');
                                                 if($filezhizhao){
                                                 $infozhizhao = $filezhizhao->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'apply_pic');
                                                 if($infozhizhao){
                                                 $orizhizhao = 'uploads/apply_pic/'.$infozhizhao->getSaveName();
                                                 $imagezhizhao = \think\Image::open('./'.$orizhizhao);
                                                 $imagezhizhao->thumb(800, 800)->save('./'.$orizhizhao);
                                                 $data['zhizhao'] = $orizhizhao;
                                                 }else{
                                                 $value = array('status'=>400,'mess'=>$filezhizhao->getError(),'data'=>array('status'=>400));
                                                 return json($value);
                                                 }
                                                 }else{
                                                 $value = array('status'=>400,'mess'=>'请上传法人手持身份证正面照片','data'=>array('status'=>400));
                                                 return json($value);
                                                 }
                        
                                                 $fileres = request()->file('imageres');
                                                 if($fileres){
                                                 if(count($fileres) <= 5){
                                                 $zlpicres = array();
                                                 foreach($fileres as $key => $filexinxi){
                                                 $infoxinxi = $filexinxi->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'apply_pic');
                                                 if($infoxinxi){
                                                 $yuantu = 'uploads/apply_pic/'.$infoxinxi->getSaveName();
                                                 $images = \think\Image::open('./'.$yuantu);
                                                 $images->thumb(800, 800)->save('./'.$yuantu);
                                                 $zlpicres[] = $yuantu;
                                                 }else{
                                                 $value = array('status'=>400,'mess'=>$infoxinxi->getError(),'data'=>array('status'=>400));
                                                 return json($value);
                                                 }
                                                 }
                                                 }else{
                                                 $value = array('status'=>400,'mess'=>'补充资料图片最多允许上传5张','data'=>array('status'=>400));
                                                 return json($value);
                                                 }
                                                 }*/
                        
                        
                                                /*$fileres = request()->file('imageres');
                                                 if($fileres && is_array($fileres)){
                                                 if(count($fileres) <= 10){
                                                 if(empty($fileres[0])){
                                                 $value = array('status'=>400,'mess'=>'请上传logo','data'=>array('status'=>400));
                                                 return json($value);
                                                 }
                        
                                                 if(empty($fileres[1])){
                                                 $value = array('status'=>400,'mess'=>'请上传法人身份证正面照片','data'=>array('status'=>400));
                                                 return json($value);
                                                 }
                        
                                                 if(empty($fileres[2])){
                                                 $value = array('status'=>400,'mess'=>'请上传法人身份证背面照片','data'=>array('status'=>400));
                                                 return json($value);
                                                 }
                        
                                                 if(empty($fileres[3])){
                                                 $value = array('status'=>400,'mess'=>'请上传法人手持身份证正面面照片','data'=>array('status'=>400));
                                                 return json($value);
                                                 }
                        
                                                 if(empty($fileres[4])){
                                                 $value = array('status'=>400,'mess'=>'请上传营业执照照片','data'=>array('status'=>400));
                                                 return json($value);
                                                 }
                        
                                                 foreach($fileres as $key => $file){
                                                 //移动到框架应用根目录/public/uploads/目录下
                                                 if($key == 0){
                                                 $mulu = 'logo';
                                                 }else{
                                                 $mulu = 'apply_pic';
                                                 }
                                                 $info = $file->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . $mulu);
                                                 if($info){
                                                 $original = 'uploads/'.$mulu.'/'.$info->getSaveName();
                                                 $image = \think\Image::open('./'.$original);
                                                 if($key == 0){
                                                 $image->thumb(300, 300)->save('./'.$original);
                                                 }else{
                                                 $image->thumb(800, 800)->save('./'.$original);
                                                 }
                                                 if($key == 0){
                                                 $data['logo'] = $original;
                                                 }elseif($key == 1){
                                                 $data['sfzz_pic'] = $original;
                                                 }elseif($key == 2){
                                                 $data['sfzb_pic'] = $original;
                                                 }elseif($key == 3){
                                                 $data['frsfz_pic'] = $original;
                                                 }elseif($key == 4){
                                                 $data['zhizhao'] = $original;
                                                 }elseif($key >= 5){
                                                 $zlpicres[] = $original;
                                                 }
                                                 }else{
                                                 $value = array('status'=>400,'mess'=>$file->getError(),'data'=>array('status'=>400));
                                                 return json($value);
                                                 }
                                                 }
                                                 }else{
                                                 $value = array('status'=>400,'mess'=>'补充资料最多上传5张图片','data'=>array('status'=>400));
                                                 return json($value);
                                                 }
                                                 }else{
                                                 $value = array('status'=>400,'mess'=>'请上传相关证件','data'=>array('status'=>400));
                                                 return json($value);
                                                 }*/
                        
                        
                        
                                                // 启动事务
                                                Db::startTrans();
                                                try{
                                                    $apply_id = Db::name('apply_info')->insertGetId(array(
                                                        'indus_id'=>$data['indus_id'],
                                                        'shop_name'=>$data['shop_name'],
                                                        'shop_desc'=>$data['shop_desc'],
                                                        'logo'=>$data['logo'],
                                                        'contacts'=>$data['contacts'],
                                                        'telephone'=>$data['telephone'],
                                                        'email'=>$data['email'],
                                                        'pro_id'=>$data['pro_id'],
                                                        'city_id'=>$data['city_id'],
                                                        'area_id'=>$data['area_id'],
                                                        'shengshiqu'=>$pros['pro_name'].$citys['city_name'].$areas['area_name'],
                                                        'address'=>$data['address'],
                                                        'sfz_num'=>$data['sfz_num'],
                                                        'sfzz_pic'=>$data['sfzz_pic'],
                                                        'sfzb_pic'=>$data['sfzb_pic'],
                                                        'frsfz_pic'=>$data['frsfz_pic'],
                                                        'zhizhao'=>$data['zhizhao'],
                                                        'apply_type'=>2,
                                                        'apply_time'=>time(),
                                                        'user_id'=>$user_id
                                                    ));
                        
                                                    if($apply_id){
                                                        foreach ($cateids as $val){
                                                            Db::name('manage_apply')->insert(array('cate_id'=>$val,'apply_id'=>$apply_id,'apply_time'=>time()));
                                                        }
                                                        if(!empty($zlpicres)){
                                                            foreach ($zlpicres as $v){
                                                                Db::name('apply_ziliaopic')->insert(array('img_url'=>$v,'apply_id'=>$apply_id));
                                                            }
                                                        }
                                                    }
                                                    // 提交事务
                                                    Db::commit();
                                                    $value = array('status'=>200,'mess'=>'提交资料成功，请待审核','data'=>array('status'=>200));
                                                } catch (\Exception $e) {
                                                    // 回滚事务
                                                    Db::rollback();
                                                    $value = array('status'=>400,'mess'=>'提交资料失败','data'=>array('status'=>400));
                                                }
                                            }else{
                                                $value = array('status'=>400,'mess'=>'请选择区域，操作失败','data'=>array('status'=>400));
                                            }
                                        }else{
                                            $value = array('status'=>400,'mess'=>'请选择区域，操作失败','data'=>array('status'=>400));
                                        }
                                    }else{
                                        $value = array('status'=>400,'mess'=>'请选择区域，操作失败','data'=>array('status'=>400));
                                    }
                                }else{
                                    $value = array('status'=>400,'mess'=>'缺少入驻类型参数','data'=>array('status'=>400));
                                }
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'信息有误，提交失败','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'请先完成账号安全设置，提交失败','data'=>array('status'=>400));
                    }
                }else{
                    $value = $result;
                }
            }else{
                $value = array('status'=>400,'mess'=>'缺少用户令牌','data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    //获取入驻审核状态信息
    public function applystatus(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
        
                    $applyinfos = Db::name('apply_info')->where('user_id',$user_id)->field('id,indus_id,checked,qht,state,complete,remarks')->order('apply_time desc')->find();
                    if($applyinfos){
                        $xinxi = '';
                        $remarks = '';
                        $industrys = array();
                        
                        if($applyinfos['checked'] == 2 && $applyinfos['qht'] == 0 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                            $zhuangtai = 6;
                            $xinxi = '您提交的商家申请资料被拒绝';
                            $remarks = $applyinfos['remarks'];
                        }elseif($applyinfos['checked'] == 0 && $applyinfos['qht'] == 0 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                            $zhuangtai = 1;
                            $xinxi = '您提交的入驻申请正在审核中，请耐心等待';
                        }elseif($applyinfos['checked'] == 1 && $applyinfos['qht'] == 0 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                            $zhuangtai = 2;
                            $xinxi = '您提交的入驻申请已审核通过，请等待签署入驻合同协议';
                        }elseif($applyinfos['checked'] == 1 && $applyinfos['qht'] == 1 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                            $zhuangtai = 3;
                            $xinxi = '您的入驻合同协议已签署，请缴纳保证金完成入驻';
                            $rzorders = Db::name('rz_order')->where('user_id',$user_id)->where('apply_id',$applyinfos['id'])->field('id,state')->find();
                            if(!$rzorders || $rzorders['state'] == 0){
                                $industrys = Db::name('industry')->where('id',$applyinfos['indus_id'])->where('is_show',1)->field('id,industry_name,ser_price,remind')->find();
                                if(!$industrys){
                                    $value = array('status'=>400,'mess'=>'信息错误','data'=>array('status'=>400));
                                    return json($value);
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'信息错误','data'=>array('status'=>400));
                                return json($value);
                            }
                        }elseif($applyinfos['checked'] == 1 && $applyinfos['qht'] == 1 && $applyinfos['state'] == 1 && $applyinfos['complete'] == 0){
                            $zhuangtai = 4;
                            $xinxi = '您的入驻流程已完成，平台将及时为您开通商家后台，请耐心等待';
                        }elseif($applyinfos['checked'] == 1 && $applyinfos['qht'] == 1 && $applyinfos['state'] == 1 && $applyinfos['complete'] == 1){
                            $zhuangtai = 5;
                            $xinxi = '您的账号已开通商家，目前暂且支持一个账号申请入驻一家商家';
                        }else{
                            $value = array('status'=>400,'mess'=>'信息错误','data'=>array('status'=>400));
                        }
                        
                        $ruzhuinfos = array('zhuangtai'=>$zhuangtai,'xinxi'=>$xinxi,'remarks'=>$remarks,'industrys'=>$industrys);
                        $value = array('status'=>200,'mess'=>'获取入驻申请状态信息成功','data'=>$ruzhuinfos);
                    }else{
                        $value = array('status'=>400,'mess'=>'找不到相关入驻申请，请先提交入驻申请','data'=>array('status'=>400));
                    }
                }else{
                    $value = $result;
                }
            }else{
                $value = array('status'=>400,'mess'=>'缺少用户令牌','data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    public function orderzhifu(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.zf_type') && in_array(input('post.zf_type'), array(1,2))){
                        $zf_type = input('post.zf_type');
                        
                        $applyinfos = Db::name('apply_info')->where('user_id',$user_id)->order('apply_time desc')->find();
                        if($applyinfos){
                            if($applyinfos['checked'] == 1 && $applyinfos['qht'] == 1 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                                $industrys = Db::name('industry')->where('id',$applyinfos['indus_id'])->where('is_show',1)->field('id,ser_price')->find();
                                if($industrys){
                                    $rzorders = Db::name('rz_order')->where('user_id',$user_id)->where('apply_id',$applyinfos['id'])->field('id,ordernumber,state,total_price')->find();
                                    if($rzorders){
                                        if($rzorders['state'] == 0){
                                            if($rzorders['total_price'] != $industrys['ser_price']){
                                                $ordernumber = 'R'.date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
                                                $dingdan = Db::name('rz_order')->where('ordernumber',$ordernumber)->find();
                                                if(!$dingdan){
                                                    $count = Db::name('rz_order')->update(array('id'=>$rzorders['id'],'ordernumber'=>$ordernumber,'total_price'=>$industrys['ser_price']));
                                                    if($count > 0){
                                                        $rzorders = Db::name('rz_order')->where('id',$rzorders['id'])->where('user_id',$user_id)->field('id,ordernumber,state,total_price')->find();
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>'系统错误，支付失败','data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }else{
                                                    $value = array('status'=>400,'mess'=>'系统错误，支付失败','data'=>array('status'=>400));
                                                    return json($value);
                                                }
                                            }
                                            
                                            $zhuangtai = 'zhuangtai';
                                        }else{
                                            $value = array('status'=>400,'mess'=>'信息错误，支付失败','data'=>array('status'=>400));
                                            return json($value);
                                        }
                                    }else{
                                        $ordernumber = 'R'.date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
                                        $dingdan = Db::name('rz_order')->where('ordernumber',$ordernumber)->find();
                                        if(!$dingdan){
                                            $order_id = Db::name('rz_order')->insertGetId(array(
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
                                            if($order_id){
                                                $rzorders = Db::name('rz_order')->where('id',$order_id)->where('user_id',$user_id)->field('id,ordernumber,state,total_price')->find();
                                            }else{
                                                $value = array('status'=>400,'mess'=>'提交订单失败','data'=>array('status'=>400));
                                                return json($value);
                                            }
                                        }else{
                                            $value = array('status'=>400,'mess'=>'提交订单失败','data'=>array('status'=>400));
                                            return json($value);
                                        }
                                    }
                        
                                    if($rzorders){
                                        $webconfig = $this->webconfig;
                                        
                                        switch($zf_type){
                                            case 1:
                                                $value = array('status'=>400,'mess'=>'支付宝支付暂未开通','data'=>array('status'=>400));
                                                return json($value);
                                                break;
                                            case 2:
                                                //获取订单号
                                                $reoderSn = $rzorders['ordernumber'];
                                                //获取支付金额
                                                $money = $rzorders['total_price'];
                                        
                                                $wx = new Wxpay();
                                                 
                                                $body = '一一孝笑好-商品支付';//支付说明
                                        
                                                $out_trade_no = $reoderSn;//订单号
                                        
                                                $total_fee = $money * 100;//支付金额(乘以100)
                                        
                                                $time_start = time();
                                        
                                                $time_expire = time()+3600;
                                        
                                                $notify_url = $webconfig['weburl'].'/apicloud/Wxpayrzorder/rznotify';//回调地址
                                        
                                                $order = $wx->getPrePayOrder($body, $out_trade_no, $total_fee, $time_start, $time_expire, $notify_url);//调用微信支付的方法
                                                if($order['prepay_id']){
                                                    //判断返回参数中是否有prepay_id
                                                    $order1 = $wx->getOrder($order['prepay_id']);//执行二次签名返回参数
                                                    $value = array('status'=>200,'mess'=>'成功','data'=>array('ordernumber'=>$rzorders['ordernumber'],'wxpayinfos'=>$order1));
                                                }else{
                                                    $value = array('status'=>400,'mess'=>$order['err_code_des'],'data'=>array('status'=>400));
                                                }
                                                break;
                                        }
                                    }else{
                                        $value = array('status'=>400,'mess'=>'信息错误，支付失败','data'=>array('status'=>400));
                                    }
                                }else{
                                    $value = array('status'=>400,'mess'=>'行业信息错误，支付失败','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'资料审核尚未通过','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'请先提交申请资料','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'支付方式错误','data'=>array('status'=>400));
                    }
                }else{
                    $value = $result;
                }
            }else{
                $value = array('status'=>400,'mess'=>'缺少用户令牌','data'=>array('status'=>400));
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
}