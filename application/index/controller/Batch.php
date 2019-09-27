<?php

namespace app\index\controller;


use think\Controller;
use think\Db;

class Batch extends Controller {
    /**
     * 扫码静茹页面
     */
    public function batch(){
        $tp_num=input('tp_num');
        $res=db('tray')
        ->where('tp_num',$tp_num)
        ->field('state')
        ->select();
        if($res[0]['state']==0){//未分配
            return redirect('fenpei',['tp_num'=>$tp_num]);
        }elseif($res[0]['state']==1){//入库
            return redirect('chuku',['tp_num'=>$tp_num]);
        }
    }
    /**
     * 分配托盘
     */
    public function fenpei(){
        $tp_num=input('tp_num');
        $res=db('tray')
        ->where('tray.tp_num',$tp_num)  
        ->join('warehouse','warehouse.id=tray.warehouse_id','left')
        ->where('tray.is_del',0)
        ->select();
        return view('fenpei',['res'=>$res,'tp_num'=>$tp_num]);
    }
    /**
     * 指定拖盘加入货物
     */
    public function tray_rk_insert(){
        $data=input();
        $batch=implode(',',$data['batch']);
        $res=db('tray')
        ->where('tp_num',$data['tp_num'])
        ->update([
            'goods_name'=>$data['goods_name'],
            'num'=>$data['num'],
            'batch'=>$batch,
            'state'=>1,
            'goods_time'=>strtotime($data['ck_time'])
        ]);
        echo db('tray')->getlastSql();
        db('tray_log')
        ->insert([
            'tp_num'=>$data['tp_num'],
            'goods_name'=>$data['goods_name'],
            'num'=>$data['num'],
            'batch'=>$batch,
            'state'=>0,
            'time'=>time()
        ]);
        
        dump($res);
        if($res){
            $this->success('成功');
        }else{
            $this->error('失败');
        }
    }
    /**
     * 托盘查看
     */
    public function show_tray(){
        $search = '';
        $data=input();
        if(!empty($data['name'])){
            $name=$data['name'];
            $search = 'warehouse.name like ' . "'%" . $name . '%' . "'";
        }else{
            $name='';
        }
        if(!empty($data['tp_num'])){
            $tp_num=$data['tp_num'];
            if (!empty($search)) {
                $search .= ' and tray.tp_num like ' . "'%" . $tp_num . '%' . "'";
            }else{
                $search = 'tray.tp_num like ' . "'%" . $tp_num . '%' . "'";
            }
        }else{
            $tp_num='';
        }
        if(!empty($data['batch'])){
            $batch=$data['batch'];
            if (!empty($search)) {
                $search .= ' and tray.batch like ' . "'%" . $batch . '%' . "'";
            }else{
                $search = 'tray.batch like ' . "'%" . $batch . '%' . "'";
            }
        }else{
            $batch='';
        }
        $res=db('tray')
        ->join('warehouse','warehouse.id=tray.warehouse_id','left')
        ->where('tray.is_del',0)
        ->where($search)
        ->where('state',1)
        ->field('tray.*,warehouse.name')
        ->select();
        $cks = db('warehouse')->where('is_del',1)->select();
        return view('show_tray',['res'=>$res,'cks'=>$cks,'name'=>$name,'tp_num'=>$tp_num,'batch',$batch]);
    }
    /**
     * 批次查看
     */
    public function tray_batch(){
        $id=$_POST['id'];
        $res=db('tray')
        ->where('id',$id)
        ->select();
        $data=explode(',',$res[0]['batch']);
        return $data;
    }
    /**
     * 托盘出库页
     */
    public function chuku(){
        $tp_num=input('tp_num');
        $res=db('tray')
        ->where('tray.tp_num',$tp_num)  
        ->join('warehouse','warehouse.id=tray.warehouse_id','left')
        ->where('tray.is_del',0)
        ->select();
        $batch=explode(',',$res[0]['batch']);
        return view('chuku',['res'=>$res,'tp_num'=>$tp_num,'batch'=>$batch]);
    }
    /**
     * 出库执行
     */
    public function tray_ck_insert(){
        $data=input();
        $res=db('tray')
        ->where('tp_num',$data['tp_num'])
        ->update([
            'order'=>$data['order'],
            'delivery'=>$data['delivery'],
            'state'=>0
        ]);
        db('tray_log')
        ->insert([
            'tp_num'=>$data['tp_num'],
            'goods_name'=>$data['goods_name'],
            'num'=>$data['num'],
            'order'=>$data['order'],
            'delivery'=>$data['delivery'],
            'batch'=>implode(',',$data['batch']),
            'state'=>1,
            'time'=>time(),
        ]);
        if($res){
            $this->success('成功');
        }else{
            $this->error('失败');
        }
    }
    /**
     * 批次日志
     */
    public function tray_log(){
        $search = '';
        $data=input();
        if(!empty($data['name'])){
            $name=$data['name'];
            $search = 'warehouse.name like ' . "'%" . $name . '%' . "'";
        }else{
            $name='';
        }
        if(!empty($data['tp_num'])){
            $tp_num=$data['tp_num'];
            if (!empty($search)) {
                $search .= ' and tray.tp_num like ' . "'%" . $tp_num . '%' . "'";
            }else{
                $search = 'tray.tp_num like ' . "'%" . $tp_num . '%' . "'";
            }
        }else{
            $tp_num='';
        }
        if(!empty($data['batch'])){
            $batch=$data['batch'];
            if (!empty($search)) {
                $search .= ' and tray.batch like ' . "'%" . $batch . '%' . "'";
            }else{
                $search = 'tray.batch like ' . "'%" . $batch . '%' . "'";
            }
        }else{
            $batch='';
        }
        $res=db('tray_log')
        ->join('tray','tray.tp_num=tray_log.tp_num','left')
        ->join('warehouse','warehouse.id=tray.warehouse_id','left')
        ->field('warehouse.name w_name,tray_log.id t_id,tray_log.tp_num tp_num,tray_log.batch t_batch,tray_log.time t_time,tray_log.goods_name t_goods_name,tray_log.order t_order,tray_log.delivery t_delivery,tray_log.id t_id,tray_log.state t_state,tray_log.num t_num')
        ->where($search)
        ->where('tray_log.is_del',0)
        ->select();
        $cks = db('warehouse')->where('is_del',1)->select();
        return view('tray_log',['res'=>$res,'cks'=>$cks,'name'=>$name,'tp_num'=>$tp_num,'batch',$batch]);
    }
    /**
     * 日志批次查看
     */
    public function tray_log_batch(){
        $id=$_POST['id'];
        $res=db('tray_log')
        ->where('id',$id)
        ->select();
        $data=explode(',',$res[0]['batch']);
        return $data;
    }
}
// ,'tp_num'=>$tp_num,'batch'=>$batch]