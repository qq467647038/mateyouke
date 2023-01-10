<?php
namespace app\apicloud\controller;
use think\Db;
use think\Controller;
use app\admin\services\Upush;
class Bctest extends Controller {
    /**
     * @func 单个设备推送测试
     */
    public function testpush(){
        $cid = $_POST['cid'];
        $data = [
            //'cid' => '75f970715764bfe8018f6db7589b92d8',
            'cid' => $cid,
            'title' => 'api接口推送标题',
            'content' => 'api接口推送内容',
            'payload' => '透传内容'
            //'payload' => '{"title":"api接口推送标题","content":"api接口推送内容","sound":"default","payload":"test","local":"1","type":"2"}'
        ];
        $model = new Upush();
        print_r($model->pushOne($data));
    }

    /***
     * 群推消息测试
     */
    public function pushall(){
        $data = [
            //75f970715764bfe8018f6db7589b92d8
            //744af86c434ca32869abc9a94715eac5
            //897cd21d17a077d38339e2835fbfa4ae
            //'cid' => 'bb53ebb0ded20d636a161a5e87cc3923',
            'cid' => 'e822fc957e5474fd797326dca34dfdc57a1c688d7bd798f7d57ed25368ba2add',
            'title' => 'api群推接口推送标题',
            'content' => 'api群推接口推送内容',
            'payload' => '{"title":"api接口推送标题","content":"api接口推送内容","sound":"default","payload":"test"}'
        ];
        $model = new Upush();
        print_r($model->pushAll($data));
    }

    /***
     * 查询测试
     */
    public function searchTest(){
        $order_num = "D2019101115255753554810";
        $orders = Db::name('order')->alias('a')->field('a.id,a.ordernumber,a.contacts,a.telephone,a.province,a.city,a.zf_type,a.area,a.address,a.goods_price,a.freight,a.youhui_price,a.coupon_id,a.coupon_price,a.coupon_str,a.total_price,a.beizhu,a.state,a.pay_time,a.fh_status,a.fh_time,a.order_status,a.shouhou,a.is_show,a.coll_time,a.can_time,a.ping,a.order_type,a.pin_type,a.pin_id,a.zong_id,a.shop_id,a.addtime,a.zdsh_time,a.time_out,b.order_number,c.shop_name,w.id,w.psnum')->join('sp_order_zong b','a.zong_id = b.id','LEFT')->join('order_wuliu w','a.id = w.order_id','LEFT')->join('sp_shops c','a.shop_id = c.id','LEFT')->where('a.ordernumber',$order_num)->where('a.is_show',1)->find();
        echo json_encode($orders);exit();
    }

    


}