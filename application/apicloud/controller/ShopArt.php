<?php
namespace app\apicloud\controller;
use think\Db;
use think\Controller;
use app\apicloud\model\Gongyong as GongyongMx;

class ShopArt extends Common {



//	// 课程视频二维码分享
//	public function shareImg(){
//        $course_id = input('course_id/d', 0);
//        $width = input('width/d', 750);
//        $height = input('height/d', 1200);
//        $arr['imgurl'] = $this->getShareImage($course_id, $width, $height);
//
//		datamsg(WIN, '获取成功', $arr);
//    }
//
//    private function getNetworkImgType($url){
//
//
//        $ch = curl_init(); //初始化curl
//
//        curl_setopt($ch, CURLOPT_URL, $url); //设置需要获取的URL
//        curl_setopt($ch, CURLOPT_NOBODY, 1);
//        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);//设置超时
//        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
//        curl_setopt($ch, CURLOPT_ENCODING, "");
//        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1); //支持https
//        curl_exec($ch);//执行curl会话
//        $http_code = curl_getinfo($ch);//获取curl连接资源句柄信息
//        curl_close($ch);//关闭资源连接
//        if ($http_code['http_code'] == 200) {
//            $theImgType = explode('/',$http_code['content_type']);
//            if($theImgType[0] == 'image'){
//                return $theImgType[1];
//            }else{
//                return false;
//            }
//        }else{
//            return false;
//        }
//    }
//
//    private function createImageFromFile($file){
//        if(preg_match('/http(s)?:\/\//',$file)){
//            $fileSuffix = $this->getNetworkImgType($file);
//        }else{
//            $fileSuffix = pathinfo($file, PATHINFO_EXTENSION);
//        }
//
//        if(!$fileSuffix) return false;
//        switch ($fileSuffix){
//            case 'jpeg':
//                $theImage = @imagecreatefromjpeg($file);
//                // dump($theImage);die;
//                break;
//            case 'jpg':
//                $theImage = @imagecreatefromjpeg($file);
//                break;
//            case 'png':
//                $theImage = @imagecreatefrompng($file);
//                break;
//            case 'gif':
//                $theImage = @imagecreatefromgif($file);
//                break;
//            default:
//                $theImage = @imagecreatefromstring(file_get_contents($file));
//                break;
//        }
//        return $theImage;
//    }
//
//    //生成分享图
//    public function getShareImage($course_id, $width, $height){
//        if(empty($course_id)) return '';
//        $course  = Db::name('course_course')->where('course_id', $course_id)->find();
//        $teacher = Db::name('course_teacher')->where('teacher_id', $course['teacher_id'])->value('title');
//        if(preg_match('/http/', $course['imgurl'])){
//            $imgurl = $course['imgurl'];
//        }else{
//            $imgurl = $_SERVER["DOCUMENT_ROOT"] .'/'. $course['imgurl'];
//        }
//
//        list($g_w, $gs_h)  = getimagesize($imgurl);
//        $g_h = 700;
//
//        $canvas_width  = $width;
//        $canvas_heigth = 600;//$height;
//        $im = imagecreatetruecolor($canvas_width, $canvas_heigth);
//
//        //填充画布背景色
//        $color = imagecolorallocate($im, 255, 255, 255);//白色
//        imagefill($im, 0, 0, $color);//填充
//
//        //字体文件
//        $font_file      = $_SERVER["DOCUMENT_ROOT"]."/public/css/msyhl.ttc";
//        $font_file_bold = $_SERVER["DOCUMENT_ROOT"]."/public/css/bold.ttf";
//        $t2             = $_SERVER["DOCUMENT_ROOT"]."/public/css/t2.ttf";
//
//        //设定字体的颜色
//        $font_color_2     = ImageColorAllocate ($im, 0, 0, 0);
//        $font_color_red   = ImageColorAllocate ($im, 217, 45, 32);
//        $font_color_3     = ImageColorAllocate ($im, 133, 133, 133);
//
//        //画课程图片
//        $courseImg          = $this->createImageFromFile($imgurl);
//        $imgw = $canvas_width;
//        $per = round($width / $g_w, 3);
//        $n_h = $gs_h * $per;
//        imagecopyresampled($im, $courseImg, 0, 0, 0, 0, $imgw, $n_h, $g_w, $gs_h);
//
//        $cus_height = 350;
//
//        $bottomim = imagecreatetruecolor($canvas_width, 300);
//        $bottomcolor = imagecolorallocate($bottomim, 255, 255, 255);//白色
//        imagefill($bottomim, 0, 0, $bottomcolor);//填充
//        imagecopyresampled($im, $bottomim, 0, 350, 0, 0, $canvas_width, 300, $canvas_width, 350);
//
//        $eyeimgurl = $_SERVER["DOCUMENT_ROOT"] . '/template/mobile/new2/static/course/images/eye.png';
//
//        list($g_e_w, $gs_e_h)  = getimagesize($eyeimgurl);
//        $eyeimg          = $this->createImageFromFile($eyeimgurl);
//        imagecopyresampled($im, $eyeimg, 20, $cus_height - 38, 0, 0, 130, 38, $g_e_w, $gs_e_h);
//
//        $sitelogo = $_SERVER["DOCUMENT_ROOT"] . '/template/mobile/new2/static/course/images/06.png';
//        list($g_l_w, $gs_l_h)  = getimagesize($sitelogo);
//        $logoimg          = $this->createImageFromFile($sitelogo);
//        imagecopyresampled($im, $logoimg, 20, 20, 0, 0, $g_l_w, $gs_l_h, $g_l_w, $gs_l_h);
//
//        //获取用户头像
//        $user_info      = Db::name('member')->where(['id'=>$this->user['user_id']])->find();
//
//        if(empty($user_info["headimgurl"])){
//            $head_pic = $_SERVER["DOCUMENT_ROOT"] . '/template/mobile/new2/static/images/user68.jpg';
//        }else{
//            $ch = curl_init();
//            curl_setopt($ch,CURLOPT_URL, $user_info["headimgurl"]);
//            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//            $file_content = curl_exec($ch);
//            curl_close($ch);
//
//            if ($file_content) {
//                $head_pic_path = $_SERVER["DOCUMENT_ROOT"]."/".time().rand(1, 10000).'.png';
//                file_put_contents($head_pic_path, $file_content);
//                $head_pic = $head_pic_path;
//            }else{
//                $head_pic = $_SERVER["DOCUMENT_ROOT"] . '/template/mobile/new2/static/images/user68.jpg';
//            }
//        }
//
//        //画用户头像
//        $logo = @imagecreatefromstring(file_get_contents($head_pic));
//        $wh  = getimagesize($head_pic);
//        $w   = $wh[0];
//        $h   = $wh[1];
//        $w   = min($w, $h);
//        $h   = $w;
//        $img = imagecreatetruecolor($w, $h);
//        imagesavealpha($img, true);
//        $bg = imagecolorallocatealpha($img, 255, 255, 255, 127);
//        imagefill($img, 0, 0, $bg);
//        $r   = $w / 2;
//        $y_x = $r;
//        $y_y = $r;
//        for ($x = 0; $x < $w; $x++) {
//            for ($y = 0; $y < $h; $y++) {
//                $rgbColor = imagecolorat($logo, $x, $y);
//                if (((($x - $r) * ($x - $r) + ($y - $r) * ($y - $r)) < ($r * $r))) {
//                    imagesetpixel($img, $x, $y, $rgbColor);
//                }
//            }
//        }
//
//        imagecopyresampled($im, $img, 60 , $cus_height - 30, 0, 0, 50, 50, $w, $h);
//        if($head_pic && !empty($user_info["headimgurl"])){
//            unlink($head_pic);
//        }
//        //描述
//        //imagettftext($im, 14,0, 60, $cus_height + 70, $font_color_2 ,$font_file, $user_info["nickname"]);
//
//        imagettftext($im, 14, 0, 10, $cus_height + 90, $font_color_2 ,$font_file, $this->mg_cn_substr($course["title"], 30) . '...');
//
//        imagettftext($im, 12, 0, 10, $cus_height + 130, $font_color_3 ,$font_file, $this->mg_cn_substr($user_info["user_name"], 10) . ' 邀请您一起来学习');
//
//        imagettftext($im, 14, 0, 10, $cus_height + 180, $font_color_3 ,$font_file, '来自 [ 孝笑学堂 ]');
//        //imagettftext($im, 14,0, 60, 420, $font_color_2 ,$font_file, $teacher);
//
//        //二维码
//        vendor('phpqrcode.phpqrcode');
//        // $value = "http://".$_SERVER["HTTP_HOST"]."/mobile/course/course_detail/course_id/" . $course["course_id"] . "/user_id/" . $user_info["id"];
//        $value = "/Portal/Course-Details/Course-Details/course_id/" . $course["course_id"] . "/user_id/" . $user_info["id"];
//
//        $errorCorrectionLevel = 'L';          //容错级别
//        $matrixPointSize = 5;                 //生成图片大小
//        //生成二维码图片
//        $qr_code_path = $_SERVER["DOCUMENT_ROOT"].'/upload/qr_code/';
//        $filename     =$qr_code_path .time().rand(1, 10000).'.png';
//        \QRcode::png($value,$filename , $errorCorrectionLevel, $matrixPointSize, 2);
//        $QR = $filename;        //已经生成的原始二维码图片文件
//        $QR = imagecreatefromstring(file_get_contents($QR));
//
//        $wh  = getimagesize($filename);
//        imagecopyresampled($im, $QR, $canvas_width - 160, $cus_height + 50, 0, 0, 150, 150, $wh[0], $wh[1]);
//        imagettftext($im, 12, 0, $canvas_width - 140, $cus_height + 210, $font_color_2 ,$font_file, '扫码查看此课程');
//        unlink($filename);
//
//        $image_data_base64 = "";
//        ob_start();
//        imagepng($im);
//        $image_data = ob_get_contents ();
//        ob_end_clean ();
//        $image_data_base64 = "data:image/png;base64,". base64_encode ($image_data);
//
//        imagedestroy($im);
//        imagedestroy($bottomim);
//        imagedestroy($courseImg);
//        imagedestroy($QR);
//        return $image_data_base64;
//    }
//
//    private function mg_cn_substr($str,$len,$start = 0){
//        $q_str = '';
//        $q_strlen = ($start + $len)>strlen($str) ? strlen($str) : ($start + $len);
//
//        //如果start不为起始位置，若起始位置为乱码就按照UTF-8编码获取新start
//        if($start and json_encode(substr($str,$start,1)) === false){
//            for($a=0;$a<3;$a++){
//                $new_start = $start + $a;
//                $m_str = substr($str,$new_start,3);
//                if(json_encode($m_str) !== false) {
//                    $start = $new_start;
//                    break;
//                }
//            }
//        }
//
//        //切取内容
//        for($i=$start;$i<$q_strlen;$i++){
//            //ord()函数取得substr()的第一个字符的ASCII码，如果大于0xa0的话则是中文字符
//            if(ord(substr($str,$i,1))>0xa0){
//                $q_str .= substr($str,$i,3);
//                $i+=2;
//            }else{
//                $q_str .= substr($str,$i,1);
//            }
//        }
//        return $q_str;
//    }

	


}