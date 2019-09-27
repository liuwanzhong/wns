<?php

namespace app\index\controller;


use think\Controller;
use think\Db;

class Batch extends Controller {
    /**
     * 添加入库订单列表
     */
    public function ruku(){
        $rows=array();
        if(!empty(input('id'))){
            $id=input('id');
        }else{
            $id='';
        }
        // if(!empty($time)){
        //     $times=explode('-',$time);
        //     $y=$times[0];
        //     $m=$times[1];
        //     $rows=db('record')
        //     ->where('record.is_del',1)
        //     ->where("date_format(from_unixtime(time),'%m')=$m")
        //     ->where("date_format(from_unixtime(time),'%Y')=$y")
        //     ->join('rukuform_xq','rukuform_xq.rk_huowei_id=record.huowei','left')
        //     ->join('cabinet','cabinet.id=record.huowei','left')
        //     ->join('warehouse','warehouse.id=cabinet.warehouse_id','left')
        //     ->group('record.huowei')
        //     ->field('warehouse.name w_name,cabinet.name c_name,rukuform_xq.product_name rk_name,record.huowei huowei')
        //     ->select();
        // }

        $cks = db('warehouse')->where('is_del',1)->select();
        return view('ruku',['cks'=>$cks]);
    }
    /**
     * 入库托盘信息
     */
    public function ruku_ck(){
        $id=input('id');
        $res=db('tray')
        ->where('tray.warehouse_id',7)  
        ->join('warehouse','warehouse.id=tray.warehouse_id','left')
        ->where('tray.is_del',0)
        ->select();
        // dump($res);
        // exit;
        return view('ruku_ck',['res'=>$res]);
    }
    /**
     * 托盘详细
     */
    public function tray_xx(){
            
    }
}
