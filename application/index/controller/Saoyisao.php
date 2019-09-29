<?php

namespace app\index\controller;


use think\Controller;
use think\Db;
use think\Cookie;
use think\Cache;
class Saoyisao extends Controller {
    /**
     * 生成扫码出库单
     */
    public function index(){
        $cks = db('warehouse')->where('is_del',1)->select();
        // Cookie::clear('think_');
        Cookie(null,'think_');
        return view('index',['cks'=>$cks]);
    }

    public function create_saoyisao(){
        $md=[];
        $warehouse_id=input('warehouse_id');
        $delivery=input('delivery');
        $delivery_name=input('delivery_name');
        $tp_num=input('tp_num');
        if(Cookie::has('tp_num','think_')){
            $md=Cookie::get('tp_num','think_');
            $f=$md.','.$tp_num;
            Cookie::set('tp_num',$f,['prefix'=>'think_']);
        }else{
            Cookie::set('tp_num',$tp_num,['prefix'=>'think_']);
        }
        if(Cookie::has('tp_num','think_')){
            $s=Cookie::get('tp_num','think_');
        }else{
            dump('不存在');
        }
        if(Cookie::has('name','think_') || Cookie::has('delivery','think_') || Cookie::has('delivery_name','think_')){
            $name=Cookie::get('name','think_');
            $delivery=Cookie::get('delivery','think_');
            $delivery_name=Cookie::get('delivery_name','think_');
            $cks = db('warehouse')->where('is_del',1)->where('id',$name)->find();
        }else{
            $cks = db('warehouse')->where('is_del',1)->where('id',$warehouse_id)->find();
            $name=$cks['name'];
            Cookie::set('name',$cks['name'],['prefix'=>'think_']);
            Cookie::set('delivery',$delivery,['prefix'=>'think_']);
            Cookie::set('delivery_name',$delivery_name,['prefix'=>'think_']);
        }
        
        if(empty($name)){
            $this->error('仓库未选择');
        }
        foreach($s as $v){
            $k['']
        }
        if(!empty($tp_num)){
            $xx=db('tray')
            ->where('tp_num',$tp_num)
            ->select();
        }else{
            $xx='';
        }
        return view('create_saoyisao',['name'=>$name,'delivery'=>$delivery,'delivery_name'=>$delivery_name,'xx'=>$xx,'tp_num'=>$tp_num]);
    }
    public function saoma(){
        return view();
    }
}