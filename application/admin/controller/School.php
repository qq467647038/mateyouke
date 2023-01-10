<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\CourseTeacher as CateNewMx;
use app\admin\model\CourseCourse as CourseModel;
use app\admin\model\CourseVideo as VideoModel;
use app\admin\model\CourseOrder as OrderModel;
use app\admin\model\CourseFeed as FeedModel;

class School extends Common{
    /**
     * @return mixed
     * @throws \think\exception\DbException
     * @info 学堂 - 老师列表
     */
    public function teacherlist(){
        $list = array();
        $p = input('p/d', 1);
        $size = input('size/d', 20);
        $where = array();
        $keywords = trim(input('keywords'));
        $keywords && $where['title'] = array('like', '%' . $keywords . '%');
        $keywords && $this->assign('keywords', $keywords);
        $list = Db::name('course_teacher')->where($where)->order('teacher_id desc')->paginate($size)->each(function ($item){
            $item['nickname'] = Db::name('member')->where('id', $item['user_id'])->value('user_name');

            return $item;
        });

        $page = $list->render();
        $this->assign('keywords',$keywords);// 赋值数据集
        $this->assign('list',$list);// 赋值数据集
        $this->assign('page',$page);// 赋值分页输出
        return $this->fetch();
    }

    public function delete(){
        $input = input('param.');

//        $courses = Db::name('course_course')->where('teacher_id', $input['id'])->select();
//        if($courses){
//            foreach ($courses as $key => $value) {
//                Db::name('course_video')->where('course_id', $value['course_id'])->where('teacher_id', $input['id'])->delete();
//                Db::name('course_card')->where('course_id', $value['course_id'])->delete();
//                Db::name('course_order')->where('course_id', $value['course_id'])->delete();
//                Db::name('course_course')->where('course_id', $value['course_id'])->delete();
//
//                Db::name('course_study')->where('course_id', $value['course_id'])->delete();
//            }
//        }


        $r = Db::name('course_teacher')->where('teacher_id', $input['id'])->delete();

        if($r){
            $value = array('status'=>1,'mess'=>'删除成功');
        }else{
            $value = array('status'=>0,'mess'=>'删除失败');
        }

        return json($value);
    }

    public function edit_teacher(){
        if(request()->isAjax()){
            $data = input('post.');
            $admin_id = session('admin_id');
            $result = $this->validate($data,'School.teacher');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $ars = Db::name('course_teacher')->where('teacher_id',$data['teacher_id'])->find();
                if($ars){
                    if(!empty($data['pic_id'])){
                        $zssjpics = Db::name('huamu_zspic_art')->where('id',$data['pic_id'])->where('admin_id',$admin_id)->find();
                        if($zssjpics && $zssjpics['img_url']){
                            $data['imgurl'] = $zssjpics['img_url'];
                        }else{
                            $data['imgurl'] = $ars['imgurl'];
                        }
                    }else{
                        $data['imgurl'] = $ars['imgurl'];
                    }

                    if(!empty($data['addtime'])){
                        $data['addtime'] = strtotime($data['addtime']);
                    }else{
                        $data['addtime'] = time();
                    }

                    $news = new CateNewMx();
                    $count = $news->allowField(true)->save($data,array('teacher_id'=>$data['teacher_id']));
                    if($count !== false){
                        if(!empty($zssjpics) && $zssjpics['img_url']){
                            Db::name('huamu_zspic_art')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                            if($ars['imgurl'] && file_exists('./'.$ars['imgurl'])){
                                @unlink('./'.$ars['imgurl']);
                            }
                        }
                        $value = array('status'=>1,'mess'=>'编辑成功');
                    }else{
                        $value = array('status'=>0,'mess'=>'编辑失败');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'找不到相关信息，编辑失败');
                }
            }
            return json($value);
        }else{
            $this->assign('pnum', input('page') ?? 1);
            $teacher_id = input('teacher_id/d', 0);
            $data = Db::name('course_teacher')->where('teacher_id', $teacher_id)->find();
            $this->assign('info', $data);
            return $this->fetch();
        }

    }

    public function addTeacher(){
        if(request()->isAjax()){
            $data = input('post.');
            $admin_id = session('admin_id');
            $result = $this->validate($data,'School.teacher');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $data['addtime'] = time();
                if(!empty($data['pic_id'])){
                    $zssjpics = Db::name('huamu_zspic_art')->where('id',$data['pic_id'])->where('admin_id',$admin_id)->find();
                    if($zssjpics && $zssjpics['img_url']){
                        $data['imgurl'] = $zssjpics['img_url'];
                    }
                }
                $cate = new CateNewMx();
                $cate->data($data);

                $lastId = $cate->allowField(true)->save();
                if($lastId){
                    if(isset($zssjpics) && $zssjpics['img_url']){
                        Db::name('huamu_zspic_art')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                    }
                    ys_admin_logs('新增课堂老师','course_teacher',$cate->teacher_id);
                    $value = array('status'=>1,'mess'=>'增加成功');
                }else{
                    $value = array('status'=>0,'mess'=>'增加失败');
                }
            }
            return json($value);
        }else{

            return $this->fetch();
        }
    }

    /**
     * @return int
     * @info 学堂 - 课程分类
     *
     */
    public function coursecate(){
        $p = input('p/d', 1);
        $size = input('size/d', 20);
        $list = Db::name('course_category')->order('catid desc')->paginate($size);
        $page = $list->render();
        $this->assign('list',$list);// 赋值数据集
        $this->assign('page',$page);// 赋值分页输出
        return $this->fetch();
    }

    public function add_category(){
        return $this->fetch();
    }

    public function save_category(){
        $input = input('post.');
        $catid = $input['catid'];
        $data = [
            'catname' => $input['catname']
        ];
        if($catid){
            $text = '更新';
        }else{
            $text = '增加';
        }

        $result = $this->validate($data,'School.category');
        if(true !== $result){
            $value = array('status'=>0,'mess'=>$result);
        }else{
            if($catid > 0){
                $r = Db::name('course_category')->where('catid', $catid)->update($data);
            }else{
                $r = Db::name('course_category')->insertGetId($data);
            }

            if($r !== false){
                $value = array('status'=>1,'mess'=>$text.'成功');
            }else{
                $value = array('status'=>0,'mess'=>$text.'失败');
            }
        }


        return json($value);
    }

    public function edit_category(){
        $catid = input('catid/d', 0);
        $data = Db::name('course_category')->where('catid', $catid)->find();
        $this->assign('info', $data);
        return $this->fetch('add_category');
    }

    /**
     * @return int
     * @info 学堂 - 课程列表
     *
     */
    public function course_list(){
        $list = array();
        $p = input('p/d', 1);
        $size = input('size/d', 20);
        $where = array();
        $keywords = trim(input('keywords'));
        $keywords && $where['content|title|description'] = array('like', '%' . $keywords . '%');
        $keywords && $this->assign('keywords', $keywords);
        $teacher_id = input('teacher_id');
        $teacher_id && $where['teacher_id'] = array('=', $teacher_id);
        $teacher_id && $this->assign('teacher_id', $teacher_id);

        $catid = input('catid');
        $catid && $where['catid'] = $catid;
        $catid && $this->assign('catid', $catid);

        $list = Db::name('course_course')->where($where)->order('addtime desc')->paginate($size, false, ['query' => request()->param(),])->each(function ($item){
            $item['nickname'] = Db::name('member')->where('id', $item['user_id'])->value('user_name');
            $item['t_title'] = Db::name('course_teacher')->where('teacher_id', $item['teacher_id'])->value('title');

//            $iscard = Db::name('course_card')->where('course_id', $item['course_id'])->count();
//            $item['iscard'] = $iscard;

            return $item;
        });

        $page = $list->render();//分页显示输出
        $this->assign('list',$list);// 赋值数据集
        $this->assign('page',$page);// 赋值分页输出

        $teachers = Db::name('course_teacher')->select();
        $this->assign('teachers', $teachers);

        $cats = Db::name('course_category')->select();
        $this->assign('cats', $cats);

        return $this->fetch();
    }

    public function save_course(){
        $input = input('post.');
        $admin_id = session('admin_id');
        $course_id = $input['course_id'];
        $teacher_id = $input['teacher_id'];
        $result = $this->validate($input,'School.course');
        if(true !== $result){
            $value = array('status'=>0,'mess'=>$result);
        }else{
            $user_id = Db::name('course_teacher')->where('teacher_id', $teacher_id)->value('user_id');
            $hasuser = Db::name('member')->where('id', $user_id)->count();
            if($hasuser == 0){
                $value = array('status'=>0,'mess'=>'用户不存在');
                exit;
            }

            $courseData = Db::name('course_course')->where('course_id', $course_id)->find();

            $data = [
                'title' => $input['title'],
                'catid' => $input['catid'],
                'description' => $input['description'],
                'content' => $input['content'],
                'tags' => '',
                'price' => $input['price'],
                'useable' => $input['useable'],
                'teacher_id' => $teacher_id,
                'user_id' => $user_id
            ];
            if(!empty($input['pic_id'])){
                $zssjpics = Db::name('huamu_zspic_art')->where('id',$input['pic_id'])->where('admin_id',$admin_id)->find();
                if($zssjpics && $zssjpics['img_url']){
                    $data['imgurl'] = $zssjpics['img_url'];
                }
            }

            if($course_id > 0){
                $r = Db::name('course_course')->where('course_id', $course_id)->update($data);


            }else{
                $data['addtime'] = time();

                $r = Db::name('course_course')->insertGetId($data);
            }

            if($r !== false){
                if(!empty($zssjpics) && $zssjpics['img_url']){
                    Db::name('huamu_zspic_art')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                    if($courseData['imgurl'] && file_exists('./'.$courseData['imgurl'])){
                        @unlink('./'.$ars['imgurl']);
                    }
                }

                ys_admin_logs('新增课程','course_course', $r);
                $value = array('status'=>1,'mess'=>'增加成功');
            }else{
                $value = array('status'=>0,'mess'=>'增加失败');
            }
        }

        return json($value);
    }

    public function add_course(){
        $cats = Db::name('course_category')->select();
        $this->assign('cats', $cats);
        $teachers = Db::name('course_teacher')->where('useable', 1)->select();
        $this->assign('teachers', $teachers);

        return $this->fetch();
    }

    public function edit_course(){
        $course_id = input('course_id/d', 0);
        $data = Db::name('course_course')->where('course_id', $course_id)->find();
        $this->assign('info', $data);
        $cats = Db::name('course_category')->select();
        $this->assign('cats', $cats);
        $teachers = Db::name('course_teacher')->where('useable', 1)->select();
        $this->assign('teachers', $teachers);

        return $this->fetch('add_course');
    }

    public function deletecourse(){
        $input = input('param.');

        $r = Db::name('course_course')->where('course_id', $input['id'])->delete();

        if($r){
            $value = array('status'=>1,'mess'=>'删除成功');
        }else{
            $value = array('status'=>0,'mess'=>'删除失败');
        }

        return json($value);
    }

    /**
     * @return int
     * @info 学堂 - 视频列表
     *
     */
    public function video_list(){
        $list = array();
        $p = input('p/d', 1);
        $size = input('size/d', 20);
        $where = array();
        $teacher_id = input('teacher_id');
        $teacher_id && $where['teacher_id'] = array('=', $teacher_id);
        $teacher_id && $this->assign('teacher_id', $teacher_id);

        $course_id = input('course_id');
        $course_id && $where['course_id'] = array('=', $course_id);
        $course_id && $this->assign('course_id', $course_id);

        $list = Db::name('course_video')->where($where)->order('addtime desc')->paginate($size)->each(function ($value){
            $value['teacher'] = Db::name('course_teacher')->where('teacher_id', $value['teacher_id'])->value('title');
            $value['t_title'] = Db::name('course_course')->where('course_id', $value['course_id'])->value('title');
            $value['pname'] = Db::name('course_video_category')->where('catid', $value['pid'])->value('catname');
            $value['catname'] = Db::name('course_video_category')->where('catid', $value['catid'])->value('catname');

            return $value;
        });

        $page = $list->render();//分页显示输出
        $this->assign('list',$list);// 赋值数据集
        $this->assign('page',$page);// 赋值分页输出

        $teachers = Db::name('course_teacher')->select();
        $this->assign('teachers', $teachers);

        return $this->fetch();
    }

    public function add_video(){
        $teachers = Db::name('course_teacher')->where('useable', 1)->select();
        $this->assign('teachers', $teachers);
        return $this->fetch();
    }

    public function getcourse(){
        $teacher_id = input('teacher_id/d', 0);
        $list = Db::name('course_course')->where('teacher_id', $teacher_id)->where('useable', 1)->select();
        echo json_encode($list);
    }

    public function getcategory(){
        $input = input('post.');
        $course_id = $input['course_id'];
        $pid = $input['pid'];
        if($input['kind'] == 0){
            $list = Db::name('course_video_category')->where("pid = '0' and course_id = '$course_id'")->select();
        }else{
            $list = Db::name('course_video_category')->where("pid = '$pid' and course_id = '$course_id'")->select();
        }

        echo json_encode(['code' => 1, 'list' => $list]);
    }

    //保存视频章节
    public function savecategory(){
        $input = input('post.');
        $course_id = $input['course_id'];
        $pid = $input['pid'];
        $catname = $input['catname'];

        $num = Db::name('course_video_category')->where('catname', $catname)->where('pid', $pid)->where('course_id', $course_id)->count();
        if($num > 0){
            echo json_encode(['code' => 0, 'msg' => '该章节已存在']);
        }else{
            $data = [
                'pid' => $pid,
                'course_id' => $course_id,
                'catname' => $catname
            ];

            $catid = Db::name('course_video_category')->insertGetId($data);
            if($catid){
                echo json_encode(['code' => 1, 'msg' => 'ok', 'catid' => $catid]);
            }else{
                echo json_encode(['code' => 0, 'msg' => '添加失败']);
            }
        }
    }

    public function save_video(){
        $input = input('post.');
        $admin_id = session('admin_id');
        $video_id = $input['video_id'];
        $course_id = $input['course_id'];
        $teacher_id = $input['teacher_id'];
        $result = $this->validate($input,'School.video');
        $VideoModel = new VideoModel();
        if(true !== $result){
            $value = array('status'=>0,'mess'=>$result);
        }else {
            $user_id = Db::name('course_teacher')->where('teacher_id', $teacher_id)->value('user_id');
            $hasvideo = $VideoModel->where('user_id', $user_id)->where('catid', $input['catid'])->where('course_id', $course_id)->find();
            if (!isset($video_id) && !empty($hasvideo)) {
                $video_id = $hasvideo['video_id'];
            }

            $data = [
                'pid' => $input['pid'],
                'catid' => $input['catid'],
                // 'imgurl' => $input['imgurl'],
                'videourl' => $input['videourl'],
                'out_videourl' => $input['out_videourl'],
                'goods_id' => $input['goods_id'],
                'user_id' => $user_id,
                'teacher_id' => $teacher_id,
                'course_id' => $course_id,
                'useable' => $input['useable'],
                'displaytime' => $input['displaytime'],
                'alltime' => $input['alltime']
            ];
            if(!empty($input['pic_id'])){
                $zssjpics = Db::name('huamu_zspic_art')->where('id',$input['pic_id'])->where('admin_id',$admin_id)->find();
                if($zssjpics && $zssjpics['img_url']){
                    $data['imgurl'] = $zssjpics['img_url'];
                }
            }
            if(!empty($input['addtime'])){
                $data['addtime'] = strtotime($input['addtime']);
            }else{
                $data['addtime'] = time();
            }

            if ($video_id > 0) {
                $r = $VideoModel->where('video_id', $video_id)->update($data);
                if($r){
                    if(!empty($zssjpics) && $zssjpics['img_url']){
                        Db::name('huamu_zspic_art')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                        if($hasvideo['imgurl'] && file_exists('./'.$hasvideo['imgurl'])){
                            @unlink('./'.$hasvideo['imgurl']);
                        }
                    }
                }
            } else {
                $data['addtime'] = time();
                $r = $VideoModel->insertGetId($data);
                if ($r) {
                    Db::name('course_course')->where('course_id', $course_id)->setInc('coursenum', 1);
                }
            }

            if ($r !== false) {
                $value = array('status'=>1,'mess'=>"操作成功");
            } else {
                $value = array('status'=>0,'mess'=>'操作失败');
            }
        }

        return json($value);
    }

    public function edit_video(){
        $video_id = input('video_id/d', 0);
        $data = Db::name('course_video')->where('video_id', $video_id)->find();

        if(strpos($data['videourl'], 'http') === false){
            $data['videourl'] = $this->webconfig['weburl'].$data['videourl'];
        }

        $data['pname'] = Db::name('course_video_category')->where('catid', $data['pid'])->value('catname');
        $data['catname'] = Db::name('course_video_category')->where('catid', $data['catid'])->value('catname');
        $this->assign('info', $data);
        $teachers = Db::name('course_teacher')->where('useable', 1)->select();
        $this->assign('teachers', $teachers);
        return $this->fetch('add_video');
    }

    public function deletevideo(){
        $input = input('param.');

        $VideoModel = new VideoModel();
        $r = $VideoModel->where('video_id', $input['id'])->delete();

        if($r){
            $value = array('status'=>1,'mess'=>'删除成功');
        }else{
            $value = array('status'=>0,'mess'=>'删除失败');
        }

        return json($value);
    }

    /**
     * @return int
     * @info 学堂 - 评论列表
     *
     */
    public function feed_list(){
        $list = array();
        $p = input('p/d', 1);
        $size = input('size/d', 20);
        $where = array();
        $keywords = trim(input('keywords'));
        $keywords && $where['content'] = array('like', '%' . $keywords . '%');

        $feedModel = new FeedModel();
        $list = $feedModel->where($where)->order('addtime desc')->paginate($size)->each(function ($value){
            $value['nickname'] = Db::name('member')->where('id', $value['user_id'])->value('user_name');
        });

        $page = $list->render();//分页显示输出
        $this->assign('keywords',$keywords);// 赋值数据集
        $this->assign('list',$list);// 赋值数据集
        $this->assign('page',$page);// 赋值分页输出
        return $this->fetch();
    }

    /**
     * @return int
     * @info 学堂 - 订单列表
     *
     */
    public function order_list(){
        $list = array();
        $p = input('p/d', 1);
        $size = input('size/d', 20);
        $where = array();
        $keywords = trim(input('keywords'));
        $keywords && $where['order_sn'] = array('like', '%' . $keywords . '%');

        $orderModel = new OrderModel();
        $list = $orderModel->where($where)->order('addtime desc')->paginate($size)->each(function ($value){
            $value['nickname'] =Db::name('member')->where('id', $value['user_id'])->value('user_name');
            $value['t_title'] = Db::name('course_course')->where('course_id', $value['course_id'])->value('title');

            return $value;
        });

        $page = $list->render();//分页显示输出
        $this->assign('keywords',$keywords);// 赋值数据集
        $this->assign('list',$list);// 赋值数据集
        $this->assign('page',$page);// 赋值分页输出
        return $this->fetch();
    }




    //修改推荐
    public function gaibianfeed(){
        $id = input('post.id');
        $name = input('post.name');
        $value = input('post.value');
        $data[$name] = $value;
        $FeedModel = new FeedModel();
        $count = $FeedModel->save($data,array('feed_id'=>$id));
        if($count > 0){
            $result = 1;
        }else{
            $result = 0;
        }
        return $result;
    }

    //修改推荐
    public function gaibianvideo(){
        $id = input('post.id');
        $name = input('post.name');
        $value = input('post.value');
        $data[$name] = $value;
        $VideoModel = new VideoModel();
        $count = $VideoModel->save($data,array('video_id'=>$id));
        if($count > 0){
            $result = 1;
        }else{
            $result = 0;
        }
        return $result;
    }

    //修改推荐
    public function gaibiancourse(){
        $id = input('post.id');
        $name = input('post.name');
        $value = input('post.value');
        $data[$name] = $value;
        $CourseModel = new CourseModel();
        $count = $CourseModel->save($data,array('course_id'=>$id));
        if($count > 0){
            $result = 1;
        }else{
            $result = 0;
        }
        return $result;
    }

    //修改推荐
    public function gaibian(){
        $id = input('post.id');
        $name = input('post.name');
        $value = input('post.value');
        $data[$name] = $value;
        $data['teacher_id'] = $id;
        $news = new CateNewMx();
        $count = $news->save($data,array('teacher_id'=>$data['teacher_id']));
        if($count > 0){
            $result = 1;
        }else{
            $result = 0;
        }
        return $result;
    }

    //处理上传图片
    public function uploadify(){
        $admin_id = session('admin_id');
        $file = request()->file('filedata');
        if($file){
            $info = $file->validate(['size'=>3145728,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'school');
            if($info){
                $zssjpics = Db::name('huamu_zspic_art')->where('admin_id',$admin_id)->find();
                if($zssjpics && $zssjpics['img_url']){
                    Db::name('huamu_zspic_art')->where('id',$zssjpics['id'])->update(array('img_url'=>''));
                    if($zssjpics['img_url'] && file_exists('./'.$zssjpics['img_url'])){
                        @unlink('./'.$zssjpics['img_url']);
                    }
                }
                $getSaveName = str_replace("\\","/",$info->getSaveName());
                $original = 'uploads/school/'.$getSaveName;
                $image = \think\Image::open('./'.$original);
                $image->thumb(640, 400)->save('./'.$original,null,90);
                if($zssjpics){
                    Db::name('huamu_zspic_art')->where('id',$zssjpics['id'])->update(array('img_url'=>$original));
                    $zspic_id = $zssjpics['id'];
                }else{
                    $zspic_id = Db::name('huamu_zspic_art')->insertGetId(array('admin_id'=>$admin_id,'img_url'=>$original));
                }
                $picarr = array('img_url'=>$original,'pic_id'=>$zspic_id);
                $value = array('status'=>1,'path'=>$picarr);
            }else{
                $value = array('status'=>0,'msg'=>$file->getError());
            }
        }else{
            $value = array('status'=>0,'msg'=>'文件不存在');
        }
        return json($value);
    }

    //手动删除未保存的上传图片手机
    public function delfile(){
        if(input('post.zspic_id')){
            $admin_id = session('admin_id');
            $zspic_id = input('post.zspic_id');
            $pics = Db::name('huamu_zspic_art')->where('id',$zspic_id)->where('admin_id',$admin_id)->find();
            if($pics && $pics['img_url']){
                $count = Db::name('huamu_zspic_art')->where('id',$pics['id'])->update(array('img_url'=>''));
                if($count > 0){
                    if($pics['img_url'] && file_exists('./'.$pics['img_url'])){
                        @unlink('./'.$pics['img_url']);
                    }
                    $value = 1;
                }else{
                    $value = 0;
                }
            }else{
                $value = 0;
            }
        }else{
            $value = 0;
        }
        return json($value);
    }
}