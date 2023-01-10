<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class Alivereport extends Common{
    public function lst(){
        $limit = input('param.limit/d', 7);
        $keyword = input('param.keyword');
        $where=[];
        if ($keyword) {
            $where['f.describe|m.phone|m.user_name'] = ['like', "%{$keyword}%"];
        }
        
        // echo $domain;
        $list = Db::name('alive_report')
            ->where($where)
            ->order('id desc')
            ->paginate($limit)
            ->each(function ($item, $key) {
                     
                    $pics = db('room_report_pic')->where(['fid'=>$item['id']])->column('pathurl');
                    if($pics){
                        if($this->webconfig['cos_file'] == '开启'){
                            $domain = config('tengxunyun')['cos_domain'];
                        }else{
                            $domain = $this->webconfig['weburl'];
                        }
                        foreach($pics as $k=>$v){
                            $item['pic'][] = $domain.'/'.$v;
                        }
                    }else{
                        $item['pic'] = '';
                    }
                    $item['shop_name'] = db('shops')->where(['id'=>$item['shop_id']])->value('shop_name');
                    
                    
                    return $item;
                });
        // dump($list);
        $page = $list->render();
        $this->assign([
            'list'=>$list,
            'page'=>$page
        ]);
        return $this->fetch();
    }


    /**
     * @func 获取直播入驻的详细信息
     */
    public function info(){
        $uid = input('param.id');
        $where['m.id']=$uid;
        $field = 'm.user_name,m.phone,m.headimgurl,m.integral,m.summary,m.sex,m.email,m.wxnum,m.qqnum,m.regtime,am.*';
        $info = Db::name('alive_member')
            ->alias('am')
            ->field($field)
            ->join('member m','am.uid = m.id','LEFT')
            ->where($where)
            ->find();
        $this->assign([
           'info'=>$info
        ]);
        return $this->fetch();
    }


    public function isshow(){
        $id = input('param.id/d');
        if(empty($id)){
            datamsg(LOSE,'id不能为空');
        }else{
            $find = db('find')->where(['id'=>$id])->find();
            if(empty($find)){
                datamsg(LOSE,'没有找到对应的数据');
            }
            if($find['is_show'] == 1){
                $data['is_show']=0;
            }else{
                $data['is_show']=1;
            }
            $result = db('find')->where(['id'=>$id])->update($data);
            if($result){
                datamsg(WIN,'更新成功');
            }else{
                datamsg(LOSE,'更新失败');
            }
        }
    }


    /**
     * 直播监控
     */
    public function alivemonitor(){
        $limit = input('param.limit/d', 7);
        $keyword = input('param.keyword');
        $where=[];
        if ($keyword) {
            $where['f.describe|m.phone|m.user_name'] = ['like', "%{$keyword}%"];
        }
        $field = 'm.user_name,m.phone,m.headimgurl,a.*,am.lastlogin_time,am.hot,am.recommend,am.prohibit';
        $list = Db::name('alive')
            ->alias('a')
            ->field($field)
            ->join('member m','a.uid = m.id','LEFT')
            ->join('alive_member am','am.uid = m.id','LEFT')
            ->where($where)
            ->order('a.id desc')
            ->paginate($limit)
            ->each(function ($item){
                return $item;
            });
        $page = $list->render();
        $this->assign([
            'list'=>$list,
            'page'=>$page
        ]);
        return $this->fetch();
    }

}
?>