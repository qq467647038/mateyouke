<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/8 0008
 * Time: 11:09
 */
namespace app\apicloud\controller;
use app\apicloud\model\Gongyong as GongyongMx;
use app\apicloud\model\SignSet as SignSetmodel;
use think\Cache;

class Sign extends Common
{
    public $model;
    public $user_id;
    public function _initialize(){
        parent::_initialize();
        $this->model    =new SignSetmodel;
        $this->checktoken();
        $this->user_id = Cache::get('user_id');
    }

    /**
     * 我的签到信息
     * @param
     * @return object
     * @author:Damow
     */
    public function signInfo(){

        $signinfo = $this->model->getSign();
        $config   = model('sign_set')->get(1)->toArray();
        $signinfo['guize'] =json_decode($config['reword_order'],1);
        datamsg(WIN,SUCCESS,$signinfo);
    }

    /**
     * 签到记录
     * @param
     * @return object
     * @author:Damow
     */
    public function signLog(){
        !isset($this->data['page'])?$page=1:$page=$this->data['page'];
        $list   = db('sign_records')->where(['user_id'=>Cache::get('user_id')])->field('time,credit,log')->order('id desc')->page($page,PAGE)->select();
        count($list)<1 && datamsg(WIN,'暂无更多数据','arr');
        foreach ($list as $k=>$v){
            $list[$k]['time']   = date('Y-m-d H:i:s',$v['time']);
        }
        datamsg(WIN,'成功',$list);
    }

    /**
     * 点击签到（连续签到）
     * @param date 今天的日期
     * @param type 1连续签到奖励
     * @return object
     * @author:Damow
     */
    public function dosign()
    {
        $res = $this->checkToken();
        if($res['status'] == 400){  return json($res);  }

        isset($this->data['type'])?$this->data['type']:0;
        $today      = date('d', time());
        $tomouth    = date('Y-m', time());
        $signinfo   = $this->model->getSign();

        $config   = model('sign_set')->get(1)->toArray();
        $integral = model('member')->getUser('id',Cache::get('user_id'));

        if($this->data['type']==1){
            $days = $this->data['days'];
            $sign_info = db('sign_user')->where(['user_id'=>Cache::get('user_id'),'signdate'=>$tomouth])->find();
            $guize =json_decode($config['reword_order'],1);
            $signinfo['continuous']<$days && datamsg(LOSE,'还未达到领取标准');
            $counts =intval($signinfo['continuous']/$config['reward_default_day']);
            $findContinuous = db('sign_records')->whereTime('time','month')->where('day',$days)->find();
            if($findContinuous){
                datamsg(LOSE,'亲，您已经领取过了！');
            }

            model('member')->addLog($guize[$sign_info['sum']+1]['num'],'连续签到奖励+'.$guize[$sign_info['sum']+1]['num'],1,$days);
            $this->addIntegral(Cache::get('user_id'),$guize[$sign_info['sum']+1]['num'],11);
            datamsg(WIN,'领取成功',array('integral'=>$guize[$sign_info['sum']+1]['num']+$integral['integral']));
        }else{
            isset($this->data['date'])&&!empty($this->data['date'])?$this->data['date']:datamsg(LOSE,'请选择签到的时间');
            //普通签到
            $today!=$this->data['date'] && datamsg(LOSE,'只能签到今天的日期');
            $signinfo['today']==true && datamsg(LOSE,'今天已经签到成功');

            model('member')->addLog($config['reward_default_day'],'日常签到+'.$config['reward_default_day']);
            $this->addIntegral(Cache::get('user_id'),$config['reward_default_day'],10);
            datamsg(WIN,'签到成功',array('integral'=>$config['reward_default_day']+$integral['integral']));
        }

    }




}