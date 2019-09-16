<?php

namespace app\index\controller;


use think\Controller;
use think\Db;

class Rukuorder extends Controller {
    //添加入库订单列表
    public function index(){
        $cd=input('id');
        $id=str_replace(array("[","]","\""),"",$cd);
        $id=explode(',',$id);
        $rows = db('cw_management')->field('id,transfers_id,delivery_time,transfers_factory,material_name,production_time,sum(Bring_up_num) as countnum,sum(Bring_up_Gross_weight) as Grossweight,sum(Bring_up_net_weight) as netweight')
            ->where('is_del',0)->where('id','in',$id)
            ->group('material_name')->select();
        //入库状态
        $status = db('kc_status')->where('is_del',0)->select();
        //仓库列表
        $cks = db('warehouse')->where('is_del',1)->select();
        $cd=str_replace(array("\",\""),",",$cd);
        $cd=str_replace(array("[\""),"",$cd);
        $cd=str_replace(array("\"]"),"",$cd);
        return view('index',['rows'=>$rows,'status'=>$status,'cks'=>$cks,'id'=>$cd]);
    }
    //毛重净重计算
    public function blur() {
        $groos_min=input('groos_min');
        $min=input('min');
        $max=input('max');
        //净重
        $number=floor($min*$max)/1000;
        //毛重
        $groos=floor($groos_min*$max)/1000;
        return ['number'=>$number,'groos'=>$groos];
    }
    //添加入库订单
    public function insert(){
        $data = input();
        $userintime=strtotime($data['userintime']);
        try{
            $id = db('rukuform')->insertGetId(['shipmentnum'=>$data['shipmentnum'],'userintime'=>$userintime,'transport'=>$data['transport'],'carid'=>$data['carid'],'stevedore'=>$data['stevedore'],'ck_id'=>$data['ck_id']]);
            for ($i=0;$i<count($data['transfers_factory']);$i++){
                $rs = db('rukuform_xq')->insert(['factory'=>$data['transfers_factory'][$i],
                    'product_name'=>$data['material_name'][$i],
                    'rk_status_id'=>$data['status'][$i],
                    'rk_huowei_id'=>$data['huowei'][$i],
                    'rk_nums'=>$data['nums'][$i],
                    'product_time'=>$data['intime'][$i],
                    'product_batch'=>$data['storno'][$i],
                    'content'=>$data['content'][$i],
                    'netweight'=>$data['netweight'][$i],
                    'Grossweight'=>$data['Grossweight'][$i],
                    'transfers_id'=>$data['transfers_id'][$i],
                    'rukuid'=>$id]);
            }
            $del=db('cw_management')->where('id','in',$data['cd'])->update(['is_del'=>1]);
            if($id && $rs && $del) {
                // 提交事务
                Db::commit();
                return redirect('index');
            }
        } catch (\Exception $e) {
            $this->error('添加入库订单失败,请联系管理员');
            // 回滚事务
            Db::rollback();
        }
    }




    //入库计划
    public function to_examine() {
        $data=input();
        $search = '';
        if (!empty($data['s_transfers_id'])) {
            $search = 'rukuform_xq.factory like ' . "'%" . $data['s_transfers_id'] . '%' . "'";
        }
        // 时间转换
        if (!empty($data['s_delivery_time'])) {
            $time = explode('~', $data['s_delivery_time']);
            foreach ($time as $key) {
                $time[] = strtotime($key);
                array_shift($time);
            }
            if (!empty($search)) {
                $time = ' and rukuform.userintime BETWEEN ' . $time['0'] . ' and ' . $time['1'];
            } else {
                $time = 'rukuform.userintime BETWEEN ' . $time['0'] . ' and ' . $time['1'];
            }
            $search .= $time;
        }
        // 物料名
        if (!empty($data['s_material_name'])) {
            $material_name = $data['s_material_name'];
            if (!empty($search)) {
                $material_name = ' and rukuform_xq.transfers_id like ' . "'%" . $material_name . '%' . "'";
            } else {
                $material_name = ' rukuform_xq.transfers_id like ' . "'%" . $material_name . '%' . "'";
            }
            $search .= $material_name;
        }
        $rows=db('rukuform')
            ->join('warehouse','rukuform.ck_id=warehouse.id','left')
            ->join('rukuform_xq','rukuform.id=rukuform_xq.rukuid','left')
            ->where('rukuform.is_del',0)
            ->where('rukuform.state',0)
            ->group('rukuform_xq.factory,rukuform_xq.rukuid')
            ->where($search)
            ->field('rukuform.*,warehouse.name as w_name,rukuform_xq.factory as x_name,sum(rukuform_xq.rk_nums) as count')
            ->paginate(100);
        return view('to_examine',['rows'=>$rows]);
    }
    //订单详情
    public function to_examine_show($id) {
        $rows=db('rukuform')
            ->join('warehouse','rukuform.ck_id=warehouse.id','left')
            ->where('rukuform.is_del',0)
            ->where('rukuform.id',$id)
            ->field('rukuform.*,warehouse.name as w_name')
            ->find();
        $cats=db('rukuform_xq')
            ->where('rukuform_xq.rukuid',$rows['id'])
            ->join('cabinet','cabinet.id=rukuform_xq.rk_huowei_id','left')
            ->join('kc_status','kc_status.id=rukuform_xq.rk_status_id','left')
            ->field('rukuform_xq.*,kc_status.title as w_name,cabinet.name as c_name')
            ->select();
        $cks = db('warehouse')->where('is_del',1)->select();
        $status=db('kc_status')->where('is_del',0)->select();
        dump($cats);
        return view('to_examine_show',['rows'=>$rows,'cats'=>$cats,'id'=>$id,'status'=>$status,'cks'=>$cks]);
    }
    //修改订单
    public function to_examine_up() {
        $data=input();
        $userintime=strtotime($data['userintime']);
//        dump($data);
        array_shift($data);
        try{
            $r=db('rukuform')
                ->where('id',$data['id'])
                ->update(['shipmentnum'=>$data['shipmentnum'],'userintime'=>$userintime,'transport'=>$data['transport'],'carid'=>$data['carid'],'stevedore'=>$data['stevedore'],'ck_id'=>$data['ck_id']]);
            for ($i=0;$i<count($data['transfers_factory']);$i++){
                $rs = db('rukuform_xq')->where('id',$data['cd'][$i])->update(['factory'=>$data['transfers_factory'][$i],
                                                 'product_name'=>$data['material_name'][$i],
                                                 'rk_status_id'=>$data['status'][$i],
                                                 'rk_huowei_id'=>$data['huowei'][$i],
                                                 'rk_nums'=>$data['nums'][$i],
                                                 'product_time'=>$data['intime'][$i],
                                                 'product_batch'=>$data['storno'][$i],
                                                 'content'=>$data['content'][$i],
                                                 'netweight'=>$data['netweight'][$i],
                                                 'Grossweight'=>$data['Grossweight'][$i],
                                                 'transfers_id'=>$data['transfers_id'][$i]]);
            }
            if($r && $rs) {
                // 提交事务
                Db::commit();
                $this->error('操作成功','to_examine');
            }
        } catch (\Exception $e) {
            $this->error('添加入库订单失败,请联系管理员');
            // 回滚事务
            Db::rollback();
        }
        return redirect('to_examine');
    }
    //审核
    public function to_examine_yes() {
        $id=input('id');
        if(empty($id)){
            $this->error('缺少必要参数,请重试');
        }
        try{
            $r=db('rukuform')->where('id',$id)->update(['state'=>1]);
            $s=db('rukuform_xq')->where('rukuid',$id)->update(['state'=>1]);
            if($r && $s) {
                return redirect('to_examine');
                Db::commit();
            }
        } catch (\Exception $e) {
            $this->error('审核失败,请联系管理员');
            // 回滚事务
            Db::rollback();
        }
    }
    //删除
    public function to_examine_del() {
        $id=input('id');
        if(empty($id)){
            $this->error('缺少必要参数,请重试');
        }
        $r=db('rukuform')->where('id',$id)->update(['is_del'=>1]);
        if($r!==false){
            return redirect('to_examine');
        }else{
            $this->error('删除失败,请联系管理员');
        }
    }



    //入库台账
    public function warehousing() {
        $data=input();
        $search = '';
        if (!empty($data['s_transfers_id'])) {
            $search = 'rukuform_xq.factory like ' . "'%" . $data['s_transfers_id'] . '%' . "'";
        }
        // 时间转换
        if (!empty($data['s_delivery_time'])) {
            $time = explode('~', $data['s_delivery_time']);
            foreach ($time as $key) {
                $time[] = strtotime($key);
                array_shift($time);
            }
            if (!empty($search)) {
                $time = ' and rukuform.userintime BETWEEN ' . $time['0'] . ' and ' . $time['1'];
            } else {
                $time = 'rukuform.userintime BETWEEN ' . $time['0'] . ' and ' . $time['1'];
            }
            $search .= $time;
        }
        // 物料名
        if (!empty($data['s_material_name'])) {
            $material_name = $data['s_material_name'];
            if (!empty($search)) {
                $material_name = ' and rukuform_xq.transfers_id like ' . "'%" . $material_name . '%' . "'";
            } else {
                $material_name = ' rukuform_xq.transfers_id like ' . "'%" . $material_name . '%' . "'";
            }
            $search .= $material_name;
        }
        $rows=db('rukuform')
            ->join('warehouse','rukuform.ck_id=warehouse.id')
            ->join('rukuform_xq','rukuform.id=rukuform_xq.rukuid')
            ->where('rukuform.is_del',0)
            ->where('rukuform.state',1)
            ->group('rukuform_xq.factory,rukuform_xq.rukuid')
            ->where($search)
            ->field('rukuform.*,warehouse.name as w_name,rukuform_xq.factory as x_name,sum(rukuform_xq.rk_nums) as count')
            ->paginate(100);
        return view('warehousing',['rows'=>$rows]);
    }
    //订单详情
    public function warehousing_show($id) {
        $rows=db('rukuform')
            ->join('warehouse','rukuform.ck_id=warehouse.id')
            ->where('rukuform.is_del',0)
            ->where('rukuform.id',$id)
            ->field('rukuform.*,warehouse.name as w_name')
            ->find();
        $cats=db('rukuform_xq')
            ->where('rukuform_xq.rukuid',$rows['id'])
            ->join('cabinet','cabinet.id=rukuform_xq.rk_huowei_id')
            ->join('kc_status','kc_status.id=rukuform_xq.rk_status_id')
            ->field('rukuform_xq.*,kc_status.title as w_name,cabinet.name as c_name')
            ->select();
        return view('warehousing_show',['rows'=>$rows,'cats'=>$cats,'id'=>$id]);
    }
    //删除
    public function warehousing_del() {
        $id=input('id');
        if(empty($id)){
            $this->error('缺少必要参数,请重试');
        }
        $r=db('rukuform')->where('id',$id)->update(['is_del'=>1]);
        if($r!==false){
            return redirect('warehousing');
        }else{
            $this->error('删除失败,请联系管理员');
        }
    }

    //入库明细
    public function detailed() {
        $rows=db('rukuform_xq')
            ->join('kc_status','rukuform_xq.rk_status_id=kc_status.id')
            ->join('cabinet','rukuform_xq.rk_huowei_id=cabinet.id')
            ->join('rukuform','rukuform_xq.rukuid=rukuform.id')
            ->join('warehouse','rukuform.ck_id=warehouse.id')
            ->where('rukuform_xq.is_del',0)
            ->where('rukuform_xq.state',1)
            ->field('rukuform_xq.*,kc_status.title as k_name,cabinet.name as c_name,warehouse.name as w_name,rukuform.userintime as time')
            ->select();
        //产品属性
        $status=db('kc_status')->where('is_del',0)->select();
        return view('detailed',['rows'=>$rows,'status'=>$status]);
    }
}
