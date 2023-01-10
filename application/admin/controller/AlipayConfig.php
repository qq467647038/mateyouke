<?php
/*
 * @Descripttion: 
 * @Copyright: ©版权所有
 * @Link: www.s1107.com
 * @Contact: QQ:2487937004
 * @LastEditors: cbing
 * @LastEditTime: 2020-04-19 11:55:12
 */
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class AlipayConfig extends Common{
    
    public function info(){
        if(request()->isPost()){
            $data = input('post.');
            $result = $this->validate($data,'AlipayConfig');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $alipayconfigs = Db::name('alipay_config')->where('id',1)->find();
                if($alipayconfigs){
                    $count = Db::name('alipay_config')->update(array(
                        'appid' => $data['appid'],
                        'private_key'=>$data['private_key'],
                        'public_key'=>$data['public_key'],
                        'notify_url'=>$data['notify_url'],
                        'id'=>$alipayconfigs['id']
                    ));
                    if($count !== false){
                        $value = array('status'=>1,'mess'=>'保存成功');
                        ys_admin_logs('编辑支付宝支付配置','alipay_config',$alipayconfigs['id']);
                    }else{
                        $value = array('status'=>0,'mess'=>'保存失败');
                    }
                }else{
                    $lastId = Db::name('alipay_config')->insertGetId(array(
                        'appid' => $data['appid'],
                        'private_key'=>$data['private_key'],
                        'public_key'=>$data['public_key'],
                        'notify_url'=>$data['notify_url'],
                    ));
                    if($lastId){
                        $value = array('status'=>1,'mess'=>'保存成功');
                        ys_admin_logs('新增支付宝支付配置','alipay_config',$lastId);
                    }else{
                        $value = array('status'=>0,'mess'=>'保存失败');
                    }
                }
            }
            return json($value);
        }else{
            $alipayconfigs = Db::name('alipay_config')->order('id DESC')->find();
            $this->assign('alipayconfigs',$alipayconfigs);
            return $this->fetch();
        }
    }
}