<?php
// 出库管理
namespace app\index\controller;


use think\Controller;
use think\Db;
use think\Request;
use think\Loader;
class Outbound extends Controller {
    // 显示页
    public function index(){
        $list = db('system_order')
            // -> where("$search")
            -> order('id desc')
            -> paginate(100);
        return view("index", ['list' => $list]);
    }
    // 系统订单
    public function system_order(){

    }
    // 详细信息回显
    public function record_edit($id){
        $ms = $this -> qx();
        if ($ms == 0) {
            $this -> error('警告：越权操作');
        }
        $row = db('system_order') -> where('id', $id) -> find();
        if ($row['delivery_time'] != 0) {
            $row['delivery_time'] = date("Y/m/d", $row['delivery_time']);
        } else {
            $arr['delivery_time'] = '暂无时间';
        }
        return $row;
    }
    // 详细信息修改
    public function detailed_edit($id){
        $ms = $this -> qx();
        if ($ms == 0) {
            $this -> error('警告：越权操作');
        }
        $data = input();
        $data['delivery_time'] = strtotime($data['delivery_time']);
        $data['updata_time'] = time();
        unset($data['/index/outbound/detailed_edit_html']);
        $r = db('system_order') -> where('id', $id) -> update($data);
        if ($r) {
            return redirect('Outbound/index');
        } else {
            $this -> error('修改失败,请联系管理员');
        }
    }
    // 导入excel
    public function upload_excel(){
        $ms = $this -> qx();
        if ($ms == 0) {
            $this -> error('警告：越权操作');
        }
        //设置文件上传的最大限制
        ini_set('memory_limit', '1024M');
        //加载第三方类文件
        Loader ::import("PHPExcel.PHPExcel");
        //防止乱码
        header("Content-type:application/vnd.ms-excel");
        //实例化主文件
        //$model = new \PHPExcel();
        //接收前台传过来的execl文件
        $file = $_FILES['file'];
        //截取文件的后缀名，转化成小写
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($extension == "xlsx") {
            //2007(相当于是打开接收的这个excel)
            $objReader = \PHPExcel_IOFactory ::createReader('Excel2007');
        } else {
            //2003(相当于是打开接收的这个excel)
            $objReader = \PHPExcel_IOFactory ::createReader('Excel5');
        }

        $objContent   = $objReader -> load($file['tmp_name']);
        $sheetContent = $objContent -> getSheet(0) -> toArray();
        unset($sheetContent[0]);
        // unset($sheetContent[1]);
        // var_dump($sheetContent);exit;
        foreach ($sheetContent as $k => $v) {
            $arr['delivery_time']                   = strtotime($v[0]);//发货日期
            $arr['factory_id']               = $v[1];//工厂编号
            $arr['factory_name']          = $v[2];//工厂名
            $arr['transport_id']        = $v[3];//装运单号
            $arr['Delivery_id']                = $v[4];//交货单号
            $arr['reachout_id']               =$v[5];//售达方代码
            $arr['reachout_name']              = $v[6];//售达方名称
            $arr['reachby_id']                = $v[7];//送达方代码
            $arr['reachby_name']               = $v[8];//送达方名称
            $arr['material_id']                       = $v[9];//物料代码
            $arr['material_name']             = $v[10];//物料名
            $arr['Delivery_num']                = $v[11];//交货数量
            $arr['detailed']              =$v[12];//详细批次
            $arr['is_del']                      = 0;//软删除
            $arr['create_time']                  = time();//创建时间
            if (!empty($v[0]) && $v[0]!=0) {//无发货日期数据不写入
                $res[] = $arr;
            }
        }
        set_time_limit(0);
        $res = Db ::name('system_order') -> insertAll($res);
        if ($res) {
            return redirect('outbound/index');
        } else {
            $this -> error('导入失败');
        }
    }
    // 生成出库单
    public function make_outbound_order(){
        $f=db('rukuform_xq')->where('state',1)->select();
        for($i=0;$i<count($f);$i++){
            db('rukuform_xq')->where('id',$f[$i]['id'])->update(['sy_count'=>$f[$i]['rk_nums']]);
        }
        $cd=input('id');
        $cd=str_replace(array("\",\""),",",$cd);
        $cd=str_replace(array("[\""),"",$cd);
        $cd=str_replace(array("\"]"),"",$cd);
        $id=str_replace(array("[","]","\""),"",$cd);
        $id=explode(',',$id);
        // 发货日期
        $fh=$rows = db('system_order')
        ->field('delivery_time')
        ->where('is_del',0)
        ->where('id','in',$id)
        ->group('delivery_time')
        ->select();
        $cfh=count($fh);
        if($cfh!=1){
            $this -> error('发货日期不一致');
        }
        // 装运单号
        $zy=$rows = db('system_order')
        ->field('transport_id')
        ->where('is_del',0)
        ->where('id','in',$id)
        ->group('transport_id')
        ->select();
        $czy=count($zy);
        if($czy!=1){
            $this -> error('装运单号不一致');
        }
        // 售达方
        $sd=$rows = db('system_order')
        ->field('reachby_name')
        ->where('is_del',0)
        ->where('id','in',$id)
        ->group('reachby_name')
        ->select();
        $rows = db('system_order')
            ->field('sum(Delivery_num) as num,id,delivery_time,factory_id,factory_name,transport_id,Delivery_id,reachout_id,reachout_name,reachby_id,reachby_name,material_id,material_name,detailed')
            ->where('is_del',0)
            ->where('id','in',$id)
            ->group('material_name')
            ->select();
        $cks = db('warehouse')->where('is_del',1)->select();
        // var_dump($rows);exit;
        return view('make_outbound_order',['rows'=>$rows,'fh'=>$fh,'zy'=>$zy,'sd'=>$sd,'cks'=>$cks,'id'=>$cd]);
    }
    // 出库订单
    public function insert(){
        $data=input();
        $id = db('outbound_from')
            ->insertGetId(['transport_id'=>$data['transport_id'],'reachout_name'=>$data['reachout_name'],'delivery_time'=>$data['delivery_time'],'transport'=>$data['transport'],'carid'=>$data['carid'],'driver'=>$data['driver'],'driverphone'=>$data['driverphone'],'workers'=>$data['workers'],'transport_unit'=>$data['transport_unit'],'ck_id'=>$data['ck_id']]);
        if($id){
            echo 1;
        }else{
            echo 2;
        }
        echo "<pre>";
        print_r($data);exit;
        for ($i=0;$i<count($data['delivery_num']);$i++){
                    $rs = db('outbound_xq_from')->insert([
                        // 'factory'=>$data['transfers_factory'][$i],
                        // 'Delivery_id'=>$data['Delivery_id'][$i],
                        // 'product_name'=>$data['product_name'][$i],
                        // 'ck_huowei_id'=>$data['huowei'][$i],
                        'ck_nums'=>$data['delivery_num'][$i],
                        // 'product_time'=>$data['product_time'][$i],
                        // 'product_batch'=>$data['storno'][$i],
                        // 'content'=>$data['content'][$i],
                        // 'netweight'=>$data['netweight'][$i],
                        // 'Grossweight'=>$data['Grossweight'][$i],
                        // 'transfers_id'=>$data['transfers_id'][$i],
                        'rukuid'=>$id]);
                }
                
        echo "<pre>";
        print_r($rs);exit;
        try{
            $id = db('outbound_from')
            ->insertGetId(['transport_id'=>$data['transport_id'],'reachout_name'=>$data['reachout_name'],'delivery_time'=>$data['delivery_time'],'transport'=>$data['transport'],'carid'=>$data['carid'],'driver'=>$data['driver'],'driverphone'=>$data['driverphone'],'workers'=>$data['workers'],'transport_unit'=>$data['transport_unit'],'ck_id'=>$data['ck_id']]);
            for ($i=0;$i<count($data['delivery_num']);$i++){
                $rs = db('outbound_xq_from')->insert([
                    // 'factory'=>$data['transfers_factory'][$i],
                    // 'Delivery_id'=>$data['Delivery_id'][$i],
                    // 'product_name'=>$data['product_name'][$i],
                    // 'ck_huowei_id'=>$data['huowei'][$i],
                    'ck_nums'=>$data['delivery_num'][$i],
                    // 'product_time'=>$data['product_time'][$i],
                    // 'product_batch'=>$data['storno'][$i],
                    // 'content'=>$data['content'][$i],
                    // 'netweight'=>$data['netweight'][$i],
                    // 'Grossweight'=>$data['Grossweight'][$i],
                    // 'transfers_id'=>$data['transfers_id'][$i],
                    'rukuid'=>$id]);
            }
            $del=db('system_order')->where('id','in',$data['cd'])->update(['is_del'=>1]);
            if($id && $rs && $del) {
                // 提交事务
                Db::commit();
            }
        } catch (\Exception $e) {
            $this->error('添加入库订单失败,请联系管理员');
            // 回滚事务
            Db::rollback();
        }

    }
    // 货位查询
    public function huowei($id){
        $list = db('cabinet')
            ->where('cabinet.is_del',1)
            ->where('cabinet.warehouse_id',$id)->select();
        return json_encode($list);
    }
    // 出库计划
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
    // 出库订单详情
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
    //出库修改订单
    public function to_examine_up() {
        $data=input();
        $userintime=strtotime($data['userintime']);
        array_shift($data);
        //        try{
            $r = db('rukuform')
                -> where('id', $data['id'])
                -> update(['shipmentnum' => $data['shipmentnum'], 'userintime' => $userintime, 'transport' => $data['transport'], 'carid' => $data['carid'], 'stevedore' => $data['stevedore'], 'ck_id' => $data['ck_id']]);
            for ($i = 0; $i < count($data['transfers_factory']); $i++) {
                if (empty($data['cd'][$i])) {
                    $fs=db('rukuform_xq') -> insert(['factory'       => $data['transfers_factory'][$i],
                                                    'product_name'  => $data['material_name'][$i],
                                                    'rk_status_id'  => $data['status'][$i],
                                                    'rk_huowei_id'  => $data['huowei'][$i],
                                                    'rk_nums'       => $data['nums'][$i],
                                                    'product_time'  => $data['intime'][$i],
                                                    'product_batch' => $data['storno'][$i],
                                                    'content'       => $data['content'][$i],
                                                    'netweight'     => $data['netweight'][$i],
                                                    'Grossweight'   => $data['Grossweight'][$i],
                                                    'transfers_id'  => $data['transfers_id'][$i],
                                                    'rukuid'        => $data['id']]);
                }else{
                    $rs = db('rukuform_xq') -> where('id', $data['cd'][$i])
                        -> update(['factory' => $data['transfers_factory'][$i],'product_name'=> $data['material_name'][$i], 'rk_status_id'=> $data['status'][$i], 'rk_huowei_id'  => $data['huowei'][$i], 'rk_nums'=> $data['nums'][$i], 'product_time'  => $data['intime'][$i], 'product_batch' => $data['storno'][$i], 'content' => $data['content'][$i], 'netweight'     => $data['netweight'][$i], 'Grossweight' => $data['Grossweight'][$i], 'transfers_id' => $data['transfers_id'][$i]]);
                }

        //            }
        //            for($c=0;$c<count($data['transfers_factory']);$c++){
        //
        //            }
        //            if($r && $rs) {
        //                // 提交事务
        //                Db::commit();
        //                $this->error('操作成功','to_examine');
        //            }
        //        } catch (\Exception $e) {
        //            $this->error('添加入库订单失败,请联系管理员');
        //            // 回滚事务
        //            Db::rollback();
        //        }
        //            return redirect('to_examine');
        }
        if($r  || $rs){
            return redirect('to_examine');
        }else{
            $this->error('添加入库订单失败,请联系管理员');
        }
    }
    //出库审核
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
    //出库删除
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
    // 出库台账
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
            ->join('warehouse','rukuform.ck_id=warehouse.id','left')
            ->join('rukuform_xq','rukuform.id=rukuform_xq.rukuid','left')
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
    // 出库明细
    public function detailed() {
        $data=input();
        $search = '';
        //工厂名
        if (!empty($data['s_transfers_id'])) {
            $data['s_transfers_id']=addslashes($data['s_transfers_id']);
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
            ->select();
        //产品属性
        $status=db('kc_status')->where('is_del',0)->select();
        return view('detailed',['rows'=>$rows,'status'=>$status]);
    }
}
