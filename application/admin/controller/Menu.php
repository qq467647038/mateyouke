<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\Menu as MenuMx;

class Menu extends Common{
    //微信菜单
    public function lst(){
        $list = Db::name('menu')->field('id,name,type,pid,sort')->order('sort asc')->select();
        $this->assign('list', recursive($list));
        return $this->fetch();
    }
    
    //添加菜单
    public function add(){
        if(request()->isAjax()){
            $data = input('post.');
            $result = $this->validate($data,'Menu');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $menu = new MenuMx();
                $menu->data($data);
                $lastId = $menu->allowField(true)->save();
                if($lastId){
                    ys_admin_logs('新增自定义菜单','menu',$menu->id);
                    $value = array('status'=>1,'mess'=>'增加成功');
                }else{
                    $value = array('status'=>0,'mess'=>'增加失败');
                }
            }
            return json($value);
        }else{
            $menures = Db::name('menu')->field('id,name,pid')->order('sort asc')->select();
            $this->assign('menures', recursive($menures));
            return $this->fetch();
        }
    }
     
    
    /*
     * 编辑菜单
     */
    public function edit(){
        if(request()->isAjax()){
            $data = input('post.');
            $result = $this->validate($data,'Menu');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $menu = new MenuMx();
                $count = $menu->allowField(true)->save($data,array('id'=>$data['id']));
                if($count > 0){
                    ys_admin_logs('编辑自定义菜单','menu',$data['id']);
                    $value = array('status'=>1,'mess'=>'编辑成功');
                }else{
                    $value = array('status'=>0,'mess'=>'编辑失败');
                }
            }
            return json($value);
        }else{
            $menures = Db::name('menu')->where('id','neq',input('id'))->field('id,name,pid')->order('sort asc')->select();
            $menus = Db::name('menu')->where('id',input('id'))->find();
            $this->assign('menures', recursive($menures));
            $this->assign('menus', $menus);
            return $this->fetch();
        }
    }
    
    //处理删除菜单
    public function delete(){
        $id = input('id');
        $child = Db::name('menu')->where('pid',$id)->field('id')->limit(1)->find();
        if($child){
            $value = array('status'=>0,'mess'=>'该分类下存在子分类，删除失败');
        }else{
            $count = MenuMx::destroy($id);
            if($count > 0){
                ys_admin_logs('删除自定义菜单','menu',$id);
                $value = array('status'=>1,'mess'=>'删除成功');
            }else{
                $value = array('status'=>0,'mess'=>'删除失败');
            }
        }
        return json($value);
    }
    
    //处理排序
    public function order(){
        $data = input('post.');
        if(!empty($data['sort'])){
            foreach ($data['sort'] as $key => $v){
                $data2['sort'] = $v;
                $data2['id'] = $key;
                Db::name('menu')->update($data2);
            }
            $value = array('status'=>1,'mess'=>'更新排序成功');
        }else{
            $value = array('status'=>0,'mess'=>'未修改任何排序');
        }
        return json($value);
    }
    
    //生成菜单
    public function send(){
        //引入
        $weixin = new \wxapi\Wxapi();
        $menuarr = Db::name('menu')->where('pid',0)->field('id,type,name,url,key')->order('sort asc')->select();
        $button = array();
        foreach ($menuarr as $v){
            if($v['type'] == 'top'){
                $child = Db::name('menu')->where('pid',$v['id'])->field('id,type,name,url,key')->order('sort asc')->select();
                $childarr = array();
                foreach ($child as $val){
                    if($val['type'] == 'view'){
                        $childarr[] = array(
                            'type' => $val['type'],
                            'name' => $val['name'],
                            'url' => $val['url']
                        );
                    }elseif($val['type'] == 'click'){
                        $childarr[] = array(
                            'type' => $val['type'],
                            'name' => $val['name'],
                            'key' => $val['key']
                        );
                    }
                }
                $button[] = array(
                    'name' => $v['name'],
                    'sub_button' => $childarr
                );
            }elseif($v['type'] == 'view'){
                $button[] = array(
                    'type' => $v['type'],
                    'name' => $v['name'],
                    'url'  => $v['url']
                );
            }elseif($v['type'] == 'click'){
                $button[] = array(
                    'type' => $v['type'],
                    'name' => $v['name'],
                    'key'  => $v['key']
                );
            }
        }
        $value = $weixin->create_menu($button, $matchrule = NULL);
        return json($value);
    }
    
}