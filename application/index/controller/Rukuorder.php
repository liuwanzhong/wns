<?php

namespace app\index\controller;


use think\Controller;
use think\Db;

class Rukuorder extends Controller {
    //添加入库订单列表
    public function index(){
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告:越权操作');
        }
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
        foreach ($rows as $k=>$row) {
            if($row['countnum']!=0){
                $rows[$k]['m']=$row['Grossweight']/$row['countnum'];
                $rows[$k]['j']=$row['netweight']/$row['countnum'];
            }else{
                $rows[$k]['m']=$row['Grossweight'];
                $rows[$k]['j']=$row['netweight'];
            }
        }
        return view('index',['rows'=>$rows,'status'=>$status,'cks'=>$cks,'id'=>$cd]);
    }
    //货位
    public function show($id){
        $list = db('cabinet')
            ->where('cabinet.is_del',1)
            ->where('cabinet.warehouse_id',$id)
            ->select();
        foreach ($list as $k=>$item) {
            $rows=db('rukuform_xq')->where('is_del',0)->select();
            foreach ($rows as $row) {
                if($item['id']==$row['rk_huowei_id'] and $row['rk_nums']!=0){
                    unset($list[$k]);
                }
            }
        }
        $list=array_values($list);
        return $list;
    }
    //毛重净重计算
    public function blur() {
        $groos_min=input('groos_min');//毛重
        $min=input('min');//净重
        $max=input('max');//数量
        //净重
        $number=sprintf("%.3f",$min*$max);
        //毛重
        $groos=sprintf("%.3f",$groos_min*$max);
        return ['number'=>$number,'groos'=>$groos];
    }
    //添加入库订单
    public function insert(){
        $data = input();
        $userintime=strtotime($data['userintime']);
        try{
            $id = db('rukuform')->insertGetId(['shipmentnum'=>$data['shipmentnum'],'userintime'=>$userintime,'transport'=>$data['transport'],'carid'=>$data['carid'],'stevedore'=>$data['stevedore'],'ck_id'=>$data['ck_id']]);
            for ($i=0;$i<count($data['transfers_factory']);$i++){
                $t=strtotime($data['intime'][$i]);
                $rs = db('rukuform_xq')->insert(['factory'=>$data['transfers_factory'][$i],
                                                 'product_name'=>$data['material_name'][$i],
                                                 'rk_status_id'=>$data['status'][$i],
                                                 'rk_huowei_id'=>$data['huowei'][$i],
                                                 'rk_nums'=>$data['nums'][$i],
                                                 'product_time'=>$t,
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
            }
        } catch (\Exception $e) {
            $this->error('添加入库订单失败,请联系管理员');
            // 回滚事务
            Db::rollback();
        }
        $this->success('生成订单成功','index');
    }

    //入库计划
    public function to_examine() {
        $s_transfers_id=input('s_transfers_id');//工厂
        $s_delivery_time=input('s_delivery_time');//时间
        $s_material_name=input('s_material_name');//单号
        $search = '';
        if (!empty($s_transfers_id)) {
            $search = 'rukuform_xq.factory like ' . "'%" . $s_transfers_id . '%' . "'";
        }
        // 时间转换
        if (!empty($s_delivery_time)) {
            $time = explode('~', $s_delivery_time);
            foreach ($time as $key) {
                $time[] = strtotime($key);
                array_shift($time);
            }
            if (!empty($search)) {
                $time = ' and rukuform.userintime BETWEEN ' . $time['0'] . ' and ' . $time['1'];
            } else {
                $time = ' rukuform.userintime BETWEEN ' . $time['0'] . ' and ' . $time['1'];
            }
            $search .= $time;
        }
        // 单号
        if (!empty($s_material_name)) {
            $material_name = $s_material_name;
            if (!empty($search)) {
                $material_name = ' and rukuform.shipmentnum like ' . "'%" . $material_name . '%' . "'";
            } else {
                $material_name = ' rukuform.shipmentnum like ' . "'%" . $material_name . '%' . "'";
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
            ->paginate(20,false,['query'=>['s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name]]);
        return view('to_examine',['rows'=>$rows,'s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name]);
    }
    //订单详情
    public function to_examine_show($id) {
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告:越权操作');
        }
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
        $cabinet=db('cabinet')->where('is_del',1)->select();
//        if(!empty($rows['userintime'])){
//            $rows['userintime']=date("Y-m-d",$rows['userintime']);
//        }
        foreach ($cats as $k=>$row) {
            $cats[$k]['m']=$row['Grossweight']/$row['rk_nums'];
            $cats[$k]['j']=$row['netweight']/$row['rk_nums'];
        }
        return view('to_examine_show',['rows'=>$rows,'cats'=>$cats,'id'=>$id,'status'=>$status,'cks'=>$cks,'cabinet'=>$cabinet]);
    }
    //修改订单
    public function to_examine_up() {
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $data=input();
        $userintime=strtotime($data['userintime']);
        array_shift($data);
        $r = db('rukuform')
            -> where('id', $data['id'])
            -> update(['shipmentnum' => $data['shipmentnum'], 'userintime' => $userintime, 'transport' => $data['transport'], 'carid' => $data['carid'], 'stevedore' => $data['stevedore'], 'ck_id' => $data['ck_id']]);
        for ($i = 0; $i < count($data['transfers_factory']); $i++) {
            $t=strtotime($data['intime'][$i]);
            if (empty($data['cd'][$i])) {
                db('rukuform_xq') -> insert(['factory'       => $data['transfers_factory'][$i],
                                                 'product_name'  => $data['material_name'][$i],
                                                 'rk_status_id'  => $data['status'][$i],
                                                 'rk_huowei_id'  => $data['huowei'][$i],
                                                 'rk_nums'       => $data['nums'][$i],
                                                 'product_time'  => $t,
                                                 'product_batch' => $data['storno'][$i],
                                                 'content'       => $data['content'][$i],
                                                 'netweight'     => $data['netweight'][$i],
                                                 'Grossweight'   => $data['Grossweight'][$i],
                                                 'transfers_id'  => $data['transfers_id'][$i],
                                                 'rukuid'        => $data['id']]);
            }else{
                $rs = db('rukuform_xq') -> where('id', $data['cd'][$i])
                    -> update(['factory' => $data['transfers_factory'][$i],'product_name'=> $data['material_name'][$i], 'rk_status_id'=> $data['status'][$i], 'rk_huowei_id' => $data['huowei'][$i], 'rk_nums'=> $data['nums'][$i],'product_time'  => $t,'product_batch' => $data['storno'][$i], 'content' => $data['content'][$i], 'netweight'=> $data['netweight'][$i], 'Grossweight' => $data['Grossweight'][$i], 'transfers_id' => $data['transfers_id'][$i]]);
            }
        }
        if($r!==false  || $rs!==false){
            return redirect('to_examine');
        }else{
            $this->error('添加入库订单失败,请联系管理员');
        }
    }
    //审核
    public function to_examine_yes() {
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $id=input('id');
        $data=input();
        array_shift($data);
        if(empty($id)){
            $this->error('缺少必要参数,请重试');
        }
        $s=db('rukuform_xq')
                ->where('rukuid',$id)
                ->select();
        foreach ($s as $c){
            $jc='';
            $a=db('record')->where('huowei',$c['rk_huowei_id'])->order('id desc')->limit(1)->select();
            db('record')->insert(['rukuform_id'=>$c['id'],'time'=>$data['time'],'odd_number'=>$c['transfers_id'],'task'=>$data['task'],'customer'=>$data['customer'],'early_stage'=>0,'balance'=>$c['rk_nums'],'dh_ruku'=>$c['rk_nums'],'huowei'=>$c['rk_huowei_id']]);
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
            return redirect('to_examine');
        }else{
            $this->error('删除失败,请联系管理员');
        }
    }



    //入库台账
    public function warehousing() {
        $s_transfers_id=input('s_transfers_id');//工厂
        $s_delivery_time=input('s_delivery_time');//时间
        $s_material_name=input('s_material_name');//单号
        $search = '';
        if (!empty($s_transfers_id)) {
            $search = 'rukuform_xq.factory like ' . "'%" . $s_transfers_id . '%' . "'";
        }
        // 时间转换
        if (!empty($s_delivery_time)) {
            $time = explode('~', $s_delivery_time);
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
        if (!empty($s_material_name)) {
            $material_name = $s_material_name;
            if (!empty($search)) {
                $material_name = ' and rukuform.shipmentnum like ' . "'%" . $material_name . '%' . "'";
            } else {
                $material_name = ' rukuform.shipmentnum like ' . "'%" . $material_name . '%' . "'";
            }
            $search .= $material_name;
        }
        $rows=db('rukuform')
            ->join('warehouse','rukuform.ck_id=warehouse.id','left')
            ->join('rukuform_xq','rukuform.id=rukuform_xq.rukuid','left')
            ->where('rukuform.is_del',0)
            ->where('rukuform.state',1)
            ->group('rukuform_xq.factory,rukuform_xq.rukuid')
            ->where($search)
            ->field('rukuform.*,warehouse.name as w_name,rukuform_xq.factory as x_name,sum(rukuform_xq.rk_nums) as count')
            ->paginate(20,false,['query'=>['s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name]]);
        // var_dump($rows);exit;
        return view('warehousing',['rows'=>$rows,'s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name]);
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

    //入库明细
    public function detailed() {
        $s_transfers_id=input('s_transfers_id');//工厂
        $s_delivery_time=input('s_delivery_time');//时间
        $s_material_name=input('s_material_name');//产品属性
        $search = '';
        //工厂名
        if (!empty($s_transfers_id)) {
            $s_transfers_id=addslashes($s_transfers_id);
            $search = 'rukuform_xq.factory like ' . "'%" . $s_transfers_id . '%' . "'";
        }
        // 时间转换
        if (!empty($s_delivery_time)) {
            $time = explode('~', $s_delivery_time);
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
        // 产品属性
        if (!empty($s_material_name)) {
            $material_name = $s_material_name;
            if (!empty($search)) {
                $material_name = ' and rukuform_xq.rk_status_id like ' . "'%" . $material_name . '%' . "'";
            } else {
                $material_name = ' rukuform_xq.rk_status_id like ' . "'%" . $material_name . '%' . "'";
            }
            $search .= $material_name;
        }
        $rows=db('rukuform_xq')
            ->join('kc_status','rukuform_xq.rk_status_id=kc_status.id','left')
            ->join('cabinet','rukuform_xq.rk_huowei_id=cabinet.id','left')
            ->join('rukuform','rukuform_xq.rukuid=rukuform.id','left')
            ->join('warehouse','rukuform.ck_id=warehouse.id','left')
            ->where('rukuform_xq.is_del',0)
            ->where('rukuform_xq.state',1)
            ->where("$search")
            ->field('rukuform_xq.*,kc_status.title as k_name,cabinet.name as c_name,warehouse.name as w_name,rukuform.userintime as time')
            ->paginate(100,false,['query'=>['s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name]]);
        //产品属性
        $status=db('kc_status')->where('is_del',0)->select();
//        $a = $this->request->action();
//        echo $a;exit;
        return view('detailed',['rows'=>$rows,'status'=>$status,'s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name]);
    }
    //导出入库明细
    public function outExcel(){
        $data = input();
        unset($data['/index/rukuorder/outexcel_html']);
        $id=$data['id'];
        $da=str_replace('"', '', $id);
        $da=trim($da,'[');
        $da=trim($da,']');
        $data=db('rukuform_xq')->where("id in ($da)")->select();
        $data=db('rukuform_xq')
            ->join('kc_status','rukuform_xq.rk_status_id=kc_status.id','left')
            ->join('cabinet','rukuform_xq.rk_huowei_id=cabinet.id','left')
            ->join('rukuform','rukuform_xq.rukuid=rukuform.id','left')
            ->join('warehouse','rukuform.ck_id=warehouse.id','left')
            ->where("rukuform_xq.id in ($da)")
            ->field('rukuform_xq.*,kc_status.title as k_name,cabinet.name as c_name,warehouse.name as w_name,rukuform.userintime as time')
            ->select();
        if(!empty($data)){
            Vendor('PHPExcel.PHPExcel');
            Vendor('PHPExcel.PHPExcel.IOFactory');
            $phpExcel = new \PHPExcel();
            $phpExcel->setActiveSheetIndex(0)
                ->setCellValue('A1', '工厂')
                ->setCellValue('B1', '产品名')
                ->setCellValue('C1', '产品属性')
                ->setCellValue('D1', '入库仓库')
                ->setCellValue('E1', '入库货位')
                ->setCellValue('F1', '入库时间')
                ->setCellValue('G1', '产品时间')
                ->setCellValue('H1', '数量')
                ->setCellValue('I1', '净重')
                ->setCellValue('J1', '毛重');
            $len = count($data);
            for($i = 0 ; $i < $len ; $i++){
                $v = $data[$i];
                $rownum = $i+2;
                $phpExcel->getActiveSheet()->setCellValue('A' . $rownum, $v['factory']);
                $phpExcel->getActiveSheet()->setCellValue('B' . $rownum, $v['product_name']);
                $phpExcel->getActiveSheet()->setCellValue('C' . $rownum, $v['k_name']);
                $phpExcel->getActiveSheet()->setCellValue('D' . $rownum, $v['w_name']);
                $phpExcel->getActiveSheet()->setCellValue('E' . $rownum, $v['c_name']);
                $phpExcel->getActiveSheet()->setCellValue('F' . $rownum, date('Y-m-d',$v['time']));
                $phpExcel->getActiveSheet()->setCellValue('G' . $rownum, $v['product_time']);
                $phpExcel->getActiveSheet()->setCellValue('H' . $rownum, $v['rk_nums']);
                $phpExcel->getActiveSheet()->setCellValue('I' . $rownum, $v['netweight']);
                $phpExcel->getActiveSheet()->setCellValue('J' . $rownum, $v['Grossweight']);
            }
            $phpExcel->setActiveSheetIndex(0);
            $filename=date('Y-m-d',time()).'.xlsx';
            $objWriter=\PHPExcel_IOFactory::createWriter($phpExcel,'Excel2007');
            $filePath =$filename;
            $objWriter->save($filePath);
            if(!file_exists($filePath)){
                $response = array(
                    'status' => 'false',
                    'url' => '',
                    'token'=>''
                );
            }else{
                $response = array(
                    'status' => true,
                    'url' => $filename,
                    'token'=>$this->getDownLoadToken($filename)
                );
            }
        }else{
            $response = array(
                'status' => 'false',
                'url' => '',
                'token'=>''
            );
        }
        exit(json_encode($response));
    }
    private function getDownLoadToken($filename,$length = 10){
        $str = null;
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($strPol)-1;

        for($i=0;$i<$length;$i++){
            $str.=$strPol[rand(0,$max)];//rand($min,$max)生成介于min和max两个数之间的一个随机整数
        }
        $res = md5($str.time());
        return $res;
    }
    public function download(){
        $fileName = date('Y-m-d',time()).'.xlsx';
        $path = ROOT_PATH."\public/".$fileName;
        if(!file_exists($path)){
            header("HTTP/1.0 404 Not Found");
            exit;
        }else{
            $file = @fopen($path,"r");
            if(!$file){
                header("HTTP/1.0 505 Internal server error");
                exit;
            }
            header("Content-type: application/octet-stream");
            header("Accept-Ranges: bytes");
            header("Accept-Length: ".filesize($path));
            header("Content-Disposition: attachment; filename=" . $fileName);
            while(!feof($file)){
                echo fread($file,2048);
            }
            fclose($file);
           unlink($path);
            exit();
        }
    }
}
