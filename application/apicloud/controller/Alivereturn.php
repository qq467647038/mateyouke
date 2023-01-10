<?php
namespace app\apicloud\controller;
class Alivereturn extends Common {

    /**
     * 推流直播回调
    */
    public function pushreturn(){
        $param = input('param.');
        file_put_contents('pushreturn.log',json_encode($param));
        if(!empty($param)){
            $room = $param['stream_id'];
            $data['starttime']=time();
            $data['status']=1;
            $result = db('alive')->where(['room'=>$room])->update($data);
            if($result){
                $alive = db('alive')->where(['room'=>$room])->find();
                $insert['mid']=$alive['shop_id'];
                $insert['aid']=$alive['id'];
                $insert['starttime']=time();
                $insert['title']=$alive['title'];
                $insert['notice']=$alive['notice'];
                $insert['cover']=$alive['cover'];
                $insert['room']=$room;
                db('alive_record')->insert($insert);
            }
        }
    }

    /**
     * 直播录制回调
     */
    public function transcribeReturn(){
        $param = input('param.');
        file_put_contents('transcribeReturn.log',json_encode($param));
        if(!empty($param)){
            $data['stream_id'] = $param['stream_id'];
            $data['start_time']= $param['start_time'];
            $data['end_time']= $param['end_time'];
            $data['video_url']= $param['video_url'];
            $data['duration']= $param['duration'];
            $result = db('alive_transcribe')->insert($data);
        }
    }



    /**
     * 断流直播回调
     */
    public function breakpushreturn(){
        $param = input('param.');
        file_put_contents('breakpushreturn.log',json_encode($param));
        $room = $param['channel_id'];

        if(!empty($param)){
            $rooms = db('alive')->where(['room'=>$room])->find();
            if($rooms['status'] != 2){   //如果为管理员关闭了这个直播间
                $data['status']=-1;    
            }
            $data['endtime']=time();
            $result = db('alive')->where(['room'=>$room])->update($data);
            if($result){
                $alive = db('alive')->where(['room'=>$room])->find();
                $update['endtime']=time();
                $record = db('alive_record')->where(['mid'=>$alive['shop_id'],'room'=>$room])->order('id desc')->find();
                if($record){
                    db('alive_record')->where(['id'=>$record['id']])->update($update);
                }
            }
        }
    }

}