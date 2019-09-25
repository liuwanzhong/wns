<?php

namespace app\index\controller;


use think\Controller;
use think\Db;

class Batch extends Controller {
    //添加入库订单列表
    public function ruku(){
        $list=db('rukuform_xq')
        ->order('product_batch')
        ->select();
        dump($list);exit;
        return view();
    }
    public function chuku(){
        return view();
    }
}
