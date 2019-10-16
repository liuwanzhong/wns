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
        // Cookie::clear('think_');
        // $warehouse=self::$stafss['warehouse'];
        // $cks = db('warehouse')->where('is_del',1)->where('id','in',$warehouse)->select();
        // Cookie(null,'think_');
        // return view('index',['cks'=>$cks]);
        $warehouse=self::$stafss['warehouse'];
        $s_transfers_id=input('s_transfers_id');
        $s_delivery_time=input('s_delivery_time');
        $s_material_name=input('s_material_name');
        $search = '';
        // 出库货物
        if (!empty($s_transfers_id)) {
            $search = 'warehouse.name like ' . "'%" . $s_transfers_id . '%' . "'";
        }
        // 时间转换
        if (!empty($s_delivery_time)) {
            $time = explode('~', $s_delivery_time);
            foreach ($time as $key) {
                $time[] = strtotime($key);
                array_shift($time);
            }
            if (!empty($search)) {
                $time = ' and outbound_from.ck_time BETWEEN ' . $time['0'] . ' and ' . $time['1'];
            } else {
                $time = 'outbound_from.ck_time BETWEEN ' . $time['0'] . ' and ' . $time['1'];
            }
            $search .= $time;
        }
        // 订单号
        if (!empty($s_material_name)) {
            $material_name = $s_material_name;
            if (!empty($search)) {
                $material_name = ' and outbound_from.transport_id like ' . "'%" . $material_name . '%' . "'";
            } else {
                $material_name = ' outbound_from.transport_id like ' . "'%" . $material_name . '%' . "'";
            }
            $search .= $material_name;
        }
        $rows=db('outbound_from')
            ->join('warehouse','outbound_from.ck_id=warehouse.id','left')
            ->join('outbound_xq_from','outbound_from.id=outbound_xq_from.chukuid','left')
            ->join('staffs','outbound_from.staffs_id=staffs.id','left')
            ->where('warehouse.id','in',$warehouse)
            ->where("outbound_from.ck_sh = 0 ")
            ->where('outbound_from.is_del',0)
            ->where('outbound_from.state',1)
            ->where('outbound_xq_from.qt_ck',0)
            ->group('outbound_from.id')
            ->where($search)
            ->field('outbound_from.*,warehouse.name as w_name,outbound_xq_from.product_name as x_name,outbound_xq_from.delivery_id,staffs.staffs_name')
            ->paginate(100,false,['query'=>['s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name]]);
        $cks=db('warehouse')->where('is_del',1)->where('id','in',$warehouse)->select();
        return view('index',['rows'=>$rows,'cks'=>$cks,'s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name]);
    }
    //详细
    public function warehousing_show($id) {
        $num=0;
        $z=0;
        $rows=db('outbound_from')
            ->join('warehouse','outbound_from.ck_id=warehouse.id','left')
            ->join('warker','outbound_from.workers=warker.id','left')
            ->where('outbound_from.is_del',0)
            ->where('outbound_from.id',$id)
            ->field('outbound_from.*,warehouse.name as w_name,warker.name as workers')
            ->find();
        $cats=db('outbound_xq_from')
            ->where('outbound_xq_from.chukuid',$rows['id'])
            ->join('cabinet','cabinet.id=outbound_xq_from.ck_huowei_id','left')
            ->field('outbound_xq_from.*,cabinet.name as c_name')
            ->select();
        $cks = db('warehouse')->where('is_del',1)->select();
        $cabinet=db('cabinet')->where('is_del',1)->select();
       if(!empty($rows['userintime'])){
           $rows['userintime']=date("Y-m-d",$rows['userintime']);
       }
        foreach ($cats as $k=>$row) {
            if($row['netweight']!=0 &&$row['ck_nums']!=0){
                $cats[$k]['j']=$row['netweight']/$row['ck_nums'];
            }else{
                $cats[$k]['j']=0;
            }
        }
        foreach ($cats as $cat) {
            $num+=$cat['ck_nums'];
            $z+=$cat['netweight'];
        }
        return view('warehousing_show',['rows'=>$rows,'cats'=>$cats,'id'=>$id,'cks'=>$cks,'cabinet'=>$cabinet,'num'=>$num,'z'=>$z]);
    }
    //删除
    public function warehousing_del() {
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
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
    /**
     * 扫码出库
     */
    public function create_saoyisao(){
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $md=[];
        $tp_num=input('tp_num');



        if(!empty(input('id'))){
            $id=input('id');
            $rows=db('outbound_from')
            ->join('warehouse','outbound_from.ck_id=warehouse.id','left')
            ->join('outbound_xq_from','outbound_from.id=outbound_xq_from.chukuid','left')
            ->join('staffs','outbound_from.staffs_id=staffs.id','left')
            ->where('outbound_from.id',$id)
            ->where('outbound_from.is_del',0)
            ->where('outbound_from.state',1)
            ->where('outbound_xq_from.qt_ck',0)
            ->group('outbound_from.id')
            ->field('outbound_from.*,warehouse.name as w_name,outbound_xq_from.product_name as x_name,outbound_xq_from.delivery_id,staffs.staffs_name')
            ->select();    
            $res=db('tray_log')
            ->where('ru_from_id',$id)
            ->select();
            foreach($res as $v){
                $tp_num1=$v['tp_num'].',';
                $tp_num1=trim($tp_num1,',');
                Cookie::set('tp_num',$tp_num1,['prefix'=>'think_']);
            }
            $data['staffs_name']=$rows[0]['staffs_name'];//保管名
            Cookie::set('staffs_name',$data['staffs_name'],['prefix'=>'think_']);

            $data['w_name']=$rows[0]['w_name'];//出库仓库
            Cookie::set('w_name',$data['w_name'],['prefix'=>'think_']);

            $data['transport_id']=$rows[0]['transport_id'];//装运单号
            Cookie::set('transport_id',$data['transport_id'],['prefix'=>'think_']);

            $data['reachout_name']=$rows[0]['reachout_name'];//送达方
            Cookie::set('reachout_name',$data['reachout_name'],['prefix'=>'think_']);

            $data['ck_time']=$rows[0]['ck_time'];//出库日期
            Cookie::set('ck_time',$data['ck_time'],['prefix'=>'think_']);

            $data['total_shu']=$rows[0]['total_shu'];//总数量
            Cookie::set('total_shu',$data['total_shu'],['prefix'=>'think_']);

            $data['total_zhong']=$rows[0]['total_zhong'];//总重量
            Cookie::set('total_zhong',$data['total_zhong'],['prefix'=>'think_']);

            $data['id']=$id;
            Cookie::set('id',$data['id'],['prefix'=>'think_']);
        }
        $cats=db('outbound_xq_from')
            ->where('outbound_xq_from.chukuid',Cookie::get('id','think_'))
            ->join('cabinet','cabinet.id=outbound_xq_from.ck_huowei_id','left')
            ->field('outbound_xq_from.*,cabinet.name as c_name')
            ->select();
        
        
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
        $s=trim($s,',');
        if(isset($s) && strstr($s,',')){
            $s=explode(',',$s);
            foreach($s as $v){
                $xx[]=db('tray')
                ->where('tp_num',$v)
                ->select();
            }
        }else{
            $s=explode(',',$s);
            foreach($s as $v){
                $xx[]=db('tray')
                ->where('tp_num',$v)
                ->select();
            }
        }
        return view('create_saoyisao',['xx'=>$xx,'cats'=>$cats,'tp_num'=>$tp_num,'staffs_name'=>Cookie::get('staffs_name','think_'),'w_name'=>Cookie::get('w_name','think_'),'transport_id'=>Cookie::get('transport_id','think_'),'reachout_name'=>Cookie::get('reachout_name','think_'),'ck_time'=>Cookie::get('ck_time','think_'),'total_shu'=>Cookie::get('total_shu','think_'),'total_zhong'=>Cookie::get('total_zhong','think_'),'id'=>Cookie::get('id','think_')]);
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
        dump($data);exit;
        $log_id='';
        $tp_num=implode(',',$data['tp_num']);
        foreach($data['tp_num'] as $v){
        db('tray_order')
        ->where('tp_num',$v)
        ->where('ru_from_id',$data[0]['ru_from_id'])
        ->delete();
            $res=db('tray')
            ->where('tp_num',$v)
            ->update([
                'delivery'=>$data['reachout_name'],
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
                'ru_from_id'=>$data['id'],
                'sh'=>0,
            ]);
            $log_id.=$id.',';
        }
        db('outbound_from')
        ->where('id',$data['id'])
        ->update([
            'ck_sh'=>1,
        ]);
        $res=db('tray_order')
        ->insert([
            'transport_id'=>$data['transport_id'],
            'delivery'=>$data['reachout_name'],
            // 'delivery_name'=>$data['delivery_name'],
            'log_id'=>$log_id,
            'create_time'=>time(),
        ]);
        return redirect('index');
    }
    /**
     * 出库审核
     */
    public function shenghe(){
        $warehouse=self::$stafss['warehouse'];
        $s_transfers_id=input('s_transfers_id');
        $s_delivery_time=input('s_delivery_time');
        $s_material_name=input('s_material_name');
        $rows=db('outbound_from')
            ->join('warehouse','outbound_from.ck_id=warehouse.id','left')
            ->join('outbound_xq_from','outbound_from.id=outbound_xq_from.chukuid','left')
            ->join('staffs','outbound_from.staffs_id=staffs.id','left')
            ->where("outbound_from.ck_sh = 1")
            ->where('outbound_from.is_del',0)
            ->where('outbound_from.state',1)
            ->where('outbound_xq_from.qt_ck',0)
            ->group('outbound_from.id')
            ->field('outbound_from.*,warehouse.name as w_name,outbound_xq_from.product_name as x_name,outbound_xq_from.delivery_id,staffs.staffs_name')
            ->paginate(100,false,['query'=>['s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name]]);
            // ->paginate(100,false,['query'=>['s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name]]);
        $cks=db('warehouse')->where('is_del',1)->where('id','in',$warehouse)->select();
        return view('ck_sh',['rows'=>$rows,'cks'=>$cks,'s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name]);
    }
    /**
     * 执行审核
     */
    public function do_shenhe($id){
        $data=input();
        dump($data);
        $res=db('outbound_from')
        ->where('id',$id)
        ->update([
            'ck_sh'=>2,
        ]);
        if($res){
            return redirect('out_log');
        }else{
            return error('审核失败请联系管理员');
        }
    }
    /**
     * 往期出库
     */
    public function out_log(){
        $warehouse=self::$stafss['warehouse'];
        $s_transfers_id=input('s_transfers_id');
        $s_delivery_time=input('s_delivery_time');
        $s_material_name=input('s_material_name');
        $rows=db('outbound_from')
            ->join('warehouse','outbound_from.ck_id=warehouse.id','left')
            ->join('outbound_xq_from','outbound_from.id=outbound_xq_from.chukuid','left')
            ->join('staffs','outbound_from.staffs_id=staffs.id','left')
            ->where("outbound_from.ck_sh = 2")
            ->where('outbound_from.is_del',0)
            ->where('outbound_from.state',1)
            ->where('outbound_xq_from.qt_ck',0)
            ->group('outbound_from.id')
            ->field('outbound_from.*,warehouse.name as w_name,outbound_xq_from.product_name as x_name,outbound_xq_from.delivery_id,staffs.staffs_name')
            ->paginate(100,false,['query'=>['s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name]]);
        // dump($rows);exit;
        $cks=db('warehouse')->where('is_del',1)->where('id','in',$warehouse)->select();
        return view('out_log',['rows'=>$rows,'cks'=>$cks,'s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name]);
    }
    /**
     * 往期出库详细
     */
    public function sh_xx(){
        $id=input('id');
        $rows=db('outbound_from')
        ->join('warehouse','outbound_from.ck_id=warehouse.id','left')
        ->join('outbound_xq_from','outbound_from.id=outbound_xq_from.chukuid','left')
        ->join('staffs','outbound_from.staffs_id=staffs.id','left')
        ->where("outbound_from.ck_sh = 2")
        ->where('outbound_from.is_del',0)
        ->where('outbound_from.state',1)
        ->where('outbound_xq_from.qt_ck',0)
        ->group('outbound_from.id')
        ->field('outbound_from.*,warehouse.name as w_name,outbound_xq_from.product_name as x_name,outbound_xq_from.delivery_id,staffs.staffs_name')
        ->select();
        $xx=db('tray_log')
        ->where('ru_from_id',$id)
        ->select();
        $cks=db('warehouse')->where('is_del',1)->where('id',$rows[0]['ck_id'])->select();
        return view('sh_xx',['rows'=>$rows,'cks'=>$cks,'xx'=>$xx]);
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
     * 往期出库删除
     */
    public function tray_log_del(){
        $ms=$this->qx();
        if($ms==0){
            return 0;
        }
        $id=input('id');
        $res=db('tray_order')
        ->where('id',$id)
        ->update([
            'is_del'=>1
        ]);
        return $res;
    }
    /**
     * 入库扫码页
     */
    public function rk_saoma(){
        return view();
    }
}
