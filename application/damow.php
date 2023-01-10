<?php
/**
 * Created by PhpStorm.
 * User: Damow
 * Date: 2018/10/8 0008
 * Time: 15:21
 */

define('PAGE', 12);
define('SUCCESS', 'success');
define('UPDATE_ORDER', '修改订单');

define('WIN', 200);
define('LOSE',400);
/**
 * 请求接口返回内容
 * @param  string $url [请求的URL地址]
 * @param  string $params [请求的参数]
 * @param  int $ipost [是否采用POST形式]
 * @return  string
 */
function juhecurl($url,$params=false,$ispost=0){
    $httpInfo = array();
    $ch = curl_init();

    curl_setopt( $ch, CURLOPT_HTTP_VERSION , CURL_HTTP_VERSION_1_1 );
    curl_setopt( $ch, CURLOPT_USERAGENT , 'JuheData' );
    curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT , 60 );
    curl_setopt( $ch, CURLOPT_TIMEOUT , 60);
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER , true );
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    if( $ispost )
    {
        curl_setopt( $ch , CURLOPT_POST , true );
        curl_setopt( $ch , CURLOPT_POSTFIELDS , $params );
        curl_setopt( $ch , CURLOPT_URL , $url );
    }
    else
    {
        if($params){
            curl_setopt( $ch , CURLOPT_URL , $url.'?'.$params );
        }else{
            curl_setopt( $ch , CURLOPT_URL , $url);
        }
    }
    $response = curl_exec( $ch );
    if ($response === FALSE) {
        //echo "cURL Error: " . curl_error($ch);
        return false;
    }
    $httpCode = curl_getinfo( $ch , CURLINFO_HTTP_CODE );
    $httpInfo = array_merge( $httpInfo , curl_getinfo( $ch ) );
    curl_close( $ch );
    return $response;
}

function iunserializer($value) {
    if (empty($value)) {
        return '';
    }
    if (!is_serialized($value)) {
        return $value;
    }
    $result = unserialize($value);
    if ($result === false) {
        $temp = preg_replace_callback('!s:(\d+):"(.*?)";!s', function ($matchs){
            return 's:'.strlen($matchs[2]).':"'.$matchs[2].'";';
        }, $value);
        return unserialize($temp);
    }
    return $result;
}
/**
 * 获取每月时间
 * @param $date
 * @return array
 */
function getMonth(){
    $firstday = date("Y-m-d",strtotime('now'));
    $lastday = date("Y-m-d",strtotime("$firstday +1 month"));
    return array($firstday,$lastday);
}
/**
 * 数组分页函数  核心函数  array_slice
 * 用此函数之前要先将数据库里面的所有数据按一定的顺序查询出来存入数组中
 * $count   每页多少条数据
 * $page   当前第几页
 * $array   查询出来的所有数组
 * order 0 - 不变     1- 反序
 */

function page_array($count,$page,$array,$order){
    global $countpage; #定全局变量
    $page=(empty($page))?'1':$page; #判断当前页面是否为空 如果为空就表示为第一页面
    $start=($page-1)*$count; #计算每次分页的开始位置
    if($order==1){
        $array=array_reverse($array);
    }
    $totals=count($array);
    $countpage=ceil($totals/$count); #计算总页面数
    $pagedata=array();

    $pagedata=array_slice($array,$start,$count);
    return $pagedata;  #返回查询数据
}
/**
 * base64图片上传
 * @param $base64
 * @param string $path
 * @return bool|string
 */

function get_base64_img($base64,$path = 'upload/cards/'){
    if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64, $result)){
        mk_dirs($path.date('Ymd',time()));
        $path = $path.date('Ymd',time())."/";
        $type = $result[2];
        $co=rand('1','20');
        $new_file = $path.md5(time().$co).".{$type}";
        if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64)))){
            return "/".$new_file;
        }else{
            return  false;
        }
    }
}
/**
 * 图片转换
 * @return [type] [description]
 */
function cover_img($img='')
{
    $http = "https://".$_SERVER['SERVER_NAME'];
    $img !=''?$data = $http.$img:$data = $http.'/upload/nopic.png';
    return $data;
}

function cover_img2($img='')
{
    $http = "https://".$_SERVER['SERVER_NAME'];
    $img !=''?$data = $http.$img:$data = '';
    return $data;
}

/**
 * 只显示后4位
 * @param $str
 * @return string
 */
function func($str){
    $len = strlen($str);
    if($len <= 4){
        return $str;
    }
    return str_repeat('*', $len - 4).substr($str, -4);
}

/**
 * 获取json类型
 * @param  [type] $result [json状态]
 * @return [type]         [返回json类型]
 */

function result_type($result)
{
    $res = $result;
    switch ($result) {
        case 'arr':
            $res = array();
            break;
        case 'obj':
            $res = (object)array();
            break;
        case 'str':
            $res = "";
            break;
        case null:
            $res = (object)array();
            break;
    }
    return $res;
}


/**
 * 返回json数据
 */
if (!function_exists('datamsg')) {
    function datamsg($code, $msg, $result = '')
    {//return returnJson($code, $msg, $result);
        $data['status'] = $code;
        $data['mess'] = $msg;
        is_object($result)?$result = $result->toArray():'';
        if (is_array($result)) {
            foreach ($result as $key => $value) {
                if ($value===null) {
                    $result[$key] ='';
                }
            }
        }
        $data['data'] = result_type($result);

        echo json_encode($data);die;
    }
}

/**
 * 获取请求头数据
 *
 * @return array
 */
 function getAllHeader()
{
    // 忽略获取的header数据。这个函数后面会用到。主要是起过滤作用
    $ignore = array('host','accept','content-length','content-type');
    $headers = array();
    //这里大家有兴趣的话，可以打印一下。会出来很多的header头信息。咱们想要的部分，都是‘http_'开头的。所以下面会进行过滤输出。
    /*    var_dump($_SERVER);
        exit;*/

    foreach($_SERVER as $key=>$value){
        if(substr($key, 0, 5)==='HTTP_'){
            //这里取到的都是'http_'开头的数据。
            //前去开头的前5位
            $key = substr($key, 5);
            //把$key中的'_'下划线都替换为空字符串
            $key = str_replace('_', ' ', $key);
            //再把$key中的空字符串替换成‘-’
            $key = str_replace(' ', '-', $key);
            //把$key中的所有字符转换为小写
            $key = strtolower($key);

            //这里主要是过滤上面写的$ignore数组中的数据
            if(!in_array($key, $ignore)){
                $headers[$key] = $value;
            }
        }
    }
    //输出获取到的header
    return $headers;

}


/**
 * 重组数组的结构(二维数组)
 *
 * @param $arr
 * @param null $find_index
 * @param null $value_index
 * @param null $operation
 * @return mixed|null|number
 */
function array_index_value($arr, $find_index = null, $value_index = null, $operation = null)
{
    if(empty($arr)){
        return array();
    }
    $ret = null;
    $names = @array_reduce($arr, create_function('$v,$w', '$v['.($find_index ? '$w['.$find_index.']' : '').']='.($value_index ? '$w['.$value_index.']' : '$w').';return $v;'));

    switch($operation){
        case 'sum':
            $ret = array_sum($names);
            break;
        default:
            $ret = $names;
            break;
    }
    return $ret;
}

/**
 * 生成目录结构
 * @param string $path 插件完整路径
 * @param array $list 目录列表
 */
function mk_dirs($a1, $mode = 0777)
{
    if (is_dir($a1) || @mkdir($a1, $mode)) return TRUE;
    if (!mkdir(dirname($a1), $mode)) return FALSE;
    return @mkdir($a1, $mode);
}


