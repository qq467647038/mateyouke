<?php
namespace app\admin\controller;

use think\Db;

class WineMemberUSDT extends Common{
    //会员USDT列表
    public function lst(){

        $filter = input('filter');$post = input();
        
        $whereTime=[];
        if($post['startDate']){
            $whereTime = [$post['startDate'], $post['endDate']];
        }
        else{
            $whereTime = ['2022-01-01', date('Y-m-d', strtotime('tomorrow'))];
        }

        $list = Db::name('wine_usdt_account_generated')->alias('wuag')
                    ->field('wuag.id, wuag.user_id, wuag.address, wuag.addtime, wuag.updatetime, wuag.status, m.phone, m.user_name')
                    ->where('wuag.addtime', 'between time', $whereTime)
                    ->join('member m', 'wuag.user_id = m.id', 'left')
                    ->order('wuag.status asc, wuag.id desc')->paginate(50, false, ['query'=>request()->param()]);
        $count = Db::name('wine_usdt_account_generated')->count();
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
            'count'=>$count,
            'where_time'=>$whereTime,
            'filter'=>input('filter'),
        ));
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
    
    public function dispatchuser(){
        $id = input('id');$memberList=[];
        $info = Db::name('wine_usdt_account_generated')->where('id', $id)->find();
        
        if(!is_null($info) && $id){
            $user_id_column = Db::name('wine_usdt_account_generated')->column('user_id');
            $user_id_column = array_filter($user_id_column);
            
            $memberList = Db::name('member')->where('checked', 1)->where('id', 'notIn', $user_id_column)->column('user_name, id');
        }

        $this->assign('memberList', $memberList);
        $this->assign('info', $info);
        return $this->fetch();
    }
    
    public function handdleDispatch(){
        $input = input();
        
        $id = $input['id'];
        $member_id = $input['member_id'];
        
        $info = Db::name('wine_usdt_account_generated')->where('id', $id)->where('status', 0)->find();
        $memberInfo = Db::name('member')->where('id', $member_id)->where('checked', 1)->find();

        if(is_null($info) || is_null($memberInfo)){
            $value = array('status'=>0,'mess'=>'数据不存在');
        }
        else{
            $res = Db::name('wine_usdt_account_generated')->where('id', $id)->where('status', 0)->update([
                'status'=>1,
                'user_id'=>$member_id,
                updatetime=>time()
            ]);
            if(!$res){
                $value = array('status'=>0,'mess'=>'分配失败');
            }
            else{
                $value = array('status'=>1,'mess'=>'分配成功');
            }
        }
        
        return json($value);
    }
}
