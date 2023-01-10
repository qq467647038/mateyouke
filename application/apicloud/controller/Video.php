<?php
namespace app\apicloud\controller;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/12/15
 * Time: 15:59
 */

use app\util\obs;
class Video extends Common{
    public function index(){
        return $this->fetch();
    }

    public function test(){
        return $this->fetch('webview');
    }

    public function randNum($num = 8){
        $a ='sdjkafhwuyertwodncxzbviugfdsuyrprtjdsf';
        $length = strlen($a);
        $randNum = '';

        for($i=0; $i<$num; $i++){
            $randNum .= substr($a, rand(0, $length-1), 1);
        }

        return $randNum;
    }

    // 初始化分段上传任务
    public function initiateMultipartUpload(){
        $obs = new obs();
        $randNum = $this->randNum();
        $dirname = 'video/course/'. date('Y') . '/'. date('m-d').'/';
        $path = $dirname.uniqid().$randNum.'.mp4';
        $res = $obs->initiateMultipartUpload($path);

        $res['path'] = $path;
        echo json_encode($res);
//        printf("RequestId:%s\n",$resp['RequestId']);
//        printf("UploadId:%s\n",$resp['UploadId']);
    }

    // 逐个或并行上传段
    public function uploadPart(){
        $post = input('post.');
        $post['tmp_name'] = $_FILES['video']['tmp_name'];

        $obs = new obs();
        $res = $obs->uploadPart($post['path'], $post);

        echo json_encode($res);
//        printf("RequestId:%s\n",$resp['RequestId']);
//        printf("ETag:%s\n",$resp['ETag']);
    }

    // 合并段
    public function completeMultipartUpload(){
        $post = input('post.');
        // $post['tmp_name'] = $_FILES['video']['tmp_name'];

        $obs = new obs();
        // 获取已上传的分段
        $post['Parts'] = $obs->listParts($post['path'], $post);

        $res = $obs->completeMultipartUpload($post['path'], $post);

        echo json_encode($res);
    }

    // 取消分段上传任务
    public function abortMultipartUpload(){
        $post = input('post.');

        $obs = new obs();

        $obs->abortMultipartUpload($post['path'], $post);

        datamsg(WIN, '取消成功');
    }
}