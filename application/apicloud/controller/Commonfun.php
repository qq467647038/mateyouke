<?php
namespace app\apicloud\controller;
use think\Controller;
use Qcloud\Cos\Client;

class Commonfun extends Common
{

    /**
     * @func 图片上传类
     * @param $file图片对象
     * @param $mkdirname 图片存放的目录
     * @param $numpic最多上传图片的数量
     * @return array|string 返回上传图片的路径
     */
    public function uploadspic($file,$mkdirname,$numpic){
        // dump($file);
        if(empty($file)){
            datamsg(400,'请上传图片');
        }
        if(is_array($file)){
            if(count($file) >= $numpic){
                datamsg(LOSE,'最多上传'.$numpic.'张图片');
            }
            $picarr=[];
            foreach($file as $key=>$value){
                $info = $file[$key]->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . $mkdirname);
                if($info){
                    $original['dz'] = '/uploads/'.$mkdirname.'/'.$info->getSaveName();
                    $original['wz'] = $this->webconfig['weburl'].'/uploads/'.$mkdirname.'/'.$info->getSaveName();
                    $picarr[]=$original;
                }else{
                    $picarr[]=0;
                }
            }
            return $picarr;
        }else{
            $info = $file->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . $mkdirname);
            // dump($info);die;
            if($info){
                $original['dz'] = 'uploads/'.$mkdirname.'/'.$info->getSaveName();
                $original['wz'] = $this->webconfig['weburl'].'/uploads/'.$mkdirname.'/'.$info->getSaveName();
                $picarr[]=$original;
            }else{
                datamsg(LOSE,'图片上传失败');
            }
            return $original;
        }
    }

    /**
     * 腾讯对象存储-文件上传
     * @datatime 2018/05/17 09:20
     * @author lgp
     */
    public function qcloudCosUpload($file,$mkdirname,$numpic){
        if(empty($file)){
            datamsg(400,'请上传图片');
        }

        $qCloudConfig = [
            'region' => $this->webconfig['cos_region'],
            //  'schema' => 'https',
            'credentials' => [
                'appId' => $this->webconfig['cos_appid'],
                'secretId' => $this->webconfig['cos_secretid'],
                'secretKey' => $this->webconfig['cos_secretkey'],
            ],
        ];

        // p($this->webconfig['cos_domain']);
        // p($this->webconfig['bucket_id']);die;
        $cosClient = new Client($qCloudConfig);
        if(is_array($file)){
            if(count($file) >= $numpic){
                datamsg(LOSE,'最多上传'.$numpic.'张图片');
            }
            $picarr=[];
            foreach($file as $key=>$value){


                $info = $file[$key]->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->getInfo();
                $key = $mkdirname."/".date("Y-m-d") . "/" .$info['name'];
                $data = array( 'Bucket' => $this->webconfig['bucket_id'], 'Key'  => $key, 'Body' => fopen($info['tmp_name'], 'rb') );
                //判断文件大小 大于5M就分块上传
                $result = $cosClient->Upload( $data['Bucket'] , $data['Key'] , $data['Body']  );

                if($result){
                    $original['dz'] = $key;
                    $original['wz'] = $this->webconfig['cos_domain'].'/'.$key;
                    $picarr[]=$original;
                }else{
                    $picarr[]=0;
                }

            }
            return $picarr;
        }else{

            try {
                $info = $file->validate(['size'=>8368576,'ext'=>'jpg,png,gif,jpeg'])->getInfo();
                $key = $mkdirname."/".date("Y-m-d") . "/" .$info['name'];
                $data = array( 'Bucket' =>  $this->webconfig['bucket_id'], 'Key'  => $key, 'Body' => fopen($info['tmp_name'], 'rb') );
                //判断文件大小 大于5M就分块上传
                $result = $cosClient->Upload( $data['Bucket'] , $data['Key'] , $data['Body']  );
                p('1234567');die;
                //上传成功，自己编码
                if( $result ){
                    $original['dz'] = $key;
                    $original['wz'] = $this->webconfig['cos_domain'].'/'.$key;
                    $picarr[]=$original;
                }else{
                    datamsg(LOSE,'图片上传失败');
                }
                return $original;
            } catch (\Exception $e) {
                datamsg(LOSE,'图片上传失败');
            }

        }




    }


    /**
     * 上传图片到腾讯云图片服务器
     */
    public function tUpfile ($file) {
        $cosClient = new Client(config("tengxunyun"));

        if ($file) {
            try {
                $result = $cosClient->putObject(
                    [
                        'Bucket' => "xiaoquhenhuo-1259494372",
                        'Key' => date("Y-m-d") . "/" . md5(microtime()) . '.jpg',
                        'Body' => fopen($file->getInfo()['tmp_name'], 'rb'),
                        "ACL" => "public-read-write",
                        // "ContentType" => "image/jpeg"
                    ]
                );
                return json(
                    [
                        "code" => 0,
                        "msg" => '上传成功',
                        "data" => [
                            "src" => str_replace("cos.ap-shanghai", "picbj", $result['ObjectURL']),
                            "title" => ""
                        ]
                    ]
                );
            } catch (\Exception $e) {
                return json(
                    [
                        "code" => 1,
                        "msg" => $e,
                        "data" => [
                            "src" => "",
                            "title" => ""
                        ]
                    ]
                );
            }
        }
    }
}

