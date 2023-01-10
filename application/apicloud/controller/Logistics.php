<?php
/*
 * @Author: your name
 * @Date: 2020-08-05 00:20:45
 * @LastEditTime: 2020-08-05 02:34:36
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: /daohang/Users/wutong/Library/Caches/com.binarynights.ForkLift-3/FileCache/FE8614C2-3AE1-4FC9-9E5B-131EEAEE9180/Logistics.php
 */
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use think\Db;
use think\Request;

class Logistics extends Common{

    public $customer; // 快递100
    public $key;
    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->customer = '00DF95CA3E67CD5D0A3B624BC0591926';
        $this->key = 'mixxUzxm7135';
    }

    //快递鸟
    public function kdNiao1(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){
            return json($tokenRes);
        }else{
            $userId = $tokenRes['user_id'];
        }
        $data = input('post.');

        $appKey = 'a9b98ae4-c7a6-439e-a8d2-5628700fb579';
        $eBusinessId = '1661328';
        $requestData= "{'OrderCode':'','ShipperCode':'".$data['kdniao_code']."','LogisticCode':'".$data['psnum']."'}";

        $datas = array(
            'EBusinessID' => $eBusinessId,
            'RequestType' => '1002',
            'RequestData' => urlencode($requestData) ,
            'DataType' => '2',
        );

        $datas['DataSign'] = urlencode(base64_encode(md5($requestData.$appKey)));

        $url = 'http://api.kdniao.com/Ebusiness/EbusinessOrderHandle.aspx';
        $temps = array();
        foreach ($datas as $key => $value) {
            $temps[] = sprintf('%s=%s', $key, $value);
        }
        $post_data = implode('&', $temps);
        $url_info = parse_url($url);

        if(empty($url_info['port']))
        {
            $url_info['port']=80;
        }
        $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
        $httpheader.= "Host:" . $url_info['host'] . "\r\n";
        $httpheader.= "Content-Type:application/x-www-form-urlencoded\r\n";
        $httpheader.= "Content-Length:" . strlen($post_data) . "\r\n";
        $httpheader.= "Connection:close\r\n\r\n";
        $httpheader.= $post_data;
        // dump($httpheader);die;
        $fd = fsockopen($url_info['host'], $url_info['port']);
        fwrite($fd, $httpheader);
        $gets = "";
        $headerFlag = true;
        while (!feof($fd)) {
            if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
                break;
            }
        }
        while (!feof($fd)) {
            $gets.= fread($fd, 128);
        }
        fclose($fd);
        $logisticsInfo = object_to_array(json_decode($gets));
        // dump($logisticsInfo);
        if($logisticsInfo['State'] == '3'){
            datamsg(200,'获取成功',$logisticsInfo['Traces']);
        }else{
            datamsg(400,'暂未查询到物流信息');
        }

    }

    /**
     * @function新的快递接口
     * @author Feifan.Chen <1057286925@qq.com>
     * @return \think\response\Json
     */
    // public function kdNiao(){
    //     $tokenRes = $this->checkToken();
    //     if($tokenRes['status'] == 400){
    //         return json($tokenRes);
    //     }else{
    //         $userId = $tokenRes['user_id'];
    //     }
    //     $data = input('post.');

    //     $param = [
    //         'com' => $data['kdniao_code'],
    //         'num' => $data['psnum']
    //     ];
    //     $param = json_encode($param);
    //     $sign =  $param . $this->key . $this->customer;
    //     $sign = md5($sign);
    //     $sign = strtoupper($sign);
    //     $res = https_request("https://poll.kuaidi100.com/poll/query.do?customer=".$this->customer."&sign=".$sign."&param=".$param);
    //     $str = json_decode($res,true);
    //     if($str['status'] == 200){

    //         datamsg(200,'获取成功',$str['data']);

    //     }else{
    //         datamsg(400,'暂无物流信息',[]);

    //     }

    // }
    
    public function kdNiao(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){
            return json($tokenRes);
        }else{
            $userId = $tokenRes['user_id'];
        }
        $data = input('post.');


        $orderInfo = Db::name('order')->where('user_id', $userId)->where('ordernumber', $data['order_num'])->find();
        $logisticsTrend = [];
        if($orderInfo){
            $logisticsInfo = json_decode($orderInfo['logistics'], true);

            $day = ceil((time() - $orderInfo['fh_time'])/(24*60*60));

            foreach ($logisticsInfo as $k=>$v){
                if($day >= $k+1){
                    array_push($logisticsTrend, $v);
                }
            }
        }
        
        datamsg(200,'获取成功',$logisticsTrend);

    }
}
