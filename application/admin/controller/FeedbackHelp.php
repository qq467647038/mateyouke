<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class FeedbackHelp extends Common{ 
    
    public function lst(){
        $filter = input('filter');
        if(!$filter || !in_array($filter, array(1,2,10))){
            $filter = 10;
        }
        
        $where = array();
        
        switch ($filter){
            case 1:
                //待回复
                $where = array('a.reply'=>0);
                break;
            case 2:
                //已回复
                $where = array('a.reply'=>1);
                break;
        }
        
        $list = Db::name('feedback_help')->alias('a')->field('a.*,b.user_name,b.phone')->join('sp_member b','a.user_id = b.id','LEFT')->where($where)->order('a.time desc')->paginate(25);
        $page = $list->render();
        
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        
        $this->assign(array(
            'list'=>$list,
            'page'=>$page,
            'pnum'=>$pnum,
            'filter'=>$filter
        ));
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
    
    public function reply(){
        if(request()->isPost()){
            if(input('post.fid')){
                $fid = input('post.fid');
                $fks = Db::name('feedback_help')->where('id',$fid)->find();
                if($fks){
                    if(input('post.content')){
                        $content = input('post.content');
                        if(input('post.id') && $fks['reply'] == 1){
                            $replys = Db::name('reply')->where('id',input('post.id'))->where('fid',$fid)->find();
                            if($replys){
                                $count = Db::name('reply')->update(array('content'=>$content,'fid'=>$fid,'admin_id'=>session('admin_id'),'replytime'=>time(),'id'=>input('post.id')));
                                if($count !== false){
                                    ys_admin_logs('回复用户反馈','reply',input('post.id'));
                                    $value = array('status'=>1, 'mess'=>'回复成功');
                                }else{
                                    $value = array('status'=>0, 'mess'=>'回复失败');
                                }
                            }else{
                                $value = array('status'=>0, 'mess'=>'参数错误，回复失败');
                            }
                        }elseif(!input('post.id') && $fks['reply'] == 0){
                            // 启动事务
                            Db::startTrans();
                            try{
                                $fedid = Db::name('reply')->insertGetId(array('content'=>$content,'fid'=>$fid,'admin_id'=>session('admin_id'),'replytime'=>time()));
                                Db::name('feedback_help')->update(array('reply'=>1,'id'=>$fid));
                                // 提交事务
                                Db::commit();
                                ys_admin_logs('回复用户反馈','reply',$fedid);
                                $value = array('status'=>1, 'mess'=>'回复成功');
                            } catch (\Exception $e) {
                                // 回滚事务
                                Db::rollback();
                                $value = array('status'=>0, 'mess'=>'回复失败');
                            }
                        }else{
                            $value = array('status'=>0, 'mess'=>'参数有误，回复失败');
                        }
                    }else{
                        $value = array('status'=>0, 'mess'=>'请填写回复内容，回复失败');
                    }
                }else{
                    $value = array('status'=>0, 'mess'=>'参数错误，回复失败');
                }
            }else{
                $value = array('status'=>0, 'mess'=>'参数错误，回复失败');
            }
            return json($value);
        }else{
            if(input('fid') && input('filter')){
                if(in_array(input('filter'), array(1,2,10))){
                    $fid = input('fid');
                    $fks = Db::name('feedback_help')->alias('a')->field('a.*,b.user_name,b.phone')->join('sp_member b','a.user_id = b.id','LEFT')->where('a.id',$fid)->find();
                    if($fks){
                        $replys = Db::name('reply')->alias('a')->field('a.*,b.en_name')->join('sp_admin b','a.admin_id = b.id','LEFT')->where('a.fid',$fks['id'])->find();
                        if(input('s')){
                            $this->assign('search',input('s'));
                        }
                        $this->assign('pnum',input('page'));
                        $this->assign('filter',input('filter'));
                        $this->assign('fks',$fks);
                        $this->assign('replys',$replys);
                        return $this->fetch();
                    }else{
                        $this->error('参数错误');
                    }
                }else{
                    $this->error('参数错误');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }
    
    //删除
    public function delete(){
        if(input('post.id')){
            $id = array_filter(explode(',', input('post.id')));
        }else{
            $id = input('id');
        }
        if(!empty($id)){
            if(is_array($id)){
                $count = Db::name('feedback_help')->delete($id);
                if($count > 0){
                    foreach($id as $v){
                        Db::name('reply')->where('fid',$v)->delete();
                    }
                    $value = array('status'=>1,'mess'=>'删除成功');
                }else{
                    $value = array('status'=>0,'mess'=>'删除失败');
                }
            }else{
                $count = Db::name('feedback_help')->delete($id);
                if($count > 0){
                    Db::name('reply')->where('fid',$id)->delete();
                    $value = array('status'=>1,'mess'=>'删除成功');
                }else{
                    $value = array('status'=>0,'mess'=>'删除失败');
                }
            }
        }else{
            $value = array('status'=>0,'mess'=>'请选择删除项');
        }
        return json($value);
    }
    
    
    public function search(){
        if(input('post.keyword') != ''){
            cookie('fk_keyword',input('post.keyword'),7200);
        }else{
            cookie('fk_keyword',null);
        }
        
        if(input('post.fk_zt') != ''){
            cookie("fk_zt", input('post.fk_zt'), 7200);
        }else{
            cookie('fk_zt',null);
        }
        
        if(input('post.leixing') != ''){
            cookie("fk_leixing", input('post.leixing'), 7200);
        }
        
        if(input('post.starttime') != ''){
            $fkstarttime = strtotime(input('post.starttime'));
            cookie('fkstarttime',$fkstarttime,3600);
        }else{
            cookie('fkstarttime',null);
        }
        
        if(input('post.endtime') != ''){
            $fkendtime = strtotime(input('post.endtime'));
            cookie('fkendtime',$fkendtime,3600);
        }else{
            cookie('fkendtime',null);
        }
        
        $where = array();
        
        if(cookie('fk_zt') != ''){
            $fk_zt = (int)cookie('fk_zt');
            if($fk_zt != 0){
                switch($fk_zt){
                    //待回复
                    case 1:
                        $where['a.reply'] = 0;
                        break;
                        //已回复
                    case 2:
                        $where['a.reply'] = 1;
                        break;
                }
            }
        }
        
        if(cookie('fk_keyword')){
            $where['a.content'] = array('like','%'.cookie('fk_keyword').'%');
        }
        
        if(cookie('fkendtime') && cookie('fkstarttime')){
            $where['a.time'] = array(array('egt',cookie('fkstarttime')), array('lt',cookie('fkendtime')));
        }
        
        if(cookie('fkstarttime') && !cookie('fkendtime')){
            $where['a.time'] = array('egt',cookie('fkstarttime'));
        }
        
        if(cookie('fkendtime') && !cookie('fkstarttime')){
            $where['a.time'] = array('lt',cookie('fkendtime'));
        }
        
        if(cookie('fk_leixing') != ''){
            $fk_leixing = (int)cookie('fk_leixing');
            if($fk_leixing != 0){
                switch($fk_leixing){
                    //销售人员
                    case 1:
                        $where['b.leixing'] = 1;
                        break;
                        //行政人员
                    case 2:
                        $where['b.leixing'] = 2;
                        break;
                        //经销商
                    case 3:
                        $where['b.leixing'] = 3;
                        break;
                        //安装师傅
                    case 4:
                        $where['b.leixing'] = 4;
                        break;
                }
            }
        }
        
        $list = Db::name('feedback_help')->alias('a')->field('a.*,b.user_name,b.phone')->join('sp_member b','a.user_id = b.id','LEFT')->where($where)->order('a.time desc')->paginate(25);
        $page = $list->render();
        
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $search = 1;
        
        if(cookie('fkstarttime')){
            $this->assign('starttime',cookie('fkstarttime'));
        }
        
        if(cookie('fkendtime')){
            $this->assign('endtime',cookie('fkendtime'));
        }
        
        if(cookie('fk_keyword')){
            $this->assign('keyword',cookie('fk_keyword'));
        }
        
        if(cookie('fk_zt') != ''){
            $this->assign('fk_zt',cookie('fk_zt'));
        }
        
        if(cookie('fk_leixing') != ''){
            $this->assign('leixing',cookie('fk_leixing'));
        }
        
        if(input('post.filter')){
            $filter = input('post.filter');
        }else{
            $filter = 10;
        }
        $this->assign('search',$search);
        $this->assign('pnum', $pnum);
        $this->assign('filter',$filter);
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $page);// 赋值分页输出
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }

}
?>
