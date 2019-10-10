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
        $warehouse=self::$stafss['warehouse'];
        $cks = db('warehouse')->where('is_del',1)->where('id','in',$warehouse)->select();
        Cookie(null,'think_');
        return view('index',['cks'=>$cks]);
    }
    /**
     * 扫码出库
     */
    public function create_saoyisao(){
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
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
            // $this->error('仓库未选择');
//        dump(input());
        }
        if(isset($s) && strstr($s,',')){
            $s=explode(',',$s);
            foreach($s as $v){
                $xx[]=db('tray')
                ->where('tp_num',$v)
                ->select();
            }
        }else{
            if(!empty($tp_num)){
                $xx[]=db('tray')
                ->where('tp_num',$tp_num)
                ->select();
            }else{
                $xx='';
            }
        }
        return view('create_saoyisao',['name'=>$name,'delivery'=>$delivery,'delivery_name'=>$delivery_name,'xx'=>$xx,'tp_num'=>$tp_num]);
    }
    /**
     * 生成出库单据
     */
    public function create_order(){
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $data=input();
        $log_id='';
        $tp_num=implode(',',$data['tp_num']);
        foreach($data['tp_num'] as $v){
            $res=db('tray')
            ->where('tp_num',$v)
            ->update([
                'delivery'=>$data['delivery'],
                'state'=>0
            ]);
            $row=db('tray')
            ->where('tp_num',$v)
            ->select();
            $id=db('tray_log')
            ->insertGetId([
                'tp_num'=>$v,
                'goods_name'=>$row[0]['goods_name'],
                'goods_time'=>$row[0]['goods_time'],
                'num'=>$row[0]['num'],
                'order'=>$row[0]['order'],
                'delivery'=>$row[0]['delivery'],
                'batch'=>$row[0]['batch'],
                'state'=>1,
                'time'=>time(),
            ]);
            $log_id.=$id.',';
        }
        $res=db('tray_order')
        ->insert([
            'transport_id'=>$data['transport_id'],
            'delivery'=>$data['delivery'],
            'delivery_name'=>$data['delivery_name'],
            'log_id'=>$log_id,
            'create_time'=>time(),
        ]);
        return redirect('index');
    }
    /**
     * 往期出库
     */
    public function out_log(){
        static $md=[];
        $warehouse=self::$stafss['warehouse'];
        $cks=db('warehouse')->where('is_del',1)->where('id','in',$warehouse)->select();
        $search = '';
        $data=input();
        if(!empty($data['name'])){
            $name=$data['name'];
            $search = 'transport_id like ' . "'%" . $name . '%' . "'";
        }else{
            $name='';
        }
        $re=db('tray_order')
        ->where('is_del',0)
        ->where($search )
        ->order('create_time desc')
        ->paginate(100,false,['query'=>['name'=>$name]]);
        $res=$re->all();
        foreach ($res as $k=>$re) {
            foreach ($cks as $ck) {
                if($re['transport_id']==$ck['name']){
                    $md[]=$re;
                }
            }
        }
        return view('out_log',['res'=>$re,'name'=>$name,'cks'=>$cks,'md'=>$md]);
//        ->paginate(100,false,['query'=>['name'=>$name]]);
//        return view('out_log',['res'=>$res,'name'=>$name]);
    }
    /**
     * 往期出库详细
     */
    public function tray_log_xx(){
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $id=input('id');
        $log_id='';
        $res=db('tray_order')
        ->where('id',$id)
        ->select();
        $log_id=trim($res[0]['log_id'],',');
        $id=explode(',',$log_id);
        foreach($id as $v){
            $row[]=db('tray_log')
            ->where('id',$v)
            ->select();
        }
        return view('tray_log_xx',['res'=>$res,'row'=>$row]);
    }
    /**
     * 入库扫码页
     */
    public function rk_saoma(){
        return view();
    }
}
