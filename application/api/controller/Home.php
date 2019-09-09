<?php
/**
 * Created by PhpStorm.
 * User: 金戋
 * Date: 2019/9/4
 * Time: 14:21
 */

namespace app\api\controller;


use think\Controller;

class Home extends Controller
{
    //首页接口
    public function index()
    {
        //首页图片
        $carousel=db('img')->where('state',0)->where('is_delete',0)->order('sort desc')->field('img,url')->select();
        foreach ($carousel as $k=>$item) {
            $carousel[$k]['img']='http://crm.cdqifa.cn:66'.$carousel[$k]['img'];
       }
        //首页导航
        $navigation=db('home_navigation')->where('state',0)->where('is_delete',0)->order('sort desc')->field('one_name,two_name,img,url')->select();
        foreach ($navigation as $k=>$item) {
            $navigation[$k]['img']='http://crm.cdqifa.cn:66'.$navigation[$k]['img'];
        }
        //广告
        $poster=db('poster')->where('state',0)->where('is_delete',0)->field('img,url')->select();
        foreach ($poster as $k=>$item) {
            $poster[$k]['img']='http://crm.cdqifa.cn:66'.$poster[$k]['img'];
        }
        return ['carousel'=>$carousel,'navigation'=>$navigation,'poster'=>$poster];
    }
}