<?php
/**
 * Created by PhpStorm.
 * @anthor: Pupil_Chen
 * Date: 2020/11/16 0016
 * Time: 21:48
 */

namespace app\apicloud\controller;


class Baidu extends Common
{

    public function face(){
        $url = 'https://api-cn.faceplusplus.com/imagepp/v1/mergeface';
        $img1 = $this->imgToBase64('D:\phpstudy_pro\WWW\img1/8.png');
        $img2 = $this->imgToBase64('D:\phpstudy_pro\WWW\img1/wujin.jpg');
        $img1 = explode(',', $img1);
        $img1 = $img1[1];
        $img2 = explode(',',$img2);
        $img2 = $img2[1];
        $arr = [
            'api_key' => 'z5RmdzxIu5ayPhUOA0UMsVbPH-xBp1aj',
            'api_secret' => 'YEsKOrUzew33cc-oxEm2j-NXxg8xthyq',
            'template_base64' => $img1,
            'merge_base64' => $img2
        ];

//        $arr_json = json_encode($arr);
        $res = $this->send_post($url, $arr);
        $res = json_decode($res,1);
        halt($res);
        if (is_null($res['result'])){
            print_r($res);
        }else{
            $img_base64 = $res['result'];
            echo '<img src="' . 'data:image/jpg;base64,'.$img_base64 . '">';
        }

//        var_dump($res);
    }

    public function test(){
        $url = 'https://aip.baidubce.com/rest/2.0/face/v1/merge?access_token=' . $this->getAccessToken();
        $img1 = $this->imgToBase64('D:\phpstudy_pro\WWW\img1/1.jpg');
        $img2 = $this->imgToBase64('D:\phpstudy_pro\WWW\img1/3.jpg');
        $img1 = explode(',', $img1);
        $img1 = $img1[1];
        $img2 = explode(',',$img2);
        $img2 = $img2[1];
        $arr = [
            'image_template' => [
                'image' => $img1,
                'image_type' => 'BASE64',
                'quality_control' => 'HIGH',
            ],
            'image_target' => [
                'image' => $img2,
                'image_type' => 'BASE64',
                'quality_control' => 'HIGH',
            ]
        ];
        $arr_json = json_encode($arr);

        $bodys = "{\"image_template\":{\"image\":\"$img1\",\"image_type\":\"BASE64\",\"quality_control\":\"NONE\"},\"image_target\":{\"image\":\"$img2\",\"image_type\":\"BASE64\",\"quality_control\":\"NONE\"}}";
        $res = $this->request_post($url, $arr_json);
        $res = json_decode($res,1);
        if ($res['error_code'] == 0 && $res['error_msg'] = 'SUCCESS'){
            $img_base64 = $res['result']['merge_image'];
            echo '<img src="' . 'data:image/jpg;base64,'.$img_base64 . '">';

        }else{
            echo '++++++++fail';
            var_dump($res);
        }


    }

    /**
     * ???????????????Base64??????(?????????url)
     * @date 2017-02-20 19:41:22
     *
     * @param $img_file ????????????????????????
     *
     * @return string
     */
    function imgToBase64($img_file) {

        $img_base64 = '';
        if (file_exists($img_file)) {
            $app_img_file = $img_file; // ????????????
            $img_info = getimagesize($app_img_file); // ?????????????????????????????????

            //echo '<pre>' . print_r($img_info, true) . '</pre><br>';
            $fp = fopen($app_img_file, "r"); // ????????????????????????

            if ($fp) {
                $filesize = filesize($app_img_file);
                $content = fread($fp, $filesize);
                $file_content = chunk_split(base64_encode($content)); // base64??????
                switch ($img_info[2]) {           //??????????????????
                    case 1: $img_type = "gif";
                        break;
                    case 2: $img_type = "jpg";
                        break;
                    case 3: $img_type = "png";
                        break;
                }

                $img_base64 = 'data:image/' . $img_type . ';base64,' . $file_content;//???????????????base64??????

            }
            fclose($fp);
        }

        return $img_base64; //???????????????base64
    }
    /**
     * @function????????????access_token
     * @author Feifan.Chen <1057286925@qq.com>
     * @return mixed
     */
    public function getAccessToken(){
        $url = 'https://aip.baidubce.com/oauth/2.0/token';
        $post_data['grant_type']       = 'client_credentials';
        $post_data['client_id']      = 'kcfpjDyzGyaLNNj1QVjrUOGk';
        $post_data['client_secret'] = 'nsk5VxgRaOPbh1M6P7eRCDhNmRUm6amB';
        $res = $this->send_post($url,$post_data);
        $access_token = json_decode($res,1);
        $access_token = $access_token['access_token'];
        return $access_token;
    }


    /**
     * @function??????post??????
     * @param $url
     * @param $post_data
     * @author Feifan.Chen <1057286925@qq.com>
     * @return bool|string
     */
    function send_post($url, $post_data) {
        $postdata = http_build_query($post_data);
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'content' => $postdata,
                'timeout' => 15 * 60 // ?????????????????????:s???
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        return $result;
    }

    /**
     * @function????????????
     * @param string $url
     * @param string $param
     * @author Feifan.Chen <1057286925@qq.com>
     * @return bool|mixed
     */
    function request_post($url = '', $param = '')
    {
        if (empty($url) || empty($param)) {
            return false;
        }

        $postUrl = $url;
        $curlPost = $param;
        // ?????????curl
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $postUrl);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        // ?????????????????????????????????????????????
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        // post????????????
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
        // ??????curl
        $data = curl_exec($curl);
        curl_close($curl);

        return $data;
    }



}