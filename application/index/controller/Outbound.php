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
        $data=input();
        $delivery_time='';//发货时间
        $factory_name='';//工厂名
        $transport_id='';//装运单号
        $reachby_name='';//送达方名称
        $material_name='';//物料名
        $search = '';
        if(!empty($data['factory_name'])){
            $factory_name=$data['factory_name'];
            $search = 'factory_name like ' . "'%" . $factory_name . '%' . "'";
        }
        if(!empty($data['delivery_time'])){
            $delivery_time=$data['delivery_time'];
            $time = explode('~', $delivery_time);
            foreach ($time as $key) {
                $time[] = strtotime($key);
                array_shift($time);
            }
            if (!empty($search)) {
                $s_delivery_time = ' and delivery_time BETWEEN ' . $time['0'] . ' and ' . $time['1'];
            } else {
                $s_delivery_time = 'delivery_time BETWEEN ' . $time['0'] . ' and ' . $time['1'];
            }
            $search .= $s_delivery_time;
        }
        if(!empty($data['transport_id'])){
            $transport_id=$data['transport_id'];
            if (!empty($search)) {
                $s_transport_id = ' and transport_id like ' . "'%" . $transport_id . '%' . "'";
            } else {
                $s_transport_id = ' transport_id like ' . "'%" . $transport_id . '%' . "'";
            }
            $search .= $s_transport_id;
        }
        if(!empty($data['reachby_name'])){
            $reachby_name=$data['reachby_name'];
            if (!empty($search)) {
                $s_reachby_name = ' and reachby_name like ' . "'%" . $reachby_name . '%' . "'";
            } else {
                $s_reachby_name = ' reachby_name like ' . "'%" . $reachby_name . '%' . "'";
            }
            $search .= $s_reachby_name;

        }
        if(!empty($data['material_name'])){
            $material_name=$data['material_name'];
            if (!empty($search)) {
                $s_material_name = ' and material_name like ' . "'%" . $material_name . '%' . "'";
            } else {
                $s_material_name = ' material_name like ' . "'%" . $material_name . '%' . "'";
            }
            $search .= $s_material_name;
        }
        $res = db('system_order')
            ->where('is_del',0)
            ->field('factory_name as name')
            ->group('factory_name')
            ->select();
        $list = db('system_order')
            -> where("is_del",0)
            -> order('id desc')
            ->where($search)
            -> paginate(100,false,['query'=>['delivery_time'=>$delivery_time,'factory_name'=>$factory_name,'transport_id'=>$transport_id,'reachby_name'=>$reachby_name,'material_name'=>$material_name]]);
        return view("index", ['list' => $list,'res'=>$res,'delivery_time' => $delivery_time,'factory_name' => $factory_name,'transport_id' => $transport_id,'reachby_name' => $reachby_name,'material_name' => $material_name]);
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
        array_shift($data);
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
        $num=0;
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $f=db('rukuform_xq')->where('state',1)->select();
        for($i=0;$i<count($f);$i++){
            db('rukuform_xq')->where('id',$f[$i]['id'])->update(['sy_count'=>$f[$i]['rk_nums']]);
        }
        $cd=input('id');
        $cd=str_replace(array("\",\""),",",$cd);
        $cd=str_replace(array("[\""),"",$cd);
        $cd=str_replace(array("\"]"),"",$cd);
        $id=str_replace(array("[","]","\""),"",$cd);
        if(strstr($id,'null')){
            $this -> error('请选择至少一条数据');
        }else{
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
            foreach ($rows as $row) {
                $num+=$row['num'];
            }
            return view('make_outbound_order',['rows'=>$rows,'fh'=>$fh,'zy'=>$zy,'sd'=>$sd,'cks'=>$cks,'id'=>$cd,'num'=>$num]);
        }
            
    }
    // 出库订单
    public function insert(){
        Db::startTrans();
        $data=input();
        $time=time();
        if(empty($data['huowei_name'])){
        }
        $ck_time=strtotime($data['ck_time']);
        if(!empty($data['huowei_name'])){
            for ($i=0;$i<count($data['huowei_name']);$i++){
                if(!empty($data['huowei_name']['i'])){
                    $this->error('货位必填');
                }
            }
        }
            try{
                $id = db('outbound_from')
                ->insertGetId(['transport_id'=>$data['transport_id'],'reachout_name'=>$data['reachout_name'],'delivery_time'=>strtotime($data['delivery_time']),'transport'=>$data['transport'],'carid'=>$data['carid'],'driver'=>$data['driver'],'driverphone'=>$data['driverphone'],'workers'=>$data['workers'],'transport_unit'=>$data['transport_unit'],'ck_id'=>$data['ck_id'],'total_shu'=>$data['all_count'],'total_zhong'=>$data['all_weight'],'ck_time'=>$ck_time]);
                for ($i=0;$i<count($data['delivery_num']);$i++){
                    db('outbound_xq_from')->insert([
                            'chukuid'=>$id,
                            'delivery_id'=>$data['Delivery_id'][$i],
                            'product_name'=>$data['material_name'][$i],
                            'ck_huowei_id'=>$data['huowei'][$i],
                            'ck_nums'=>$data['huowei_out'][$i],
                            'netweight'=>$data['jin'][$i],
                            'product_time'=>strtotime($data['product_time'][$i]),
                            'content'=>$data['detailed'][$i],
                            'product_batch'=>$data['product_batch'][$i],
                            'create_time'=>$time,
                            'count'=>$data['delivery_num'][$i],
                            'state'=>0,
                            'sy_count'=>$data['sy_count'][$i]
                        ]);
                }

                $del=db('system_order')->where('id','in',$data['cd'])->update(['is_del'=>1]);
                if($id && $del) {
                    // 提交事务
                    Db::commit();
                }
            } catch (\Exception $e) {
                $this->error('添加出库订单失败,请联系管理员');
                // 回滚事务
                Db::rollback();
                db('outbound_from')->where('id',$id)->delete();
            }
            $this->success('生成订单成功','index');
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
        static $md;
        $warehouse=self::$stafss['warehouse'];
        $s_transfers_id=input('s_transfers_id');
        $s_delivery_time=input('s_delivery_time');
        $s_material_name=input('s_material_name');
        $data=input();
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
                $material_name = ' and outbound_xq_from.delivery_id like ' . "'%" . $material_name . '%' . "'";
            } else {
                $material_name = ' outbound_xq_from.delivery_id like ' . "'%" . $material_name . '%' . "'";
            }
            $search .= $material_name;
        }
        $rows=db('outbound_from')
            ->join('warehouse','outbound_from.ck_id=warehouse.id','left')
            ->join('outbound_xq_from','outbound_from.id=outbound_xq_from.chukuid','left')
            ->where('warehouse.id','in',$warehouse)
            ->where('outbound_from.is_del',0)
            ->where('outbound_from.state',0)
            ->group('outbound_xq_from.chukuid')
            ->where($search)
            ->field('outbound_from.*,warehouse.name as w_name,outbound_xq_from.product_name as x_name,sum(outbound_xq_from.ck_nums) as count,outbound_xq_from.delivery_id')
            ->paginate(100,false,['query'=>['s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name]]);
        $cks=db('warehouse')->where('is_del',1)->where('id','in',$warehouse)->select();
        return view('to_examine',['rows'=>$rows,'cks'=>$cks,'s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name]);
    }
    // 出库订单详情
    public function to_examine_show($id) {
        $warehouse=self::$stafss['warehouse'];
        $num=0;
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $f=db('rukuform_xq')->where('state',1)->select();
        for($i=0;$i<count($f);$i++){
            db('rukuform_xq')->where('id',$f[$i]['id'])->update(['sy_count'=>$f[$i]['rk_nums']]);
        }
        $rows=db('outbound_from')
            ->join('warehouse','outbound_from.ck_id=warehouse.id','left')
            ->where('outbound_from.is_del',0)
            ->where('outbound_from.id',$id)
            ->field('outbound_from.*,warehouse.name as w_name')
            ->find();
        $cats=db('outbound_xq_from')
            ->where('outbound_xq_from.chukuid',$rows['id'])
            ->join('cabinet','cabinet.id=outbound_xq_from.ck_huowei_id','left')
            ->field('outbound_xq_from.*,cabinet.name as c_name')
            ->select();
        $cks = db('warehouse')->where('is_del',1)->where('id','in',$warehouse)->select();
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
        foreach ($cats as $row) {
            $num+=$row['count'];
        }
        $weight=0;
        $nums=0;
        foreach($cats as $v){
            $weight += $v['netweight'];
            $nums += $v['ck_nums'];
        }
        return view('to_examine_show',['rows'=>$rows,'cats'=>$cats,'id'=>$id,'cks'=>$cks,'cabinet'=>$cabinet,'num'=>$num,'nums'=>$nums,'weight'=>$weight]);
    }
    //出库修改订单
    public function to_examine_up() {
        $data=input();
            $r = db('outbound_from')
            -> where('id', $data['id'])
            -> update([
                'transport_id' => $data['transport_id'],
                'reachout_name' => $data['reachout_name'],
                'delivery_time' => strtotime($data['delivery_time']),
                'transport' => $data['transport'],
                'carid' => $data['carid'],
                'driver' => $data['driver'],
                'driverphone' => $data['driverphone'],
                'workers' => $data['workers'],
                'transport_unit' => $data['transport_unit'],
                'update_time' => time(),
                'ck_id' => $data['ck_id'],
                'ck_time'=>strtotime($data['userintime']),'total_shu'=>$data['all_count'],'total_zhong'=>$data['all_weight'],
                ]);
        for($i=0;$i<count($data['material_name']);$i++){
            if(empty($data['cd'][$i])){
                db('outbound_xq_from')->insert([
                    'chukuid'=>$data['id'],
                    'count'=>0,
                    'delivery_id'  => $data['delivery_id'][$i],
                    'product_name'  => $data['material_name'][$i],
                    'ck_huowei_id'  => $data['huowei'][$i],
                    'ck_nums'       => $data['nums'][$i],
                    'product_time'  => strtotime($data['product_time'][$i]),
                    'product_batch' => $data['storno'][$i],
                    'content'       => $data['content'][$i],
                    'netweight'     => $data['netweight'][$i],
                    'update_time'   => time(),
                    'sy_count'=>$data['sy_count'][$i]
                ]);
            }else{
                $fs=db('outbound_xq_from')
                    -> where('id', $data['cd'][$i])
                    -> update([
                        'delivery_id'  => $data['delivery_id'][$i],
                        'product_name'  => $data['material_name'][$i],
                        'ck_huowei_id'  => $data['huowei'][$i],
                        'ck_nums'       => $data['nums'][$i],
                        'product_time'  => strtotime($data['product_time'][$i]),
                        'product_batch' => $data['storno'][$i],
                        'content'       => $data['content'][$i],
                        'netweight'     => $data['netweight'][$i],
                        'update_time'   => time(),
                        'sy_count'=>$data['sy_count'][$i]
                    ]);
            }
        }
        if($r  || $fs){
            return redirect('to_examine');
        }else{
            $this->error('修改出库订单失败,请联系管理员');
        }
    }
    //出库审核
    public function to_examine_yes() {
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        Db::startTrans();
        $id=input('id');
        $data=input();
        $row=db('outbound_xq_from')->where('chukuid',$data['id'])->select();
        $form=db('outbound_from')->where('id',$data['id'])->find();
        if(empty($id)){
            $this->error('缺少必要参数,请重试');
        }
        try{
            foreach ($row as $r) {
                $d=db('rukuform_xq')->where('is_del',0)->where('rk_huowei_id',$r['ck_huowei_id'])->field('rk_nums')->find();
                $d=(int)$d['rk_nums'];
                $balance=$d-$r['ck_nums'];
                //中间表
                $a=db('record')->insert(['time'=>$form['ck_time'],'odd_number'=>$data['transport_id'],'task'=>$data['task'],'customer'=>$data['reachout_name'],'early_stage'=>$d,'xx_chuku'=>$r['ck_nums'],'balance'=>$balance,'huowei'=>$r['ck_huowei_id'],'count'=>$r['content'],'hw_name'=>$r['product_name']]);
                //改变实时数量
                $b=db('rukuform_xq')->where('rk_huowei_id',$r['ck_huowei_id'])->update(['rk_nums'=>$balance]);
                $rows=db('rukuform_xq')->where('is_del',0)->select();
                for($i=0;$i<count($rows);$i++){
                    if($rows[$i]['rk_nums']<1){
                        db('rukuform_xq')->where('id',$rows[$i]['id'])->update(['is_del'=>1]);
                        db('record')->where('huowei',$rows[$i]['rk_huowei_id'])->update(['is_del'=>0]);
                    }
                }
            }
            $r=db('outbound_from')->where('id',$id)->update(['state'=>1]);
            $s=db('outbound_xq_from')->where('chukuid',$id)->update(['state'=>1]);
            if($a && $b && $r && $s) {
                Db::commit();
            }
        } catch (\Exception $e) {
            $this->error('审核失败,请联系管理员');
            // 回滚事务
            Db::rollback();
        }
        return redirect('warehousing');
    }
    //出库删除
    public function to_examine_del() {
        $id=input('id');
        if(empty($id)){
            $this->error('缺少必要参数,请重试');
        }
        $r=db('outbound_from')->where('id',$id)->update(['is_del'=>1]);
        if($r!==false){
            return redirect('to_examine');
        }else{
            $this->error('删除失败,请联系管理员');
        }
    }
    // 出库台账
    public function warehousing() {
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
            ->where('warehouse.id','in',$warehouse)
            ->where('outbound_from.is_del',0)
            ->where('outbound_from.state',1)
            ->where('outbound_xq_from.qt_ck',0)
            ->group('outbound_from.id')
            ->where($search)
            ->field('outbound_from.*,warehouse.name as w_name,outbound_xq_from.product_name as x_name,sum(outbound_xq_from.ck_nums) as count,outbound_xq_from.delivery_id')
            ->paginate(100,false,['query'=>['s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name]]);
        $cks=db('warehouse')->where('is_del',1)->where('id','in',$warehouse)->select();
        return view('warehousing',['rows'=>$rows,'cks'=>$cks,'s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name]);
    }
    //订单详情
    public function warehousing_show($id) {
        $num=0;
        $z=0;
        $rows=db('outbound_from')
            ->join('warehouse','outbound_from.ck_id=warehouse.id','left')
            ->where('outbound_from.is_del',0)
            ->where('outbound_from.id',$id)
            ->field('outbound_from.*,warehouse.name as w_name')
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
    // 出库明细
    public function detailed() {
        $warehouse=self::$stafss['warehouse'];
        $s_transfers_id=input('s_transfers_id');//工厂
        $s_delivery_time=input('s_delivery_time');//日期
        $s_material_name=input('s_material_name');//产品名称
        $data=input();
        $search = '';
        //工厂名
        if (!empty($s_transfers_id)) {
            $s_transfers_id=addslashes($s_transfers_id);
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
                $time = ' and outbound_from.delivery_time BETWEEN ' . $time['0'] . ' and ' . $time['1'];
            } else {
                $time = 'outbound_from.delivery_time BETWEEN ' . $time['0'] . ' and ' . $time['1'];
            }
            $search .= $time;
        }
        // 产品名称
        if (!empty($s_material_name)) {
            $material_name = $s_material_name;
            if (!empty($search)) {
                $material_name = ' and outbound_xq_from.product_name like ' . "'%" . $material_name . '%' . "'";
            } else {
                $material_name = ' outbound_xq_from.product_name like' . "'%" . $material_name . '%' . "'";
            }
            $search .= $material_name;
        }
        $rows=db('outbound_xq_from')
            ->where('outbound_xq_from.qt_ck',0)
            ->join('outbound_from','outbound_from.id=outbound_xq_from.chukuid','left')
            ->join('warehouse','outbound_from.ck_id=warehouse.id','left')
            ->join('cabinet','outbound_xq_from.ck_huowei_id=cabinet.id','left')
            ->where('warehouse.id','in',$warehouse)
            ->where('outbound_from.is_del',0)
            ->where('outbound_from.state',1)
            ->group('outbound_xq_from.id')
            ->where($search)
            ->field('outbound_xq_from.*,warehouse.name as w_name,outbound_from.transport_id as t_id,sum(outbound_xq_from.ck_nums) as count,outbound_from.reachout_name,outbound_from.delivery_time,cabinet.name as c_name,outbound_from.ck_time')
            ->paginate(100,false,['query'=>['s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name]]);
        $cks=db('warehouse')->where('is_del',1)->where('id','in',$warehouse)->select();
        return view('detailed',['rows'=>$rows,'cks'=>$cks,'s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name]);
    }


    //导出
    public function outExcel2(){
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $data = input();
        array_shift($data);
        $id=$data['id'];
        $da=str_replace('"', '', $id);
        $da=trim($da,'[');
        $da=trim($da,']');
        $data=db('outbound_from')
            ->where("outbound_from.id in ($da)")
            ->join('warehouse','warehouse.id=outbound_from.ck_id')
            ->field('outbound_from.*,warehouse.name')
            ->select();
        if(!empty($data)){
            Vendor('PHPExcel.PHPExcel');
            Vendor('PHPExcel.PHPExcel.IOFactory');
            $phpExcel = new \PHPExcel();
            $phpExcel->setActiveSheetIndex(0)
                ->setCellValue('A1', '出库仓库')
                ->setCellValue('B1', '装运单号')
                ->setCellValue('C1', '送达方')
                ->setCellValue('D1', '出库日期')
                ->setCellValue('E1', '总数量')
                ->setCellValue('F1', '吨位');
            $len = count($data);
            for($i = 0 ; $i < $len ; $i++){
                $v = $data[$i];
                $rownum = $i+2;
                $phpExcel->getActiveSheet()->setCellValue('A' . $rownum, $v['name']);
                $phpExcel->getActiveSheet()->setCellValue('B' . $rownum, $v['transport_id']);
                $phpExcel->getActiveSheet()->setCellValue('C' . $rownum, $v['reachout_name']);
                $phpExcel->getActiveSheet()->setCellValue('D' . $rownum, date('Y-m-d',$v['ck_time']));
                $phpExcel->getActiveSheet()->setCellValue('E' . $rownum, $v['total_shu']);
                $phpExcel->getActiveSheet()->setCellValue('F' . $rownum, $v['total_zhong']);
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
    public function outExcel(){
        $data=input();
        array_shift($data);
        $id=$data['id'];
        $da=str_replace('"', '', $id);
        $da=trim($da,'[');
        $da=trim($da,']');
        $data=db('system_order')
            ->where("id in ($da)")
            ->select();
        if(!empty($data)){
            Vendor('PHPExcel.PHPExcel');
            Vendor('PHPExcel.PHPExcel.IOFactory');
            $phpExcel = new \PHPExcel();
            $phpExcel->setActiveSheetIndex(0)
                ->setCellValue('A1', '发货日期')
                ->setCellValue('B1', '工厂')
                ->setCellValue('C1', '工厂名称')
                ->setCellValue('D1', '装运单号')
                ->setCellValue('E1', '交货单号')
                ->setCellValue('F1', '售达方代码')
                ->setCellValue('G1', '售达方名称')
                ->setCellValue('H1', '送达方代码')
                ->setCellValue('I1', '送达方名称')
                ->setCellValue('J1', '物料')
                ->setCellValue('K1', '物料名称')
                ->setCellValue('L1', '交货数量')
                ->setCellValue('M1', '详细批次');
            $len = count($data);
            for($i = 0 ; $i < $len ; $i++){
                $v = $data[$i];
                $rownum = $i+2;
                $phpExcel->getActiveSheet()->setCellValue('A' . $rownum, date('Y-m-d',$v['delivery_time']));
                $phpExcel->getActiveSheet()->setCellValue('B' . $rownum, $v['factory_id']);
                $phpExcel->getActiveSheet()->setCellValue('C' . $rownum, $v['factory_name']);
                $phpExcel->getActiveSheet()->setCellValue('D' . $rownum, $v['transport_id']);
                $phpExcel->getActiveSheet()->setCellValue('E' . $rownum, $v['Delivery_id']);
                $phpExcel->getActiveSheet()->setCellValue('F' . $rownum, $v['reachout_id']);
                $phpExcel->getActiveSheet()->setCellValue('G' . $rownum, $v['reachout_name']);
                $phpExcel->getActiveSheet()->setCellValue('H' . $rownum, $v['reachby_id']);
                $phpExcel->getActiveSheet()->setCellValue('I' . $rownum, $v['reachby_name']);
                $phpExcel->getActiveSheet()->setCellValue('J' . $rownum, $v['material_id']);
                $phpExcel->getActiveSheet()->setCellValue('K' . $rownum, $v['material_name']);
                $phpExcel->getActiveSheet()->setCellValue('L' . $rownum, $v['Delivery_num']);
                $phpExcel->getActiveSheet()->setCellValue('M' . $rownum, $v['detailed']);
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
    /**
     * 拆分
     */
    public function chaifen(){
        $data=input();
        $num=(int)$data['cf_Delivery_num'];
        $nums=0;
        foreach($data['detailed'] as $v){
            $v=(int)$v;
            $nums+=$v;
        }
        if($num!=$nums){
            $this->error('数量不匹配，请重新输入');
        }
        foreach($data['detailed'] as $v){
            if($v!=0){
                db('system_order')
                ->insert([
                    'delivery_time'=>strtotime($data['cf_delivery_time']),
                    'factory_id'=>$data['cf_factory_id'],
                    'factory_name'=>$data['cf_factory_name'],
                    'transport_id'=>$data['cf_transport_id'],
                    'Delivery_id'=>$data['cf_Delivery_id'],
                    'reachout_id'=>$data['cf_reachout_id'],
                    'reachout_name'=>$data['cf_reachout_name'],
                    'reachby_id'=>$data['cf_reachby_id'],
                    'reachby_name'=>$data['cf_reachby_name'],
                    'material_id'=>$data['cf_material_id'],
                    'material_name'=>$data['cf_material_name'],
                    'Delivery_num'=>$v,
                    'detailed'=>$data['cf_detailed'],
                    'create_time'=>time(),
                ]);
            }
        }
        $id=input('cf_id');
        $update['is_del']=1;
        $r = db('system_order') -> where('id', $id)->update($update);//  ->select();
        return redirect('index');
    }
    /**
     * 拆分总数回显
     */
    public function cf_edit($id){
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
}
