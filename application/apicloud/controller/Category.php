<?php
namespace app\apicloud\controller;
use app\apicloud\controller\Common;
use app\apicloud\model\Gongyong as GongyongMx;
use think\Db;

class Category extends Common{
    
    //获取分类信息
    public function index(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(0);
            if($result['status'] == 200){
                $cateres = Db::name('category')->where('id', 'notIn', [433,432])->where('pid',0)->where('is_show',1)->field('id,cate_name')->order('sort asc')->select();
                $recom_cate = Db::name('category')->where('id', 'notIn', [433,432])->where('show_in_recommend',1)->where('is_show',1)->field('id,cate_name,cate_pic')->order('sort asc')->select();
                $webconfig = $this->webconfig;
                // foreach ($recom_cate as $key =>$val){
                //     $recom_cate[$key]['cate_pic'] =  $webconfig['weburl'].'/'.$val['cate_pic'];
                // }
                $cateinfos = array('cateres'=>$cateres,'recom_cate'=>$recom_cate);
                $value = array('status'=>200,'mess'=>'获取平台分类信息成功','data'=>$cateinfos);
            }else{
                $value = $result;
                
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    //通过顶级分类id获取子类
    public function getchild(){
        if(request()->isPost()){
            $gongyong = new GongyongMx();
            $result = $gongyong->apivalidate(0);
            if($result['status'] == 200){
                $webconfig = $this->webconfig;
                if(input('post.cate_id')){
                    $cate_id = input('post.cate_id');
                    $categorys = Db::name('category')->where('id',$cate_id)->where('pid',0)->where('is_show',1)->field('id,cate_name,cate_pic')->find();
                    if($categorys){
                        $child_cate = Db::name('category')->where('pid',$cate_id)->where('is_show',1)->field('id,cate_name,cate_pic')->order('sort asc')->select();
                        if($child_cate){
                            foreach ($child_cate as $key =>$val){
                                // $child_cate[$key]['cate_pic'] = $webconfig['weburl'].'/'.$val['cate_pic'];
                                $child_cate[$key]['three'] = Db::name('category')->where('pid',$val['id'])->where('is_show',1)->field('id,cate_name,cate_pic')->order('sort asc')->select();
                                if(!$child_cate[$key]['three']){
                                    $child_cate[$key]['three'][] = $val;
                                }
                                // foreach ($child_cate[$key]['three'] as $key2 => $val2){
                                //     $child_cate[$key]['three'][$key2]['cate_pic'] = $webconfig['weburl'].'/'.$val2['cate_pic'];
                                // }
                            }
                        }else{
                            // $categorys['cate_pic'] = $webconfig['weburl'].'/'.$categorys['cate_pic'];
                            $child_cate[] = $categorys;
                        }
                        $value = array('status'=>200,'mess'=>'获取子类成功','data'=>$child_cate);
                    }else{
                        $value = array('status'=>400,'mess'=>'分类id参数错误','data'=>array('status'=>400));
                    }
                }elseif(input('post.cate_id') == 0){
                    $recom_cate = Db::name('category')->where('show_in_recommend',1)->where('is_show',1)->field('id,cate_name,cate_pic')->order('sort asc')->select();
                    // foreach ($recom_cate as $kr => $vr){
                    //     $recom_cate[$kr]['cate_pic'] = $webconfig['weburl'].'/'.$vr['cate_pic'];
                    // }
                    $value = array('status'=>200,'mess'=>'获取推荐分类信息成功','data'=>$recom_cate);
                }else{
                    $value = array('status'=>400,'mess'=>'缺少分类id参数','data'=>array('status'=>400));
                }
            }else{
                $value = $result;
            }
        }else{
            $value = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return json($value);
    }
    
    
    
    
}