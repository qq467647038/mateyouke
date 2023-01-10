<?php
/**
 * Created by PhpStorm.
 * User: 云
 * Date: 2022/1/17
 * Time: 19:55
 */

namespace app\admin\controller;

use think\Db;

class WineMemberLevel extends Common
{
    public function lst(){
        $list = Db::name('wine_level')->select();

        $this->assign('list', $list);
        return $this->fetch();
    }

    public function view()
    {
        $id = input('id');
        $level = Db::name('wine_level')->where('id', $id)->find();

        $this->assign('level', $level);

        return $this->fetch();
    }

    public function manageRewardsView()
    {
        $id = input('id');
        $rewards = Db::name('wine_manage_rewards')->where('id', $id)->find();

        $this->assign('level', $rewards);
        return $this->fetch();
    }

    public function edit(){
        $input = input();
        $id = $input['id'];
        if(request()->isAjax()){
            $level_name = $input['level_name'];
            $rate = $input['rate'];
            $team_num = $input['team_num'];
            $uplevel_num = $input['uplevel_num'];
            if($rate <= 0){
                $value = array('status'=>0,'mess'=>'利率设置异常');
                return json($value);
            }
            if($team_num <= 0){
                $value = array('status'=>0,'mess'=>'团队数设置异常');
                return json($value);
            }
            if($uplevel_num <= 0){
                $value = array('status'=>0,'mess'=>'直推数设置异常');
                return json($value);
            }
            $data['level_name'] = $level_name;
            $data['rate'] = $rate;
            $data['team_num'] = $team_num;
            $data['uplevel_num'] = $uplevel_num;

            $res = Db::name('wine_level')->where('id', $id)->update($data);
            if($res > 0){
                return $value = array('status'=>1,'mess'=>'修改成功');
            }
            $value = array('status'=>0,'mess'=>'设置失败');
            return json($value);
        }else{
            $list = Db::name('wine_level')->where('id', $id)->find();

            $this->assign('list', $list);
            return $this->fetch();
        }


    }

    public function manage_reward_edit(){
        $input = input();
        $id = $input['id'];
        if(request()->isAjax()){
            $rate = $input['rate'];
            $generation_num = $input['generation_num'];
            if($rate <= 0){
                $value = array('status'=>0,'mess'=>'利率设置异常');
                return json($value);
            }
            if($generation_num <= 0){
                $value = array('status'=>0,'mess'=>'代数设置异常');
                return json($value);
            }

            $data['rate'] = $rate;
            $data['generation_num'] = $generation_num;

            $res = Db::name('wine_manage_rewards')->where('id', $id)->update($data);
            if($res > 0){
                return $value = array('status'=>1,'mess'=>'修改成功');
            }
            $value = array('status'=>0,'mess'=>'设置失败');
            return json($value);
        }else{
            $list = Db::name('wine_manage_rewards')->where('id', $id)->find();

            $this->assign('list', $list);
            return $this->fetch();
        }


    }

    public function manage_rewards(){
        $list = Db::name('wine_manage_rewards')->select();

        $this->assign('list', $list);
        return $this->fetch();
    }
}