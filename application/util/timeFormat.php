<?php
namespace app\util;

class timeFormat{
    private $format_time = '';
    private $date_arr = [
        '60'        => 'seconds',
        '3600'      => 'minutes',
        '86400'     => 'hours',
        '2592000'   => 'day',
        '31536000'  => 'month'
    ];

    public function __construct($time = '')
    {
        $this->format_time = $time;
    }

    public function getMethod($surplusTime){
        foreach ($this->date_arr as $k=>$v){
            if($surplusTime < $k){
                $this->methodName = $v;
                break;
            }else{
                $this->methodName = 'year';
            }
        }
    }

    public function timeqh($surplusTime){
        if($surplusTime > 0){
            $this->timeTxt .= '前';
        }elseif($surplusTime < 0){
            $this->timeTxt .= '后';
        }elseif ($surplusTime == 0){
            $this->timeTxt = '刚刚';
        }
    }

    public function calculateTime(){
        $surplusTime = time() - $this->format_time;

        $this->getMethod(abs($surplusTime));

        call_user_func(array($this, $this->methodName), abs($surplusTime));

        $this->timeqh($surplusTime);

        return $this;
    }

    public function seconds($surplusTime){
//        if($surplusTime == 0)
//            $surplusTime=1;
        $this->timeTxt =  $surplusTime . '秒';
    }

    public function minutes($surplusTime){
        $arrayKey = array_keys($this->date_arr);
        $time = $arrayKey[0];

        $this->timeTxt =  floor($surplusTime / $time).'分钟';
    }

    public function hours($surplusTime){
        $arrayKey = array_keys($this->date_arr);
        $time = $arrayKey[1];

        $this->timeTxt =  floor($surplusTime / $time).'小时';
    }

    public function day($surplusTime){
        $arrayKey = array_keys($this->date_arr);
        $time = $arrayKey[2];

        $this->timeTxt =  floor($surplusTime / $time).'天';
    }

    public function month($surplusTime){
        $arrayKey = array_keys($this->date_arr);
        $time = $arrayKey[3];

        $this->timeTxt =  floor($surplusTime / $time).'月';
    }

    public function year($surplusTime){
        $arrayKey = array_keys($this->date_arr);
        $time = $arrayKey[4];

        $this->timeTxt =  floor($surplusTime / $time).'年';
    }

    public function getTime(){
        return $this->timeTxt;
    }

}