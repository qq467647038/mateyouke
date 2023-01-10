<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use think\Db;

class Article extends Common
{
    public function getArticleByTitle(){
        // 验证token
        $res = $this->checkToken(0);
        if($res['status'] == 400){  return json($res);  }

        $title = input('post.title');
        // dump(input('post.'));
        if(!empty($title)){
            $article = db('news')->where(['ar_title'=>$title])->find();

            $ar_content = img_add_protocal($article['ar_content'], $this->webconfig['weburl']);
            $article['ar_content'] = $ar_content[0];

            if($article){
                $value = array('status'=>200,'mess'=>'获取文章信息成功','data'=>array('article'=>$article));
            }else{
                $value = array('status'=>400,'mess'=>'获取文章信息失败','data'=>array('status'=>400));
            }
            
        }else{
            $value = array('status'=>400,'mess'=>'缺少文章标题参数','data'=>array('status'=>400));
        }

        return json($value);
        
    }

    public function getNoticeById(){
        // 验证token
        $res = $this->checkToken(0);
        if($res['status'] == 400){  return json($res);  }

        $id = input('post.id');
        // dump(input('post.'));
        if(!empty($id)){
            $notice = db('site_notice')->find($id);
            if($notice){
                $value = array('status'=>200,'mess'=>'获取公告信息成功','data'=>$notice);
            }else{
                $value = array('status'=>400,'mess'=>'获取公告信息失败','data'=>array('status'=>400));
            }
            
        }else{
            $value = array('status'=>400,'mess'=>'缺少id参数','data'=>array('status'=>400));
        }

        return json($value);
        
    }

    public function getArticleById(){
        // 验证token
        $res = $this->checkToken(0);
        if($res['status'] == 400){  return json($res);  }

        $id = input('post.id');
        // dump(input('post.'));
        if(!empty($id)){
            $article = db('news')->find($id);
            if($article){
                $article['ar_pic'] = config('weburl') . $article['ar_pic'];
                $value = array('status'=>200,'mess'=>'获取文章信息成功','data'=>array('article'=>$article));
            }else{
                $value = array('status'=>400,'mess'=>'获取文章信息失败','data'=>array('status'=>400));
            }
            
        }else{
            $value = array('status'=>400,'mess'=>'缺少id参数','data'=>array('status'=>400));
        }

        return json($value);
        
    }


    //根据分类获取文章列表
    public function getlist(){
        $res = $this->checkToken(0);
        $cate_id = input('post.cate_id');
        $list = db('news')->where('cate_id', $cate_id)->order('addtime','desc')->limit(8)->select();
        foreach ($list as $k =>$v){
            if ($v['ar_pic']) {
                $list[$k]['ar_pic'] = $this->webconfig['weburl'].'/'.$v['ar_pic'];
            }
        }

        $value = array('status'=>200,'mess'=>'获取文章列表成功','data'=>$list);

        return json($value);
    }


}
