<?php
namespace app\apicloud\controller;
use app\apicloud\model\Gongyong as GongyongMx;
use think\Db;

class Comment extends Common {
    // 添加商品评价
    public function addGoodsComment(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if($result['status'] == 200) {
                if(!isset($result['user_id'])){
                    datamsg(LOSE, '请先登录');
                }
                $content = input('param.content');
//                $tags = input('param.tags');
                $goodsId = input('param.goods_id');
                $orderId = input('param.order_id');
                $goodsStar = input('param.goods_star');
                $logisticsStar = input('param.logistics_star');
                $serviceStar = input('param.service_star');
                $fpic = input('param.pic');
                if (empty($orderId)) {
                    datamsg(LOSE, '缺少订单参数');
                }
                if (empty($content)) {
                    datamsg(LOSE, '请填写评价内容');
                }
                if (empty($goodsStar)) {
                    datamsg(LOSE, '请给商品打分');
                }
                if (empty($serviceStar)) {
                    datamsg(LOSE, '请给服务打分');
                }
                if (empty($logisticsStar)) {
                    datamsg(LOSE, '请给物流打分');
                }

                $member = db('member')->where(['id'=>$result['user_id']])->find();
                $orderGoodsInfo = db('order_goods')->where('order_id',$orderId)->where('goods_id',$goodsId)->find();
                if(!$orderGoodsInfo){
                    datamsg(LOSE,'商品信息有误，暂时无法评价');
                }else{
                    $shopId = $orderGoodsInfo['shop_id'];
                    $orderGoodsId = $orderGoodsInfo['id'];
                }

                $data['user_id'] = $result['user_id'];
                $data['content'] = $content;
                $data['order_id'] = $orderId;
                $data['shop_id'] = $shopId;
                $data['goods_star'] = $goodsStar;
                $data['logistics_star'] = $logisticsStar;
                $data['service_star'] = $serviceStar;
//                    $data['tags'] = $tags;
                $data['goods_id'] = $goodsId;
                $data['orgoods_id'] = $orderGoodsId;
                $data['time'] = time();
                Db::startTrans();
                $commentResult = Db::name('comment')->insertGetId($data);
                $datapic = explode(',', $fpic);
                $picarr = [];
                foreach ($datapic as $key => $value) {
                    $picarr[$key]['img_url'] = $value;
                    $picarr[$key]['com_id'] = $commentResult;
                }
                $resultpic = Db::name('comment_pic')->insertAll($picarr);
                $updateCommentStatus = Db::name('order_goods')->where('goods_id',$goodsId)->where('order_id',$orderId)->update(['ping'=>1]);
                // 查找未评价的商品
                $findNoCommentGoods = Db::name('order_goods')->where('order_id',$orderId)->where('ping',0)->find();
                if(!$findNoCommentGoods){
                    Db::name('order')->where('id',$orderId)->update(['ping'=>1]);
                }

                if ($commentResult && $resultpic && $updateCommentStatus) {
                    //9订单评价（次）(会员积分)
                    $num0 = $this->getIntegralRules(9);//获取积分
                    $this->addIntegral($result['user_id'],$num0,9,$orderId);

                    Db::commit();
                    datamsg(WIN, '发布成功');
                }else{
                    Db::rollback();
                    datamsg(LOSE, '发布失败');
                }

            }else{
                datamsg(LOSE,$result['mess']);
            }
        }else{
            datamsg(LOSE,'请求方式不正确');
        }
    }

    /**
     * @function商品追评
     * @author Feifan.Chen <1057286925@qq.com>
     */
    public function addGoodsCommentExtend(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if($result['status'] == 200) {
                if(!isset($result['user_id'])){
                    datamsg(LOSE, '请先登录');
                }
                $content = input('param.content');
//                $tags = input('param.tags');
                $goodsId = input('param.goods_id');
                $orderId = input('param.order_id');
                $fpic = input('param.pic');
                if (empty($orderId)) {
                    datamsg(LOSE, '缺少订单参数');
                }
                if (empty($content)) {
                    datamsg(LOSE, '请填写评价内容');
                }

                $orderGoodsInfo = db('order_goods')->where('order_id',$orderId)->where('goods_id',$goodsId)->find();
                if(!$orderGoodsInfo){
                    datamsg(LOSE,'商品信息有误，暂时无法评价');
                }else{
                    $shopId = $orderGoodsInfo['shop_id'];
                    $orderGoodsId = $orderGoodsInfo['id'];
                }

                $data['user_id'] = $result['user_id'];
                $data['content'] = $content;
                $data['order_id'] = $orderId;
                $data['shop_id'] = $shopId;
                $data['goods_id'] = $goodsId;
                $data['orgoods_id'] = $orderGoodsId;
                $data['time'] = time();
                Db::startTrans();
                $commentResult = Db::name('comment_extend')->insertGetId($data);
                $datapic = explode(',', $fpic);
                $picarr = [];
                foreach ($datapic as $key => $value) {
                    $picarr[$key]['img_url'] = $value;
                    $picarr[$key]['com_id'] = $commentResult;
                }
                $resultpic = Db::name('comment_pic_extend')->insertAll($picarr);
                $updateCommentStatus = Db::name('order_goods')
                    ->where('goods_id',$goodsId)
                    ->where('order_id',$orderId)
                    ->update(['ping_extend'=>1]);
                // 查找未评价的商品
                $findNoCommentGoods = Db::name('order_goods')
                    ->where('order_id',$orderId)
                    ->where('ping_extend',0)
                    ->find();
                if(!$findNoCommentGoods){
                    Db::name('order')
                        ->where('id',$orderId)
                        ->update(['ping_extend'=>1]);
                }

                if ($commentResult && $resultpic && $updateCommentStatus) {
                    //9订单评价（次）(会员积分)

                    Db::commit();
                    datamsg(WIN, '发布成功');
                }else{
                    Db::rollback();
                    datamsg(LOSE, '发布失败');
                }

            }else{
                datamsg(LOSE,$result['mess']);
            }
        }else{
            datamsg(LOSE,'请求方式不正确');
        }
    }

    // 我的商品评价列表
    public function myGoodsCommentList(){
        if(request()->isPost()){
            $size = input('param.size') ?  input('param.size') : 5;
            if(!is_numeric($size)){
                datamsg(LOSE,'长度类型错误');
            }
            $type = input('param.type');
            $where=[];
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if($result['status'] == 200){
                $list = Db::name('comment')
                    ->alias('c')
                    ->join('sp_order o', 'c.order_id = o.id', 'LEFT')
                    ->join('sp_order_goods og','c.orgoods_id = og.id','LEFT')
                    ->join('member m','c.user_id = m.id')
                    ->where('c.user_id',$result['user_id'])
                    ->field("c.*,og.ping_extend,o.id as oid,m.user_name,m.headimgurl,og.price as goods_price,og.goods_num,o.ordernumber as ordernuber,og.goods_name,og.thumb_url")
                    ->order('time desc')
                    ->paginate($size)
                    ->each(function ($item, $key) {
                        $domain = $this->webconfig['weburl'];
                        $item['thumb_url'] = $domain.'/'.$item['thumb_url'];
                        $item['headimgurl'] = setMedia($item['headimgurl']);
                        $item['createtime'] = date('Y-m-d H:i:s', $item['time']);
                        $imgurl_arr = db('comment_pic')->where(['com_id' => $item['id']])->column('img_url');
                        foreach($imgurl_arr as $key1=>$value){
                            $item['imgurl'][$key1] = $domain.'/'.$value;
                        }
                        //查找出追评的内容
                        $comment_extend = Db::name('comment_extend')
                            ->where('user_id',$item['user_id'])
                            ->where('orgoods_id',$item['orgoods_id'])
                            ->where('order_id',$item['order_id'])
                            ->find();
                        //几天后追评
                        $after_day_str = $this->transferSecond($comment_extend['time'] - $item['time']);
                        $item['after_day'] = $after_day_str;
                        $item['comment_extend'] = $comment_extend['content'];
                        $imgurl_arr_extend = db('comment_pic_extend')->where(['com_id' => $comment_extend['id']])->column('img_url');
                        foreach($imgurl_arr_extend as $key1=>$value){
                            $item['imgurl_extend'][$key1] = $domain.'/'.$value;
                        }

                        return $item;
                    });
                $list_copy = $list->toArray();

                datamsg(WIN,'获取数据成功',$list_copy);
            }else{
                datamsg(LOSE,$result['mess']);
            }
        }else{
            datamsg(LOSE,'请求方式不正确');
        }
    }

    // 商品评价列表
    public function goodsCommentList(){
        if(request()->isPost()){
            $size = input('param.size') ?  input('param.size') : 5;
            if(!is_numeric($size)){
                datamsg(LOSE,'长度类型错误');
            }
            $goodsId = input('post.goods_id');
            if(empty($goodsId)){
                datamsg(LOSE,'缺少商品ID参数');
            }
            $type = input('param.type');
            $where=[];
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(0);
            if($result['status'] == 200){

                $list = Db::name('comment')
                    ->alias('c')
                    ->join('sp_goods g','c.goods_id = g.id','LEFT')
                    ->join('sp_member m', 'c.user_id = m.id', 'LEFT')
                    ->where('c.checked',1)
                    ->where('c.goods_id', $goodsId)
                    ->field('c.*,g.thumb_url,m.user_name,m.headimgurl,m.oauth')
                    ->order('time desc')
                    ->paginate($size)
                    ->each(function ($item, $key) {
                        $domain = $this->webconfig['weburl'];
                        $item['thumb_url'] = $domain.'/'.$item['thumb_url'];
                        $item['headimgurl'] =  $domain.'/'.$item['headimgurl'];
                        $item['createtime'] = date('Y-m-d H:i:s', $item['time']);
                        $imgurl_arr = db('comment_pic')->where(['com_id' => $item['id']])->column('img_url');
                        foreach($imgurl_arr as $key1=>$value){
                            $item['imgurl'][$key1] = $domain.'/'.$value;
                        }
                        return $item;
                    });
                $list_copy = $list->toArray();
                datamsg(WIN,'获取数据成功',$list_copy);
            }else{
                datamsg(LOSE,$result['mess']);
            }
        }else{
            datamsg(LOSE,'请求方式不正确');
        }
    }

    /**
     * @function商品详情页显示一个评论
     * @author Feifan.Chen <1057286925@qq.com>
     * @throws \think\exception\DbException
     */
    public function goodsComment(){
        if(request()->isPost()){
            $goodsId = input('post.goods_id');
            if(empty($goodsId)){
                datamsg(LOSE,'缺少商品ID参数');
            }
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(0);
            if($result['status'] == 200){

                $list = Db::name('comment')
                    ->alias('c')
                    ->join('sp_goods g','c.goods_id = g.id','LEFT')
                    ->join('sp_member m', 'c.user_id = m.id', 'LEFT')
                    ->where('c.checked',1)
                    ->where('c.goods_id', $goodsId)
                    ->field('c.*,g.thumb_url,m.user_name,m.headimgurl,m.oauth')
                    ->order('time desc')
                    ->limit(1)
                    ->select();
                    foreach($list as $key=>&$item){
                        $domain = $this->webconfig['weburl'];
                        $item['thumb_url'] = $domain.'/'.$item['thumb_url'];
                        if($item['oauth'] == 0){
                            $item['headimgurl'] =  $domain.'/'.$item['headimgurl'];
                        }
                        $item['createtime'] = date('Y-m-d H:i:s', $item['time']);
                        $imgurl_arr = db('comment_pic')->where(['com_id' => $item['id']])->column('img_url');
                        foreach($imgurl_arr as $key1=>$value){
                            $item['imgurl'][$key1] = $domain.'/'.$value;
                        }
                    }

                $list_copy = $list;
                datamsg(WIN,'获取数据成功',$list_copy);
            }else{
                datamsg(LOSE,$result['mess']);
            }
        }else{
            datamsg(LOSE,'请求方式不正确');
        }
    }

    public function deleteGoodsComment(){
        if (request()->isPost()) {
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if ($result['status'] == 200) {
                $user_id = $result['user_id'];
                $id = input('post.id');
                if(empty($id)){
                    datamsg(LOSE, '缺少id参数');
                }else{
                    Db::startTrans();
                    $commentInfo = db('comment')->where('id',$id)->find();
                    $res = db('comment')->where(['user_id'=>$user_id,'checked'=>0])->delete($id);
                    // 删除评价后，将对应的订单商品和订单的评价状态设置为0
                    $updateOrderGoodsCommentStatus = db('order_goods')->where('id',$commentInfo['orgoods_id'])->update(['ping'=>0]);
                    $updateOrderCommentStatus = db('order')->where('id',$commentInfo['order_id'])->update(['ping'=>0]);

                    if($res && $updateOrderGoodsCommentStatus){
                        Db::commit();
                        datamsg(WIN, '删除成功');
                    }else{
                        Db::rollback();
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
     * @function秒转换成天、时、分
     * @param $time_gap
     * @author Feifan.Chen <1057286925@qq.com>
     * @return string
     */
    public function transferSecond($time_gap){
        $d = floor($time_gap / (3600*24));
        $h = floor(($time_gap % (3600*24)) / 3600);
        $m = floor((($time_gap % (3600*24)) % 3600) / 60);

        if($d>'0'){
            $after_day_str =  $d.'天';
        }else{
            if($h!='0'){
                $after_day_str =  $h.'小时';
            }else{
                if ($time_gap < 60){
                    $after_day_str =  $time_gap.'秒';
                }else{
                    $after_day_str =  $m.'分';
                }
            }
        }
        return $after_day_str;
    }
}