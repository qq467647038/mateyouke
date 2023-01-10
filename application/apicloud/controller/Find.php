<?php
namespace app\apicloud\controller;
use app\apicloud\model\Gongyong as GongyongMx;
use think\Db;
use Qcloud\Cos\Client;

class Find extends Common {

    /**
     * @func获取发现列表
     * @param size 获取的长度
     * @param client_id 设备1 app
     * @param api_token 接口请求验证失败
     * @param token 用户登录的token
     * @date 2019-3-8
     * @author LX
     */
    public function findinfo(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if($result['status'] == 200) {
                $value = Db::name('member')
                    ->alias('m')
                    ->join('sp_find f', 'm.id = f.mid', 'LEFT')
                    ->where(['m.id'=>$result['user_id']])
                    ->field('m.id,m.user_name,m.summary,m.phone,m.headimgurl,COUNT(f.mid) as dynamic')
                    ->group('f.mid')
                    ->find();
                if(empty($value['user_name'])){
                    $value['user_name']=$value['phone'];
                    unset($value['phone']);
                }else{
                    unset($value['phone']);
                }
                $value['followcount'] = db('find_follow')->where(['mid'=>$result['user_id']])->count();
                $find_ids = db('find')->where(['mid'=>$result['user_id']])->column('id');
                $value['laudcount'] = db('find_laud')->where(['fid'=>['in',$find_ids]])->count();
                datamsg(WIN,'获取数据成功',$value);
            }else{
                datamsg(LOSE,$result['mess']);
            }
        }else{
            datamsg(LOSE,'请求方式不正确');
        }
    }



    /**
     * @func获取发现列表
     * @param size 获取的长度
     * @param client_id 设备1 app
     * @param api_token 接口请求验证失败
     * @param token 用户登录的token
     * @date 2019-3-8
     * @author LX
     */
    public function index(){
        if(request()->isPost()){
            $page = input('param.page') ? input('param.page') : 1;
            $size = input('param.size') ?  input('param.size') : 5;
            if(!is_numeric($size)){
                datamsg(LOSE,'长度类型错误');
            }
            $type = input('param.type');
            $where=[];
            if(!empty($type)){
                if($type != 1){
                    datamsg(400,'类型错误');
                }
            }
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(0);
            if($result['status'] == 200){
                $mid = input('param.mid');      //查看我的动态
                if(!empty($mid)){
                    $where['mid']=$mid;
                }
                $where['ishot'] = 1;

                if(!empty($type)) {         //是否推荐和关注判断
                    if (isset($result['user_id'])) {
                        $uids = db('find_follow')->where(['uid' => $result['user_id']])->column('mid');
                        if (empty($uids)) {
                            $where['mid'] = ['in', -1];
                        } else {
                            $where['mid'] = ['in', $uids];
                        }
                    }
                }


                $list = Db::name('find')
                    ->alias('f')
                    ->join('sp_goods g', 'f.gid = g.id', 'LEFT')
                    ->join('sp_member m', 'f.mid = m.id', 'LEFT')
                    ->where(['is_show' => 1])
                    ->where($where)
                    ->field('f.id,f.mid,f.gid,f.describe,f.createtime,f.star,g.id as gid,g.goods_name,g.thumb_url,m.user_name,m.phone,m.headimgurl')
                    ->order('createtime desc')
                    ->paginate($size)
                    ->each(function ($item, $key) {
                        $item['createtime'] = time_ago(date('Y-m-d H:i:s', $item['createtime']));
                        $imgurl_arr = db('find_pic')->where(['fid' => $item['id']])->column('pathurl');
                        if($this->webconfig['cos_file'] == '开启'){
                            $domain = config('tengxunyun')['cos_domain'];
                        }else{
                            $domain = $this->webconfig['weburl'];
                        }
                        foreach($imgurl_arr as $key1=>$value){

                            $item['imgurl'][$key1] = $domain.'/'.$value;
                        }
                        $item['thumb_url'] = $this->webconfig['weburl'].'/'.$item['thumb_url'];
                        $item['headimgurl'] = $this->webconfig['weburl'].'/'.$item['headimgurl'];
                        $item['laudcount'] = db('find_laud')->where(['fid'=>$item['id']])->count();
                        $item['download'] = db('find_download')->where(['fid'=>$item['id']])->count();
                        $item['sharecount'] = db('find_share')->where(['fid'=>$item['id']])->count();
                        return $item;
                    });
                $list_copy = $list->toArray();
                foreach($list_copy['data'] as $key2=>&$value2){
                    if(isset($result['user_id'])){
                        $is_follow = db('find_follow')->where(['uid'=>$result['user_id'],'mid'=>$value2['mid']])->count();
                        $is_follow ? $list_copy['data'][$key2]['is_follow']=1 : $list_copy['data'][$key2]['is_follow']=0;
                        $is_laud = db('find_laud')->where(['mid'=>$result['user_id'],'fid'=>$value2['id']])->count();
                        $is_laud ? $list_copy['data'][$key2]['is_laud']=1 : $list_copy['data'][$key2]['is_laud']=0;
                        $is_share = db('find_share')->where(['uid'=>$result['user_id'],'fid'=>$value2['id']])->count();
                        $is_share ? $list_copy['data'][$key2]['is_share']=1 : $list_copy['data'][$key2]['is_share']=0;
                        $is_download = db('find_download')->where(['uid'=>$result['user_id'],'fid'=>$value2['id']])->count();
                        $is_download ? $list_copy['data'][$key2]['is_download']=1 : $list_copy['data'][$key2]['is_download']=0;
                    }else{
                        $list_copy['data'][$key2]['is_follow']=0;
                        $list_copy['data'][$key2]['is_laud']=0;
                        $list_copy['data'][$key2]['is_download']=0;
                    }
                }
                datamsg(WIN,'获取数据成功',$list_copy);
            }else{
                datamsg(LOSE,$result['mess']);
            }
        }else{
            datamsg(LOSE,'请求方式不正确');
        }
    }

    // 我的评价列表
    public function myApraiseList(){
        if(request()->isPost()){
            $size = input('param.size') ?  input('param.size') : 5;
            if(!is_numeric($size)){
                datamsg(LOSE,'长度类型错误');
            }
            $type = input('param.type');
            $where=[];
            if(!empty($type)){
                if($type != 1){
                    datamsg(400,'类型错误');
                }
            }
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if($result['status'] == 200){

                $where['mid']=$result['user_id'];


                if(!empty($type)) {         //是否推荐和关注判断
                    if (isset($result['user_id'])) {
                        $uids = db('find_follow')->where(['uid' => $result['user_id']])->column('mid');
                        if (empty($uids)) {
                            $where['mid'] = ['in', -1];
                        } else {
                            $where['mid'] = ['in', $uids];
                        }
                    }
                }

                // echo $domain;
                $list = Db::name('find')
                    ->alias('f')
                    ->join('sp_order o', 'f.order_id = o.id', 'LEFT')
                    ->join('sp_member m', 'f.mid = m.id', 'LEFT')
                    // ->where(['is_show' => 1])
                    ->where($where)
                    ->field('f.id,f.mid,f.gid,f.describe,f.createtime,f.star,o.id as oid,o.ordernumber as ordernuber,m.user_name,m.phone,m.headimgurl')
                    ->order('createtime desc')
                    ->paginate($size)
                    ->each(function ($item, $key) {
                        $item['createtime'] = time_ago(date('Y-m-d H:i:s', $item['createtime']));
                        $imgurl_arr = db('find_pic')->where(['fid' => $item['id']])->column('pathurl');
                        foreach($imgurl_arr as $key1=>$value){
                            // 如果开启了腾讯云存储，使用腾讯云的访问域名
                            if($this->webconfig['cos_file'] == '开启'){
                                $domain = config('tengxunyun')['cos_domain'];
                            }else{
                                $domain = $this->webconfig['weburl'];
                            }
                            $item['imgurl'][$key1] = $domain.'/'.$value;
                        }
                        $item['headimgurl'] = $this->webconfig['weburl'].'/'.$item['headimgurl'];
                        $item['laudcount'] = db('find_laud')->where(['fid'=>$item['id']])->count();
                        $item['download'] = db('find_download')->where(['fid'=>$item['id']])->count();
                        $item['sharecount'] = db('find_share')->where(['fid'=>$item['id']])->count();
                        return $item;
                    });
                $list_copy = $list->toArray();
                foreach($list_copy['data'] as $key2=>&$value2){

                    $is_follow = db('find_follow')->where(['uid'=>$result['user_id'],'mid'=>$value2['mid']])->count();
                    $is_follow ? $list_copy['data'][$key2]['is_follow']=1 : $list_copy['data'][$key2]['is_follow']=0;
                    $is_laud = db('find_laud')->where(['mid'=>$result['user_id'],'fid'=>$value2['id']])->count();
                    $is_laud ? $list_copy['data'][$key2]['is_laud']=1 : $list_copy['data'][$key2]['is_laud']=0;
                    $is_share = db('find_share')->where(['uid'=>$result['user_id'],'fid'=>$value2['id']])->count();
                    $is_share ? $list_copy['data'][$key2]['is_share']=1 : $list_copy['data'][$key2]['is_share']=0;
                    $is_download = db('find_download')->where(['uid'=>$result['user_id'],'fid'=>$value2['id']])->count();
                    $is_download ? $list_copy['data'][$key2]['is_download']=1 : $list_copy['data'][$key2]['is_download']=0;
                    $goods = db('order_goods')->find($value2['oid']);
                    if($goods){
                        $goods['thumb_url'] = $this->webconfig['weburl'].'/'.$goods['thumb_url'];
                        $list_copy['data'][$key2]['goods']=$goods;
                    }else{
                        $list_copy['data'][$key2]['goods']='';
                    }



                }
                datamsg(WIN,'获取数据成功',$list_copy);
            }else{
                datamsg(LOSE,$result['mess']);
            }
        }else{
            datamsg(LOSE,'请求方式不正确');
        }
    }

    // 购物车评价列表
    public function cartApraiseList(){
        if(request()->isPost()){
            $size = input('param.size') ?  input('param.size') : 5;
            if(!is_numeric($size)){
                datamsg(LOSE,'长度类型错误');
            }
            $type = input('param.type');
            $where=[];
            if(!empty($type)){
                if($type != 1){
                    datamsg(400,'类型错误');
                }
            }
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if($result['status'] == 200){

                // $where['f.shop_id']=$shop_id;
                $shop_id = input('post.shop_id');
                if(empty($shop_id)){
                    datamsg(LOSE,'缺少店铺id参数');
                }
                $orderIds = db('order')->where(['shop_id'=>$shop_id])->column('id');
                // dump($orderIds);die;
                // $orderIds = array(1,2,3);
                if($orderIds){
                    $where['f.order_id'] = ['in',$orderIds];
                }
                // dump($where);die;



                if(!empty($type)) {         //是否推荐和关注判断
                    if (isset($result['user_id'])) {
                        $uids = db('find_follow')->where(['uid' => $result['user_id']])->column('mid');
                        if (empty($uids)) {
                            $where['mid'] = ['in', -1];
                        } else {
                            $where['mid'] = ['in', $uids];
                        }
                    }
                }

                // echo $domain;
                $list = Db::name('find')
                    ->alias('f')
                    ->join('sp_order o', 'f.order_id = o.id', 'LEFT')
                    ->join('sp_member m', 'f.mid = m.id', 'LEFT')
                    ->where(['f.is_show' => 1])
                    ->where($where)
                    ->field('f.id,f.mid,f.gid,f.describe,f.createtime,f.star,o.id as oid,o.ordernumber as ordernuber,m.user_name,m.phone,m.headimgurl')
                    ->order('createtime desc')
                    ->paginate($size)
                    ->each(function ($item, $key) {
                        $item['createtime'] = time_ago(date('Y-m-d H:i:s', $item['createtime']));
                        $imgurl_arr = db('find_pic')->where(['fid' => $item['id']])->column('pathurl');
                        foreach($imgurl_arr as $key1=>$value){
                            // 如果开启了腾讯云存储，使用腾讯云的访问域名
                            if($this->webconfig['cos_file'] == '开启'){
                                $domain = config('tengxunyun')['cos_domain'];
                            }else{
                                $domain = $this->webconfig['weburl'];
                            }
                            $item['imgurl'][$key1] = $domain.'/'.$value;
                        }
                        if(empty($item['headimgurl'])){
                            $item['headimgurl'] = $this->webconfig['weburl'].'/static/images/head.png';
                        }else{
                            $item['headimgurl'] = $this->webconfig['weburl'].'/'.$item['headimgurl'];
                        }
                        $item['laudcount'] = db('find_laud')->where(['fid'=>$item['id']])->count();
                        $item['download'] = db('find_download')->where(['fid'=>$item['id']])->count();
                        $item['sharecount'] = db('find_share')->where(['fid'=>$item['id']])->count();
                        return $item;
                    });
                $list_copy = $list->toArray();
                foreach($list_copy['data'] as $key2=>&$value2){

                    $is_follow = db('find_follow')->where(['uid'=>$result['user_id'],'mid'=>$value2['mid']])->count();
                    $is_follow ? $list_copy['data'][$key2]['is_follow']=1 : $list_copy['data'][$key2]['is_follow']=0;
                    $is_laud = db('find_laud')->where(['mid'=>$result['user_id'],'fid'=>$value2['id']])->count();
                    $is_laud ? $list_copy['data'][$key2]['is_laud']=1 : $list_copy['data'][$key2]['is_laud']=0;
                    $is_share = db('find_share')->where(['uid'=>$result['user_id'],'fid'=>$value2['id']])->count();
                    $is_share ? $list_copy['data'][$key2]['is_share']=1 : $list_copy['data'][$key2]['is_share']=0;
                    $is_download = db('find_download')->where(['uid'=>$result['user_id'],'fid'=>$value2['id']])->count();
                    $is_download ? $list_copy['data'][$key2]['is_download']=1 : $list_copy['data'][$key2]['is_download']=0;
                    $goods = db('order_goods')->find($value2['oid']);
                    if($goods){
                        $goods['thumb_url'] = $this->webconfig['weburl'].'/'.$goods['thumb_url'];
                        $list_copy['data'][$key2]['goods']=$goods;
                    }else{
                        $list_copy['data'][$key2]['goods']='';
                    }



                }
                datamsg(WIN,'获取数据成功',$list_copy);
            }else{
                datamsg(LOSE,$result['mess']);
            }
        }else{
            datamsg(LOSE,'请求方式不正确');
        }
    }

    // 购物车评价总数
    public function cartApraiseCount(){
        if(request()->isPost()){
            $size = input('param.size') ?  input('param.size') : 5;
            if(!is_numeric($size)){
                datamsg(LOSE,'长度类型错误');
            }
            $type = input('param.type');
            $where=[];
            if(!empty($type)){
                if($type != 1){
                    datamsg(400,'类型错误');
                }
            }
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if($result['status'] == 200){

                // $where['f.shop_id']=$shop_id;
                $shop_id = input('post.shop_id');
                if(empty($shop_id)){
                    datamsg(LOSE,'缺少店铺id参数');
                }
                $orderIds = db('order')->where(['shop_id'=>$shop_id])->column('id');
                // dump($orderIds);die;
                // $orderIds = array(1,2,3);
                if($orderIds){
                    $where['f.order_id'] = ['in',$orderIds];
                }
                // dump($where);die;




                // echo $domain;
                $count = Db::name('find')
                    ->alias('f')
                    ->join('sp_order o', 'f.order_id = o.id', 'LEFT')
                    ->join('sp_member m', 'f.mid = m.id', 'LEFT')
                    ->where(['f.is_show' => 1])
                    ->where($where)
                    ->field('f.id,f.mid,f.gid,f.describe,f.createtime,f.star,o.id as oid,o.ordernumber as ordernuber,m.user_name,m.phone,m.headimgurl')
                    ->order('createtime desc')
                    ->count();

                datamsg(WIN,'获取数据成功',array('count'=>(int)$count));
            }else{
                datamsg(LOSE,$result['mess']);
            }
        }else{
            datamsg(LOSE,'请求方式不正确');
        }
    }

    /**
     * @func 我的评价条数
     * @param size 获取的长度
     * @param client_id 设备1 app
     * @param api_token 接口请求验证失败
     * @param token 用户登录的token
     * @date 2019-3-8
     * @author LX
     */
    public function myApraise(){
        if(request()->isPost()){
            $size = input('param.size') ?  input('param.size') : 5;
            if(!is_numeric($size)){
                datamsg(LOSE,'长度类型错误');
            }
            $type = input('param.type');
            $where=[];
            if(!empty($type)){
                if($type != 1){
                    datamsg(400,'类型错误');
                }
            }
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if($result['status'] == 200){
                if($result['user_id']){
                    // $mid = input('param.mid');      //查看我的动态
                    // if(!empty($mid)){
                    $where['mid']=$result['user_id'];
                    // }


                    if(!empty($type)) {         //是否推荐和关注判断
                        if (isset($result['user_id'])) {
                            $uids = db('find_follow')->where(['uid' => $result['user_id']])->column('mid');
                            if (empty($uids)) {
                                $where['mid'] = ['in', -1];
                            } else {
                                $where['mid'] = ['in', $uids];
                            }
                        }
                    }


                    $count = Db::name('find')
                        ->alias('f')
                        ->join('sp_goods g', 'f.gid = g.id', 'LEFT')
                        ->join('sp_member m', 'f.mid = m.id', 'LEFT')
                        ->where(['is_show' => 1])
                        ->where($where)
                        ->field('f.id,f.mid,f.gid,f.describe,f.createtime,g.id as gid,g.goods_name,g.thumb_url,m.user_name,m.phone,m.headimgurl')
                        ->order('createtime desc')
                        ->count();

                    datamsg(WIN,'获取数据成功',array('count'=>$count));
                }else{
                    datamsg(WIN,'未登录',array('count'=>0));
                }

            }else{
                datamsg(WIN,$result['mess'],array('count'=>0));
            }
        }else{
            datamsg(WIN,'请求方式不正确',array('count'=>0));
        }
    }


    /**
     * 发现板块个人主页商品
     */
    public function getfindgoods(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            $size = input('param.size') ? input('param.size') : 5;

            $mid = input('param.mid');
            if($result['status'] == 200) {
                if(!isset($result['user_id'])){
                    datamsg(LOSE,'请登录您的token');
                }
                $member = db('member')->where(['id'=>$mid])->find();
                if(empty($member['shop_id'])){
                    datamsg(LOSE,'您还不是店主');
                }
                $goodslist = db('goods')
                    ->where(['onsale'=>1,'checked'=>1,'shop_id'=>$member['shop_id']])
                    ->field("id,goods_name,thumb_url,market_price")
                    ->paginate($size)
                    ->each(function ($item, $key) {
                        $item['thumb_url'] = $this->webconfig['weburl'].'/'.$item['thumb_url'];
                        return $item;
                    });
                if(empty($goodslist)){
                    datamsg(WIN,'你还没有发布过商品');
                }else{
                    datamsg(WIN,'获取成功',$goodslist);
                }
            }else{
                datamsg(LOSE,$result['mess']);
            }
        }else{
            datamsg(LOSE,'请求方式不正确');
        }
    }



    /**
     * 发现板块个人主页详细信息
     */
    public function getfindgoodsinfor(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            $size = input('param.size') ? input('param.size') : 5;
            $mid = input('param.mid');
            if(empty($mid)){
                datamsg(LOSE,'mid不能为空');
            }
            if($result['status'] == 200) {
                $member = db('member')->where(['id'=>$mid])->find();
                if(empty($member['shop_id'])){
                    datamsg(LOSE,'店铺id错误');
                }
                $shops = db('shops')->where(['id'=>$member['shop_id']])->field('shop_name,shop_desc')->find();
                $shops['dt'] = db('find')->where(['mid'=>$mid])->count();
                datamsg(WIN,'获取成功',$shops);
            }else{
                datamsg(LOSE,$result['mess']);
            }
        }else{
            datamsg(LOSE,'请求方式不正确');
        }
    }



    /**
     * @func用户点赞发现
     * @param fid 发现的id
     * @param client_id 设备1 app
     * @param api_token 接口请求验证失败
     * @param token 用户登录的token
     * @date 2019-3-8
     * @author LX
     */
    public function laud(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            $fid = input('param.fid');
            if(empty($fid)){
                datamsg(LOSE,'发现id不能为空');
            }else{
                $count = db('find')->where(['id'=>$fid,'is_show'=>0])->count();
                if($count){
                    datamsg(LOSE,'没有找到对应的发现文章，可能已经被删除');
                }
            }
            if($result['status'] == 200) {
                if(!isset($result['user_id'])){
                    datamsg(LOSE,'请登录');
                }
                $is_laud = db('find_laud')->where(['mid'=>$result['user_id'],'fid'=>$fid])->count();
                if($is_laud){
                    $delfollow = db('find_laud')->where(['fid'=>$fid,'mid'=>$result['user_id']])->delete();
                    if($delfollow){
                        datamsg(WIN,'取消点赞');
                    }else{
                        datamsg(LOSE,'删除没有找到对应的信息');
                    }
                }else {
                    $data['mid'] = $result['user_id'];
                    $data['createtime'] = time();
                    $data['fid'] = $fid;
                    $savefollow = db('find_laud')->insert($data);
                    if ($savefollow) {
                        datamsg(WIN, '点赞成功');
                    } else {
                        datamsg(LOSE, '插入点赞失败，系统错误');
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
     * @func用户关注发现的人
     * @param mid 从发现列表获得，被关注人的id
     * @param client_id 设备1 app
     * @param api_token 接口请求验证失败
     * @param token 用户登录的token
     * @date 2019-3-8
     * @author LX
     */
    public function follow(){
        if(request()->isPost()){
            $data = input('param.');
            $token = input('param.token');
            if(empty($token)){
                datamsg(LOSE,'请传入token');
            }
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            $mid = input('param.mid');
            if($result['status'] == 200) {
                if(empty($mid)){
                    datamsg(LOSE,'关注id不能为空');
                }else{
                    $count = db('member')->where(['id'=>$mid])->count();
                    if($count){
                        if($mid == $result['user_id']){
                            datamsg(LOSE,'对不起，自己不能关注自己');
                        }
                    }else{
                        datamsg(LOSE,'没有找到对应的用户');
                    }
                }
                if(!isset($result['user_id'])){
                    datamsg(LOSE,'用户不存在，或已经禁用');
                }
                $is_laud = db('find_follow')->where(['mid'=>$mid,'uid'=>$result['user_id']])->count();
                if($is_laud){
                    $delfollow = db('find_follow')->where(['mid'=>$mid,'uid'=>$result['user_id']])->delete();
                    if($delfollow){
                        datamsg(WIN,'取消关注');
                    }else{
                        datamsg(LOSE,'删除没有找到对应的信息');
                    }
                }else {
                    $insert['mid'] = $mid;
                    $insert['createtime'] = time();
                    $insert['uid'] = $result['user_id'];
                    $savefollow = db('find_follow')->insert($insert);
                    if ($savefollow) {
                        datamsg(WIN, '关注成功');
                    } else {
                        datamsg(LOSE, '插入关注失败，系统错误');
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
     * @func我的关注列表
     * @param mid 从发现列表获得，被关注人的id
     * @param client_id 设备1 app
     * @param api_token 接口请求验证失败
     * @param token 用户登录的token
     * @date 2019-3-8
     * @author LX
     */
    public function myfollow(){
        if(request()->isPost()){
            $token = input('param.token');
            if(empty($token)){
                datamsg(LOSE,'请传入token');
            }
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            $size = input('param.size') ?  input('param.size') : 5;
            if(!is_numeric($size)){
                datamsg(LOSE,'长度类型错误');
            }
            if($result['status'] == 200) {
                $list = Db::name('find_follow')
                    ->alias('ff')
                    ->join('member m', 'ff.uid = m.id', 'LEFT')
                    ->where(['ff.mid'=>$result['user_id']])
                    ->field('m.id,m.user_name,m.summary,m.phone,m.headimgurl')
                    ->paginate($size)
                    ->each(function($item,$key){
                        if(empty($item['user_name'])){
                            $item['user_name']=$item['phone'];
                            unset($item['phone']);
                        }else{
                            unset($item['phone']);
                        }
                        return $item;
                    });

                datamsg(WIN,'获取成功',$list);
            }else{
                datamsg(LOSE,$result['mess']);
            }
        }else{
            datamsg(LOSE,'请求方式不正确');
        }
    }



    /**
     * @func热门话题
     * @param mid 从发现列表获得，被关注人的id
     * @param client_id 设备1 app
     * @param api_token 接口请求验证失败
     * @param token 用户登录的token
     * @date 2019-3-8
     * @author LX
     */
    public function hottalk(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(0);
            if ($result['status'] == 200) {
                $size = input('param.size') ?  input('param.size') : 5;
                if(!is_numeric($size)){
                    datamsg(LOSE,'长度类型错误');
                }
                $fids = Db::name('find_laud')
                    ->where('fid', 'IN', function($query){
                        $sqlstr = 'COUNT(fid) >'.$this->webconfig['find_hottalknum'];
                        $query->table('sp_find_laud')->group('fid')->having($sqlstr)->field('fid');
                    })
                    ->group('fid')
                    ->column('fid');
                //$map['ishot']=1;
                $map['f.id']=['in',$fids];
                $map['m.shop_id']=['neq',0];    //只获取有店铺的热门活动
                $list = Db::name('find')
                    ->alias('f')
                    ->join('member m', 'f.mid = m.id', 'LEFT')
                    ->join('find_pic fp','fp.fid = f.id','LEFT')
                    ->where($map)
                    ->whereOr(['f.ishot'=>1])
                    ->group('f.id')
                    ->field('f.id,f.describe,f.createtime,m.user_name,m.phone,fp.pathurl,f.mid,f.title')
                    ->paginate($size)
                    ->each(function ($item,$key){
                        if(empty($item['user_name'])){
                            $item['user_name']=$item['phone'];
                            unset($item['phone']);
                        }else{
                            unset($item['phone']);
                        }
                        $item['createtime'] = time_ago(date('Y-m-d H:i:s', $item['createtime']));
                        $item['describe'] = cut_str($item['describe'],40);
                        $item['pathurl'] = $this->webconfig['weburl'].'/'.$item['pathurl'];
                        return $item;
                    });
                datamsg(WIN,'获取成功',$list);
            } else {
                datamsg(LOSE, $result['mess']);
            }
        }else{
            datamsg(LOSE,'请求方式不正确');
        }
    }


    /**
     * @func 根据发现id获取下载图片下载
     */
    public function downloadpic(){
        if(request()->isPost()) {
            $fid = input('param.fid');
            if(empty($fid)){
                datamsg(LOSE,'发现id不能为空');
            }
            $token = input('param.token');
            if(empty($token)){
                datamsg(LOSE,'token不能为空');
            }
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if($result['status'] == 200) {
                if(empty($result['user_id'])){
                    datamsg(LOSE,'用户id没有找到');
                }
                $find_pic = db('find_pic')->where(['fid'=>$fid])->column('pathurl');
                if(empty($find_pic)){
                    datamsg(LOSE,'对不起，没有找到图片信息');
                }
                $downcount = db('find_download')->where(['uid'=>$result['user_id'],'fid'=>$fid])->count();
                if($downcount){
                    foreach($find_pic as $key=>&$value){
                        $value = $this->webconfig['weburl'].'/'.$value;
                    }
                    datamsg(WIN,'获取成功',$find_pic);
                }else{
                    db('find_download')->insert(['fid'=>$fid,'uid'=>$result['user_id'],'createtime'=>time()]);
                    foreach($find_pic as $key=>&$value) {
                        $value = $this->webconfig['weburl'].'/'.$value;
                    }
                    datamsg(WIN,'获取成功',$find_pic);
                }
            }else{
                datamsg(LOSE,$result['mess']);
            }

        }else{
            datamsg(LOSE,'请求方式不正确');
        }
    }



    /**
     * @func 分享到朋友圈
     */
    public function sharewxpy(){
        if(request()->isPost()) {
            $fid = input('param.fid');
            if(empty($fid)){
                datamsg(LOSE,'发现id不能为空');
            }
            $token = input('param.token');
            if(empty($token)){
                datamsg(LOSE,'token不能为空');
            }
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if($result['status'] == 200) {
                if(empty($result['user_id'])){
                    datamsg(LOSE,'用户id没有找到');
                }
                $find_pic = db('find')->where(['id'=>$fid])->count();
                if(empty($find_pic)){
                    datamsg(LOSE,'对不起，该发现已经被删除');
                }
                $sharecount = db('find_share')->where(['fid'=>$fid,'uid'=>$result['user_id']])->count();
                if(empty($sharecount)){
                    db('find_share')->insert(['fid'=>$fid,'uid'=>$result['user_id'],'createtime'=>time()]);
                    datamsg(WIN,'写入成功');
                }else{
                    datamsg(WIN,'获取成功');
                }
                datamsg(WIN,'写入成功');
            }else{
                datamsg(LOSE,$result['mess']);
            }

        }else{
            datamsg(LOSE,'请求方式不正确');
        }
    }







    /***
     * 发布商品获取个人或者店铺商品商品
     */
    public function getgoods(){

        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            $size = input('param.size') ? input('param.size') : 100;
            if($result['status'] == 200) {
                if(!isset($result['user_id'])){
                    datamsg(LOSE,'请登录您的token');
                }
                $member = db('member')->where(['id'=>$result['user_id']])->find();
                if(empty($member['shop_id'])){
                    $order_ids = db('order')->where(['user_id'=>$result['user_id'],'state'=>1])->column('id');
                    if(empty($order_ids)){
                        $order_ids=[-1];
                    }
                    $goods_ids = db('order_goods')
                        ->where(['order_id'=>['in',$order_ids]])
                        ->group('goods_id')
                        ->column('goods_id');
                    $goodslist = db('goods')
                        ->where(['id'=>['in',$goods_ids]])
                        ->where(['onsale'=>1,'checked'=>1])
                        ->field("id,goods_name,cate_id")
                        ->select();
                    if(empty($goodslist)){
                        datamsg(WIN,'你还没有购买过商品');
                    }else{
                        datamsg(WIN,'获取成功',$goodslist);
                    }
                }else{
                    $goodslist = db('goods')
                        ->where(['onsale'=>1,'checked'=>1,'shop_id'=>$member['shop_id']])
                        ->field("id,goods_name,cate_id")
                        ->select();
                    if(empty($goodslist)){
                        datamsg(WIN,'你还没有发布过商品');
                    }else{
                        datamsg(WIN,'获取成功',$goodslist);
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
     * @func获取标签
     * @param mid 从发现列表获得，被关注人的id
     * @param client_id 设备1 app
     * @param api_token 接口请求验证失败
     * @param token 用户登录的token
     * @date 2019-3-8
     */
    public function findtag(){
        $size = input('param.size') ?  input('param.size') : 5;
        if(!is_numeric($size)){
            datamsg(LOSE,'长度类型错误');
        }
        $gid = input('param.gid');
        if(empty($gid)){
            datamsg(LOSE,'商品id不能为空');
        }
        $type = input('param.type');
        if(!empty($type)){
            if($type != 1){
                datamsg(400,'类型错误');
            }
            $where['recommend']=$type;
        }else{
            $where=[];
        }
        $cate=db('goods')->where(['id'=>$gid])->value('cate_id');
        $name = db('find_tags')->where(['is_delete'=>0,'cate_id'=>$cate])->where($where)->field('id,name')->paginate($size);
        datamsg(WIN,'获取成功',$name);
    }

    /**
     * @func添加发现
     * @param mid 从发现列表获得，被关注人的id
     * @param client_id 设备1 app
     * @param api_token 接口请求验证失败
     * @param token 用户登录的token
     * @date 2019-3-8
     * @author LX
     */
    public function addfind(){

        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if($result['status'] == 200) {
                if(!isset($result['user_id'])){
                    datamsg(LOSE, '请先登录');
                }
                $describe = input('param.describe');
                $tags = input('param.tags');
                // $gid = input('param.gid');
                $orderId = input('param.order_id');
                $star = input('param.star');
                $fpic = input('param.pic');
                if (empty($orderId)) {
                    datamsg(LOSE, '缺少订单参数');
                }
                if (empty($describe)) {
                    datamsg(LOSE, '请填写评价内容');
                }
                if (empty($star)) {
                    datamsg(LOSE, '请给商品打分');
                }

                $member = db('member')->where(['id'=>$result['user_id']])->find();
                // if(empty($gid)){
                //     if ($member['shop_id']) {
                //         datamsg(LOSE, '请先发布商品');
                //     }else{
                //         datamsg(LOSE, '您还没有购买过商品');
                //     }
                // }
                if ($result['status'] == 200) {
                    $data['mid'] = $result['user_id'];
                    $data['describe'] = $describe;
                    $data['title'] = $describe;
                    $data['order_id'] = $orderId;
                    $data['star'] = $star;
                    $data['tags'] = $tags;
                    // $data['gid'] = $gid;
                    $data['createtime'] = time();
                    Db::startTrans();
                    $findresult = Db::name('find')->insertGetId($data);
                    $datapic = explode(',', $fpic);
                    $picarr = [];
                    foreach ($datapic as $key => $value) {
                        $picarr[$key]['pathurl'] = $value;
                        $picarr[$key]['fid'] = $findresult;
                    }
                    $resultpic = Db::name('find_pic')->insertAll($picarr);

                    //11购物评价 12优质购物评价（晒图，30字以上）(直播间粉丝积分)
                    $num1 = $this->getAliveIntegralRules(11);
                    $this->addAliveIntegral($user_id,$shopid,$room,$num1,11,$orderId,$findresult);

                    if ($findresult && $resultpic) {

                        //9订单评价（次）(会员积分)
                        $num0 = $this->getIntegralRules(9);//获取积分
                        $this->addIntegral($user_id,$num0,9,$orderId);

                        //11购物评价 12优质购物评价（晒图，30字以上）(直播间粉丝积分)
                        $num2 = $this->getAliveIntegralRules(12);
                        $this->addAliveIntegral($user_id,$shopid,$room,$num2,12,$orderId,$findresult);

                        Db::commit();
                        datamsg(WIN, '发布成功');
                    } else {
                        Db::rollback();
                        datamsg(LOSE, '发布失败');
                    }
                }
            }else{
                datamsg(LOSE,$result['mess']);
            }
        }else{
            datamsg(LOSE,'请求方式不正确');
        }
    }

    public function deleteApraise(){
        if (request()->isPost()) {
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if ($result['status'] == 200) {
                $user_id = $result['user_id'];

                $id = input('post.id');
                if(empty($id)){
                    datamsg(LOSE, '缺少id参数');
                }else{
                    $res = db('find')->where(['mid'=>$user_id])->delete($id);
                    if($res){
                        datamsg(WIN, '删除成功');
                    }else{
                        datamsg(LOSE, '删除失败');
                    }
                }

            } else {
                datamsg(LOSE, $result['mess']);
            }
        }else{
            datamsg(LOSE,'请求方式错误');
        }
    }

    /**
     * @func发现详情
     */
    public function finddetail(){
        $id = input('param.id');
        if(empty($id)){
            datamsg(LOSE,'发现id不能为空');
        }
        $find = db('find')->where(['id'=>$id])->field('id,mid,describe,title,gid')->find();
        if(!empty($find['gid'])) {
            $find['goods'] = db('goods')->where(['id' => $find['gid']])->field('id,goods_name,thumb_url,market_price')->find();
            $find['goods']['thumb_url'] = $this->webconfig['weburl'].'/'.$find['goods']['thumb_url'];
        }
        $find['pics'] = db('find_pic')->where(['fid'=>$find['id']])->column('pathurl');
        foreach($find['pics'] as $key=>&$value){
            $value=$this->webconfig['weburl'].'/'.$value;
        }

        if(!empty($find['mid'])){
            $find['person']=db('member')->where(['id'=>$find['mid']])->find();
            dump($find['persion']);
        }
        datamsg(WIN,'获取成功',$find);

    }






    /**
     * @func 图片上传
     */
    public function uploadspic(){
        if(request()->isPost()) {
//            $gongyong = new GongyongMx();
//            $result = $gongyong->apivalidate();
            $file = request()->file('file');
            $common = new Commonfun();
            $picarr = $common->uploadspic($file, 'find_pic', 9);
            // $picarr = $common->qcloudCosUpload($file, 'find_pic', 9);
            // $picarr = $common->tUpfile($file);
            // return $picarr;
            $data['code']=200;
            $data['data']['src']=$picarr;
            $data['msg']='获取成功';
            echo json_encode($data);die();
        }else{
            $data['code']=400;
            $data['msg']='获取失败';
            echo json_encode($data);die();
        }
    }



    /**
     * @func 后台聊天接口
     */
    public function huploadspic(){
        if(request()->isPost()) {
//            $gongyong = new GongyongMx();
//            $result = $gongyong->apivalidate();
            $file = request()->file('file');
            $common = new Commonfun();
            $picarr = $common->uploadspic($file, 'find_pic', 9);

            $srcs = $picarr['wz'];
            $data['code']=0;
            $data['data']['src']=$srcs;
            $data['msg']='获取成功';
            echo json_encode($data);die();
        }else{
            $data['code']=400;
            $data['msg']='获取失败';
            echo json_encode($data);die();
        }
    }


    //直播封面图上传

    public function upload(){
        $file = request()->file('file');
        $result = $this->validate(
            ['file2' => $file], 
            ['file2'=>'image','file2'=>'fileSize:40000000'],
            ['file2.image' => '上传文件必须为图片','file2.fileSize' => '上传文件过大']                
        );
        if (true !== $result || !$file) {    
            $return_url = '';        
            $state = "ERROR" . $result;
        }else{
            $savePath = 'alive/'.date('Y').'/'.date('m-d').'/';
            $info = $file->rule(function ($file) {    
                return  md5(mt_rand()); // 使用自定义的文件保存规则
            })->move('public/upload/'.$savePath);             
            if ($info) {
                $state = "SUCCESS";
            } else {
                $state = "ERROR" . $file->getError();
            }
            $return_url = '/public/upload/'.$savePath.$info->getSaveName();
        }
        echo $return_url;
    }





    public function ceshi(){
//        $data['type']='chat';
//        $data['mess']='退出登录';
//        $data['data']=['type'=>'conn','mess'=>'吃饭了吗','data'=>[]];
//        echo json_encode($data);
//        die();
        $message = input('param.message');
        $redis = new \Redis();
        $key = 'msg';//Channel 订阅这频道的订阅者，都能收收到消息
        $res = $redis->connect('127.0.0.1', 6379,1);
        $result = $redis->publish($key,$message);
        dump($result);
    }

    public function link(){
        $client_id = input('param.client_id');
        $module = input('param.module');
        $controller = input('param.controller');
        $action = input('param.action');
        $secretstr = $module.'/'.$controller.'/'.$action;
        $client_secret = Db::name('secret')->where('id',$client_id)->value('client_secret');
        $api_token_server = md5($secretstr.date('Y-m-d', time()).$client_secret);
        echo $api_token_server;
    }

    // 口碑
    public function praise(){
        // $praiseList = db()
    }



}