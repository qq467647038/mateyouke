<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use think\Db;

class ThApply extends Common{

    //退换货申请方式信息
    public function getthtype(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.order_num') && input('post.orgoods_id')){
                        $order_num = input('post.order_num');
                        $orgoods_id = input('post.orgoods_id');
                        $orders = Db::name('order')
                            ->where('ordernumber',$order_num)
                            ->where('state',1)
                            ->where('order_status','<>',2)//修改为确认收货后也能申请售后
                            ->where('user_id',$user_id)
                            ->field('id,ordernumber')
                            ->find();
                        if($orders){
                            $orgoods = Db::name('order_goods')->where('id',$orgoods_id)->where('order_id',$orders['id'])->where('th_status','in','0,3,7,8')->field('id,goods_id,goods_name,thumb_url,goods_attr_str,price,goods_num,order_id')->find();
                            if($orgoods){
                                $orgoods['ordernumber'] = $orders['ordernumber'];
                                $webconfig = $this->webconfig;
                                $orgoods['thumb_url'] = $webconfig['weburl'].'/'.$orgoods['thumb_url'];
                                
                                $thcateres = Db::name('thcate')->where('is_show',1)->field('id,cate_name,desc')->order('sort asc')->select();
                                if($thcateres){
                                    $thxinxi = array('orgoods'=>$orgoods,'type'=>$thcateres);
                                    $value = array('status'=>200,'mess'=>'获取相关退换方式成功','data'=>$thxinxi);
                                }else{
                                    $value = array('status'=>400,'mess'=>'获取相关信息失败','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'找不到相关订单商品信息','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'找不到相关订单信息','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少参数','data'=>array('status'=>400));
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
    
    //选择退换服务方式
    public function xzthfw(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.order_num') && input('post.orgoods_id')){
                        if(input('post.th_cate')){
                            $order_num = input('post.order_num');
                            $orgoods_id = input('post.orgoods_id');
                            $th_cate = input('post.th_cate');
    
                            $thcates = Db::name('thcate')->where('id',$th_cate)->where('is_show',1)->find();
                            if($thcates && in_array($thcates['id'],array(1,2,3))){
                                $where = array();
                                if($thcates['id'] == 1){
                                    $where = array('ordernumber'=>$order_num,'state'=>1,'order_status'=>0,'user_id'=>$user_id);
                                }elseif(in_array($thcates['id'],array(2,3))){
                                    $where = array('ordernumber'=>$order_num,'state'=>1,'fh_status'=>1,'order_status'=>0,'user_id'=>$user_id);
                                }
                                $orders = Db::name('order')->where($where)->field('id,ordernumber')->find();
                                if($orders){
                                    $orgoods = Db::name('order_goods')->where('id',$orgoods_id)->where('order_id',$orders['id'])->where('th_status','in','0,3,7,8')->field('id')->find();
                                    if($orgoods){
                                        $orgoods['ordernumber'] = $orders['ordernumber'];
                                        $thxinxi = array('orgoods'=>$orgoods,'th_cate'=>$thcates['id']);
                                        $value = array('status'=>200,'mess'=>'选择退换服务方式成功','data'=>$thxinxi);
                                    }else{
                                        $value = array('status'=>400,'mess'=>'找不到相关订单商品信息','data'=>array('status'=>400));
                                    }
                                }else{
                                    $value = array('status'=>400,'mess'=>'该订单状态不支持此种退换方式','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'找不到退换服务类型','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'请选择退换服务类型','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少参数','data'=>array('status'=>400));
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
    
    //获取申请退款信息
    public function thindex(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.order_num') && input('post.orgoods_id')){
                        if(input('post.th_cate')){
                            $order_num = input('post.order_num');
                            $orgoods_id = input('post.orgoods_id');
                            $th_cate = input('post.th_cate');
                            $thcates = Db::name('thcate')->where('id',$th_cate)->where('is_show',1)->find();
                            if($thcates && in_array($thcates['id'],array(1,2,3))){
                                $tui_canshu = 0;
                                if(input('post.tui_canshu') && input('post.tui_canshu') == 1){
                                    $tui_canshu = input('post.tui_canshu');
                                }
                                
                                $where = array();

                                if($thcates['id'] == 1){
                                    if($tui_canshu){
                                        $where = array('ordernumber'=>$order_num,'state'=>1,'fh_status'=>0,'user_id'=>$user_id);//未发货
                                    }else{
                                        $where = array('ordernumber'=>$order_num,'state'=>1,'fh_status'=>1,'user_id'=>$user_id);//已发货
                                    }
                                }elseif(in_array($thcates['id'],array(2,3))){
                                    //$where = array('ordernumber'=>$order_num,'state'=>1,'fh_status'=>1,'order_status'=>1,'user_id'=>$user_id);
                                    $where = array('ordernumber'=>$order_num,'state'=>1,'fh_status'=>1,'user_id'=>$user_id);
                                }

                                //更改为收货和也是可以退款的
                                $orders = Db::name('order')
                                    ->where($where)
                                    ->where('order_status <> 2')
                                    ->field('id,ordernumber,contacts,telephone,province,city,area,address,dz_id,freight,fh_status')
                                    ->find();

                                if($orders){
                                    $orgoods = Db::name('order_goods')->where('id',$orgoods_id)->where('order_id',$orders['id'])->where('th_status','in','0,3,7,8')->field('id,goods_id,goods_name,thumb_url,goods_attr_str,price,goods_num,order_id,is_free,shop_id')->find();
                                    if($orgoods){
                                        $webconfig = $this->webconfig;
                                        $orgoods['thumb_url'] = $webconfig['weburl'].'/'.$orgoods['thumb_url'];
                                        
                                        $orgoods['ordernumber'] = $orders['ordernumber'];
                                        
                                        if(in_array($thcates['id'],array(1,2))){
                                            if($thcates['id'] == 1 && $orders['fh_status'] == 0){
                                                
                                                $othergdinfos = Db::name('order_goods')->where('id','neq',$orgoods_id)->where('order_id',$orders['id'])->where('th_status',0)->where('is_free',0)->field('id')->find();
                                                if($othergdinfos){
                                                    $orgoods['tui_price'] = $orgoods['price']*$orgoods['goods_num'];
                                                }else{
                                                    if($orgoods['is_free'] == 0){
                                                        $orgoods['tui_price'] = ($orgoods['price']*$orgoods['goods_num'])+$orders['freight'];
                                                    }else{
                                                        $orgoods['tui_price'] = $orgoods['price']*$orgoods['goods_num'];
                                                    }
                                                }
                                            }else{
                                                $orgoods['tui_price'] = $orgoods['price']*$orgoods['goods_num'];
                                            }
                                            
                                            $orgoods['tui_price'] = sprintf("%.2f", $orgoods['tui_price']);
                                        }
                                        
                                        if($tui_canshu){
                                            $thmessres = Db::name('thmess')->where('cate_id',$thcates['id'])->where('leixing',0)->field('id,mess,leixing')->order('sort asc')->select();
                                        }else{
                                            $thmessres = Db::name('thmess')->where('cate_id',$thcates['id'])->field('id,mess,leixing')->order('sort asc')->select();
                                        }
                                        if($thmessres){
                                            $dizhis = array();
                                            if($thcates['id'] == 3){
                                                $dizhis['id'] = $orders['dz_id'];
                                                $dizhis['contacts'] = $orders['contacts'];
                                                $dizhis['telephone'] = $orders['telephone'];
                                                $dizhis['pro_name'] = $orders['province'];
                                                $dizhis['city_name'] = $orders['city'];
                                                $dizhis['area_name'] = $orders['area'];
                                                $dizhis['address'] = $orders['address'];
                                            }
                                            $thxinxi = array('orgoods'=>$orgoods,'th_cate'=>$thcates['id'],'tui_canshu'=>$tui_canshu,'thmessres'=>$thmessres,'dizhis'=>$dizhis);
                                            $value = array('status'=>200,'mess'=>'获取相关退换信息成功','data'=>$thxinxi);
                                        }else{
                                            $value = array('status'=>400,'mess'=>'获取退换原因信息失败','data'=>array('status'=>400));
                                        }
                                    }else{
                                        $value = array('status'=>400,'mess'=>'找不到相关订单商品信息','data'=>array('status'=>400));
                                    }
                                }else{
                                    $value = array('status'=>400,'mess'=>'该订单不支持此种退换服务方式','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'找不到退换服务类型','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'请选择退换服务类型','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少参数','data'=>array('status'=>400));
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
    
    //获取退换货原因
    public function getreason(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];

                    if(input('post.sh_type') && in_array(input('post.sh_type'), array(1,2))){
                        $sh_type = input('post.sh_type');
                        switch ($sh_type){
                            case 1:
                                $reason = Db::name('thmess')->where('cate_id',1)->where('leixing',0)->field('id,mess,leixing')->order('sort asc')->select();
                                break;
                            case 2:
                                $reason = Db::name('thmess')->where('cate_id',1)->where('leixing',1)->field('id,mess,leixing')->order('sort asc')->select();
                                break;
                        }
                        if(!empty($reason)){
                            $value = array('status'=>200,'mess'=>'获取成功','data'=>array('reason'=>$reason));
                        }else{
                            $value = array('status'=>400,'mess'=>'获取失败','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'参数错误','data'=>array('status'=>400));
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
    
    
    
    public function addthorder(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.order_num') && input('post.orgoods_id')){
                        if(input('post.th_cate')){
                            if(input('post.reason_id')){
                                $order_num = input('post.order_num');
                                $orgoods_id = input('post.orgoods_id');
                                $th_cate = input('post.th_cate');
                                $reason_id = input('post.reason_id');
                                
                                $ordouts = Db::name('order_timeout')->where('id',1)->find();
                                if($ordouts){
                                    if(input('post.th_content')){
                                        $th_content = input('post.th_content');
                                        if(mb_strlen($th_content,'utf8') > 50){
                                            $value = array('status'=>400,'mess'=>'退换说明最多50个字符','data'=>array('status'=>400));
                                            return json($value);
                                        }
                                    }else{
                                        $th_content = '';
                                    }
                                    
                                    $thpicres = array();
                                    
/*                                     $files = request()->file('image');
                                     if($files){
                                     if(count($files) <= 6){
                                     foreach($files as $key => $file){
                                     //移动到框架应用根目录/public/uploads/目录下
                                     $info = $file->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'thapply_pic');
                                     if($info){
                                     $original = 'uploads/thapply_pic/'.$info->getSaveName();
                                     $image = \think\Image::open('./'.$original);
                                     $image->thumb(640, 640)->save('./'.$original);
                                     $thpicres[] = $original;
                                     }else{
                                     $value = array('status'=>400,'mess'=>$file->getError(),'data'=>array('status'=>400));
                                     return json($value);
                                     }
                                     }
                                     }else{
                                     $value = array('status'=>400,'mess'=>'最多允许上传6张凭证图片','data'=>array('status'=>400));
                                     return json($value);
                                     }
                                    } */
                                    $images = [];
                                    $param = input('param.');
                                    $images = $param['imageres'];
                                    // halt($images);
                                    if($images != '') $thpicres = $images;
                                    // $file1 = request()->file('image1');
                                    // if($file1){
                                    //     $infoxinxi1 = $file1->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'thapply_pic');
                                    //     if($infoxinxi1){
                                    //         $yuantu1 = 'uploads/thapply_pic/'.$infoxinxi1->getSaveName();
                                    //         $images = \think\Image::open('./'.$yuantu1);
                                    //         $images->thumb(800, 800)->save('./'.$yuantu1,null,90);
                                    //         $thpicres[] = $yuantu1;
                                    //     }else{
                                    //         $value = array('status'=>400,'mess'=>$file1->getError(),'data'=>array('status'=>400));
                                    //         return json($value);
                                    //     }
                                    // }
                                    
                                    
                                    // $file2 = request()->file('image2');
                                    // if($file2){
                                    //     $infoxinxi2 = $file2->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'thapply_pic');
                                    //     if($infoxinxi2){
                                    //         $yuantu2 = 'uploads/thapply_pic/'.$infoxinxi2->getSaveName();
                                    //         $images = \think\Image::open('./'.$yuantu2);
                                    //         $images->thumb(800, 800)->save('./'.$yuantu2,null,90);
                                    //         $thpicres[] = $yuantu2;
                                    //     }else{
                                    //         $value = array('status'=>400,'mess'=>$file2->getError(),'data'=>array('status'=>400));
                                    //         return json($value);
                                    //     }
                                    // }
                                    
                                    // $file3 = request()->file('image3');
                                    // if($file3){
                                    //     $infoxinxi3 = $file3->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'thapply_pic');
                                    //     if($infoxinxi3){
                                    //         $yuantu3 = 'uploads/thapply_pic/'.$infoxinxi3->getSaveName();
                                    //         $images = \think\Image::open('./'.$yuantu3);
                                    //         $images->thumb(800, 800)->save('./'.$yuantu3,null,90);
                                    //         $thpicres[] = $yuantu3;
                                    //     }else{
                                    //         $value = array('status'=>400,'mess'=>$file3->getError(),'data'=>array('status'=>400));
                                    //         return json($value);
                                    //     }
                                    // }
                                    
                                    $thcates = Db::name('thcate')->where('id',$th_cate)->where('is_show',1)->find();
                                    if($thcates && in_array($thcates['id'],array(1,2,3))){
                                        if($thcates['id'] == 3){
                                            if(!input('post.dz_id')){
                                                $value = array('status'=>400,'mess'=>'缺少收货地址信息参数','data'=>array('status'=>400));
                                                return json($value);
                                            }else{
                                                $shdizhi = Db::name('address')->alias('a')->field('a.id,a.contacts,a.phone,a.pro_id,a.city_id,a.area_id,a.address,b.pro_name,c.city_name,d.area_name')->join('sp_province b','a.pro_id = b.id','LEFT')->join('sp_city c','a.city_id = c.id','LEFT')->join('sp_area d','a.area_id = d.id','LEFT')->where('a.id',input('post.dz_id'))->where('a.user_id',$user_id)->find();
                                                if(!$shdizhi){
                                                    $value = array('status'=>400,'mess'=>'找不到相关收货地址信息','data'=>array('status'=>400));
                                                    return json($value);
                                                }
                                            }
                                        }
                                    
                                        $tui_canshu = 0;
                                    
                                        if(input('post.tui_canshu') && input('post.tui_canshu') == 1){
                                            $tui_canshu = input('post.tui_canshu');
                                        }
                                    
                                        $where = array();
                                        if($thcates['id'] == 1){
                                            if($tui_canshu){
                                                $where = array('ordernumber'=>$order_num,'state'=>1,'fh_status'=>0,'user_id'=>$user_id);
                                            }else{
                                                $where = array('ordernumber'=>$order_num,'state'=>1,'fh_status'=>1,'user_id'=>$user_id);
                                            }
                                        }elseif(in_array($thcates['id'],array(2,3))){
                                            $where = array('ordernumber'=>$order_num,'state'=>1,'fh_status'=>1,'user_id'=>$user_id);
                                        }
                                    
                                        $orders = Db::name('order')
                                            ->where($where)
                                            ->where('order_status <> 2')
                                            ->field('id,ordernumber,fh_status,shouhou,freight,order_type,pin_type,pin_id')
                                            ->find();
                                        if($orders){
                                            if($orders['order_type'] == 2){
                                                $pintuans = Db::name('pintuan')->where('id',$orders['pin_id'])->where('state',1)->field('id,pin_num,tuan_num,pin_status,timeout')->find();
                                                if($pintuans){
                                                    if($pintuans['pin_status'] != 1){
                                                        $value = array('status'=>400,'mess'=>'参数错误，发起售后申请失败','data'=>array('status'=>400));
                                                        return json($value);
                                                    }
                                                }else{
                                                    $value = array('status'=>400,'mess'=>'参数错误，发起售后申请失败','data'=>array('status'=>400));
                                                    return json($value);
                                                }
                                            }
                                    
                                            if($thcates['id'] == 1){
                                                if($orders['fh_status'] == 0){
                                                    $sh_type = 1;
                                                }else{
                                                    if(!input('post.sh_type') || !in_array(input('post.sh_type'), array(1,2))){
                                                        $value = array('status'=>400,'mess'=>'缺少收货状态参数','data'=>array('status'=>400));
                                                        return json($value);
                                                    }else{
                                                        $sh_type = input('post.sh_type');
                                                    }
                                                }
                                            }else{
                                                $sh_type = 2;
                                            }
                                    
                                            if($sh_type == 2){
                                                if($orders['fh_status'] == 0){
                                                    $value = array('status'=>400,'mess'=>'该订单不支持此种退换服务方式','data'=>array('status'=>400));
                                                    return json($value);
                                                }
                                                $reasons = Db::name('thmess')->where('id',$reason_id)->where('leixing',1)->where('cate_id',$thcates['id'])->find();
                                            }elseif($sh_type == 1){
                                                $reasons = Db::name('thmess')->where('id',$reason_id)->where('leixing',0)->where('cate_id',$thcates['id'])->find();
                                            }
                                    
                                            if($reasons){
                                                $orgoods = Db::name('order_goods')->where('id',$orgoods_id)->where('order_id',$orders['id'])->where('th_status','in','0,3,7,8')->field('id,goods_id,goods_name,thumb_url,goods_attr_str,price,goods_num,order_id,is_free,shop_id')->find();
                                                if($orgoods){
                                                    if(in_array($thcates['id'], array(1,2))){
                                                        if($thcates['id'] == 1 && $orders['fh_status'] == 0){
                                                            $othergdinfos = Db::name('order_goods')->where('id','neq',$orgoods_id)->where('order_id',$orders['id'])->where('th_status',0)->where('is_free',0)->field('id')->find();
                                                            if($othergdinfos){
                                                                $tui_price = $orgoods['price']*$orgoods['goods_num'];
                                                            }else{
                                                                if($orgoods['is_free'] == 0){
                                                                    $tui_price = ($orgoods['price']*$orgoods['goods_num'])+$orders['freight'];
                                                                }else{
                                                                    $tui_price = $orgoods['price']*$orgoods['goods_num'];
                                                                }
                                                            }
                                                        }else{
                                                            $tui_price = $orgoods['price']*$orgoods['goods_num'];
                                                        }
                                    
                                                        $tui_price = sprintf("%.2f", $tui_price);
                                                    }else{
                                                        $tui_price = 0;
                                                    }
                                    
                                                    $th_number = 'T'.date('YmdHis').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
                                                    $applys = Db::name('th_apply')->where('th_number',$th_number)->find();
                                                    if(!$applys){
                                                        $data = array();
                                                        $data['th_number'] = $th_number;
                                                        $data['thfw_id'] = $thcates['id'];
                                                        $data['sh_type'] = $sh_type;
                                                        $data['th_reason'] = $reasons['mess'];
                                                        if($th_content){
                                                            $data['th_content'] = $th_content;
                                                        }
                                                        $data['tui_price'] = $tui_price;
                                                        $data['tui_num'] = $orgoods['goods_num'];
                                                        if($thcates['id'] == 3){
                                                            $data['contacts'] = $shdizhi['contacts'];
                                                            $data['telephone'] = $shdizhi['phone'];
                                                            $data['dz_id'] = $shdizhi['id'];
                                                            $data['pro_id'] = $shdizhi['pro_id'];
                                                            $data['city_id'] = $shdizhi['city_id'];
                                                            $data['area_id'] = $shdizhi['area_id'];
                                                            $data['province'] = $shdizhi['pro_name'];
                                                            $data['city'] = $shdizhi['city_name'];
                                                            $data['area'] = $shdizhi['area_name'];
                                                            $data['shengshiqu'] = $shdizhi['pro_name'].' '.$shdizhi['city_name'].' '.$shdizhi['area_name'];
                                                            $data['address'] = $shdizhi['address'];
                                                        }
                                                        $data['orgoods_id'] = $orgoods['id'];
                                                        $data['order_id'] = $orgoods['order_id'];
                                                        $data['user_id'] = $user_id;
                                                        $data['apply_status'] = 0;
                                                        $data['apply_time'] = time();
                                                        $data['check_timeout'] = time()+$ordouts['check_timeout']*24*3600;
                                                        $data['shop_id'] = $orgoods['shop_id'];
                                    
                                                        // 启动事务
                                                        Db::startTrans();
                                                        try{
                                                            $th_id = Db::name('th_apply')->insertGetId($data);
                                                            if($th_id){
                                                                if($orders['shouhou'] == 0){
                                                                    Db::name('order')->where('id',$orders['id'])->update(array('shouhou'=>1));
                                                                }
                                    
                                                                if(in_array($thcates['id'], array(1,2))){
                                                                    $th_status = 1;
                                                                }else{
                                                                    $th_status = 5;
                                                                }
                                    
                                                                Db::name('order_goods')->where('id',$orgoods['id'])->update(array('th_status'=>$th_status));
                                    
                                                                if($thpicres){
                                                                    foreach ($thpicres as $v){
                                                                        Db::name('thapply_pic')->insert(array('th_id'=>$th_id,'img_url'=>$v));
                                                                    }
                                                                }
                                                            }
                                                            // 提交事务
                                                            Db::commit();
                                                            $value = array('status'=>200,'mess'=>'申请'.$thcates['cate_name'].'成功','data'=>array('status'=>200));
                                                        } catch (\Exception $e) {
                                                            // 回滚事务
                                                            Db::rollback();
                                                            $value = array('status'=>400,'mess'=>'申请失败','data'=>array('status'=>400));
                                                        }
                                                    }else{
                                                        $value = array('status'=>400,'mess'=>'申请失败，请重试','data'=>array('status'=>400));
                                                    }
                                                }else{
                                                    $value = array('status'=>400,'mess'=>'找不到相关订单商品信息','data'=>array('status'=>400));
                                                }
                                            }else{
                                                $value = array('status'=>400,'mess'=>'退款原因不存在','data'=>array('status'=>400));
                                            }
                                        }else{
                                            $value = array('status'=>400,'mess'=>'该订单不支持此种退换服务方式','data'=>array('status'=>400));
                                        }
                                    }else{
                                        $value = array('status'=>400,'mess'=>'找不到退换服务类型','data'=>array('status'=>400));
                                    }
                                }else{
                                    $value = array('status'=>400,'mess'=>'申请失败','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'请选择退换原因','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'请选择退换服务类型','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少参数','data'=>array('status'=>400));
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
    
    

    
    
    
    
    
    
    //拼团未完成订单申请退款
    public function pinapplytui(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.order_num')){
                        $order_num = input('post.order_num');
                        $orders = Db::name('order')->where('ordernumber',$order_num)->where('user_id',$user_id)->where('state',1)->where('fh_status',0)->where('order_status',0)->where('order_type',2)->field('id,ordernumber,total_price,order_type,pin_type,pin_id')->find();
                        if($orders){
                            $pintuans = Db::name('pintuan')->where('id',$orders['pin_id'])->where('state',1)->where('pin_status',0)->field('id,pin_num,tuan_num,pin_status,timeout')->find();
                            if($pintuans){
                                // 启动事务
                                Db::startTrans();
                                try{
                                    $order_assembles = Db::name('order_assemble')->lock(true)->where('pin_id',$pintuans['id'])->where('order_id',$orders['id'])->where('user_id',$user_id)->where('state',1)->where('tui_status',0)->find();
                                    if($order_assembles){
                                        Db::name('order')->update(array('order_status'=>2,'can_time'=>time(),'id'=>$orders['id']));
                                        Db::name('order_assemble')->where('id',$order_assembles['id'])->update(array('tui_status'=>1,'dakuan_status'=>1,'dakuan_time'=>time()));
                                        Db::name('pintuan')->where('id',$pintuans['id'])->setDec('tuan_num',1);
                                        
                                        $tuan_num = Db::name('pintuan')->where('id',$pintuans['id'])->value('tuan_num');
                                        if($tuan_num <= 0){
                                            Db::name('pintuan')->where('id',$pintuans['id'])->update(array('pin_status'=>2));
                                        }
                                        
                                        $wallets = Db::name('wallet')->where('user_id',$user_id)->find();
                                        $wallet_info = $wallets;
                                        if($wallets){
                                            Db::name('wallet')->where('id',$wallets['id'])->setInc('price', $orders['total_price']);

                                            $detail = [
                                                'de_type'=>1,
                                                'sr_type'=>2,
                                                'before_price'=> $wallet_info['price'],
                                                'price'=>$orders['total_price'],
                                                'after_price'=> $wallet_info['price']+$orders['total_price'],
                                                'order_type'=>4,
                                                'order_id'=>$orders['id'],
                                                'user_id'=>$user_id,
                                                'wat_id'=>$wallets['id'],
                                                'time'=>time()
                                            ];
                                            $this->addDetail($detail);
//                                            Db::name('detail')->insert($detail);
                                        }
                                        
                                        $orgoods = Db::name('order_goods')->where('order_id',$orders['id'])->field('goods_id,goods_attr_id,goods_num,hd_type,hd_id')->find();
                                        
                                        if($orgoods){
                                            Db::name('product')->where('goods_id',$orgoods['goods_id'])->where('goods_attr',$orgoods['goods_attr_id'])->setInc('goods_number', $orgoods['goods_num']);
                                        }
                                    }else{
                                        $value = array('status'=>400,'mess'=>'找不到相关拼团信息','data'=>array('status'=>400));
                                        return json($value);
                                    }
                                
                                    // 提交事务
                                    Db::commit();
                                    $value = array('status'=>200,'mess'=>'退款成功','data'=>array('status'=>200));
                                } catch (\Exception $e) {
                                    // 回滚事务
                                    Db::rollback();
                                    $value = array('status'=>400,'mess'=>'退款失败','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'找不到相关拼团信息','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'找不到相关订单信息','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少参数','data'=>array('status'=>400));
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
    
    //获取退换货详情接口
    public function applyinfo(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.order_num')){
                        if(input('post.orgoods_id')){
                            $order_num = input('post.order_num');
                            $orgoods_id = input('post.orgoods_id');
                            $orders = Db::name('order')->where('ordernumber',$order_num)->where('state',1)->where('user_id',$user_id)->field('id,ordernumber,fh_status')->find();
                            if($orders){
                                $ordouts = Db::name('order_timeout')->where('id',1)->find();
                                $webconfig = $this->webconfig;
                                
                                $orgoods = Db::name('order_goods')->where('id',$orgoods_id)->where('order_id',$orders['id'])->where('th_status','neq',0)->field('id,goods_id,goods_name,thumb_url,goods_attr_str,price,goods_num,order_id')->find();
                                if($orgoods){
                                    $orgoods['ordernumber'] = $orders['ordernumber'];
                                    $orgoods['fh_status'] = $orders['fh_status'];
                                    $orgoods['thumb_url'] = $webconfig['weburl'].'/'.$orgoods['thumb_url'];
                                    
                                    $applys = Db::name('th_apply')->where('orgoods_id',$orgoods['id'])->where('order_id',$orders['id'])->where('user_id',$user_id)->field('id,th_number,thfw_id,sh_type,th_reason,th_content,tui_price,tui_num,contacts,telephone,shengshiqu,address,orgoods_id,order_id,apply_status,apply_time,agree_time,refuse_time,refuse_reason,dcfh_status,dcfh_time,sh_status,sh_time,fh_status,fh_time,shou_status,shou_time,che_time,com_time,check_timeout,shoptui_timeout,yhfh_timeout,yhshou_timeout,shop_id')->order('apply_time desc')->find();
                                    if($applys){
                                        $thapply_id = $applys['id'];
                                        
                                        if($applys['apply_status'] == 0 && $applys['check_timeout'] <= time()){
                                            // 启动事务
                                            Db::startTrans();
                                            try{
                                                if($applys['thfw_id'] == 1){
                                                    $shoptui_timeout = time()+$ordouts['shoptui_timeout']*24*3600;
                                                    Db::name('th_apply')->update(array('apply_status'=>1,'agree_time'=>time(),'shoptui_timeout'=>$shoptui_timeout,'id'=>$applys['id']));
                                                }elseif(in_array($applys['thfw_id'], array(2,3))){
                                                    $yhfh_timeout = time()+$ordouts['yhfh_timeout']*24*3600;
                                                    Db::name('th_apply')->update(array('apply_status'=>1,'agree_time'=>time(),'yhfh_timeout'=>$yhfh_timeout,'id'=>$applys['id']));
                                                }
                                        
                                                if(in_array($applys['thfw_id'], array(1,2))){
                                                    $th_status = 2;
                                                }elseif($applys['thfw_id'] == 3){
                                                    $th_status = 6;
                                                }
                                        
                                                if(!empty($th_status)){
                                                    Db::name('order_goods')->update(array('th_status'=>$th_status,'id'=>$applys['orgoods_id']));
                                                }
                                        
                                                // 提交事务
                                                Db::commit();
                                                $applys = Db::name('th_apply')->where('id',$thapply_id)->where('user_id',$user_id)->field('id,th_number,thfw_id,sh_type,th_reason,th_content,tui_price,tui_num,contacts,telephone,shengshiqu,address,orgoods_id,order_id,apply_status,apply_time,agree_time,refuse_time,refuse_reason,dcfh_status,dcfh_time,sh_status,sh_time,fh_status,fh_time,shou_status,shou_time,che_time,com_time,check_timeout,shoptui_timeout,yhfh_timeout,yhshou_timeout,shop_id')->find();
                                            } catch (\Exception $e) {
                                                // 回滚事务
                                                Db::rollback();
                                                $value = array('status'=>400,'mess'=>'系统错误，请重试','data'=>array('status'=>400));
                                                return json($value);
                                            }
                                        }elseif($applys['thfw_id'] == 1 && $applys['apply_status'] == 1 && $applys['shoptui_timeout'] <= time()){
                                            $orgoods = Db::name('order_goods')->where('id',$applys['orgoods_id'])->field('goods_id,goods_attr_id,hd_type,hd_id')->find();
                                            if($orgoods){
                                                // 启动事务
                                                Db::startTrans();
                                                try{
                                                    Db::name('th_apply')->update(array('apply_status'=>3,'com_time'=>time(),'id'=>$applys['id']));
                                                    Db::name('order_goods')->update(array('th_status'=>4,'id'=>$applys['orgoods_id']));
                                                    $ordergoods = Db::name('order_goods')->where('id','neq',$applys['orgoods_id'])->where('order_id',$applys['order_id'])->where('th_status','in','0,1,2,3,5,6,7,8')->field('id')->find();
                                                    if(!$ordergoods){
                                                        $orders = Db::name('order')->where('id',$applys['order_id'])->find();
                                                        if($orders){
                                                            Db::name('order')->where('id',$applys['order_id'])->update(array('order_status'=>2,'shouhou'=>0,'can_time'=>time()));
                                                            if($orders['coupon_id']){
                                                                Db::name('member_coupon')->where('user_id',$orders['user_id'])->where('coupon_id',$orders['coupon_id'])->where('is_sy',1)->where('shop_id',$orders['shop_id'])->update(array('is_sy'=>0));
                                                            }
                                                        }
                                                    }else{
                                                        $ordergoodres = Db::name('order_goods')->where('id','neq',$applys['orgoods_id'])->where('order_id',$applys['order_id'])->where('th_status','in','1,2,3,5,6,7')->field('id')->find();
                                        
                                                        if($ordergoodres){
                                                            $shouhou = 1;
                                                        }else{
                                                            $shouhou = 0;
                                                        }
                                        
                                                        if($shouhou == 0){
                                                            $orders = Db::name('order')->where('id',$applys['order_id'])->find();
                                                            if($orders){
                                                                $zdsh_time = time()+$ordouts['zdqr_sh_time']*24*3600;
                                                                Db::name('order')->where('id',$applys['order_id'])->update(array('shouhou'=>0,'zdsh_time'=>$zdsh_time));
                                                            }
                                                        }
                                                    }
                                        
                                                    if(in_array($orgoods['hd_type'],array(0,2,3))){
                                                        $prokc = Db::name('product')->where('goods_id',$orgoods['goods_id'])->where('goods_attr',$orgoods['goods_attr_id'])->find();
                                                        if($prokc){
                                                            Db::name('product')->where('goods_id',$orgoods['goods_id'])->where('goods_attr',$orgoods['goods_attr_id'])->setInc('goods_number', $applys['tui_num']);
                                                        }
                                                    }elseif($orgoods['hd_type'] == 1){
                                                        $hdactivitys = Db::name('rush_activity')->where('id',$orgoods['hd_id'])->find();
                                                        if($hdactivitys){
                                                            Db::name('rush_activity')->where('id',$orgoods['hd_id'])->setInc('kucun',$applys['tui_num']);
                                                            Db::name('rush_activity')->where('id',$orgoods['hd_id'])->setDec('sold',$applys['tui_num']);
                                                        }
                                                    }
                                        
                                                    // 提交事务
                                                    Db::commit();
                                                    $applys = Db::name('th_apply')->where('id',$thapply_id)->where('user_id',$user_id)->field('id,th_number,thfw_id,sh_type,th_reason,th_content,tui_price,tui_num,contacts,telephone,shengshiqu,address,orgoods_id,order_id,apply_status,apply_time,agree_time,refuse_time,refuse_reason,dcfh_status,dcfh_time,sh_status,sh_time,fh_status,fh_time,shou_status,shou_time,che_time,com_time,check_timeout,shoptui_timeout,yhfh_timeout,yhshou_timeout,shop_id')->find();
                                                } catch (\Exception $e) {
                                                    // 回滚事务
                                                    Db::rollback();
                                                    $value = array('status'=>400,'mess'=>'系统错误，请重试','data'=>array('status'=>400));
                                                    return json($value);
                                                }
                                            }
                                        }elseif($applys['thfw_id'] == 2 && $applys['apply_status'] == 1 && $applys['dcfh_status'] == 1 && $applys['sh_status'] == 1 && $applys['shoptui_timeout'] <= time()){
                                            $orgoods = Db::name('order_goods')->where('id',$applys['orgoods_id'])->field('goods_id,goods_attr_id,hd_type,hd_id')->find();
                                            if($orgoods){
                                                // 启动事务
                                                Db::startTrans();
                                                try{
                                                    Db::name('th_apply')->update(array('apply_status'=>3,'com_time'=>time(),'id'=>$applys['id']));
                                                    Db::name('order_goods')->update(array('th_status'=>4,'id'=>$applys['orgoods_id']));
                                                    $ordergoods = Db::name('order_goods')->where('id','neq',$applys['orgoods_id'])->where('order_id',$applys['order_id'])->where('th_status','in','0,1,2,3,5,6,7,8')->field('id')->find();
                                                    if(!$ordergoods){
                                                        $orders = Db::name('order')->where('id',$applys['order_id'])->find();
                                                        if($orders){
                                                            Db::name('order')->where('id',$applys['order_id'])->update(array('order_status'=>2,'shouhou'=>0,'can_time'=>time()));
                                                            if($orders['coupon_id']){
                                                                Db::name('member_coupon')->where('user_id',$orders['user_id'])->where('coupon_id',$orders['coupon_id'])->where('is_sy',1)->where('shop_id',$orders['shop_id'])->update(array('is_sy'=>0));
                                                            }
                                                        }
                                                    }else{
                                                        $ordergoodres = Db::name('order_goods')->where('id','neq',$applys['orgoods_id'])->where('order_id',$applys['order_id'])->where('th_status','in','1,2,3,5,6,7')->field('id')->find();
                                        
                                                        if($ordergoodres){
                                                            $shouhou = 1;
                                                        }else{
                                                            $shouhou = 0;
                                                        }
                                        
                                                        if($shouhou == 0){
                                                            $orders = Db::name('order')->where('id',$applys['order_id'])->find();
                                                            if($orders){
                                                                $zdsh_time = time()+$ordouts['zdqr_sh_time']*24*3600;
                                                                Db::name('order')->where('id',$applys['order_id'])->update(array('shouhou'=>0,'zdsh_time'=>$zdsh_time));
                                                            }
                                                        }
                                                    }
                                        
                                                    if(in_array($orgoods['hd_type'],array(0,2,3))){
                                                        $prokc = Db::name('product')->where('goods_id',$orgoods['goods_id'])->where('goods_attr',$orgoods['goods_attr_id'])->find();
                                                        if($prokc){
                                                            Db::name('product')->where('goods_id',$orgoods['goods_id'])->where('goods_attr',$orgoods['goods_attr_id'])->setInc('goods_number', $applys['tui_num']);
                                                        }
                                                    }elseif($orgoods['hd_type'] == 1){
                                                        $hdactivitys = Db::name('rush_activity')->where('id',$orgoods['hd_id'])->find();
                                                        if($hdactivitys){
                                                            Db::name('rush_activity')->where('id',$orgoods['hd_id'])->setInc('kucun',$applys['tui_num']);
                                                            Db::name('rush_activity')->where('id',$orgoods['hd_id'])->setDec('sold',$applys['tui_num']);
                                                        }
                                                    }
                                        
                                                    // 提交事务
                                                    Db::commit();
                                                    $applys = Db::name('th_apply')->where('id',$thapply_id)->where('user_id',$user_id)->field('id,th_number,thfw_id,sh_type,th_reason,th_content,tui_price,tui_num,contacts,telephone,shengshiqu,address,orgoods_id,order_id,apply_status,apply_time,agree_time,refuse_time,refuse_reason,dcfh_status,dcfh_time,sh_status,sh_time,fh_status,fh_time,shou_status,shou_time,che_time,com_time,check_timeout,shoptui_timeout,yhfh_timeout,yhshou_timeout,shop_id')->find();
                                                } catch (\Exception $e) {
                                                    // 回滚事务
                                                    Db::rollback();
                                                    $value = array('status'=>400,'mess'=>'系统错误，请重试','data'=>array('status'=>400));
                                                    return json($value);
                                                }
                                            }
                                        }elseif(in_array($applys['thfw_id'], array(2,3)) && $applys['apply_status'] == 1 && $applys['dcfh_status'] == 0 && $applys['yhfh_timeout'] <= time()){
                                            $orders = Db::name('order')->where('id',$applys['order_id'])->where('state',1)->where('fh_status',1)->field('id')->find();
                                            if($orders){
                                                // 启动事务
                                                Db::startTrans();
                                                try{
                                                    Db::name('th_apply')->update(array('apply_status'=>4,'che_time'=>time(),'id'=>$applys['id']));
                                                    Db::name('order_goods')->update(array('th_status'=>0,'id'=>$applys['orgoods_id']));
                                        
                                                    $ordergoods = Db::name('order_goods')->where('id','neq',$applys['orgoods_id'])->where('order_id',$applys['order_id'])->where('th_status','in','1,2,3,5,6,7')->field('id')->find();
                                        
                                                    if($ordergoods){
                                                        $shouhou = 1;
                                                    }else{
                                                        $shouhou = 0;
                                                    }
                                        
                                                    if($shouhou == 0){
                                                        $orders = Db::name('order')->where('id',$applys['order_id'])->find();
                                                        if($orders){
                                                            $zdsh_time = time()+$ordouts['zdqr_sh_time']*24*3600;
                                                            Db::name('order')->where('id',$applys['order_id'])->update(array('shouhou'=>0,'zdsh_time'=>$zdsh_time));
                                                        }
                                                    }
                                        
                                                    // 提交事务
                                                    Db::commit();
                                                    $applys = Db::name('th_apply')->where('id',$thapply_id)->where('user_id',$user_id)->field('id,th_number,thfw_id,sh_type,th_reason,th_content,tui_price,tui_num,contacts,telephone,shengshiqu,address,orgoods_id,order_id,apply_status,apply_time,agree_time,refuse_time,refuse_reason,dcfh_status,dcfh_time,sh_status,sh_time,fh_status,fh_time,shou_status,shou_time,che_time,com_time,check_timeout,shoptui_timeout,yhfh_timeout,yhshou_timeout,shop_id')->find();
                                                } catch (\Exception $e) {
                                                    // 回滚事务
                                                    Db::rollback();
                                                    $value = array('status'=>400,'mess'=>'系统错误，请重试','data'=>array('status'=>400));
                                                    return json($value);
                                                }
                                            }
                                        }elseif($applys['thfw_id'] == 3 && $applys['apply_status'] == 1 && $applys['dcfh_status'] == 1 && $applys['sh_status'] == 1 && $applys['fh_status'] == 1 && $applys['shou_status'] == 0 && $applys['yhshou_timeout'] <= time()){
                                            // 启动事务
                                            Db::startTrans();
                                            try{
                                                Db::name('th_apply')->update(array('shou_status'=>1,'apply_status'=>3,'shou_time'=>time(),'com_time'=>time(),'id'=>$applys['id']));
                                                Db::name('order_goods')->update(array('th_status'=>8,'id'=>$applys['orgoods_id']));
                                        
                                                $ordergoods = Db::name('order_goods')->where('id','neq',$applys['orgoods_id'])->where('order_id',$applys['order_id'])->where('th_status','in','1,2,3,5,6,7')->field('id,th_status')->find();
                                        
                                                if($ordergoods){
                                                    $shouhou = 1;
                                                }else{
                                                    $shouhou = 0;
                                                }
                                        
                                                if($shouhou == 0){
                                                    $orders = Db::name('order')->where('id',$applys['order_id'])->find();
                                                    if($orders){
                                                        $zdsh_time = time()+$ordouts['zdqr_sh_time']*24*3600;
                                                        Db::name('order')->where('id',$applys['order_id'])->update(array('shouhou'=>0,'zdsh_time'=>$zdsh_time));
                                                    }
                                                }
                                        
                                                // 提交事务
                                                Db::commit();
                                                $applys = Db::name('th_apply')->where('id',$thapply_id)->where('user_id',$user_id)->field('id,th_number,thfw_id,sh_type,th_reason,th_content,tui_price,tui_num,contacts,telephone,shengshiqu,address,orgoods_id,order_id,apply_status,apply_time,agree_time,refuse_time,refuse_reason,dcfh_status,dcfh_time,sh_status,sh_time,fh_status,fh_time,shou_status,shou_time,che_time,com_time,check_timeout,shoptui_timeout,yhfh_timeout,yhshou_timeout,shop_id')->find();
                                            } catch (\Exception $e) {
                                                // 回滚事务
                                                Db::rollback();
                                                $value = array('status'=>400,'mess'=>'系统错误，请重试','data'=>array('status'=>400));
                                                return json($value);
                                            }
                                        }
                                        
                                        
                                        $applys['apply_time'] = date('Y-m-d H:i:s',$applys['apply_time']);
                                        if(!empty($applys['agree_time'])){
                                            $applys['agree_time'] = date('Y-m-d H:i:s',$applys['agree_time']);
                                        }
                                        
                                        if(!empty($applys['refuse_time'])){
                                            $applys['refuse_time'] = date('Y-m-d H:i:s',$applys['refuse_time']);
                                        }
                                        
                                        if(!empty($applys['dcfh_time'])){
                                            $applys['dcfh_time'] = date('Y-m-d H:i:s',$applys['dcfh_time']);
                                        }
                                        
                                        if(!empty($applys['sh_time'])){
                                            $applys['sh_time'] = date('Y-m-d H:i:s',$applys['sh_time']);
                                        }
                                        
                                        if(!empty($applys['fh_time'])){
                                            $applys['fh_time'] = date('Y-m-d H:i:s',$applys['fh_time']);
                                        }
                                        
                                        if(!empty($applys['shou_time'])){
                                            $applys['shou_time'] = date('Y-m-d H:i:s',$applys['shou_time']);
                                        }
                                        
                                        if(!empty($applys['che_time'])){
                                            $applys['che_time'] = date('Y-m-d H:i:s',$applys['che_time']);
                                        }
                                        
                                        if(!empty($applys['com_time'])){
                                            $applys['com_time'] = date('Y-m-d H:i:s',$applys['com_time']);
                                        }
                                        
                                        $applys['thfw'] = Db::name('thcate')->where('id',$applys['thfw_id'])->value('cate_name');
                                        
                                        if($applys['apply_status'] == 0){
                                            $applys['zhuangtai'] = '待商家同意';
                                            $applys['filter'] = 1;
                                            $applys['sycheck_timeout'] = time2string($applys['check_timeout']-time());
                                        }elseif(in_array($applys['apply_status'], array(1,3))){
                                            switch ($applys['thfw_id']){
                                                case 1:
                                                    if($applys['apply_status'] == 1){
                                                        $applys['zhuangtai'] = '商家已同意（退款中）';
                                                        $applys['filter'] = 2;
                                                        $applys['syshoptui_timeout'] = time2string($applys['shoptui_timeout']-time());
                                                    }elseif($applys['apply_status'] == 3){
                                                        $applys['zhuangtai'] = '退款已完成';
                                                        $applys['filter'] = 3;
                                                    }
                                                    break;
                                                case 2:
                                                    if($applys['apply_status'] == 1){
                                                        if($applys['dcfh_status'] == 0){
                                                            $applys['zhuangtai'] = '商家已同意（填写退货物流信息）';
                                                            $applys['filter'] = 4;
                                                            $applys['syyhfh_timeout'] = time2string($applys['yhfh_timeout']-time());
                                                        }elseif($applys['dcfh_status'] == 1 && $applys['sh_status'] == 0){
                                                            $applys['zhuangtai'] = '等待商家确认收货（退货退款中）';
                                                            $applys['filter'] = 5;
                                                        }elseif($applys['dcfh_status'] == 1 && $applys['sh_status'] == 1){
                                                            $applys['zhuangtai'] = '商家已收货（退货退款中）';
                                                            $applys['filter'] = 6;
                                                            $applys['syshoptui_timeout'] = time2string($applys['shoptui_timeout']-time());
                                                        }
                                                    }elseif($applys['apply_status'] == 3){
                                                        $applys['zhuangtai'] = '退货退款已完成';
                                                        $applys['filter'] = 7;
                                                    }
                                                    break;
                                                case 3:
                                                    if($applys['apply_status'] == 1){
                                                        if($applys['dcfh_status'] == 0){
                                                            $applys['zhuangtai'] = '商家已同意（填写退货物流信息）';
                                                            $applys['filter'] = 8;
                                                            $applys['syyhfh_timeout'] = time2string($applys['yhfh_timeout']-time());
                                                        }elseif($applys['dcfh_status'] == 1 && $applys['sh_status'] == 0){
                                                            $applys['zhuangtai'] = '等待商家确认收货（换货中）';
                                                            $applys['filter'] = 9;
                                                        }elseif($applys['dcfh_status'] == 1 && $applys['sh_status'] == 1 && $applys['fh_status'] == 0){
                                                            $applys['zhuangtai'] = '商家已收货（换货中）';
                                                            $applys['filter'] = 10;
                                                        }elseif($applys['dcfh_status'] == 1 && $applys['sh_status'] == 1 && $applys['fh_status'] == 1){
                                                            $applys['zhuangtai'] = '商家已发货（换货中）';
                                                            $applys['filter'] = 11;
                                                            $applys['syyhshou_timeout'] = time2string($applys['yhshou_timeout']-time());
                                                        }
                                                    }elseif($applys['apply_status'] == 3){
                                                        $applys['zhuangtai'] = '换货已完成';
                                                        $applys['filter'] = 12;
                                                    }
                                                    break;
                                            }
                                        }elseif($applys['apply_status'] == 2){
                                            $applys['zhuangtai'] = '商家已拒绝';
                                            $applys['filter'] = 13;
                                        }elseif($applys['apply_status'] == 4){
                                            $applys['zhuangtai'] = '已撤销';
                                            $applys['filter'] = 14;
                                        }
    
                                        $thpicres = Db::name('thapply_pic')->where('th_id',$applys['id'])->select();
                                        //获取平台的退换货地址信息
                                        $webconfig = $this->webconfig;
                                        $shopdzs = ['name'=>$webconfig['sh_name'],'telephone'=>$webconfig['sh_tel'],'address'=>$webconfig['sh_address'],
                                            'province' => '' , 'city' => '', 'area' => ''
                                        ];
                                        // if(in_array($applys['thfw_id'],array(2,3)) && $applys['apply_status'] == 1){
                                        //     $shopdzs = Db::name('shop_shdz')->where('shop_id',$applys['shop_id'])->find();
                                        // }else{
                                        //     $shopdzs = array();
                                        // }
    
                                        $tuiwulius = array();
                                        if(in_array($applys['thfw_id'], array(2,3)) && $applys['apply_status'] == 1 && $applys['dcfh_status'] == 1){
                                            $tuiwulius = Db::name('tui_wuliu')->where('th_id',$applys['id'])->find();
                                        }
    
                                        $wulius = array();
                                        if($applys['thfw_id'] == 3 && $applys['apply_status'] == 1 && $applys['dcfh_status'] == 1 && $applys['sh_status'] == 1 && $applys['fh_status'] == 1){
                                            $wulius = Db::name('huan_wuliu')->alias('a')->field('a.*,b.log_name,b.telephone')->join('sp_logistics b','a.ps_id = b.id','LEFT')->where('a.th_id',$applys['id'])->find();
                                        }
    
                                        $thapplyinfo = array('orgoods'=>$orgoods,'applys'=>$applys,'thpicres'=>$thpicres,'shopdzs'=>$shopdzs,'tuiwulius'=>$tuiwulius,'wulius'=>$wulius);
                                        $value = array('status'=>200,'mess'=>'获取退换货申请信息成功','data'=>$thapplyinfo);
                                    }else{
                                        $value = array('status'=>400,'mess'=>'找不到相关退换货信息','data'=>array('status'=>400));
                                    }
                                }else{
                                    $value = array('status'=>400,'mess'=>'订单商品信息错误','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'订单信息错误','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'缺少商品编号','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少订单号','data'=>array('status'=>400));
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
    
    //撤销退换申请
    public function chexiao(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.th_number')){
                        $th_number = input('post.th_number');
                        $applys = Db::name('th_apply')->where('th_number',$th_number)->where('user_id',$user_id)->where('apply_status','in','0,1,2')->field('id,thfw_id,orgoods_id,order_id,apply_status,dcfh_status')->find();
                        if($applys){
                            $ordouts = Db::name('order_timeout')->where('id',1)->find();
                            $orders = Db::name('order')->where('id',$applys['order_id'])->where('state',1)->where('fh_status',1)->where('order_status',0)->where('user_id',$user_id)->field('id')->find();
                            if($orders){
                                if(in_array($applys['thfw_id'], array(2,3))){
                                    if($applys['dcfh_status'] == 1){
                                        $value = array('status'=>400,'mess'=>'您已发货，撤销退换申请失败','data'=>array('status'=>400));
                                        return json($value);
                                    }
                                }
                        
                                // 启动事务
                                Db::startTrans();
                                try{
                                    Db::name('th_apply')->update(array('apply_status'=>4,'che_time'=>time(),'id'=>$applys['id']));
                                    Db::name('order_goods')->update(array('th_status'=>0,'id'=>$applys['orgoods_id']));
                                
                                    $ordergoods = Db::name('order_goods')->where('id','neq',$applys['orgoods_id'])->where('order_id',$applys['order_id'])->where('th_status','in','1,2,3,5,6,7')->field('id')->find();
                                
                                    if($ordergoods){
                                        $shouhou = 1;
                                    }else{
                                        $shouhou = 0;
                                    }
                                
                                    if($shouhou == 0){
                                        $orders = Db::name('order')->where('id',$applys['order_id'])->find();
                                        if($orders){
                                            $zdsh_time = time()+$ordouts['zdqr_sh_time']*24*3600;
                                            Db::name('order')->where('id',$applys['order_id'])->update(array('shouhou'=>0,'zdsh_time'=>$zdsh_time));
                                        }
                                    }
                                
                                    // 提交事务
                                    Db::commit();
                                    $value = array('status'=>200,'mess'=>'撤销退换申请成功','data'=>array('status'=>200));
                                } catch (\Exception $e) {
                                    // 回滚事务
                                    Db::rollback();
                                    $value = array('status'=>400,'mess'=>'撤销退换申请失败','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'订单类型错误不允许撤销','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'找不到相关退换货申请信息','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少退换流水号','data'=>array('status'=>400));
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
    
    //获取退货商家地址信息
    public function getwuliuinfo(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.th_number')){
                        $th_number = input('post.th_number');
                        $applys = Db::name('th_apply')->where('th_number',$th_number)->where('user_id',$user_id)->where('thfw_id','in','2,3')->where('apply_status',1)->where('dcfh_status',0)->field('id,thfw_id,orgoods_id,apply_status,dcfh_status,shop_id')->find();
                        if($applys){
                            $orgoods = Db::name('order_goods')->where('id',$applys['orgoods_id'])->field('id,goods_id,goods_name,thumb_url,goods_attr_str,price,goods_num,order_id')->find();
                            if($orgoods){
                                $webconfig = $this->webconfig;
                                $orgoods['thumb_url'] = $webconfig['weburl'].'/'.$orgoods['thumb_url'];
                                
                                $infos = array('orgoods'=>$orgoods,'th_number'=>$th_number);
                                $value = array('status'=>200,'mess'=>'获取物流信息成功','data'=>$infos);
                            }else{
                                $value = array('status'=>400,'mess'=>'退换商品信息错误','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'找不到相关退换货申请信息','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少退换流水号','data'=>array('status'=>400));
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
    
    //用户退货发货
    public function thfahuo(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.th_number')){
                        if(input('post.ps_name') && input('post.ps_num')){
                            if(input('post.telephone')){
                                $th_number = input('post.th_number');
                                $ps_name = input('post.ps_name');
                                $ps_num = input('post.ps_num');
                                $telephone = input('post.telephone');
                                if(preg_match("/^1[3456789]{1}\\d{9}$/", $telephone)){
                                    if(mb_strlen($ps_name,'utf8') <= 20){
                                        if(mb_strlen($ps_num,'utf8') <= 50){
                                            $wuliu_infos = Db::name('tui_wuliu')->where('ps_num',$ps_num)->find();
                                            if(!$wuliu_infos){
                                                $applys = Db::name('th_apply')->where('th_number',$th_number)->where('user_id',$user_id)->where('thfw_id','in','2,3')->where('apply_status',1)->where('dcfh_status',0)->field('id,order_id')->find();
                                                //print_r($applys);exit();
                                                if($applys){
                                                    // 启动事务
                                                    Db::startTrans();
                                                    try{
                                                        $wuliu_id = Db::name('tui_wuliu')->insertGetId(array('ps_name'=>$ps_name,'ps_num'=>$ps_num,'telephone'=>$telephone,'th_id'=>$applys['id']));
                                                        if($wuliu_id){
                                                            Db::name('th_apply')->update(array('dcfh_status'=>1,'dcfh_time'=>time(),'id'=>$applys['id']));
                                                        }
                                                        //更改订单状态为 8  已发货  平台待收货
                                                        Db::name('order')->update(['o_state'=>8,'id'=>$applys['order_id']]);
                                                        Db::name('th_apply')->update(array('dcfh_status'=>1,'dcfh_time'=>time(),'id'=>$applys['id']));
                                                        // 提交事务
                                                        Db::commit();
                                                        $value = array('status'=>200,'mess'=>'提交成功','data'=>array('status'=>200));
                                                    } catch (\Exception $e) {
                                                        // 回滚事务
                                                        Db::rollback();
                                                        $value = array('status'=>400,'mess'=>'提交失败','data'=>array('status'=>400));
                                                    }
                                                }else{
                                                    $value = array('status'=>400,'mess'=>'找不到相关退换货申请信息','data'=>array('status'=>400));
                                                }
                                            }else{
                                                $value = array('status'=>400,'mess'=>'物流单号已存在','data'=>array('status'=>400));
                                            }
                                        }else{
                                            $value = array('status'=>400,'mess'=>'物流单号最多50个字符','data'=>array('status'=>400));
                                        }
                                    }else{
                                        $value = array('status'=>400,'mess'=>'物流公司最多20个字符','data'=>array('status'=>400));
                                    }
                                }else{
                                    $value = array('status'=>400,'mess'=>'请填写正确的手机号码','data'=>array('status'=>400));
                                }
                            }else{
                                $value = array('status'=>400,'mess'=>'请填写联系手机号','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'请完善物流信息','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少退换流水号','data'=>array('status'=>400));
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
    
    //换货确认收货
    public function surehuan(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    if(input('post.th_number')){
                        $th_number = input('post.th_number');
                        
                        $applys = Db::name('th_apply')->where('th_number',$th_number)->where('user_id',$user_id)->where('thfw_id',3)->where('apply_status',1)->where('dcfh_status',1)->where('sh_status',1)->where('fh_status',1)->where('shou_status',0)->field('id,thfw_id,orgoods_id,order_id')->find();
                        if($applys){
                            $ordouts = Db::name('order_timeout')->where('id',1)->find();
                            
                            // 启动事务
                            Db::startTrans();
                            try{
                                Db::name('th_apply')->update(array('shou_status'=>1,'apply_status'=>3,'shou_time'=>time(),'com_time'=>time(),'id'=>$applys['id']));
                                Db::name('order_goods')->update(array('th_status'=>8,'id'=>$applys['orgoods_id']));
                            
                                $ordergoods = Db::name('order_goods')->where('id','neq',$applys['orgoods_id'])->where('order_id',$applys['order_id'])->where('th_status','in','1,2,3,5,6,7')->field('id')->find();
                            
                                if($ordergoods){
                                    $shouhou = 1;
                                }else{
                                    $shouhou = 0;
                                }
                            
                                if($shouhou == 0){
                                    $orders = Db::name('order')->where('id',$applys['order_id'])->find();
                                    if($orders){
                                        $zdsh_time = time()+$ordouts['zdqr_sh_time']*24*3600;
                                        Db::name('order')->where('id',$applys['order_id'])->update(array('shouhou'=>0,'zdsh_time'=>$zdsh_time));
                                    }
                                }
                                // 提交事务
                                Db::commit();
                                $value = array('status'=>200,'mess'=>'确认收货成功','data'=>array('status'=>200));
                            } catch (\Exception $e) {
                                // 回滚事务
                                Db::rollback();
                                $value = array('status'=>400,'mess'=>'确认收货失败','data'=>array('status'=>400));
                            }
                        }else{
                            $value = array('status'=>400,'mess'=>'找不到相关退换货申请信息','data'=>array('status'=>400));
                        }
                    }else{
                        $value = array('status'=>400,'mess'=>'缺少退换流水号','data'=>array('status'=>400));
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