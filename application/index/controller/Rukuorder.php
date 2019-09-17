<?php

namespace app\index\controller;


use think\Controller;
use think\Db;

class Rukuorder extends Controller {
    public function index(){
        $id=input('id');
        $id=str_replace(array("[","]","\""),"",$id);
        $id=explode(',',$id);
        $rows = db('cw_management')->field('id,transfers_id,delivery_time,transfers_factory,material_name,production_time,sum(Bring_up_num) as countnum,sum(Bring_up_Gross_weight) as Grossweight,sum(Bring_up_net_weight) as netweight')
            ->where('is_del',0)->where('id','in',$id)
            ->group('material_name')->select();
        //入库状态
        $status = db('kc_status')->where('is_del',0)->select();
        //仓库列表
        $cks = db('warehouse')->where('is_del',1)->select();
        return view('index',['rows'=>$rows,'status'=>$status,'cks'=>$cks,'id'=>$id]);
    }

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
    public function insert(){
        $data = input();
        dump($data);
        Db::startTrans();
//        foreach($data['transfers_factory'] as $k=>$v){
//            $rs = db('rukuform_201909')->insert(['factory'=>$v,
//                'product_name'=>$data['material_name'][$k],
//                'rk_status_id'=>$data['status'][$k],
//                'rk_huowei_id'=>$data['huowei'][$k],
//                'rk_nums'=>$data['nums'][$k],
//                'product_time'=>$data['intime'][$k],
//                'product_batch'=>$data['storno'][$k],
//                'content'=>$data['content'][$k],
//                'netweight'=>$data['netweight'][$k],
//                'Grossweight'=>$data['Grossweight'][$k],
//                'transfers_id'=>$data['transfers_id'][$k],
//                'rukuid',$id]);
//        }
//        if($rs){
//            echo '提交成功';
//        }else{
//            echo '提交失败';
//        }
//        exit;
//        $id = 1;
//        for ($i=0;$i<count($data['transfers_factory'])-1;$i++){
//            $rs = db('rukuform_xq')->insert(['factory'=>$data['transfers_factory'][$i],
//                'product_name'=>$data['material_name'][$i],
//                'rk_status_id'=>$data['status'][$i],
//                'rk_huowei_id'=>$data['huowei'][$i],
//                'rk_nums'=>$data['nums'][$i],
//                'product_time'=>$data['intime'][$i],
//                'product_batch'=>$data['storno'][$i],
//                'content'=>$data['content'][$i],
//                'netweight'=>$data['netweight'][$i],
//                'Grossweight'=>$data['Grossweight'][$i],
//                'transfers_id'=>$data['transfers_id'][$i],
//                'rukuid'=>$id]);
//        }
//        if($id && $rs){
//            echo '1';
//        }else{
//            echo '2';
//        }
//        exit;
        try{
            $id = db('rukuform')->insertGetId(['shipmentnum'=>$data['shipmentnum'],'userintime'=>$data['userintime'],'transport'=>$data['transport'],'carid'=>$data['carid'],'stevedore'=>$data['stevedore'],'ck_id'=>$data['ck_id']]);
            for ($i=0;$i<count($data['transfers_factory']);$i++){
            $rs = db('rukuform_xq')
                ->insert([
                'factory'=>$data['transfers_factory'][$i],
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
            if($id && $rs) {
                echo '提交成功';
                // 提交事务
                Db::commit();
            }
        } catch (\Exception $e) {
            echo '提交失败';
            // 回滚事务
            Db::rollback();
        }
    }
}
