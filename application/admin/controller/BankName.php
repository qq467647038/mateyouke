<?php
namespace app\admin\controller;

use think\Db;

class BankName extends Common{
    //广告列表
    public function lst(){
        $list = Db::name('bank_name')->order('id desc')->paginate(25);
        $page = $list->render();
        
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $this->assign('pnum',$pnum);
        $this->assign('list',$list);
        $this->assign('page',$page);
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch();
        }
    }
    
    //添加广告
    public function add(){
        if(request()->isAjax()){
            $admin_id = session('admin_id');
            $data = input('post.');
            $result = $this->validate($data,'BankName');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                // 启动事务
                Db::startTrans();
                try{
                    // if(!empty($data['picres_id'])){
                    //     $canshu = $data['canshu'];
                    //     $sort2 = $data['sort2'];
                    //     foreach ($data['picres_id'] as $k => $v){
                    //         $img_url = Db::name('huamu_zsduopic')->where('id',$v)->where('admin_id',$admin_id)->value('img_url');
                    //         if($img_url){
                    //             if(empty($sort2[$k])){
                    //                 $sort2[$k] = 0;
                    //             }
                    //             Db::name('ad_pic')->insert(array('pic'=>$img_url,'canshu'=>$canshu[$k],'sort'=>$sort2[$k],'ad_id'=>$ad_id));
                    //         }
                    //     }
                    // }
                    $d = [
                        'name' => $data['name'],
                        'addtime'=>time()
                    ];
                    
                    Db::name('bank_name')->insert($d);
                    // 提交事务
                    Db::commit();
                    $value = array('status'=>1,'mess'=>'增加成功');
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $value = array('status'=>0,'mess'=>'增加失败');
                }
            }
            return json($value);
        }else{
            $admin_id = session('admin_id');
            
            return $this->fetch();
        }
    }

    public function delete(){
        if(input('post.id')){
            $id = array_filter(explode(',', input('post.id')));
        }else{
            $id = input('id');
        }
        
        if(!empty($id)){
            $count = Db::name('bank_name')->where('id', 'in', $id)->delete();
            
            if($count){
                
                $value = array('status'=>1,'mess'=>'删除成功');
            }else{
                $value = array('status'=>0,'mess'=>'删除失败');
            }
        }else{
            $value = array('status'=>0,'mess'=>'请选择删除项');
        }
        return json($value);
    }
    
    public function edit(){
        if(request()->isAjax()){
            if(input('post.id')){
                $admin_id = session('admin_id');
                $data = input('post.');
                $result = $this->validate($data,'BankName');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $ads = Db::name('bank_name')->where('id',$data['id'])->find();
 
                    if($ads){
                            // 启动事务
                            Db::startTrans();
                            try{
                                Db::name('bank_name')->update($data);
                                
                                // 提交事务
                                Db::commit();
                                $value = array('status'=>1,'mess'=>'编辑成功');
                            } catch (\Exception $e) {
                                // 回滚事务
                                Db::rollback();
                                $value = array('status'=>0,'mess'=>'编辑失败');
                            }
                    }else{
                        $value = array('status'=>0,'mess'=>'找不到相关信息');
                    }
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少参数');
            }
            return json($value);
        }else{
            if(input('id')){
                $id = input('id');
                $ads = Db::name('bank_name')->find($id);
                if($ads){
                    
                    $this->assign('pnum', input('page'));
                    if(input('s')){
                        $this->assign('search', input('s'));
                    }
                    if(input('pos_id')){
                        $this->assign('pos_id', input('pos_id'));
                    }
                    $this->assign('ads',$ads);
                    return $this->fetch();
                }else{
                    $this->error('找不到相关信息');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }
}