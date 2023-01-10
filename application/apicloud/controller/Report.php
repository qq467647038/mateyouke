<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use think\Db;

class Report extends Common
{
    // 发布投诉建议
    public function addReport()
    {
        if (request()->isPost()) {
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate();
            if ($result['status'] == 200) {
                $user_id = $result['user_id'];
                // $mid = input('post.mid');
                $title = input('post.title');
                $content = input('post.content');
                if (empty($title)) {
                    return datamsg(LOSE, '请选择举报内容');
                }
                if (empty($content)) {
                    return datamsg(LOSE, '请输入举报或建议内容');
                }
                
                if ($user_id) {
                    $data['user_id'] = $user_id;
                    // $data['mid'] = $mid;
                    $data['title'] = $title;
                    $data['content'] = $content;
                    $data['time'] = time();
                    $data['reply'] = 0;

                    $res = db('feedback_help')->insertGetId($data);

                    $pic = input('param.pic');
                    $datapic = explode(',', $pic);
                    $picarr = [];
                    foreach ($datapic as $key => $value) {
                        $picarr[$key]['pathurl'] = $value;
                        $picarr[$key]['fid'] = $res;
                    }
                    $resultPic = Db::name('feedback_pic')->insertAll($picarr);
                    if ($res && $resultPic) {
                        Db::commit();
                        return datamsg(WIN, '提交成功');
                    } else {
                        Db::rollback();
                        return datamsg(LOSE, '提交失败');
                    }

                    if ($res) {
                        return datamsg(WIN, '提交成功');
                    } else {
                        return datamsg(LOSE, '提交失败');
                    }
                } else {
                    return datamsg(LOSE, '请先登录');
                }
            } else {
                return datamsg(LOSE, $result['mess']);
            }
        } else {
            return datamsg(LOSE, '请求方式错误');
        }
    }
	
	// 口碑投诉
	public function findReport()
	{
	    if (request()->isPost()) {
	        $gongyong = new GongyongMx();
	        $result = $gongyong->apivalidate();
	        if ($result['status'] == 200) {
	            $user_id = $result['user_id'];
	            // $mid = input('post.mid');
				$k_id = input('post.k_id');
	            $title = input('post.title');
	            $content = input('post.content');
	            if (empty($title)) {
	                datamsg(LOSE, '请选择举报内容');
	            }
	            if (empty($content)) {
	                datamsg(LOSE, '请输入举报或建议内容');
	            }
	            
	            if ($user_id) {
	                $data['user_id'] = $user_id;
	                $data['k_id'] = $k_id;
	                $data['title'] = $title;
	                $data['content'] = $content;
	                $data['time'] = time();
	                $data['reply'] = 0;
	
	                $res = db('feedback_order')->insertGetId($data);
	
	                $pic = input('param.pic');
	                $datapic = explode(',', $pic);
	                $picarr = [];
	                foreach ($datapic as $key => $value) {
	                    $picarr[$key]['pathurl'] = $value;
	                    $picarr[$key]['fid'] = $res;
	                }
	                $resultPic = Db::name('feedback_order_pic')->insertAll($picarr);
	                if ($res && $resultPic) {
	                    Db::commit();
	                    datamsg(WIN, '提交成功');
	                } else {
	                    Db::rollback();
	                    datamsg(LOSE, '提交失败');
	                }
	
	                if ($res) {
	                    datamsg(WIN, '提交成功');
	                } else {
	                    datamsg(LOSE, '提交失败');
	                }
	            } else {
	                datamsg(LOSE, '请先登录');
	            }
	        } else {
	            datamsg(LOSE, $result['mess']);
	        }
	    } else {
	        datamsg(LOSE, '请求方式错误');
	    }
	}
	
	
}