<?php


namespace app\index\controller;


use think\Controller;

class Other extends Controller {
    public function transport() {
        $rows=db('fayunbb')->where('is_del',1)->select();
        return view('transport',['rows'=>$rows]);
    }
}
