<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use think\Loader;

class Alivegifts extends Common{
    public function lst(){
        $limit = input('param.limit/d', 7);
        $keyword = input('param.keyword');
        $where=[];
        if ($keyword) {
            $where['a.name'] = ['like', "%{$keyword}%"];
        }
        $where['a.is_delete']=0;
        $field = 'a.*,ag.cname';
        $list = Db::name('alive_gifts')
            ->alias('a')
            ->field($field)
            ->join('alive_giftscate ag','a.cid = ag.id','LEFT')
            ->where($where)
            ->order('a.id desc')
            ->paginate($limit)
            ->each(function ($item){
                $item['pic']=$this->webconfig['weburl'].'/'.$item['pic'];
                $item['picgif']=$this->webconfig['weburl'].'/'.$item['picgif'];
                return $item;
            });
        $page = $list->render();
        $this->assign([
            'list'=>$list,
            'page'=>$page
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



    //处理上传图片
    public function uploadify(){
        $file = request()->file('filedata');
        if($file){
            $info = $file->validate(['size'=>3145728,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'gifts');
            if($info){
                $getSaveName = str_replace("\\","/",$info->getSaveName());
                $original = 'uploads/gifts/'.$getSaveName;
                $image = \think\Image::open('./'.$original);
                $image->thumb(350, 350)->save('./'.$original);
                $picarr = array('img_url'=>$original);
                $value = array('status'=>1,'path'=>$picarr);
            }else{
                $value = array('status'=>0,'msg'=>$file->getError());
            }
        }else{
            $value = array('status'=>0,'msg'=>'文件不存在');
        }
        return json($value);
    }


    /**
     * 添加礼物
     */
    public function add(){
        if(request()->isAjax()){
            $data = input('param.');
            $validate = Loader::validate('Gifts');
            $result = $validate->scene('add')->check($data);
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $data['createtime']=time();
                $data['play']=5;
                $result = db('alive_gifts')->insertGetId($data);
                if($result){
                    $value = array('status'=>1,'mess'=>'增加成功');
                }else{
                    $value = array('status'=>0,'mess'=>'增加失败');
                }
            }
            return json($value);
        }else{
            $cateres = db('alive_giftscate')->where(['is_delete'=>0])->field('id,cname')->select();
            $this->assign([
               'cateres'=>$cateres,
            ]);
            return $this->fetch();
        }
    }


    /**
     * 修改礼物
     */
    public function edit()
    {
        $id = input('param.id');
        if (request()->isAjax()) {
            $data = input('post.');
            $validate = Loader::validate('Gifts');
            $result = $validate->scene('edit')->check($data);
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$validate->getError());
            }else{
                $result = db('alive_gifts')->where(['id'=>$id])->update($data);
                if($result){
                    $value = array('status'=>1,'mess'=>'修改成功');
                }else{
                    $value = array('status'=>0,'mess'=>'没有做任何更改');
                }
            }
            return json($value);
        } else {
            $cateres = db('alive_giftscate')->where(['is_delete' => 0])->field('id,cname')->select();
            $gifts = db('alive_gifts')->where(['id'=>$id])->find();
            $gifts['wzpic']=$this->webconfig['weburl'].'/'.$gifts['pic'];
            $gifts['wzpicgif']=$this->webconfig['weburl'].'/'.$gifts['picgif'];
            $this->assign([
                'cateres' => $cateres,
                'gifts'=>$gifts
            ]);
            return $this->fetch();
        }
    }


}
?>