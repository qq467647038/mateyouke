<?php
namespace app\util;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/12/7
 * Time: 9:45
 */

require ROOT_DIR.'/../vendor/obs/obs-autoloader.php';
use Obs\ObsClient;
class obs{
    private $obsClient;
    private $bucket = 'cxy365-file';

    public function __construct()
    {
        // 创建ObsClient实例
        $this->obsClient = new ObsClient([
            'key' => 'IXM7QWHMWJP399NE4WEL',
            'secret' => 'dhWGiAtEcc2TIgqBz8oeiqOh2ZovExesNPv73uiW',
            'endpoint' => 'obs.cn-south-1.myhuaweicloud.com:443'
        ]);
    }

    // 文件上传
    public function putObject($filename, $localfile){
        $resp = $this->obsClient->putObject([
            'Bucket' => $this->bucket,
            'Key' => $filename,
            'SourceFile' => $localfile  // localfile为待上传的本地文件路径，需要指定到具体的文件名
        ]);

        return $resp;
//        printf("RequestId:%s\n",$resp['RequestId']);
    }

    // 初始化分段上传任务
    public function initiateMultipartUpload($storage_url){
        $return = [];
        $resp = $this->obsClient->initiateMultipartUpload([
            'Bucket' => $this->bucket,
            'Key' => $storage_url
        ]);

        $return['RequestId'] = $resp['RequestId'];
        $return['UploadId'] = $resp['UploadId'];
        return $return;
//        printf("RequestId:%s\n",$resp['RequestId']);
//        printf("UploadId:%s\n",$resp['UploadId']);
    }

    // 逐个或并行上传段
    public function uploadPart($storage_url, $post){
        $resp = $this->obsClient->uploadPart([
            'Bucket' => $this->bucket,
            'Key' => $storage_url,
            // 设置分段号，范围是1~10000
            'PartNumber' => $post['PartNumber'],
            // 设置Upload ID
            'UploadId' => $post['UploadId'],
            // 设置将要上传的大文件,localfile为上传的本地文件路径，需要指定到具体的文件名
            'SourceFile' => $post['tmp_name'],
            // 设置分段大小
            'PartSize' => $post['PartSize'],
            // 设置分段的起始偏移大小
            'Offset' => $post['Offset']
        ]);

        $return['RequestId'] = $resp['RequestId'];
        $return['ETag'] = $resp['ETag'];
        return $return;
//        return $resp;
//        printf("RequestId:%s\n",$resp['RequestId']);
//        printf("ETag:%s\n",$resp['ETag']);
    }

    // 合并段
    public function completeMultipartUpload($storage_url, $post){
        $resp = $this->obsClient->completeMultipartUpload([
            'Bucket' => $this->bucket,
            'Key' => $storage_url,
            // 设置Upload ID
            'UploadId' => $post['UploadId'],
            'Parts' => $post['Parts']
        ]);

        $return['url'] = 'https://cxy365-file.obs.cn-south-1.myhuaweicloud.com/'.$resp['Key'];
        $return['errcode'] = $resp['HttpStatusCode'];
        return $return;
//        return $resp;
//        printf("RequestId:%s\n",$resp['RequestId']);
    }

    // 取消分段上传任务
    public function abortMultipartUpload($storage_url, $post){
        $resp = $this->obsClient->abortMultipartUpload([
            'Bucket' => $this->bucket,
            'Key' => $storage_url,
            // 设置Upload ID
            'UploadId' => $post['UploadId']
        ]);

        return $resp;
//        printf("RequestId:%s\n",$resp['RequestId']);
    }

    // 列举已上传的段
    public function listParts($storage_url, $post){
        $resp = $this->obsClient->listParts ( [
            'Bucket' => $this->bucket,
            'Key' => $storage_url,
            // 设置Upload ID
            'UploadId' => $post['UploadId']
        ] );

        return $resp['Parts'];
//        printf ( "RequestId:%s\n", $resp ['RequestId'] );
//        foreach ( $resp ['Parts'] as $index => $part ) {
//            printf ( "Parts[%d]\n", $index + 1 );
//            // 分段号，上传时候指定
//            printf ( "PartNumber:%s\n", $part ['PartNumber'] );
//            // 段的最后上传时间
//            printf ( "LastModified:%s\n", $part ['LastModified'] );
//            // 分段的ETag值
//            printf ( "ETag:%s\n", $part ['ETag'] );
//            // 段数据大小
//            printf ( "Size:%s\n", $part ['Size'] );
//        }
    }

    // 列举分段上传任务
    public function listMultipartUploads(){
        $resp = $this->obsClient->listMultipartUploads ( [
            'Bucket' => $this->bucket,
        ] );

        return $resp;
//        printf ( "RequestId:%s\n", $resp ['RequestId'] );
//        foreach ( $resp ['Uploads'] as $index => $upload ) {
//            printf ( "Uploads[%d]\n", $index + 1 );
//            printf ( "Key:%s\n", $upload ['Key'] );
//            printf ( "UploadId:%s\n", $upload ['UploadId'] );
//            printf ( "Initiated:%s\n", $upload ['Initiated'] );
//            printf ( "Owner[ID]:%s\n", $upload ['Owner'] ['ID'] );
//            printf ( "StorageClass:%s\n", $upload ['StorageClass'] );
//        }
    }


































}
