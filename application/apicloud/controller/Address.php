<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use think\Db;

class Address extends Common{
    //会员地址列表
    public function index(){
        
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.page') && preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
                        $webconfig = $this->webconfig;
                        $perpage = 20;
                        $offset = (input('post.page')-1)*$perpage;
                        $address = Db::name('address')->alias('a')->field('a.id,a.contacts,a.phone,a.address,a.moren,b.pro_name,c.city_name,d.area_name')->join('sp_province b','a.pro_id = b.id','LEFT')->join('sp_city c','a.city_id = c.id','LEFT')->join('sp_area d','a.area_id = d.id','LEFT')->where('a.user_id',$user_id)->where('a.type', 0)->order('a.addtime desc')->limit($offset,$perpage)->select();
                        $value = array('status'=>200,'mess'=>'获取地址信息成功','data'=>$address);
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少页数参数','data'=>array('status'=>400));
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

    //会员地址列表
    public function indexTravel(){

        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.page') && preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
                        $webconfig = $this->webconfig;
                        $perpage = 20;
                        $offset = (input('post.page')-1)*$perpage;
                        $address = Db::name('address')->alias('a')->field('a.id,a.contacts,a.phone,a.address,a.moren,b.pro_name,c.city_name,d.area_name')->join('sp_province b','a.pro_id = b.id','LEFT')->join('sp_city c','a.city_id = c.id','LEFT')->join('sp_area d','a.area_id = d.id','LEFT')->where('a.user_id',$user_id)->where('a.type', 1)->order('a.addtime desc')->limit($offset,$perpage)->select();
                        $value = array('status'=>200,'mess'=>'获取地址信息成功','data'=>$address);
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少页数参数','data'=>array('status'=>400));
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
    
    //获取省份
    public function getpro(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $prores = Db::name('province')->field('id,pro_name,zm')->where('checked',1)->where('pro_zs',1)->order('sort asc')->select();
                    $value = array('status'=>200,'mess'=>'获取省份信息成功','data'=>$prores);
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
    
    //获取城市
    public function getcity(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.pro_id')){
                        $pro_id = input('post.pro_id');
                        $cityres = Db::name('city')->where('pro_id',$pro_id)->where('checked',1)->where('city_zs',1)->field('id,city_name,zm')->order('sort asc')->select();
                        $value = array('status'=>200,'mess'=>'获取城市信息成功','data'=>$cityres);
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少省份参数','data'=>array('status'=>400));
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
    
    //获取区域
    public function getarea(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.city_id')){
                        $city_id = input('post.city_id');
                        $areares = Db::name('area')->where('city_id',$city_id)->where('checked',1)->field('id,area_name,zm')->order('sort asc')->select();
                        $value = array('status'=>200,'mess'=>'获取区域信息成功','data'=>$areares);
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少城市参数','data'=>array('status'=>400));
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

    //添加经销商地址
    public function adds(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $data = input('post.');
                    if(empty($data['moren']) || !in_array($data['moren'], array(0,1))){
                        $data['moren'] = 0;
                    }
                    $yzresult = $this->validate($data,'Address.travel');
                    if(true !== $yzresult){
                        $value = array('status'=>400,'mess'=>$yzresult,'data'=>array('status'=>400));
                    }else{
                        $pro_id = $data['pro_id'];
                        $city_id = $data['city_id'];
                        $area_id = $data['area_id'];

                        // 启动事务
                        Db::startTrans();
                        try{
                            $dz_id = Db::name('address')->insertGetId(array('contacts'=>$data['contacts'],'phone'=>$data['phone'],'moren'=>$data['moren'], 'user_id'=>$user_id, 'type'=>1));
                            if($dz_id && $data['moren'] == 1){
                                $dizhires = Db::name('address')->where('user_id',$user_id)->where('moren',1)->where('id','neq',$dz_id)->select();
                                if($dizhires){
                                    foreach ($dizhires as $v){
                                        Db::name('address')->where('id',$v['id'])->where('user_id',$user_id)->update(array('moren'=>0));
                                    }
                                }
                            }
                            // 提交事务
                            Db::commit();
                            $value = array('status'=>200,'mess'=>'增加地址成功','data'=>array('status'=>200));
                        } catch (\Exception $e) {
                            // 回滚事务
                            Db::rollback();
                            $value = array('status'=>400,'mess'=>'增加地址失败','data'=>array('status'=>400));
                        }
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
    
    //添加经销商地址
    public function add(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $data = input('post.');
                    if(empty($data['moren']) || !in_array($data['moren'], array(0,1))){
                        $data['moren'] = 0;
                    }
                    $yzresult = $this->validate($data,'Address');
                    if(true !== $yzresult){
                        $value = array('status'=>400,'mess'=>$yzresult,'data'=>array('status'=>400));
                    }else{
                        $pro_id = $data['pro_id'];
                        $city_id = $data['city_id'];
                        $area_id = $data['area_id'];
                        
                        $pros = Db::name('province')->where('id',$pro_id)->field('id')->find();
                        if($pros){
                            $citys = Db::name('city')->where('id',$city_id)->where('pro_id',$pros['id'])->field('id')->find();
                            if($citys){
                                $areas = Db::name('area')->where('id',$area_id)->where('city_id',$citys['id'])->field('id')->find();
                                if(!$areas){
                                    $value = array('status'=>400,'mess'=>'请选择区县','data'=>array('status'=>400));
                                    return json($value);
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'请选择城市','data'=>array('status'=>400));
                                return json($value);
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'请选择省份','data'=>array('status'=>400));
                            return json($value);
                        }
                        
                        // 启动事务
                        Db::startTrans();
                        try{
                            $dz_id = Db::name('address')->insertGetId(array('contacts'=>$data['contacts'],'phone'=>$data['phone'],'pro_id'=>$data['pro_id'],'city_id'=>$data['city_id'],'area_id'=>$data['area_id'],'address'=>$data['address'],'user_id'=>$user_id,'addtime'=>time(),'moren'=>$data['moren']));
                            if($dz_id && $data['moren'] == 1){
                                $dizhires = Db::name('address')->where('user_id',$user_id)->where('moren',1)->where('id','neq',$dz_id)->select();
                                if($dizhires){
                                    foreach ($dizhires as $v){
                                        Db::name('address')->where('id',$v['id'])->where('user_id',$user_id)->update(array('moren'=>0));
                                    }
                                }
                            }
                            // 提交事务
                            Db::commit();
                            $value = array('status'=>200,'mess'=>'增加地址成功','data'=>array('status'=>200));
                        } catch (\Exception $e) {
                            // 回滚事务
                            Db::rollback();
                            $value = array('status'=>400,'mess'=>'增加地址失败','data'=>array('status'=>400));
                        }
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
    
    //获取单个地址信息
    public function getinfo(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.dz_id')){
                        $dz_id = input('post.dz_id');
                        $address = Db::name('address')->where('id',$dz_id)->where('user_id',$user_id)->field('id,contacts,phone,pro_id,city_id,area_id,address,moren')->find();
                        if($address){
                            $prores = Db::name('province')->field('id,pro_name,zm')->order('sort asc')->select();
                            $cityres = Db::name('city')->where('pro_id',$address['pro_id'])->field('id,city_name,zm')->select();
                            $areares = Db::name('area')->where('city_id',$address['city_id'])->field('id,area_name,zm')->select();
                            $addressinfo = array('address'=>$address,'prores'=>$prores,'cityres'=>$cityres,'areares'=>$areares);
                            $value = array('status'=>200,'mess'=>'获取地址成功','data'=>$addressinfo);
                        }else{
                            $value = array('status'=>400,'mess'=>'找不到相关地址信息','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少地址信息','data'=>array('status'=>400));
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

    //编辑经销商地址
    public function edit_travel(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.dz_id')){
                        $data = input('post.');
                        if(empty($data['moren']) || !in_array($data['moren'], array(0,1))){
                            $data['moren'] = 0;
                        }
                        $yzresult = $this->validate($data,'Address.travel');
                        if(true !== $yzresult){
                            $value = array('status'=>400,'mess'=>$yzresult,'data'=>array('status'=>400));
                        }else{
                            $addressinfo = Db::name('address')->where('id',$data['dz_id'])->where('user_id',$user_id)->find();
                            if($addressinfo){
//                                $pro_id = $data['pro_id'];
//                                $city_id = $data['city_id'];
//                                $area_id = $data['area_id'];
//
//                                $pros = Db::name('province')->where('id',$pro_id)->field('id')->find();
//                                if($pros){
//                                    $citys = Db::name('city')->where('id',$city_id)->where('pro_id',$pros['id'])->field('id')->find();
//                                    if($citys){
//                                        $areas = Db::name('area')->where('id',$area_id)->where('city_id',$citys['id'])->field('id')->find();
//                                        if(!$areas){
//                                            $value = array('status'=>400,'mess'=>'请选择区县','data'=>array('status'=>400));
//                                            return json($value);
//                                        }
//                                    }else{
//                                        $value = array('status'=>400,'mess'=>'请选择城市','data'=>array('status'=>400));
//                                        return json($value);
//                                    }
//                                }else{
//                                    $value = array('status'=>400,'mess'=>'请选择省份','data'=>array('status'=>400));
//                                    return json($value);
//                                }

                                // 启动事务
                                Db::startTrans();
                                try{
                                    Db::name('address')->update(array('contacts'=>$data['contacts'],'phone'=>$data['phone'],'pro_id'=>$data['pro_id'],'city_id'=>$data['city_id'],'area_id'=>$data['area_id'],'address'=>$data['address'],'moren'=>$data['moren'],'id'=>$data['dz_id']));
                                    if($addressinfo['moren'] == 0 && $data['moren'] == 1){
                                        $dizhires = Db::name('address')->where('user_id',$user_id)->where('moren',1)->where('type', 1)->where('id','neq',$data['dz_id'])->select();
                                        if($dizhires){
                                            foreach ($dizhires as $v){
                                                Db::name('address')->where('id',$v['id'])->where('user_id',$user_id)->update(array('moren'=>0));
                                            }
                                        }
                                    }
                                    // 提交事务
                                    Db::commit();
                                    $value = array('status'=>200,'mess'=>'编辑地址成功','data'=>array('status'=>200));
                                } catch (\Exception $e) {
                                    // 回滚事务
                                    Db::rollback();
                                    $value = array('status'=>400,'mess'=>'编辑地址失败','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'找不到相关地址信息','data'=>array('status'=>400));
                            }
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少地址信息','data'=>array('status'=>400));
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
    
    //编辑经销商地址
    public function edit(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.dz_id')){
                        $data = input('post.');
                        if(empty($data['moren']) || !in_array($data['moren'], array(0,1))){
                            $data['moren'] = 0;
                        }
                        $yzresult = $this->validate($data,'Address');
                        if(true !== $yzresult){
                            $value = array('status'=>400,'mess'=>$yzresult,'data'=>array('status'=>400));
                        }else{
                            $addressinfo = Db::name('address')->where('id',$data['dz_id'])->where('user_id',$user_id)->find();
                            if($addressinfo){
                                $pro_id = $data['pro_id'];
                                $city_id = $data['city_id'];
                                $area_id = $data['area_id'];
                                
                                $pros = Db::name('province')->where('id',$pro_id)->field('id')->find();
                                if($pros){
                                    $citys = Db::name('city')->where('id',$city_id)->where('pro_id',$pros['id'])->field('id')->find();
                                    if($citys){
                                        $areas = Db::name('area')->where('id',$area_id)->where('city_id',$citys['id'])->field('id')->find();
                                        if(!$areas){
                                            $value = array('status'=>400,'mess'=>'请选择区县','data'=>array('status'=>400));
                                            return json($value);
                                        }
                                    }else{
                                        $value = array('status'=>400,'mess'=>'请选择城市','data'=>array('status'=>400));
                                        return json($value);
                                    }
                                }else{
                                    $value = array('status'=>400,'mess'=>'请选择省份','data'=>array('status'=>400));
                                    return json($value);
                                }
                                
                                // 启动事务
                                Db::startTrans();
                                try{
                                    Db::name('address')->update(array('contacts'=>$data['contacts'],'phone'=>$data['phone'],'pro_id'=>$data['pro_id'],'city_id'=>$data['city_id'],'area_id'=>$data['area_id'],'address'=>$data['address'],'moren'=>$data['moren'],'id'=>$data['dz_id']));
                                    if($addressinfo['moren'] == 0 && $data['moren'] == 1){
                                        $dizhires = Db::name('address')->where('user_id',$user_id)->where('moren',1)->where('type', 0)->where('id','neq',$data['dz_id'])->select();
                                        if($dizhires){
                                            foreach ($dizhires as $v){
                                                Db::name('address')->where('id',$v['id'])->where('user_id',$user_id)->update(array('moren'=>0));
                                            }
                                        }
                                    }
                                    // 提交事务
                                    Db::commit();
                                    $value = array('status'=>200,'mess'=>'编辑地址成功','data'=>array('status'=>200));
                                } catch (\Exception $e) {
                                    // 回滚事务
                                    Db::rollback();
                                    $value = array('status'=>400,'mess'=>'编辑地址失败','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'找不到相关地址信息','data'=>array('status'=>400));
                            }
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少地址信息','data'=>array('status'=>400));
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
    
    //设置默认地址
    public function setmoren(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.dz_id')){
                        $dz_id = input('post.dz_id');
                        $addressinfo = Db::name('address')->where('id',$dz_id)->where('user_id',$user_id)->find();
                        if($addressinfo){
                            if($addressinfo['moren'] == 0){
                                // 启动事务
                                Db::startTrans();
                                try{
                                    Db::name('address')->where('id',$dz_id)->where('user_id',$user_id)->update(array('moren'=>1));
                                    $dizhires = Db::name('address')->where('user_id',$user_id)->where('moren',1)->where('id','neq',$dz_id)->select();
                                    if($dizhires){
                                        foreach ($dizhires as $v){
                                            Db::name('address')->where('id',$v['id'])->where('user_id',$user_id)->update(array('moren'=>0));
                                        }
                                    }
                                    // 提交事务
                                    Db::commit();
                                    $value = array('status'=>200,'mess'=>'设置默认地址成功','data'=>array('status'=>200));
                                } catch (\Exception $e) {
                                    // 回滚事务
                                    Db::rollback();
                                    $value = array('status'=>400,'mess'=>'设置默认地址失败','data'=>array('status'=>400));
                                }                                   
                                    
                            }else{
                                $value = array('status'=>400,'mess'=>'该地址已为默认地址，请勿重复设置','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'找不到相关地址信息','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少地址信息','data'=>array('status'=>400));
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
    
    //删除地址信息
    public function del(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.dz_id')){
                        $dz_id = input('post.dz_id');
                        $addressinfo = Db::name('address')->where('id',$dz_id)->where('user_id',$user_id)->find();
                        if($addressinfo){
                            $count = Db::name('address')->where('id',$dz_id)->where('user_id',$user_id)->delete();
                            if($count > 0){
                                $value = array('status'=>200,'mess'=>'删除地址成功','data'=>array('status'=>200));
                            }else{
                                $value = array('status'=>400,'mess'=>'删除地址失败','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'找不到相关地址信息','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少地址信息','data'=>array('status'=>400));
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
