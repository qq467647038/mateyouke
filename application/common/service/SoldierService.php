<?php

namespace app\common\service;

use think\Db;

use app\common\model\SoldierCheck as SoldierCheckModel;
use app\common\model\SoldierInfo as SoldierInfoModel;
use app\common\model\SoldierPayLog as SoldierPayLogModel;
use app\common\model\SoldierPaymentLog as SoldierPaymentLogModel;
use app\common\model\Xxk as XxkModel;
use app\common\model\Users as UsersModel;
use app\common\model\Order as OrderModel;
use app\common\model\Member as MemberModel;

/**
 * 军人创业卡类
 */
class SoldierService
{
    private $user_id;

    public function __construct($user_id = 1)
    {
        $this->user_id = $user_id;
    }

    public function findByCardId($card_id)
    {
        return SoldierCheckModel::findByCardId($card_id);
    }

    public function findBySoldierType($type)
    {
        return SoldierCheckModel::findBySoldierType($type);
    }

    public function countBySoldierType($type)
    {
        return SoldierCheckModel::countBySoldierType($type);
    }

    public function findByUserId($user_id)
    {
        return SoldierCheckModel::findByUserId($user_id);
    }

    /**
     * 孝笑卡登录
     *
     * @param [type] $card_id
     * @param [type] $password
     * @return void
     */
    public function login($card_id,$password)
    {
        $count = XxkModel::findByCardIdAndPassword($card_id,$password);
        if($count > 0){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 添加审核
     *
     * @param [type] $data
     * @return void
     */
    public function add($data)
    {
        $card = SoldierCheckModel::findByCardId($data['card_id']);
        $xxk = XxkModel::findByCardId($data['card_id']);
        $data['soldier_type'] = $xxk['soldier_type'];
        if($card['status'] == 2){
            $this->edit($data); 
            return ['status'=>1];
        } 
        if($card){
            return $this->getStatus($card);
        }else{
            $data['user_id'] = $this->user_id;
            SoldierCheckModel::add($data);
            return ['status'=>1];
        }
    }

    public function edit($data)
    {
        $card = SoldierCheckModel::findByCardId($data['card_id']);
        if($card){
            $data['id'] = $card['id'];
            SoldierCheckModel::edit($data);
        }
    }

    /**
     * 添加金额日志
     *
     * @return void
     */
    public function addMoneyLog($order_sn)
    {
        $orderInfo = OrderModel::findByOrderSn($order_sn);
        $userInfo = UsersModel::findByUserId($orderInfo['user_id']);

        $count = SoldierPaymentLogModel::countByOrderId($orderInfo['order_id']);
        if($count > 0){
            return ;
        }
        Db::startTrans();
        try {

            $money = $orderInfo['order_amount'] * 0.005;
            // 军区增加金额
            SoldierInfoModel::addMoney(['soldier_type'=>$userInfo['soldier_type'],'money'=>$money]);
            // 胡姐增加金额
            SoldierInfoModel::addMoney(['soldier_type'=>0,'money'=>$money]);
            // 添加日志
            $log = [
                'user_id' => $userInfo['user_id'],
                'soldier_type' => $userInfo['soldier_type'],
                'order_id' => $orderInfo['order_id'],
                'price' => $money
            ];
            SoldierPayLogModel::add($log);
            // 添加进账日志
            $log = [
                'soldier_type' => $userInfo['soldier_type'],
                'money' => $money,
                'type' => 1,
                'order_id' => $orderInfo['order_id'],
                'desc' => '用户购买商品'
            ];
            SoldierPaymentLogModel::add($log);
            $log = [
                'soldier_type' => 0,
                'money' => $money,
                'type' => 1,
                'order_id' => $orderInfo['order_id'],
                'desc' => '用户购买商品'
            ];
            SoldierPaymentLogModel::add($log);

            Db::commit();

        } catch (\Throwable $th) {
            Db::rollback();
            print_r($th);

        }

    }

    /**
     * 通过审核
     *
     * @param [type] $data
     * @return void
     */
    public function pass($data)
    {
        Db::startTrans();
        try {

            // 通过
            SoldierCheckModel::pass($data['id']);
            // 修改孝笑卡的用户id
            $map = [
                'card_id' => $data['card_id'],
                'user_id' => $data['user_id'],
                'bind_time' => date("Y-m-d H:i:s",time())
            ];
            XxkModel::edit($map);
            $card = XxkModel::findByCardId($data['card_id']);
            // 提升用户为520会员,并且加入军区标识
            MemberModel::updateById($data['user_id'],['level'=>1,'soldier'=>$card['soldier_type']]);
            $status = true;
            Db::commit();

        } catch (\Throwable $th) {
            $status = false;
            Db::rollback();
        }
        return $status;
    }

    /**
     * 检查是否存在
     *
     * @param [type] $card_id
     * @return void
     */
    public function isCheck($card_id)
    {
        $card = SoldierCheckModel::findByCardId($card_id);
        if($card){
            if($card['status'] == 2) return ['status'=>1];
            return $this->getStatus($card);
        }
        return ['status'=>1];
    }

    /**
     * 批量生成卡号
     *
     * @return void
     */
    // public function iot()
    // {
    //     $num = 10000;
    //     $name = 'J'.date("YmdHis",time());
    //     $start = 0;
    //     $array = [];
    //     for ($i=0; $i < $num; $i++) { 
    //         $str = '';
    //         $start += 1;
    //         $str = $name.str_pad($start,7,'0',STR_PAD_LEFT);
    //         $array[] = $str;
    //     }
    //     return $array;
    // }

    // private function createCard()
    // {
    //     // $xxkrank = db("xxkrank")->where("rank_id",10)->find();
    //     // $head    = $xxkrank["head"];
    //     $card_id = 'J'.date("YmdHis",time()).rand(10000,99999);
    //     $data = [
    //         "card_id"       => $card_id,
    //         "card_psw"      => $this->cardPsw(),
    //         "card_money"    => 0,
    //         "card_rank"     => 10,
    //         "card_totle"    => 0,
    //         "user_id"       => 0,
    //         "card_type"     => 2,
    //         "active_id"     => 1,
    //         "lot"           => -1,
    //         "create_time"   => date("Y-m-d H:i:s",time()),
    //         "active_time"   => date("Y-m-d H:i:s",time()),
    //         "is_empty_card" => 1,
    //         "type"          => 0,
    //          "soldier_type" => 1
    //     ];
    //     db("xxk")->insert($data);
    //     return $card_id;
    // }

    //密码生成
    private function cardPsw($len = 6,$type = 0){
        if($type == 0){
            $str = '0123456789';
        }
        else{
            $str = '23456789abcdefghijkmnpqrstuvwxyz';
        }

        $max = strlen($str)-1;

        $psw = '';

        for($i = 0;$i < $len;$i++){
            $n = mt_rand(0,$max);
            $psw .= substr($str,$n,1);
        }

        return $psw;
    }

    private function getStatus($data)
    {
        switch ($data['status']) {
            case '0':
                $text = '正在审核中,请耐心等待';
                break;
            case '1':
                $text = '该卡号已被领取,请联系负责人';
                break;
            case '2':
                $text = '审核未通过,请填写正确的信息';
                break;
            default:
                $text = '正在审核中,请耐心等待';
                break;
        }
        return ['status'=>0,'msg'=>$text];
    }
}