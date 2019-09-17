<?php


namespace app\index\controller;

use think\Controller;
use think\Db;

class Table extends Controller {
    public function index() {
        //产品名称列表
        $list = db('cw_management')->field('material_name,transfers_factory')
            ->group('material_name ')->select();
        //入库状态
        $status = db('kc_status')->where('is_del',0)->select();
        //仓库列表
        $cks = db('warehouse')->where('is_del',1)->select();
//        var_dump($list);exit;
        return view('index',['list'=>$list,'status'=>$status,'cks'=>$cks]);
    }
    public function show($id){
        $list = db('cabinet')->where('is_del',1)->where('warehouse_id',$id)->select();
        return json_encode($list);
    }
}
