<?php
namespace app\index\beha;
use think\Controller;
use think\Request;
use think\Session;

class Cate extends Controller{
    public function run(&$params)
    {
         if ($params[1]!='login' && $params[1]!='proving'){
             if (!Session::has('users')){
                 $this->success('非法访问---请先进行登录','Login/login');
             }
         }
    }
}
