<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class ShopsInfo extends Common
{
    public function info(){
        if(request()->isPost()){
            $admin_id = session('admin_id');
            $shop_id = session('shop_id');
            $data = input('post.');
            $data['id'] = $shop_id;
            $result = $this->validate($data,'Shops');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $shops = Db::name('shops')->where('id',$shop_id)->field('id,logo,service_qrcode')->find();
                if($shops){
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
                                    
                                    $latlon = explode(',', $data['latlon']);
                                    
                                    if(!empty($data['pic_id1'])){
//                                        $zssjpics = Db::name('huamu_zspic')->where('id',$data['pic_id1'])->where('admin_id',$admin_id)->find();
//                                        if($zssjpics && $zssjpics['img_url']){
//                                            $data['service_qrcode'] = $zssjpics['img_url'];
//                                        }else{
//                                            if(!empty($shops['service_qrcode'])){
//                                                $data['service_qrcode'] = $shops['service_qrcode'];
//                                            }else{
//                                                $data['service_qrcode'] = '';
//                                            }
//                                        }
                                        $data['service_qrcode'] = $data['pic_id1'];
                                    }else{
                                        if(!empty($shops['service_qrcode'])){
                                            $data['service_qrcode'] = $shops['service_qrcode'];
                                        }else{
                                            $data['service_qrcode'] = '';
                                        }
                                    }
                                    
                                    if(!empty($data['pic_id'])){
//                                        $zssjpics = Db::name('huamu_zspic')->where('id',$data['pic_id'])->where('admin_id',$admin_id)->find();
//                                        if($zssjpics && $zssjpics['img_url']){
//                                            $data['logo'] = $zssjpics['img_url'];
//                                        }else{
//                                            if(!empty($shops['logo'])){
//                                                $data['logo'] = $shops['logo'];
//                                            }else{
//                                                $data['logo'] = '';
//                                            }
//                                        }
                                        $data['logo'] = $data['pic_id'];
                                    }else{
                                        if(!empty($shops['logo'])){
                                            $data['logo'] = $shops['logo'];
                                        }else{
                                            $data['logo'] = '';
                                        }
                                    }
                                    
                                    if(empty($data['wxnum'])){
                                        $data['wxnum'] = '';
                                    }
                                    
                                    if(empty($data['qqnum'])){
                                        $data['qqnum'] = '';
                                    }
                                    
                                    if(empty($data['sertime'])){
                                        $data['sertime'] = '';
                                    }
                                    
                                    if(empty($data['freight'])){
                                        $data['freight'] = 0;
                                    }
                                    
                                    if(empty($data['reduce'])){
                                        $data['reduce'] = 0;
                                    }
                                    
                                    $data['search_keywords'] = str_replace('，', ',', $data['search_keywords']);

                                    $count = Db::name('shops')->update(array(
                                        'shop_name'=>$data['shop_name'],
                                        'shop_desc'=>$data['shop_desc'],
                                        'search_keywords'=>$data['search_keywords'],
                                        'contacts'=>$data['contacts'],
                                        'telephone'=>$data['telephone'],
                                        'wxnum'=>$data['wxnum'],
                                        'qqnum'=>$data['qqnum'],
                                        'logo'=>$data['logo'],
                                        'service_qrcode'=>$data['service_qrcode'],
                                        'sertime'=>$data['sertime'],
                                        'freight'=>$data['freight'],
                                        'reduce'=>$data['reduce'],
                                        'pro_id'=>$data['pro_id'],
                                        'city_id'=>$data['city_id'],
                                        'area_id'=>$data['area_id'],
                                        'address'=>$data['address'],
                                        'bankcard'=>$data['bankcard'],
                                        'bankname'=>$data['bankname'],
                                        'lng'=>$latlon[0],
                                        'lat'=>$latlon[1],
                                        'fenxiao'=>$data['fenxiao'],
                                        'id'=>$data['id']
                                    ));
                                    if($count !== false){
                                        if(!empty($zssjpics) && $zssjpics['img_url']){
                                            Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                                            if($shops['logo'] && file_exists('./'.$shops['logo'])){
                                                @unlink('./'.$shops['logo']);
                                            }
                                        }
                                        $value = array('status'=>1,'mess'=>'保存信息成功');
                                    }else{
                                        $value = array('status'=>0,'mess'=>'保存信息失败');
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
                }else{
                    $value = array('status'=>0,'mess'=>'找不到相关商店信息');
                }
            }
            return json($value);
        }else{
            $admin_id = session('admin_id');
            $shop_id = session('shop_id');
            $shops = Db::name('shops')->alias('a')->field('a.*,b.pro_name,c.city_name,d.area_name')->join('sp_province b','a.pro_id = b.id','LEFT')->join('sp_city c','a.city_id = c.id','LEFT')->join('sp_area d','a.area_id = d.id','LEFT')->where('a.id',$shop_id)->where('a.open_status',1)->find();
            if($shops){
                $zssjpics = Db::name('huamu_zspic')->where('admin_id',$admin_id)->find();
                if($zssjpics && $zssjpics['img_url']){
                    Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                    if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
                        @unlink('./'.$zssjpics['img_url']);
                    }
                }
                
                $prores = Db::name('province')->where('checked',1)->where('pro_zs',1)->field('id,pro_name,zm')->order('sort asc')->select();
                $cityres = Db::name('city')->where('pro_id',$shops['pro_id'])->field('id,city_name,zm')->order('sort asc')->select();
                $areares = Db::name('area')->where('city_id',$shops['city_id'])->field('id,area_name,zm')->select();
                $this->assign('shops',$shops);
                $this->assign('prores',$prores);
                $this->assign('cityres',$cityres);
                $this->assign('areares',$areares);
                return $this->fetch();
            }else{
                $this->error('找不到相关信息','index/index');
            }
        }
    }
    
    //处理上传图片
    public function uploadify1(){
//        $admin_id = session('admin_id');
        $file = request()->file('filedata');
        if($file){
//            $info = $file->validate(['size'=>3145728,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'shop_service');
            $info = aliyunOSS($_FILES);
            if($info){
//                $zssjpics = Db::name('huamu_zspic')->where('admin_id',$admin_id)->find();
//                if($zssjpics && $zssjpics['img_url']){
//                    Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
//                    if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
//                        @unlink('./'.$zssjpics['img_url']);
//                    }
//                }
//                $getSaveName = str_replace("\\","/",$info->getSaveName());
//                $original = 'uploads/shop_service/'.$getSaveName;
                $original = $info['name'];
                // $image = \think\Image::open('./'.$original);
                // $image->thumb(300, 300)->save('./'.$original,null,90);
//                if($zssjpics){
//                    Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>$original));
//                    $zspic_id = $zssjpics['id'];
//                }else{
//                    $zspic_id = Db::name('huamu_zspic')->insertGetId(array('img_url'=>$original,'admin_id'=>$admin_id));
//                }
                $picarr = array('img_url'=>$original);
                $value = array('status'=>1,'path'=>$picarr);
            }else{
                $value = array('status'=>0,'msg'=>$file->getError());
            }
        }else{
            $value = array('status'=>0,'msg'=>'文件不存在');
        }
        return json($value);
    }
    
    //处理上传图片
    public function uploadify(){
//        $admin_id = session('admin_id');
        $file = request()->file('filedata');
        if($file){
            $info = aliyunOSS($_FILES);
//            $info = $file->validate(['size'=>3145728,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'shop_logo');
            if($info){
//                $zssjpics = Db::name('huamu_zspic')->where('admin_id',$admin_id)->find();
//                if($zssjpics && $zssjpics['img_url']){
//                    Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
//                    if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
//                        @unlink('./'.$zssjpics['img_url']);
//                    }
//                }
//                $getSaveName = str_replace("\\","/",$info->getSaveName());
//                $original = 'uploads/shop_logo/'.$getSaveName;
                $original = $info['name'];
//                $image = \think\Image::open('./'.$original);
//                $image->thumb(300, 300)->save('./'.$original,null,90);
//                if($zssjpics){
//                    Db::name('huamu_zspic')->where('id',$zssjpics['id'])->update(array('img_url'=>$original));
//                    $zspic_id = $zssjpics['id'];
//                }else{
//                    $zspic_id = Db::name('huamu_zspic')->insertGetId(array('img_url'=>$original,'admin_id'=>$admin_id));
//                }
                $picarr = array('img_url'=>$original);
                $value = array('status'=>1,'path'=>$picarr);
            }else{
                $value = array('status'=>0,'msg'=>$file->getError());
            }
        }else{
            $value = array('status'=>0,'msg'=>'文件不存在');
        }
        return json($value);
    }
    
    
    //手动删除未保存的上传图片手机
    public function delfile1(){
        if(input('post.zspic_id')){
            $admin_id = session('admin_id');
            $zspic_id = input('post.zspic_id');
            $pics = Db::name('huamu_zspic')->where('id',$zspic_id)->where('admin_id',$admin_id)->find();
            if($pics && $pics['img_url']){
                $count = Db::name('huamu_zspic')->where('id',$pics['id'])->update(array('img_url'=>''));
                if($count > 0){
                    if($pics['img_url'] && file_exists('./'.$pics['img_url'])){
                        @unlink('./'.$pics['img_url']);
                    }
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
        return json($value);
    }
    
    //手动删除未保存的上传图片手机
    public function delfile(){
        if(input('post.zspic_id')){
            $admin_id = session('admin_id');
            $zspic_id = input('post.zspic_id');
            $pics = Db::name('huamu_zspic')->where('id',$zspic_id)->where('admin_id',$admin_id)->find();
            if($pics && $pics['img_url']){
                $count = Db::name('huamu_zspic')->where('id',$pics['id'])->update(array('img_url'=>''));
                if($count > 0){
                    if($pics['img_url'] && file_exists('./'.$pics['img_url'])){
                        @unlink('./'.$pics['img_url']);
                    }
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
        return json($value);
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


}