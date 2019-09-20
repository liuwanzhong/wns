<?php


namespace app\index\controller;

use think\Controller;
use think\Db;

class Table extends Controller {
    static public $sy;
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
        $list = db('cabinet')
            ->where('cabinet.is_del',1)
            ->where('cabinet.warehouse_id',$id)->select();
        return json_encode($list);
    }

    public function houwei_ck() {
        $sy=isset($_POST['sy']) ? $_POST['sy'] : 0;
        $id=input('id');
        static $md=[];
        $ck=db('cabinet')->where('warehouse_id',$id)->where('is_del',1)->select();
        foreach ($ck as $c) {
            $rukuform=db('rukuform_xq')
                ->where('rukuform_xq.state',1)
                ->where('rukuform_xq.sy_count>=1')
                ->join('cabinet','cabinet.id=rukuform_xq.rk_huowei_id')
                ->field('rukuform_xq.*,cabinet.name')
                ->select();
            foreach ($rukuform as $k=>$item) {
                if($c['id']==$item['rk_huowei_id']){
                    $md[]=$item;
                }
            }
        }
        foreach ($md as $k=>$row) {
            $md[$k]['m']=$row['Grossweight']/$row['rk_nums'];
            $md[$k]['j']=$row['netweight']/$row['rk_nums'];
            $md[$k]['sy']=$row['rk_nums']-$sy;
        }
        return $md;
    }
    public function houwei_cd() {
        $data=input();
        array_shift($data);
        $mj=$this->blur($data['m'],$data['j'],$data['count']);
        $row=db('rukuform_xq')->where('id',$data['id'])->find();
        db('rukuform_xq')->where('id',$data['id'])->update(['sy_count'=>$row['sy_count']-$data['count']]);
        $row=db('rukuform_xq')->where('id',$data['id'])->find();
        $mj['sy']=$row['sy_count'];
        return $mj;
    }


    public function blur($groos_min,$min,$max) {
        //净重
        $number=sprintf("%.3f",$min*$max);
        //毛重
        $groos=sprintf("%.3f",$groos_min*$max);
        return ['m'=>$number,'j'=>$groos];
    }
}
