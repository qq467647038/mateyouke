<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\CommunityList as CommunityListModel;
use app\admin\model\CommunityArticle as CommunityArticleModel;
use app\admin\model\CommunityFeed as CommunityFeedModel;

class Community extends Common{
    /**
     * @return mixed
     * @throws \think\exception\DbException
     * @info 社群 - 社群列表
     *
     */
    public function community_list(){
        $list = array();
        $p = input('p/d', 1);
        $size = input('size/d', 20);
        $where = array();
        $keywords = trim(input('keywords'));
        $keywords && $where['title'] = array('like', '%' . $keywords . '%');

        $CommunityListModel = new CommunityListModel();
        $list = $CommunityListModel->where($where)->order('comm_id desc')->paginate($size)->each(function ($value){
            $value['nickname'] = Db::name('member')->where('id', $value['user_id'])->value('user_name');

            return $value;
        });

        $page = $list->render();
        $this->assign('keywords',$keywords);// 赋值数据集
        $this->assign('list',$list);// 赋值数据集
        $this->assign('page',$page);// 赋值分页输出
        return $this->fetch();
    }

    /**
     * @return int
     * @info 社群 - 社群内容
     *
     */
    public function article_list(){
        $list = array();
        $p = input('p/d', 1);
        $size = input('size/d', 20);
        $where = array();
        $keywords = trim(input('keywords'));
        $keywords && $where['content|title|description'] = array('like', '%' . $keywords . '%');

        $CommunityArticleModel = new CommunityArticleModel();
        $list = $CommunityArticleModel->where($where)->order('addtime desc')->paginate($size)->each(function ($value){
            $value['nickname'] = Db::name('member')->where('id', $value['user_id'])->value('user_name');
            $value['comm_title'] = Db::name('community_list')->where('comm_id', $value['comm_id'])->value('title');

            return $value;
        });

        $page = $list->render();
        $this->assign('keywords',$keywords);// 赋值数据集
        $this->assign('list',$list);// 赋值数据集
        $this->assign('page',$page);// 赋值分页输出
        return $this->fetch();
    }

    /**
     * @return int
     * @info 社群 - 社群评论
     *
     */
    public function feed_list(){
        $list = array();
        $p = input('p/d', 1);
        $size = input('size/d', 20);
        $where = array();
        $keywords = trim(input('keywords'));
        $keywords && $where['content'] = array('like', '%' . $keywords . '%');

        $CommunityFeedModel = new CommunityFeedModel();
        $list = $CommunityFeedModel->where($where)->order('addtime desc')->paginate($size)->each(function ($value){
            $value['nickname'] = Db::name('member')->where('id', $value['user_id'])->value('user_name');

            return $value;
        });

        $page = $list->render();
        $this->assign('keywords',$keywords);// 赋值数据集
        $this->assign('list',$list);// 赋值数据集
        $this->assign('page',$page);// 赋值分页输出
        return $this->fetch();
    }




    //修改推荐
    public function gaibiancommfeed(){
        $id = input('post.id');
        $name = input('post.name');
        $value = input('post.value');
        $data[$name] = $value;

        $CommunityFeedModel = new CommunityFeedModel();
        $count = $CommunityFeedModel->save($data,array('feed_id'=>$id));
        if($count > 0){
            $result = 1;
        }else{
            $result = 0;
        }
        return $result;
    }

    //修改推荐
    public function gaibiancommarticle(){
        $id = input('post.id');
        $name = input('post.name');
        $value = input('post.value');
        $data[$name] = $value;

        $CommunityArticleModel = new CommunityArticleModel();
        $count = $CommunityArticleModel->save($data,array('article_id'=>$id));
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

        $CommunityListModel = new CommunityListModel();
        $count = $CommunityListModel->save($data,array('comm_id'=>$id));
        if($count > 0){
            $result = 1;
        }else{
            $result = 0;
        }
        return $result;
    }
}