<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class FindTags extends Common{
    /**
     * @func 列表
     */
    public function lst(){
        $limit = input('param.limit/d', 10);
        $keyword = input('param.keyword');
        $cate_id = input('param.cate_id');
        $where=[];
        if ($keyword) {
            $where['t.name'] = ['like', "%{$keyword}%"];
        }
        if($cate_id){
            $where['t.cate_id']=$cate_id;
        }
        $where['is_delete']=0;
        $list = Db::name('find_tags')
            ->alias('t')
            ->field('t.*,c.cate_name')
            ->join('sp_category c','c.id = t.cate_id','LEFT')
            ->where($where)
            ->order('t.createtime desc')
            ->paginate($limit)
            ->each(function($item){
                $item['createtime']=date('Y-m-d H:i:s',$item['createtime']);
                return $item;
            });
        $page = $list->render();
        $cateres = Db::name('category')->field('id,cate_name,pid')->order('sort asc')->select();
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->assign('cateres',recursive($cateres));
        return $this->fetch('lst');
    }



    public function add(){
        if(request()->isPost()){
            $data = input('post.');
            $result = $this->validate($data,'FindTags');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $data['createtime']=time();
                $insertresult = db('find_tags')->insert($data);
                if($insertresult){
                    $value = ['status'=>1,'mess'=>'添加成功'];
                }else{
                    $value = ['status'=>0,'mess'=>'添加失败'];
                }

            }
            return json($value);
        }else{
            $cateres = Db::name('category')->field('id,cate_name,pid')->order('sort asc')->select();
            $this->assign('cateres',recursive($cateres));
            return $this->fetch();
        }
    }


    public function edit(){
        if(request()->isPost()){
            $data = input('post.');
            $result = $this->validate($data,'FindTags');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $insertresult = db('find_tags')->update($data);
                if($insertresult){
                    $value = ['status'=>1,'mess'=>'修改成功'];
                }else{
                    $value = ['status'=>0,'mess'=>'没有做任何修改'];
                }

            }
            return json($value);
        }else{
            $id= input('param.id');
            $list = db('find_tags')->where(['id'=>$id])->find();
            $cateres = Db::name('category')->field('id,cate_name,pid')->order('sort asc')->select();
            $this->assign([
                'cateres'=>recursive($cateres),
                'list'=>$list
            ]);
            return $this->fetch();
        }
    }



    /**
     * @func 推荐和取消推荐
     */
    public function isshow(){
        $id = input('param.id/d');
        if(empty($id)){
            datamsg(LOSE,'id不能为空');
        }else{
            $find = db('find_tags')->where(['id'=>$id])->find();
            if(empty($find)){
                datamsg(LOSE,'没有找到对应的数据');
            }
            if($find['recommend'] == 1){
                $data['recommend']=0;
            }else{
                $data['recommend']=1;
            }
            $result = db('find_tags')->where(['id'=>$id])->update($data);
            if($result){
                datamsg(WIN,'操作成功');
            }else{
                datamsg(LOSE,'操作失败');
            }
        }
    }




    public function del(){
        $id = input('param.id/d');
        if(empty($id)){
            datamsg(LOSE,'id不能为空');
        }else{
            $find = db('find_tags')->where(['id'=>$id])->find();
            if(empty($find)){
                datamsg(LOSE,'没有找到对应的数据');
            }
            $result = db('find_tags')->where(['id'=>$id])->update(['is_delete'=>1]);
            if($result){
                datamsg(WIN,'删除成功');
            }else{
                datamsg(LOSE,'删除失败');
            }
        }
    }


}
?>