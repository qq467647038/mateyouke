<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class Finds extends Common{
    public function lst(){
        $limit = input('param.limit/d', 7);
        $keyword = input('param.keyword');
        $where=[];
        if ($keyword) {
            $where['f.describe|m.phone|m.user_name'] = ['like', "%{$keyword}%"];
        }
        $field = 'f.id,f.mid as uid,m.phone,m.user_name,g.goods_name,m.shop_id,f.describe,f.gid,f.createtime,f.is_show,f.ishot,f.tags';
        $list = Db::name('find')
            ->alias('f')
            ->field($field)
            ->join('sp_member m','f.mid = m.id','LEFT')
            ->join('sp_goods g','f.gid = g.id','LEFT')
            ->where($where)
            ->order('f.createtime desc')
            ->paginate($limit)
            ->each(function ($item){
                $item['createtime'] = date('Y-m-d H:i:s',$item['createtime']);
                $name = db('find_tags')->field('GROUP_CONCAT(NAME) as tagsname')->where(['id'=>['in',$item['tags']]])->find();
                $item['tagsname']=$name['tagsname'];
                return $item;
            });
        $page = $list->render();
        $this->assign([
            'list'=>$list,
            'page'=>$page
        ]);
        return $this->fetch();
    }


    /**
     * @隐藏显示
     */
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
     * @是否热门
     */
    public function ishot(){
        $id = input('param.id/d');
        if(empty($id)){
            datamsg(LOSE,'id不能为空');
        }else{
            $find = db('find')->where(['id'=>$id])->find();
            if(empty($find)){
                datamsg(LOSE,'没有找到对应的数据');
            }
            if($find['ishot'] == 1){
                $data['ishot']=0;
            }else{
                $data['ishot']=1;
            }
            $result = db('find')->where(['id'=>$id])->update($data);
            if($result){
                datamsg(WIN,'更新成功');
            }else{
                datamsg(LOSE,'更新失败');
            }
        }
    }

}
?>