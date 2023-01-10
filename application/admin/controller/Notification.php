<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class Notification extends Common
{
    //平台消息列表
    public function index(){
        $this->fetch();
    }

    // 新增平台消息
    public function addAlive(){
        if(request()->isAjax()){
            $admin_id = session('admin_id');
            $data = input('post.');
            $result = $this->validate($data,'Alive');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $shop = db('shops')->find($data['shop_id']);
                if(!$shop){
                    $value = array('status'=>0,'mess'=>'该店铺ID不存在');
                    return json($value);
                }
                $data['create_time'] = time();
                $res = db('alive')->insert($data);
                if($res){
                    $value = array('status'=>1,'mess'=>'添加成功');
                }else{
                    $value = array('status'=>0,'mess'=>'添加失败');
                }
            }
            return json($value);
        }else{
            $typeList = db('type')->field('id,type_name')->select();
            $this->assign('typeList',$typeList);
            return $this->fetch();
        }
    }

    // 修改平台消息
    public function editAlive(){
        if(request()->isAjax()){
            $admin_id = session('admin_id');
            $data = input('post.');
            $result = $this->validate($data,'Alive');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                // echo 1;
                // dump($data);die;
                $shop = db('shops')->find($data['shop_id']);
                if(!$shop){
                    $value = array('status'=>0,'mess'=>'该店铺ID不存在');
                    return json($value);
                }
                $res = db('alive')->where(['id'=>$data['id']])->update($data);
                // dump($res);
                if($res !== false){
                    $value = array('status'=>1,'mess'=>'编辑成功');
                }else{
                    $value = array('status'=>0,'mess'=>'编辑失败');
                }
            }
            return json($value);
        }else{
            $id = input('param.id');
            $aliveInfo = db('alive')->find($id);
            $typeList = db('type')->field('id,type_name')->select();
            // echo $_SERVER['HTTP_REFERER'];die;
            $this->assign('typeList',$typeList);
            $this->assign('data',$aliveInfo);
            $this->assign('referer',$_SERVER['HTTP_REFERER']);
            return $this->fetch();
        }
    }
}