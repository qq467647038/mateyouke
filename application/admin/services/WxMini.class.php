<?php


namespace Weixin;


class Wxmini
{
    private function getaccessToken(){
        $appid="wxef0e377134383617";
        $appScrect="42023f381c90a5976e17f3db2ec43e73";
        $resultdd = '';
        $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$appScrect;
        $params = array();
        $result = $this->do_post($url,$params, 'GET');
        $result1 = json_decode($result,true);
        if(array_key_exists("errcode",$result1))
        {
            if($result1['errcode'] != 0)
                $this->getaccessToken();
        }
        else{
            $resultdd = $result1['access_token'];
        }
        return $resultdd;
    }

    private function do_post($url, $params,$type) {
        $headers=array("X-AjaxPro-Method:ShowList","Content-Type:application/json;charset=utf-8");
        $ch = curl_init ();
        curl_setopt($ch, CURLOPT_URL, $url );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST,  $type);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
    private function do_post1($url, $params='') {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'X-AjaxPro-Method:ShowList',
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($params))
        );
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    public function checkMsg($content)
    {
        $result ='';
        $token = $this->getaccessToken();
        if($token !== '')
        {
            $url = "https://api.weixin.qq.com/wxa/msg_sec_check?access_token=".$token;
            $params1 = '{"content":"'.$content.'"}';
            $result = $this->do_post1($url,$params1);
        }
        return $result;
    }

}