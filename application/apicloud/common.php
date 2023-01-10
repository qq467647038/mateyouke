<?php
/*
 * @Descripttion: 
 * @Copyright: ©版权所有
 * @Link: www.s1107.com
 * @Contact: QQ:2487937004
 * @LastEditors: cbing
 * @LastEditTime: 2020-04-14 11:16:55
 */

/**
 * @function检测该会员是否是vip会员
 * @param int $user_id
 * @author Feifan.Chen <1057286925@qq.com>
 * @return int|mixed
 */
function checkVIP($user_id = 0){
    $is_vip = 0;
    if ($user_id){
        $is_vip = \think\Db::name('member')->where('id',$user_id)->value('is_vip');
    }
    return $is_vip;
}

    /**
 * 模拟 http 请求
 * @param  String $url  请求网址
 * @param  Array  $data 数据
 */
function https_request($url, $data = null){
    // curl 初始化
    $curl = curl_init();

    // curl 设置
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

    // 判断 $data get  or post
    if ( !empty($data) ) {
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    // 执行
    $res = curl_exec($curl);
    curl_close($curl);
    return $res;
}


function time_ago($posttime){
    //当前时间的时间戳
    $nowtimes = strtotime(date('Y-m-d H:i:s'),time());
    //之前时间参数的时间戳
    $posttimes = strtotime($posttime);
    //相差时间戳
    $counttime = $nowtimes - $posttimes;
    //进行时间转换
    if($counttime<=10){
        return '刚刚';
    }else if($counttime>10 && $counttime<=30){
        return '刚才';
    }else if($counttime>30 && $counttime<=60){
        return '刚一会';
    }else if($counttime>60 && $counttime<=120){
        return '1分钟前';
    }else if($counttime>120 && $counttime<=180){
        return '2分钟前';
    }else if($counttime>180 && $counttime<3600){
        return intval(($counttime/60)).'分钟前';
    }else if($counttime>=3600 && $counttime<3600*24){
        return intval(($counttime/3600)).'小时前';
    }else if($counttime>=3600*24 && $counttime<3600*24*2){
        return '昨天';
    }else if($counttime>=3600*24*2 && $counttime<3600*24*3){
        return '前天';
    }else if($counttime>=3600*24*3 && $counttime<=3600*24*20){
        return intval(($counttime/(3600*24))).'天前';
    }else{
        return $posttime;
    }
}


/*
Utf-8、gb2312都支持的汉字截取函数
cut_str(字符串, 截取长度, 开始长度, 编码);
编码默认为 utf-8
开始长度默认为 0
*/
function cut_str($str,$len,$suffix="..."){
    if(function_exists('mb_substr')){
        if(strlen($str) > $len){
            $str= mb_substr($str,0,$len,'utf-8').$suffix;
        }
        return $str;
    }else{
        if(strlen($str) > $len){
            $str= substr($str,0,$len,'utf-8').$suffix;
        }
        return $str;
    }
}


/**
 * @func 获取当前域名地址和https
 */
function domain($domianname){
//    $type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
//    if(empty($type)){
//        $type = "https://";
//    }
//    $domain = $type.$_SERVER['SERVER_NAME'].'/';
    return $domianname;
}

/**
 * 获取唯一房间号
 */
function getRefereeId(){
    $code = rand(10000, 99999999);
    $userinfor = db('alive')->where(['room'=>$code])->find();
    if(!empty($userinfor)){
        return getRefereeId(); //存在  就再运行
    }else{
        return $code;
    }
}

// 给图片增加域名
function img_add_protocal($content, $protocal){
    $pattern = '/<img.*?src="(.*?)".*?\/?>/i';
    preg_match_all($pattern,$content,$match);
    if(count($match[1]) > 0){
        foreach ($match[1] as $k=>$v){
            $index = strpos($v, 'http');

            if($index === false){
                $content = str_replace($v, $protocal.$v, $content);

                $match[1][$k] = $protocal.$v;
            }
        }
    }

    $content = str_replace('\\', '/', $content);
    $content = str_replace('<p><img', '<p style="text-align: center;"><img', $content);
    $content = str_replace('<img', '<img style="max-width:100%;!important;"', $content);
    $content = str_replace('<article>', '', $content);
    $content = str_replace('</article>', '', $content);
    $content = str_replace('section', 'div', $content);

    return [$content, isset($match[1]) ? $match[1] : []];
}


/**
 * CURL请求
 * @param $url 请求url地址
 * @param $method 请求方法 get post
 * @param null $postfields post数据数组
 * @param array $headers 请求header信息
 * @param bool|false $debug  调试开启 默认false
 * @return mixed
 */
function httpRequest($url, $method="GET", $postfields = null, $headers = array(), $debug = false) {
    $method = strtoupper($method);
    $ci = curl_init();
    /* Curl settings */
    curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    curl_setopt($ci, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/72.0.3626.121 Safari/537.36");
    curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 60); /* 在发起连接前等待的时间，如果设置为0，则无限等待 */
    curl_setopt($ci, CURLOPT_TIMEOUT, 30); /* 设置cURL允许执行的最长秒数 */
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
    switch ($method) {
        case "POST":
            curl_setopt($ci, CURLOPT_POST, true);
            if (!empty($postfields)) {
                $tmpdatastr = is_array($postfields) ? http_build_query($postfields) : $postfields;
                curl_setopt($ci, CURLOPT_POSTFIELDS, $tmpdatastr);
            }
            break;
        default:
            curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method); /* //设置请求方式 */
            break;
    }
    $ssl = preg_match('/^https:\/\//i',$url) ? TRUE : FALSE;
    curl_setopt($ci, CURLOPT_URL, $url);
    if($ssl){
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, FALSE); // 不从证书中检查SSL加密算法是否存在
    }
    //curl_setopt($ci, CURLOPT_HEADER, true); /*启用时会将头文件的信息作为数据流输出*/
    curl_setopt($ci, CURLOPT_FOLLOWLOCATION, 0);
    curl_setopt($ci, CURLOPT_MAXREDIRS, 2);/*指定最多的HTTP重定向的数量，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的*/
    curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ci, CURLINFO_HEADER_OUT, true);
    /*curl_setopt($ci, CURLOPT_COOKIE, $Cookiestr); * *COOKIE带过去** */
    if(strstr($url,'m.kuaidi100.com')){
        curl_setopt($ci, CURLOPT_REFERER, "https://m.kuaidi100.com");

        curl_setopt($ci,CURLOPT_COOKIE,'Hm_lvt_22ea01af58ba2be0fec7c11b25e88e6c=1556584482; WWWID=WWW2BC23FF9698A07DE7FEE7DE63417D89C; Hm_lpvt_22ea01af58ba2be0fec7c11b25e88e6c=1556584508');
    }
    $response = curl_exec($ci);
    $requestinfo = curl_getinfo($ci);
    $http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
    if ($debug) {
        echo "=====post data======\r\n";
        var_dump($postfields);
        echo "=====info===== \r\n";
        print_r($requestinfo);
        echo "=====response=====\r\n";
        print_r($response);
    }
    curl_close($ci);
    return $response;
    //return array($http_code, $response,$requestinfo);
}

//function img_add_protocal($content, $protocal){
//    if(strpos($content, 'http') === false){
//        if(strpos($content, '/public/') !== false){
//            $content = str_replace('/public/', $protocal.'public/', $content);
//        }elseif(strpos($content, '/uploads/') !== false){
//            $content = str_replace('/uploads/', $protocal.'/uploads/', $content);
//        }elseif(strpos($content, 'uploads/') !== false){
//            $content = str_replace('uploads/', $protocal.'uploads/', $content);
//        }elseif(strpos($content, '/upload/') !== false){
//            // 社区最新 - 热门  图片
//            $content = str_replace('//upload/', $protocal.'/upload/', $content);
//            $content = str_replace('/upload/', $protocal.'/upload/', $content);
//        }elseif(strpos($content, 'upload/') !== false){
//            $content = str_replace('upload/', $protocal.'upload/', $content);
//        }
//    }
//
//    $content = str_replace('\\', '/', $content);
//    $content = str_replace('<p><img', '<p style="text-align: center;"><img', $content);
//    $content = str_replace('<img', '<img style=max-width:100%', $content);
//
//    return $content;
//}





























