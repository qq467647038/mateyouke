<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class ManageCate extends Common{
    
    public function lst(){
        $filter = input('filter');
        if(!$filter || !in_array($filter, array(1,2,3,5))){
            $filter = 5;
        }
        
        $where = array();
        
        switch($filter){
            //待审核
            case 1:
                $where['checked'] = 0;
                break;
            //已通过
            case 2:
                $where['checked'] = 1;
                break;
            //已拒绝
            case 3:
                $where['checked'] = 2;
                break;
            //全部
            case 5:
            
                break;
        }
        
        $list = Db::name('manage_cate')->alias('a')->field('a.*,b.cate_name,c.shop_name')->join('sp_category b','a.cate_id = b.id','LEFT')->join('sp_shops c','a.shop_id = c.id','LEFT')->where($where)->order('a.apply_time desc')->paginate(25);
        
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        
        $this->assign('pnum',$pnum);
        $this->assign('page',$page);
        $this->assign('filter',$filter);
        $this->assign('list',$list);// 赋值数据集
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }

    //修改状态
    public function gaibian(){
        $id = input('post.id');
        $name = input('post.name');
        $value = input('post.value');
        $manages = Db::name('manage_cate')->where('id',$id)->find();
        if($manages){
            $data[$name] = $value;
            $data['id'] = $id;
            $count = Db::name('coupon')->update($data);
            if($count > 0){
                ys_admin_logs('修改商家经营类目状态','manage_cate',$id);
                $result = 1;
            }else{
                $result = 0;
            }
        }else{
            $result = 0;
        }
        return $result;
    }
    
    public function checked(){
        if(request()->isPost()){
            $data = input('post.');
            if(!empty($data['id'])){
                if(!empty($data['checked']) && in_array($data['checked'], array(1,2))){
                    $manages = Db::name('manage_cate')->where('id',$data['id'])->where('checked',0)->find();
                    if($manages){
                        $count = Db::name('manage_cate')->where('id',$data['id'])->update(array('checked'=>$data['checked'],'checked_time'=>time()));
                        if($count !== false){
                            if($data['checked'] == 1){
                                ys_admin_logs('审核通过商家经营类目状态','manage_cate',$data['id']);
                            }elseif($data['checked'] == 2){
                                ys_admin_logs('审核不通过商家经营类目状态','manage_cate',$data['id']);
                            }
                            $value = array('status'=>1,'mess'=>'设置成功');
                        }else{
                            $value = array('status'=>0,'mess'=>'设置失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'找不到相关信息');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'参数错误');
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少参数');
            }
            return json($value);
        }else{
            if(input('id')){
                $manages = Db::name('manage_cate')->alias('a')->field('a.*,b.cate_name,c.shop_name')->join('sp_category b','a.cate_id = b.id','LEFT')->join('sp_shops c','a.shop_id = c.id','LEFT')->where('a.id',input('id'))->where('a.checked',0)->find();
                if($manages){
                    $indus_id = Db::name('shops')->where('id',$manages['shop_id'])->value('indus_id');
                    $manages['industry_name'] = Db::name('industry')->where('id',$indus_id)->value('industry_name');
                    if(input('s')){
                        $this->assign('search', input('s'));
                    }
                    $this->assign('pnum', input('page'));
                    $this->assign('filter',input('filter'));
                    $this->assign('manages',$manages);
                    return $this->fetch();
                }else{
                    $this->error('找不到相关信息');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }
    
    public function info(){
        if(input('id')){
            $manages = Db::name('manage_cate')->alias('a')->field('a.*,b.cate_name,c.shop_name')->join('sp_category b','a.cate_id = b.id','LEFT')->join('sp_shops c','a.shop_id = c.id','LEFT')->where('a.id',input('id'))->find();
            if($manages){
                $indus_id = Db::name('shops')->where('id',$manages['shop_id'])->value('indus_id');
                $manages['industry_name'] = Db::name('industry')->where('id',$indus_id)->value('industry_name');
                if(input('s')){
                    $this->assign('search', input('s'));
                }
                $this->assign('pnum', input('page'));
                $this->assign('filter',input('filter'));
                $this->assign('manages',$manages);
                return $this->fetch();
            }else{
                $this->error('找不到相关信息');
            }
        }else{
            $this->error('缺少参数');
        }
    }
    
    public function search(){
        if(input('post.keyword') != ''){
            cookie('manage_keyword',input('post.keyword'),7200);
        }else{
            cookie('manage_keyword',null);
        }
    
        if(input('post.starttime') != ''){
            $managestarttime = strtotime(input('post.starttime'));
            cookie('managestarttime',$managestarttime,7200);
        }else{
            cookie('managestarttime',null);
        }
    
        if(input('post.endtime') != ''){
            $manageendtime = strtotime(input('post.endtime'));
            cookie('manageendtime',$manageendtime,7200);
        }else{
            cookie('manageendtime',null);
        }
    
        if(input('post.checked') != ''){
            cookie('managechecked',input('post.checked'),7200);
        }
    
        $where = array();
    
        if(cookie('manage_keyword')){
            $shops = Db::name('shops')->where('shop_name',cookie('manage_keyword'))->field('id')->find();
            if($shops){
                $where['a.shop_id'] = $shops['id'];
            }else{
                $where['a.shop_id'] = 'aa';
            }
        }
    
        if(cookie('manageendtime') && cookie('managestarttime')){
            $where['a.apply_time'] = array(array('egt',cookie('managestarttime')), array('lt',cookie('manageendtime')));
        }
    
        if(cookie('managestarttime') && !cookie('manageendtime')){
            $where['a.apply_time'] = array('egt',cookie('managestarttime'));
        }
    
        if(cookie('manageendtime') && !cookie('managestarttime')){
            $where['a.apply_time'] = array('lt',cookie('manageendtime'));
        }
    
        if(cookie('managechecked') != ''){
            $managechecked = (int)cookie('managechecked');
            if(!empty($managechecked)){
                switch ($managechecked){
                    //待神格
                    case 1:
                        $where['a.checked'] = 0;
                        break;
                        //已审核
                    case 2:
                        $where['a.checked'] = 1;
                        break;
                        //已拒绝
                    case 3:
                        $where['a.checked'] = 2;
                        break;
                }
            }
        }
    
        $list = Db::name('manage_cate')->alias('a')->field('a.*,b.cate_name,c.shop_name')->join('sp_category b','a.cate_id = b.id','LEFT')->join('sp_shops c','a.shop_id = c.id','LEFT')->where($where)->order('a.apply_time desc')->paginate(25);
        $page = $list->render();
    
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
    
        $search = 1;
    
        if(cookie('managestarttime')){
            $this->assign('starttime',cookie('managestarttime'));
        }
    
        if(cookie('manageendtime')){
            $this->assign('endtime',cookie('manageendtime'));
        }
    
        if(cookie('manage_keyword')){
            $this->assign('keyword',cookie('manage_keyword'));
        }
    
        if(cookie('managechecked')){
            $this->assign('checked',cookie('managechecked'));
        }
    
        $this->assign('search',$search);
        $this->assign('pnum', $pnum);
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $page);// 赋值分页输出
        $this->assign('filter',3);
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
    
    
}