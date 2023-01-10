<?php
namespace app\index\controller;

use think\Controller;

class Apppage extends Controller
{
    public function find()
    {
        $fid = input('param.id');
        $find = db('find')->where(['id'=>$fid])->find();
        $find['pic'] = db('find_pic')->where(['fid'=>$fid])->column('pathurl');
        $find['laudcount']=db('find_laud')->where(['fid'=>$fid])->count();
        $find['sharecount']=db('find_share')->where(['fid'=>$fid])->count();
        $find['downloadcount']=db('find_download')->where(['fid'=>$fid])->count();
        $find['member']=db('member')->where(['id'=>$find['mid']])->field('user_name,headimgurl')->find();
        $find['goods']=db('goods')->where(['id'=>$find['gid']])->field('goods_name,thumb_url')->find();
        $this->assign([
            'find'=>$find
        ]);
        return $this->fetch();
    }
}
