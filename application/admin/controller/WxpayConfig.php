<?php
/*
 * @Descripttion: 
 * @Copyright: ©版权所有
 * @Link: www.s1107.com
 * @Contact: QQ:2487937004
 * @LastEditors: cbing
 * @LastEditTime: 2020-04-19 11:53:04
 */
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class WxpayConfig extends Common{
    
    public function info(){
        if(request()->isPost()){
            $data = input('post.');
            $result = $this->validate($data,'WxpayConfig');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $wxpayconfigs = Db::name('wxpay_config')->where('id',1)->find();
                if($wxpayconfigs){
                    $count = Db::name('wxpay_config')->update(array(
                        'appid' => $data['appid'],
                        'mch_id'=>$data['mch_id'],
                        'api_key'=>$data['api_key'],
                        'notify_url'=>$data['notify_url'],
                        'id'=>$wxpayconfigs['id']
                    ));
                    if($count !== false){
                        $value = array('status'=>1,'mess'=>'保存成功');
                        ys_admin_logs('编辑微信支付配置','wxpay_config',$wxpayconfigs['id']);
                    }else{
                        $value = array('status'=>0,'mess'=>'保存失败');
                    }
                }else{
                    $lastId = Db::name('wxpay_config')->insertGetId(array(
                        'appid' => $data['appid'],
                        'mch_id'=>$data['mch_id'],
                        'api_key'=>$data['api_key'],
                        'notify_url'=>$data['notify_url'],
                    ));
                    if($lastId){
                        $value = array('status'=>1,'mess'=>'保存成功');
                        ys_admin_logs('新增微信支付配置','wxpay_config',$lastId);
                    }else{
                        $value = array('status'=>0,'mess'=>'保存失败');
                    }
                }
            }
            return json($value);
        }else{
            $wxpayconfigs = Db::name('wxpay_config')->order('id DESC')->find();
            $this->assign('wxpayconfigs',$wxpayconfigs);
            return $this->fetch();
        }
    }
}