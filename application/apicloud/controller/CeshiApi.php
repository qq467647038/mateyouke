<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;

class CeshiApi extends Common{
    public function ceshi(){
        // echo 1;die;
        if(request()->isPost()){
            $secretstr = input('post.url');
            // echo $secretstr;die;
            if($secretstr){
                $client_secret = 'yiling6670238160ravntyoneapp7926';
                $api_token_server = md5($secretstr.date('Y-m-d', time()).$client_secret);
                return $api_token_server;
            }else{
                $value = array('status'=>400,'mess'=>'缺失url参数，url格式为：apicloud/CeshiApi/ceshi','data'=>array('status'=>400));
            }
            
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
            
        }
        return json($value); 
    }
    

}