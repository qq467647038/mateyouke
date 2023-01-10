<?php
namespace app\apicloud\controller;
use app\apicloud\model\Gongyong as GongyongMx;
use app\util\timeFormat;
use app\common\model\Alive as AliveModel;
use app\common\model\AliveOrder as AliveOrderModel;
use app\common\model\AliveChargeLog;
use app\common\model\Shops as ShopsModel;
use app\common\model\Member as MemberModel;
use think\Db;
class Alive extends Common {
    /**
     * @func 获取直播banner图
     */
    public function getalivebanner($pos_id = 6){
        // 验证api_token
        $res = $this->checkToken(0);
        if($res['status'] == 400){  return json($res);  }

        if(request()->isPost()) {
            // $pos
            $bannerList = db('ad')->where(['pos_id'=>$pos_id,'is_on'=>1])->order('id DESC')->select();
            foreach($bannerList as $k=>$v){
                $bannerList[$k]['ad_pic'] = "https://".$_SERVER['HTTP_HOST'].'/'.$v['ad_pic'];
            }
            datamsg(WIN, '获取成功', $bannerList);
        }else{
            datamsg(WIN, '请求方式错误');
        }

    }

    // 获取关注的直播间
    public function getFollowLive(){
        if(request()->isPost()) {
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            // dump($result);die;
            if($result['status'] == 200){
                $user_id = $result['user_id'];
                if($user_id){
                    $followShop = db('coll_shops')->where(['user_id'=>$user_id])->column('shop_id');
                    $page = input('param.page') ? input('param.page') : 1;
                    $size = input('param.size') ? input('param.size') : 10;
                    $isnewperson = input('param.isnewperson');
                    $type = input('param.typeid');
                    $shop_id = input('param.shop_id') ? input('param.shop_id') : "0" ;
                    $where = ['shop_id'=>['IN',$followShop]];
                    // if (empty($type)) {
                    //     if (empty($isnewperson)) {
                    //         datamsg(LOSE, '请传入参数');
                    //     } else {
                    //         $where['isnewperson'] = 1;
                    //     }
                    // } else {
                    //     if ($type != -1) {
                    //         $where['cateid'] = $type;
                    //     }
                    // }

                    $list = db('alive')->where($where)->field("id,shop_id,status,room,title,notice,issincerity,isrecommend,cover,city_name,area_name,video_link,if(shop_id=".$shop_id.",1,0) as ol")
                        ->order("isrecommend desc")
                        ->order("ol desc")
                        ->paginate($size)
                        ->each(function ($item, $key) {
                            $alive = new Coldlivepush();
                            $item['addressitem'] = 'https://'.$this->webconfig['playdomain'].'/live/'.$item['room'].'.m3u8';
                            $shop_logo = db('shops')->where(['id' => $item['shop_id']])->value('logo');
                            $item['shop_logo'] = $shop_logo ? $this->webconfig['weburl'] .'/'. $shop_logo : $this->webconfig['weburl'] . '/uploads/default.jpg';
                            $tuijiangoods = db('goods')->where(['shop_id'=>$item['shop_id']])->field('goods_name,keywords,thumb_url,market_price')->find();
                            if(!empty($tuijiangoods)){
                                $tuijiangoods['thumb_url'] = $this->webconfig['weburl'] . $tuijiangoods['thumb_url'];
                            }else{
                                $tuijiangoods = db('goods')->where(['shop_id'=>1])->orderRaw('rand()')->field('goods_name,keywords,thumb_url,market_price')->find();
                                $tuijiangoods['thumb_url'] = $this->webconfig['weburl'] . $tuijiangoods['thumb_url'];
                            }
                            if($this->webconfig['cos_file'] == '开启'){
                                $item['cover'] = config('tengxunyun')['cos_domain'].'/'.$item['cover'];
                            }else{
                                $item['cover'] = $this->webconfig['weburl'].'/'.$item['cover'];
                            }
                            $tuijiangoods['goods_name'] = $item['notice'];
                            $item['goods'] = $tuijiangoods;

                            //最新的3条留言
                            $item['message'] = db('alive_message')->where(['room'=>$item['room']])->field('message,fromid,room,type,comeintime')->limit(3)->order('id DESC')->select();

                            //在线人数
                            $item['online_num'] = db('alive_comein')->where(['room'=>$item['room']])->count();

                            //关注人数
                            $item['follow_num'] = db('coll_shops')->where(['shop_id'=>$item['shop_id']])->count();

                            return $item;

                        });

                    datamsg(WIN, '获取成功', $list);


                }else{
                    datamsg(WIN,'请先登录');
                }

            }else{
                datamsg(LOSE,'请先登录');
            }


        }else{
            datamsg(LOSE,'请求方式不正确');
        }
    }


    /**
     * @func 获取直播间      --------------   复制原来的直播间数据-更改action方法名
     */
    public function getaliveindex_copy(){
        if(request()->isPost()) {
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(0);
            if($result['status'] == 200){
                $user_id = null;
                try {
                    $res = $gongyong->apivalidate();
                    $user_id = $res['user_id'];
                } catch (\Throwable $th) {
                    //throw $th;
                }
                $page = input('param.page') ? input('param.page') : 1;
                $size = input('param.size') ? input('param.size') : 10;
                $isnewperson = input('param.isnewperson');
                $type = input('param.typeid');
                $shop_id = input('param.shop_id') ? input('param.shop_id') : "0" ;
                $where = [];
                if(input('param.isrecomment') == 1){
                    $where['isrecommend'] = 1;
                }

                // if (empty($type)) {
                //     if (empty($isnewperson)) {
                //         datamsg(LOSE, '请传入参数');
                //     } else {
                //         $where['isnewperson'] = 1;
                //     }
                // } else {
                //     if ($type != -1) {
                //         $where['cateid'] = $type;
                //     }
                // }
                $list = db('alive')->where($where)->field("id,shop_id,status,room,title,notice,alivetime,is_pay,alive_sn,issincerity,cover,isrecommend,city_name,area_name,video_link,if(shop_id=".$shop_id.",1,0) as ol")
                    ->order("isrecommend desc")
                    ->order("alivetime desc")
                    ->order("ol desc")
                    ->paginate($size)
                    ->each(function ($item, $key) {
                        $alive = new Coldlivepush();

                        if(empty($item['alivetime'])){
                            $item['alivetimetxt'] = '未开播';
                        }elseif ($item['status'] == 1){
                            $item['alivetimetxt'] = '直播中';
                        }elseif ($item['alivetime'] <= time()){
                            $item['alivetimetxt'] = '上次开播时间：'.(new timeFormat($item['alivetime']))->calculateTime()->getTime();
                        }elseif ($item['alivetime'] > time()){
                            $item['alivetimetxt'] = '下次开播时间：'.(new timeFormat($item['alivetime']))->calculateTime()->getTime();
                        }

                        $item['addressitem'] = 'https://'.$this->webconfig['playdomain'].'/live/'.$item['room'].'.m3u8';
                        // if($item['status'] == 1){ // 直播中
                        //     $item['addressitem'] = 'https://'.$this->webconfig['playdomain'].'/live/'.$item['room'].'.m3u8';
                        //     $item['transcribe'] = 0;  // 是否回放
                        // }else{
                        //     $item['addressitem'] = db('alive_transcribe')->where('stream_id',$item['room'])->value('video_url');
                        //     if(!empty($item['addressitem'])){
                        //         $item['transcribe'] = 1;
                        //     }else{
                        //         $item['transcribe'] = 0;
                        //     }

                        // }


                        $shop_logo = db('shops')->where(['id' => $item['shop_id']])->value('logo');
                        $shop_name = db('shops')->where(['id' => $item['shop_id']])->value('shop_name');
                        $item['shop_logo'] = $shop_logo ? $this->webconfig['weburl'] .'/'. $shop_logo : $this->webconfig['weburl'] . '/uploads/default.jpg';
                        $item['shop_name'] = $shop_name;
                        $tuijiangoods = db('goods')->where(['shop_id'=>$item['shop_id']])->field('goods_name,keywords,thumb_url,market_price')->find();
                        if(!empty($tuijiangoods)){
                            $tuijiangoods['thumb_url'] = $this->webconfig['weburl'] . $tuijiangoods['thumb_url'];
                        }else{
                            $tuijiangoods = db('goods')->where(['shop_id'=>1])->orderRaw('rand()')->field('goods_name,keywords,thumb_url,market_price')->find();
                            $tuijiangoods['thumb_url'] = $this->webconfig['weburl'] . $tuijiangoods['thumb_url'];
                        }
                        $tuijiangoods['goods_name'] = $item['notice'];
                        $item['goods'] = $tuijiangoods;

                        // 	if($this->webconfig['cos_file'] == '开启'){
                        //        $item['cover'] = config('tengxunyun')['cos_domain'].'/'.$item['cover'];
                        //    }else{
                        //        $item['cover'] = $this->webconfig['weburl'].'/'.$item['cover'];
                        //    }

                        $item['cover'] = $this->webconfig['weburl'].'/'.$item['cover'];
                        //最新的3条留言
                        $item['message'] = db('alive_message')->where(['room'=>$item['room']])->field('message,fromid,room,type,comeintime')->limit(3)->order('id DESC')->select();

                        //在线人数
                        $item['online_num'] = db('alive_comein')->where(['room'=>$item['room']])->count();

                        //关注人数
                        $item['follow_num'] = db('coll_shops')->where(['shop_id'=>$item['shop_id']])->count();

                        //$item['title'] = $item['notice'];


                        return $item;
                    });

                $list = $list->toArray();
                $list['msg_num'] = 10;
                foreach ($list['data'] as &$value) {
                    if($value['is_pay'] == 1){
                        $res = AliveOrderModel::findByUseridAndSn($user_id,$value['alive_sn']);
                        if($res){
                            $value['pay_sign'] = 1;
                        }else{
                            $value['pay_sign'] = 0;
                        }
                    }
                }

                // return $list;
               return datamsg(WIN, '获取成功2', $list);

            }else{
                return datamsg(LOSE,$result['mess']);
            }


        }else{
            return datamsg(LOSE,'请求方式不正确');
        }
    }


    /**
     * @func 获取直播间
     */
    public function getaliveindex(){
        if(request()->isPost()) {
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(0);
            if($result['status'] == 200){
                $user_id = null;
                try {
                    $res = $gongyong->apivalidate();
                    $user_id = $res['user_id'];
                } catch (\Throwable $th) {
                    //throw $th;
                }
                $page = input('param.page') ? input('param.page') : 1;
                $size = input('param.size') ? input('param.size') : 10;
                $isnewperson = input('param.isnewperson');
                $type = input('param.typeid');
                $shop_id = input('param.shop_id') ? input('param.shop_id') : "0" ;
                $where = [];
                if(input('param.isrecomment') == 1){
                    $where['isrecommend'] = 1;
                }

                // if (empty($type)) {
                //     if (empty($isnewperson)) {
                //         datamsg(LOSE, '请传入参数');
                //     } else {
                //         $where['isnewperson'] = 1;
                //     }
                // } else {
                //     if ($type != -1) {
                //         $where['cateid'] = $type;
                //     }
                // }
                $list = db('alive')->where($where)->field("id,shop_id,status,room,title,notice,alivetime,is_pay,alive_sn,issincerity,cover,isrecommend,city_name,area_name,video_link,if(shop_id=".$shop_id.",1,0) as ol")
                    ->order("isrecommend desc")
                    ->order("alivetime desc")
                    ->order("ol desc")
                    ->paginate($size)
                    ->each(function ($item, $key) {
                        $alive = new Coldlivepush();

                        if(empty($item['alivetime'])){
                            $item['alivetimetxt'] = '未开播';
                        }elseif ($item['status'] == 1){
                            $item['alivetimetxt'] = '直播中';
                        }elseif ($item['alivetime'] <= time()){
                            $item['alivetimetxt'] = '上次开播时间：'.(new timeFormat($item['alivetime']))->calculateTime()->getTime();
                        }elseif ($item['alivetime'] > time()){
                            $item['alivetimetxt'] = '下次开播时间：'.(new timeFormat($item['alivetime']))->calculateTime()->getTime();
                        }
                        
                        $item['addressitem'] = 'https://'.$this->webconfig['playdomain'].'/live/'.$item['room'].'.m3u8';
                        // if($item['status'] == 1){ // 直播中
                        //     $item['addressitem'] = 'https://'.$this->webconfig['playdomain'].'/live/'.$item['room'].'.m3u8';
                        //     $item['transcribe'] = 0;  // 是否回放
                        // }else{
                        //     $item['addressitem'] = db('alive_transcribe')->where('stream_id',$item['room'])->value('video_url');
                        //     if(!empty($item['addressitem'])){
                        //         $item['transcribe'] = 1;
                        //     }else{
                        //         $item['transcribe'] = 0;
                        //     }

                        // }


                        $shop_logo = db('shops')->where(['id' => $item['shop_id']])->value('logo');
                        $shop_name = db('shops')->where(['id' => $item['shop_id']])->value('shop_name');
                        $item['shop_logo'] = $shop_logo ? $this->webconfig['weburl'] .'/'. $shop_logo : $this->webconfig['weburl'] . '/uploads/default.jpg';
                        $item['shop_name'] = $shop_name;
                        $tuijiangoods = db('goods')->where(['shop_id'=>$item['shop_id']])->field('goods_name,keywords,thumb_url,market_price')->find();
                        if(!empty($tuijiangoods)){
                            $tuijiangoods['thumb_url'] = $this->webconfig['weburl'] . $tuijiangoods['thumb_url'];
                        }else{
                            $tuijiangoods = db('goods')->where(['shop_id'=>1])->orderRaw('rand()')->field('goods_name,keywords,thumb_url,market_price')->find();
                            $tuijiangoods['thumb_url'] = $this->webconfig['weburl'] . $tuijiangoods['thumb_url'];
                        }
                        $tuijiangoods['goods_name'] = $item['notice'];
                        $item['goods'] = $tuijiangoods;

					// 	if($this->webconfig['cos_file'] == '开启'){
                    //        $item['cover'] = config('tengxunyun')['cos_domain'].'/'.$item['cover'];
                    //    }else{
                    //        $item['cover'] = $this->webconfig['weburl'].'/'.$item['cover'];
                    //    }

                        $item['cover'] = $this->webconfig['weburl'].'/'.$item['cover'];
                        //最新的3条留言
                        $item['message'] = db('alive_message')->where(['room'=>$item['room']])->field('message,fromid,room,type,comeintime')->limit(3)->order('id DESC')->select();

                        //在线人数
                        $item['online_num'] = db('alive_comein')->where(['room'=>$item['room']])->count();

                        //关注人数
                        $item['follow_num'] = db('coll_shops')->where(['shop_id'=>$item['shop_id']])->count();

                        //$item['title'] = $item['notice'];


                        return $item;
                    });

                $list = $list->toArray();
                $list['msg_num'] = 10;
                foreach ($list['data'] as &$value) {
                    if($value['is_pay'] == 1){
                        $res = AliveOrderModel::findByUseridAndSn($user_id,$value['alive_sn']);
                        if($res){
                            $value['pay_sign'] = 1;
                        }else{
                            $value['pay_sign'] = 0;
                        }
                    }
                }
                datamsg(WIN, '获取成功', $list);

            }else{
                datamsg(LOSE,$result['mess']);
            }


        }else{
            datamsg(LOSE,'请求方式不正确');
        }
    }

    /**
     * 获取直播商品类型
     */
    public function gettype(){
        if(request()->isPost()) {
            $list = db('type')->field('id,type_name')->select();
            $tuian[0] = ['id' => -1, 'type_name' => '推荐'];
            $list = array_merge($tuian, $list);
            datamsg(WIN, '获取成功', $list);
        }else{
            datamsg(LOSE,'请求方式不正确');
        }
    }

    /**
     * 获取直播商品
     */
    public function getgoods(){
        if (request()->isPost()) {
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if ($result['status'] == 200) {
                $user_id = $result['user_id'];
                // echo $user_id;die;
                $userInfo = db('member')->where(['id'=>$result['user_id']])->find();
                if($userInfo['shop_id'] == 0){
                    datamsg(LOSE,'对不起，您还不是店主，不能直播',array('open'=>false));
                }else{
                    $where = [
                        'shop_id' => $userInfo['shop_id'],
                        'onsale' => 1,
                        'checked' => 1,
                        'is_recycle' => 0
                    ];
                    $goods =  db('goods')->where($where)->field('id,goods_name')->order('id desc')->select();
                    datamsg(WIN, '获取成功', $goods);
                }

            }else{
                datamsg(LOSE,$result['mess']);
            }
        }else{
            datamsg(LOSE,'请求方式不正确');
        }
    }


    /**
     * 获取直播记录
     */
    public function getlive(){
        if (request()->isPost()) {
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if ($result['status'] == 200) {
                $user_id = $result['user_id'];
                // echo $user_id;die;
                $userInfo = db('member')->where(['id'=>$result['user_id']])->find();
                if($userInfo['shop_id'] == 0){
                    datamsg(LOSE,'对不起，您还不是店主，不能直播',array('open'=>false));
                }else{

                    $live_log =  db('alive_record')->field('FROM_UNIXTIME(starttime,"%Y-%m-%d %H:%i:%s") as starttime,FROM_UNIXTIME(endtime,"%Y-%m-%d %H:%i:%s") as endtime,peoplenum,income,title,cover,notice')->where('mid',$userInfo['shop_id'])->order('id desc')->select();

                    datamsg(WIN, '获取成功', $live_log);
                }

            }else{
                datamsg(LOSE,$result['mess']);
            }
        }else{
            datamsg(LOSE,'请求方式不正确');
        }
    }

    // 判断是否已开通直播间
    public function hasLiveRoom(){
        if (request()->isPost()) {
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if ($result['status'] == 200) {
                $user_id = $result['user_id'];
                // echo $user_id;die;
                $userInfo = db('member')->where(['id'=>$result['user_id']])->find();
                if($userInfo['shop_id'] == 0){
                    datamsg(LOSE,'对不起，您还不是店主，不能直播',array('open'=>false));
                }else{

                    $liveRoom = db('alive')->where(['shop_id'=>$userInfo['shop_id'],'isclose'=>0])->find();
                    if($liveRoom){
                        datamsg(WIN,'已开通直播间',array('open'=>true));
                    }else{
                        datamsg(LOSE,'对不起，您的直播间未开通或已禁播，请联系平台客服处理',array('open'=>false));
                    }
                }

            }else{
                datamsg(LOSE,$result['mess']);
            }
        }else{
            datamsg(LOSE,'请求方式不正确');
        }
    }

    // 直播间发布页面信息
    public function liveInfo(){
        if (request()->isPost()) {
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if ($result['status'] == 200) {
                $user_id = $result['user_id'];
                // echo $user_id;die;
                $userInfo = db('member')->where(['id'=>$result['user_id']])->find();
                if($userInfo['shop_id'] == 0){
                    datamsg(LOSE,'对不起，您还不是店主，不能直播');
                }else{

                    $liveRoom = db('alive')->where(['shop_id'=>$userInfo['shop_id'],'isclose'=>0])->find();
                    if($liveRoom){
                        datamsg(WIN,'已开通直播间',$liveRoom);
                    }else{
                        datamsg(LOSE,'对不起，您的直播间已禁播，请联系平台客服处理');
                    }
                }

            }else{
                datamsg(LOSE,$result['mess']);
            }
        }else{
            datamsg(LOSE,'请求方式不正确');
        }
    }


    /**
     * 发起直播提交
     *
     */
    public function launchalive(){
        if(request()->isPost()) {
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if($result['status'] == 200) {
                $data['cover'] = input('param.cover');
                $data['title'] = input('param.title');
                $data['notice'] = input('param.description');
                // $is_pay = input('param.is_pay',0);
                // $price = input('param.price',0);
                // $is_last = input('param.is_last',0);
                // $goods_id = input('param.goods/a');
                if(empty($data['cover'])){
                    datamsg(LOSE,'请上传图片封面');
                }
                $cateid = input('param.cateid');
                $user_arr = db('member')->where(['id'=>$result['user_id']])->find();
                if(empty($user_arr)){
                    datamsg(LOSE,'没有找到用户信息');
                }
                if($user_arr['shop_id'] == 0){
                    //return json($user_arr);
                    datamsg(LOSE,'对不起，你还不是店主，不能直播');
                }

                $shops_arr = db('shops')->where(['id'=>$user_arr['shop_id']])->find();
                if(empty($shops_arr)){
                    datamsg(LOSE,'对不起，没有找到您的店铺信息');
                }else{
                    if($shops_arr['open_status'] == 0){
                        datamsg(LOSE,'对不起，您的店铺已经关闭');
                    }
                    if($shops_arr['normal'] == 0){
                        datamsg(LOSE,'对不起，您的店铺已经注销');
                    }
                }



                $alive_arr = db('alive')->where(['shop_id'=>$user_arr['shop_id']])->find();
                if(empty($alive_arr)){
                    $insert['shop_id']=$user_arr['shop_id'];
                    $insert['alivetime']=time();
                    $insert['cover']=$data['cover'];
                    $insert['room']=getRefereeId();
                    $insert['cateid']=$cateid;
                    $shops = db('shops')->where(['id'=>$user_arr['shop_id']])->find();
                    $insert['title'] = $data['title'];
                    $insert['notice'] = $data['notice'];
                    $insert_id = db('alive')->insertGetId($insert);
                    $alive_arr = db('alive')->where(['id'=>$insert_id])->find();
                }

                $data['alivetime']=time();
                if(empty($alive_arr['room'])){
                    $data['room'] = getRefereeId();
                    if(!empty($cateid)){
                        $data['cateid']=$cateid;
                    }
                    db('alive')->where(['shop_id'=>$user_arr['shop_id']])->update($data);
                }else{
                    if(!empty($cateid)){
                        $data['cateid']=$cateid;
                    }
                    db('alive')->where(['id'=>$alive_arr['id']])->update($data);
                }
                $alive_arr = db('alive')->where(['shop_id'=>$user_arr['shop_id']])->find();
                if($alive_arr['isclose'] == 1){
                    datamsg(LOSE,'对不起，该直播间由于违规操作，以被关闭');
                }

                // $goods_del = db('alive_goods')->where('shop_id', $user_arr['shop_id'])->delete();

                // $goods_date['shop_id'] = $user_arr['shop_id'];

                // foreach ($goods_id as &$value) {

                //     $goods_date['goods_id'] =  $value;

                //     $alive_goods = db('alive_goods')->insert($goods_date);

                // }

                if($is_pay != 0){
                    $this->alivePay($alive_arr,$price,$is_last);
                }
                $update_alive=1;
                if($update_alive){
                    $alive = new Coldlivepush();
                    $streamalive = $alive->getstream($alive_arr['room']);
                    //dump($streamalive);
                    datamsg(WIN,'获取成功',$streamalive);
                }else{
                    datamsg(LOSE,'上传封面失败');
                }
            }else{
                datamsg(LOSE,$result['mess']);
            }

        }else{
            datamsg(LOSE,'请求方式不正确');
        }
    }

    /**
     * 付费直播时
     */
    private function alivePay($roomInfo,$price,$sign)
    {
        try {

            if($sign == 0){
                // 不继续上次直播
                $data = [
                    'is_pay' => 1,
                    'price' => $price,
                    'alive_sn' => $this->createAliveSn($roomInfo['id'])
                ];
                $map = [
                    'room' => $roomInfo['room'],
                    'price' => $price,
                    'addtime' => time(),
                    'alive_sn' => $data['alive_sn']
                ];
                // 添加付费直播记录
                AliveChargeLog::create($map);
            }else{
                // 继续上次直播
                $data = [
                    'is_pay' => 1,
                    'price' => $price
                ];
                if($roomInfo['alive_sn'] == '') $data['alive_sn'] = $this->createAliveSn($roomInfo['id']);
            }
            AliveModel::where('id',$roomInfo['id'])->update($data);

        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /**
     * 直播付费课程号
     */
    public function createAliveSn($id)
    {
        return "Z".$id.date("YmdHis",time()).rand(10,99);
    }


    /**
     * 观看直播
     */
    public function playalive(){
        if (request()->isPost()) {
            $room = input('param.room');
            if(empty($room)){
                datamsg(LOSE,'请传入房间号');
            }
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if($result['status'] == 200){
                $user_id = $result['user_id'];
                if(!$this->isPay($room,$user_id)) return returnJson(204,'为支付');
                $palystream = $this->webconfig['playdomain'];
                $type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
                if(empty($type)){
                    $type = "https://";
                }
                $data[0] = 'rtmp://'.$palystream.'/live/'.$room;
                $data[1] = $type.$palystream.'/live/'.$room.'.flv';
                $data[2] = $type.$palystream.'/live/'.$room.'.flv';
                datamsg(WIN,'获取成功',$data);
            }else{
                datamsg(LOSE,$result['mess']);
            }
        }else{
            datamsg(LOSE,'请求方式不正确');
        }
    }

    /**
     * 判断直播间是否收费
     */
    public function checkPay()
    {
        $gongyong = new GongyongMx();
        $res = $gongyong->apivalidate();
        $room = input('room');
        $result = AliveModel::findByRoom($room);
        if($result['is_pay'] == 1){
            if($res['status'] !== 200) return returnJson(204,'no');
            $user_id = $res['user_id'];
            $roomInfo = AliveModel::findByRoom($room);
            $order = AliveOrderModel::findByUseridAndSn($user_id,$roomInfo['alive_sn']);
            if(!$order) {
                return returnJson(404,'no');
            }
        }
        return returnJson(200,'yes');
    }

    /**
     * 判断直播间是否收费
     */
    private function isPay($room,$user_id)
    {
        $result = AliveModel::findByRoom($room);
        if($result['is_pay'] == 1){
            $roomInfo = AliveModel::findByRoom($room);
            $order = AliveOrderModel::findByUseridAndSn($user_id,$roomInfo['alive_sn']);
            if(!$order) {
                return false;
            }
        }
        return true;
    }

    public function findByAliveRoom()
    {
        $room = input('room');
        $data = AliveModel::findByRoom($room);
        $user = MemberModel::findByShopId($data['shop_id']);
        $data['user_name'] = $user['user_name'];
        return returnJson(200,'获取成功',$data);
    }


    /**
     * 获取直播页面商品列表
     */
    public function alivegoods()
    {
        if (request()->isPost()) {
            $data = input('param.');
            $shop_id = $data['shop_id'];
            if(empty($shop_id)){
                datamsg(LOSE,'店铺id不能为空');
            }
            $page = input('param.page') ? input('param.page') : 1;
            $size = input('param.size') ? input('param.size') : 10;
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(0);
            if ($result['status'] == 200) {
                $where['a.shop_id']=$shop_id;
                $where['g.onsale'] = 1;
                $where['g.checked']=1;
                $list =  db('alive_goods')->alias("a")->join("goods g",'a.goods_id = g.id')->field('g.id,g.goods_name,g.thumb_url,g.shop_price')->where($where)->paginate($size)->each(function($item){
                        $item['thumb_url']=$this->webconfig['weburl'].'/'.$item['thumb_url'];
                        return $item;
                    });
                // $list = db('goods')
                //     ->where($where)
                //     ->field('id,goods_name,shop_price,thumb_url')
                //     ->paginate($size)
                //     ->each(function($item){
                //         $item['thumb_url']=$this->webconfig['weburl'].'/'.$item['thumb_url'];
                //         return $item;
                //     });
                datamsg(WIN,'获取成功',$list);
            } else {
                datamsg(LOSE, $result['mess']);
            }
        }else{
            datamsg(LOSE,'请求方式错误');
        }
    }



    /**
     * 获取直播页面礼物列表
     */
    public function alivegifts()
    {
        if (request()->isPost()) {
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if ($result['status'] == 200) {
                $user_arr = db('member')->where(['id'=>$result['user_id']])->find();
                if(empty($user_arr)){
                    datamsg(LOSE,'没有找到用户');
                }
//                if(!$user_arr['shop_id']){
//                    datamsg(LOSE,'对不起，请先提交开店申请');
//                }
                $list = db('alive_gifts')->where(['is_delete'=>0])->field('id,name,point,pic,picgif,description')->select();
                foreach($list as $key=>&$value){
                    $value['pic']=$this->webconfig['weburl'].'/'.$value['pic'];
                    $value['picgif']=$this->webconfig['weburl'].'/'.$value['picgif'];
                }
                datamsg(WIN,'获取成功',$list);
            } else {
                datamsg(LOSE, $result['mess']);
            }
        }else{
            datamsg(LOSE,'请求方式错误');
        }
    }



    /**
    **直播打赏
    */

    public function sendgift(){
        if (request()->isPost()){
            $id = input('param.id');
            $shop_id = input('param.shop_id');

            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if ($result['status'] == 200) {
                $user_arr = db('member')->where(['id'=>$result['user_id']])->find();
                if(empty($user_arr)){
                    datamsg(LOSE,'没有找到用户');
                }

                $moeny = db('alive_gifts')->where('id',$id)->find();

                if($wallet['price']<$moeny['point']){
                    datamsg(WIN,'钱包余额不足,请充值',0);
                }

                $shop_user = db('member')->where(['shop_id'=>$shop_id])->find();
                $shop_wallet = db('wallet')->where(['user_id'=>$shop_user['id']])->find();

                if($shop_user){
                    datamsg(LOSE,'主播不存在');
                }

                try {
 
                    Db::startTrans();
         
                    //用户余额扣除
                    $re['t1'] =db('wallet')->where(['user_id'=>$result['user_id']])->setDec('price',$moeny['point']);

                    //用户余额扣除明细
                    $re['t2'] = db('detail')
                                ->insert([
                                    'de_type' =>2,
                                    'sr_type' => 0,
                                    'zc_type' => 3,
                                    'price' => $moeny['point'],
                                    'order_type' => 0,
                                    'order_id' => 0,
                                    'tx_id' => 0,
                                    'user_id' => $result['user_id'],
                                    'wat_id' => $wallet['id'],
                                    'time' => time(),
                                ]);
                    //用户打赏记录+
                    $re['t3'] = db('alive_givegift')
                                ->insert([
                                    'uid' =>$result['user_id'],
                                    'shop_id' =>$shop_id,
                                    'gid' =>$moeny['id'],
                                    'redbi' => $moeny['point'],
                                    'createtime' => time(),
                                ]);


                    //增加主播钱包余额
                    $add_money = number_format($moeny['point']*0.5,2);
                    $re['t4'] = db('wallet')->where(['user_id'=>$shop_user['id']])->setInc('price',$add_money);

                    //主播余额增加明细
                    $re['t5'] = db('detail')
                                ->insert([
                                    'de_type' =>1,
                                    'sr_type' => 4,
                                    'zc_type' => 0,
                                    'price' => $add_money,
                                    'order_type' => 0,
                                    'order_id' => 0,
                                    'tx_id' => 0,
                                    'user_id' => $shop_user['id'],
                                    'wat_id' => $shop_wallet['id'],
                                    'time' => time(),
                                ]);

                    //任意一个表写入失败都会抛出异常：
                    if (in_array('0', $re)) {        
                        throw new Exception('写入失败');
                    }
         
                    Db::commit();
                    datamsg(WIN,'打赏成功',1);
     
                } catch (Exception $e) {
                    //如获取到异常信息，对所有表的删、改、写操作，都会回滚至操作前的状态：
                    Db::rollback();
                    datamsg(LOSE,'赠送失败');
                }   
                
            } else {
                datamsg(LOSE, $result['mess']);
            }
        }else{
            datamsg(LOSE,'请求方式错误');
        }
    }

    /**
     * @func获取直播间的基本信息
     * @param shop_id店铺id
     */
    public function aliveinformation()
    {
        if (request()->isPost()) {
            $token = input('param.token');
            /*
			if(empty($token)){
                datamsg(LOSE,'请传入你的token');
            }
			*/
            $shop_id = input('param.shop_id');
            if(empty($shop_id)){
                datamsg(LOSE,'请传入店铺id');
            }
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(0);
            if ($result['status'] == 200) {
                $shop_id = input('post.shop_id');
                $getUserId = db('apply_info')->where('shop_id',$shop_id)->order('id DESC')->value('user_id');
                $data = db('member')->where(['id'=>$getUserId])->field('user_name,headimgurl')->find();

                $data['id'] = $getUserId;
                bandPid($getUserId,(int)trim(input('post.shareid')));
                $data['user_name'] = $data['user_name'] ? $data['user_name'] : '匿名';
                // $data['headimgurl'] = db('shops')->where(['id'=>$shop_id])->value('logo');
                // if(strpos($data['headimgurl'],'http') !== false){
                //     $data['headimgurl'] = $data['headimgurl'] ? $data['headimgurl'] : $this->webconfig['weburl'].'/uploads/default.jpg';
                // }else{
                //     $data['headimgurl'] = $data['headimgurl'] ? $this->webconfig['weburl'].'/'.$data['headimgurl'] : $this->webconfig['weburl'].'/uploads/default.jpg';
                // }
                $alive = db('alive')->where(['shop_id'=>$shop_id])->find();
                $result = $gongyong->apivalidate(1);
                $user_id = 0;
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                }
                if(!$this->isPay($alive['room'],$user_id)) return returnJson(400,'请先支付');
                $data['bicount'] = db('alive_givegift')->where(['shop_id'=>$shop_id])->sum('redbi');
                $data['ordercount'] = db('order')->where(['user_id'=>$result['user_id'],'shop_id'=>$shop_id,'state'=>1])->count();
                $data['system_notice'] = $this->webconfig['alivenotice'];
                $data['shop_id'] = $shop_id;
                $data['room'] = $alive['room'];
                $data['title'] = $alive['title'];
                $data['user_profile'] = $alive['user_profile'];
                $data['room_notice'] =$alive['notice'];
                $data['start_time'] =$alive['start_time'];
                $data['end_time'] =$alive['end_time'];
                if($this->webconfig['cos_file'] == '开启'){
                    $data['cover'] = empty($alive['cover']) ? '' : config('tengxunyun')['cos_domain'].'/'.$alive['cover'];

                }else{
                    $data['cover'] = $this->webconfig['weburl'].'/'.$alive['cover'];
                }
                if(empty($data['cover'])){
                    $data['headimgurl'] = $data['headimgurl'] ? $data['headimgurl'] : $this->webconfig['weburl'].'/uploads/default.jpg';
                }else{
                    $data['headimgurl'] = $data['cover'];    //我要直播页面 上显示图片切换为直播间房间的图片
                }

                $data['type_name'] = db('type')->where(['id'=>$alive['type_id']])->value('type_name');
                $is_follow = db('coll_shops')->where(['shop_id'=>$shop_id,'user_id'=>$result['user_id']])->count();
                $data['is_follow'] = $is_follow ? 1 : 0;

                //在线人数
                $data['online_num'] = db('alive_comein')->where(['room'=>$alive['room']])->count();
                $data['online_num'] = $data['online_num'] + 600;
                //关注人数
                $data['follow_num'] = db('coll_shops')->where(['shop_id'=>$shop_id])->count();
                $data['follow_num'] =  $data['follow_num'] +600;

                $data['type_name'] = db('type')->where(['id'=>$alive['type_id']])->value('type_name');
                $is_follow = db('coll_shops')->where(['shop_id'=>$shop_id,'user_id'=>$result['user_id']])->count();
                $data['is_follow'] = $is_follow ? 1 : 0;

                //查询是否生成粉丝数据
                $user_id = $result['user_id'];
                $room = $alive['room'];
                $follow = db('alive_fans')->where(['user_id'=>$user_id,'room'=>$room])->find();
                //默认未关注直播间
                if(empty($follow) && $user_id && $room){
                    $arr['user_id'] = $user_id;
                    $arr['room'] = $room;
                    $arr['integral'] = 0;
                    $arr['isfollow'] = 0;
                    $arr['addtime'] = time();
                    //print_r($data);die();
                    Db::name('alive_fans')->insert($arr);
                }

                datamsg(WIN,'获取成功',$data);
            } else {
                datamsg(LOSE, $result['mess']);
            }
        }else{
            datamsg(LOSE,'请求方式错误');
        }
    }


    /**
     * @func获取直播间的基本信息
     * @param shop_id店铺id
     */
    public function giftsranking()
    {
        if (request()->isPost()) {
            $token = input('param.token');
            if (empty($token)) {
                datamsg(LOSE, '请传入你的token');
            }
            $shop_id = input('param.shop_id');
            if (empty($shop_id)) {
                datamsg(LOSE, '请传入店铺id');
            }
            $givefigts = db('alive_givegift')->where(['shop_id'=>$shop_id])->group('uid')->field('sum(redbi) as countredbi,uid')->order('countredbi desc')->limit(10)->select();
            foreach($givefigts as $key=>&$value){

                $member = db('member')->where(['id'=>$value['uid']])->find();

                $value['username']=$member['user_name'] ? $member['user_name'] : '匿名';

                $value['headimgurl']=$member['headimgurl'] ? $this->webconfig['weburl'].'/'.$member['headimgurl'] : $this->webconfig['weburl'] . '/uploads/default.jpg';;

            }

            datamsg(WIN,'获取成功',$givefigts);

        }else{

            datamsg(LOSE,'请求方式错误');

        }
    }

    // 直播间举报
    public function report(){
        if (request()->isPost()) {
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if ($result['status'] == 200) {
                $user_id = $result['user_id'];
                // $mid = input('post.mid');
                $type = input('post.type');
                $tips = input('post.tips');
                $shop_id = input('post.shop_id');
                if(empty($type)){
                    datamsg(LOSE,'请选择举报内容');
                }
                if(empty($tips)){
                    datamsg(LOSE,'请输入举报或建议内容');
                }
                if(empty($shop_id)){
                    datamsg(LOSE,'缺少房间参数');
                }
                if($user_id){
                    $data['uid'] = $user_id;
                    // $data['mid'] = $mid;
                    $data['type'] = $type;
                    $data['tips'] = $tips;
                    $data['createtime'] = time();
                    $data['status'] = 2;
                    $data['shop_id'] = $shop_id;
                    $res = db('alive_report')->insertGetId($data);
                    $pic = input('param.pic');
                    $datapic = explode(',', $pic);
                    $picarr = [];
                    foreach ($datapic as $key => $value) {
                        $picarr[$key]['pathurl'] = $value;
                        $picarr[$key]['fid'] = $res;
                    }
                    $resultPic = Db::name('room_report_pic')->insertAll($picarr);

                    if ($res && $resultPic) {
                        Db::commit();
                        datamsg(WIN, '提交成功');
                    } else {
                        Db::rollback();
                        datamsg(LOSE, '提交失败');
                    }

                }else{
                    datamsg(LOSE, '请先登录');
                }
            } else {
                datamsg(LOSE, $result['mess']);
            }
        }else{
            datamsg(LOSE,'请求方式错误');
        }
    }

    // 直播间客服列表
    public function liveRoomServiceList(){
        if (request()->isPost()) {
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if ($result['status'] == 200) {
                $user_id = $result['user_id'];
                $shop_id = input('post.shop_id');
                if($user_id){
                    if(empty($shop_id)){
                        datamsg(LOSE, '缺少商户参数');
                    }else{
                        // $userIds = db('customer_service')->where(['status'=>1,'shop_id'=>$shop_id])->column('user_id');
                        $shopBossId = db('member')->where(['shop_id'=>$shop_id])->value('id');
                        $serviceList = db('member')->where(['pid'=>$shopBossId])->select();
                        if($serviceList){
                            foreach($serviceList as $k=>$v){
                                $serviceList[$k]['headimgurl'] = !empty($serviceList[$k]['headimgurl']) ? $this->webconfig['weburl'].'/'.$serviceList[$k]['headimgurl'] : '';
                                $serviceList[$k]['toid'] = db('rxin')->where(['user_id'=>$v['id']])->value('token');
                            }
                        }
                        datamsg(WIN, '获取成功',$serviceList);
                    }
                }else{
                    datamsg(LOSE, '请先登录');
                }
            } else {
                datamsg(LOSE, $result['mess']);
            }
        }else{
            datamsg(LOSE,'请求方式错误');
        }
    }

    /**
     * 分享前数据
     */
    public function shareData(){
        if (request()->isPost()) {
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if ($result['status'] == 200) {

                $user_id = $result['user_id'];

                //生成二维码
                Vendor('phpqrcode.phpqrcode');
                //生成二维码图片
                $object = new \QRcode();
                $imgrq = date('Ymd',time());
                if(!is_dir("./uploads/share/".$user_id)){
                    mkdir("./uploads/share/".$user_id);
                }
                //获取下载页面网址
                $url = $this->getConfigInfo(149);
                $url = $url['value']."/index/inviter/".$user_id;//绑定当前用户id
                $imgfilepath = "./uploads/share/".$user_id."/qrcode_".$user_id.".jpg";
                $object->png($url, $imgfilepath, 'H', 15, 2);
                $imgurlfile = "/uploads/share/".$user_id."/qrcode_".$user_id.".jpg";

                //二维码图片生成成功
                $data['qrcodeurl'] = $this->webconfig['weburl'].$imgurlfile;

                //合并图片

                //获取推广背景图
                $bg = $this->getConfigInfo(151);
                $QR = $bg['value'];
                $QR = imagecreatefromstring(file_get_contents(".".$QR));
                $imgurlfile = imagecreatefromstring(file_get_contents(".".$imgurlfile));
                $QR_width = imagesx($QR);//背景图片宽度

                $QR_height = imagesy($QR);//背景图片高度
                $imgurlfile_width = imagesx($imgurlfile);//二维码图片宽度
                $imgurlfile_height = imagesy($imgurlfile);//二维码图片高度
                //$imgurlfile_qr_width = $QR_width / 5;
                $scale = $imgurlfile_width/$imgurlfile_qr_width;
                //$imgurlfile_qr_height = $imgurlfile_height/$scale;

                $imgurlfile_qr_width = 250;
                $imgurlfile_qr_height = 250;

                $from_width = ($QR_width - $imgurlfile_qr_width) / 2;
                $from_width1 = ($QR_height - $imgurlfile_qr_height) / 1.15;
                //重新组合图片并调整大小
                imagecopyresampled($QR, $imgurlfile, $from_width, $from_width1, 0, 0, $imgurlfile_qr_width,$imgurlfile_qr_height, $imgurlfile_width, $imgurlfile_height);

                $img_path = "./uploads/share/".$user_id."/bgqrcode_". $user_id .".jpg";
                //存放拼接后的图片到本地
                imagejpeg($QR, $img_path);

                $data['tgimg'] = $this->webconfig['weburl']."/uploads/share/".$user_id."/bgqrcode_". $user_id .".jpg";

                //分享信息
                $sharedata = db('config')->where(['ca_id'=>12])->field('id,ename,value,values')->order("id desc")->select();
                $j=0;
                foreach($sharedata as $k=>$v){
                    if(in_array($v['id'], array(152,153,154,166))){
                        $arr = array("152"=>"端庄版","153"=>"硬核版","154"=>"正常版","166"=>"");
                        $data['wx'][$j]['value'] = $arr[$v['id']];
                        $data['wx'][$j]['name'] = $v['value'];
                        $j++;
                    }else{
                        $data[$v['ename']] = $v['value'];
                    }
                }

                datamsg(WIN,'获取成功',$data);
            }else{
                datamsg(LOSE,$result['mess']);
            }
        }else{
            datamsg(LOSE,'请求方式不正确');
        }
    }

    /**
     * 分享直播间
     */
    public function sharelive(){
        $id = input('param.id');
        $alive = db('alive')->where(['id'=>$id])->find();
        if(empty($alive)){
            datamsg(LOSE,'没有找到直播间');
        }

        //$result = db('alive')->insert($data);
        if($result){
            datamsg(WIN,'分享成功',$status);
        }else{
            datamsg(LOSE,'分享失败');
        }
    }

    /**
     * 分享app
     */
    public function shareApp(){


        //$result = db('alive')->insert($data);
        if($result){
            datamsg(WIN,'分享成功',$status);
        }else{
            datamsg(LOSE,'分享失败');
        }
    }

    /**
     * 分享成功
     */
    public function shareOk(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    //7分享（次）
                    $num = $this->getIntegralRules(7);//获取积分
                    $this->addIntegral($user_id,$num,7);

                    //5分享直播间（单日上线5次）
                    $num = $this->getAliveIntegralRules(5);
                    $this->addAliveIntegral($user_id,$shopid,$room,$num,5);

                    $value = array('status'=>200,'mess'=>'分享成功！','data'=>array('status'=>200));
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


    /**
     * 直播间日志
     */
    public function aliveLog(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $room = input('post.room');

                    //判断是否是自己的直播间
                    $alive = db('alive')->where(['room'=>$room])->find();
                    $user = db('member')->where(['id'=>$user_id])->find();
                    if($alive['shop_id'] == $user['shop_id']){
                        $fromid = input('post.token');
                        $type = input('post.type');
                        $data['room'] = $room;
                        $data['fromid'] = $fromid;
                        $data['fromuid'] = $user_id;
                        $data['type'] = $type;
                        $data['text'] = $type == 1 ? "主播上线" : "主播下线";
                        $data['addtime'] = time();
                        //print_r($data);die();
                        Db::name('alive_log')->insert($data);
                    }

                    $value = array('status'=>200,'mess'=>'成功！','data'=>array('status'=>200));
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

    /**
     * 直播间粉丝日志
     */
    public function aliveFansLog(){
        if(request()->isPost()){
            if(input('post.token')){
                $gongyong = new GongyongMx();
                $result = $gongyong->apivalidate();
                if($result['status'] == 200){
                    $user_id = $result['user_id'];
                    $room = input('post.room');

                    $fromid = input('post.token');
                    $type = input('post.type');
                    $data['room'] = $room;
                    $data['fromid'] = $fromid;
                    $data['fromuid'] = $user_id;
                    $data['type'] = $type;
                    $data['text'] = $type == 1 ? "进入直播间" : "退出直播间";
                    $data['addtime'] = time();
                    //print_r($data);die();
                    Db::name('alive_fans_log')->insert($data);

                    Db::name('order')->where('id',$v['order_id'])->update(array('order_status'=>2,'shouhou'=>0,'can_time'=>time()));

                    if($type == 2){
                        //上一个进入时间
                        $prevdata = db('alive_fans_log')->where(['room'=>$room,fromuid=>$user_id,type=>1])->where("addtime", "<", $data['addtime'])->order("addtime", "desc")->find();

                        if($prevdata){
                            $timediff = abs($data['addtime'] - $prevdata['addtime']);
                            $mintues = floor($timediff / 60);
                        }else{
                            $mintues = 0;
                        }


                        $alive = db('alive')->where(['room'=>$room])->find();
                        $shopid = $alive['shop_id'];

                        //观看直播送积分
                        if($mintues >= 10 && $mintues < 30){//1累积观看10分钟
                            $num0 = $this->getAliveIntegralRules(1);
                            $this->addAliveIntegral($user_id,$shopid,$room,$num,1);
                        }elseif($mintues >= 30 && $mintues < 60){//2累积观看30分钟 3累积观看60分钟
                            $num0 = $this->getAliveIntegralRules(1);
                            $this->addAliveIntegral($user_id,$shopid,$room,$num0,1);
                            $num1 = $this->getAliveIntegralRules(2);
                            $this->addAliveIntegral($user_id,$shopid,$room,$num1,2);

                            $num3 = $this->getIntegralRules(5);//获取积分
                            $this->addIntegral($user_id,$num3,5,$orders['id']);

                        }elseif($mintues >= 60){//3累积观看60分钟
                            $num0 = $this->getAliveIntegralRules(1);
                            $this->addAliveIntegral($user_id,$shopid,$room,$num0,1);
                            $num1 = $this->getAliveIntegralRules(2);
                            $this->addAliveIntegral($user_id,$shopid,$room,$num1,2);
                            $num2 = $this->getAliveIntegralRules(3);
                            $this->addAliveIntegral($user_id,$shopid,$room,$num2,3);


                            $num3 = $this->getIntegralRules(5);//获取积分
                            $this->addIntegral($user_id,$num3,5,$orders['id']);
                        }
                    }

                    $value = array('status'=>200,'mess'=>'成功！','data'=>array('status'=>200));
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