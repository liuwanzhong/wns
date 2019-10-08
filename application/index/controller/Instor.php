<?php
namespace app\index\controller;
use think\Controller;
use think\Cookie;
use think\Db;
use think\Request;
use think\response\Redirect;
use think\Session;

class Instor extends Controller
{
    //货物列表
    public function index(){
        $warehouse=self::$stafss['warehouse'];
        $zt=Session::get('zt');
        static $md=[];
        $time=time();
        $zq=db('rukuform_xq')->where('is_del',0)->where('state',1)->where("product_time<$time-60*60*24*30")->select();
        $rows=db('rukuform_xq')->where('is_del',0)->select();
        for($i=0;$i<count($rows);$i++){
            if($rows[$i]['rk_nums']<1){
                db('rukuform_xq')->where('id',$rows[$i]['id'])->update(['is_del'=>1]);
                db('record')->where('huowei',$rows[$i]['rk_huowei_id'])->update(['is_del'=>0]);
            }
        }
        $s_transfers_id=input('s_transfers_id');//仓库名称
        $s_delivery_time=input('s_delivery_time');//生产日期
        $s_material_name=input('s_material_name');//产品名称
        $s_material_zt=input('s_material_zt');//状态
        $status_id=input('status');//产品属性
        $search = '';
        if (!empty($s_transfers_id)) {
            $search = "warehouse.id=$s_transfers_id";
        }
        // 时间转换
        if (!empty($s_delivery_time)) {
            $time = explode('~', $s_delivery_time);
            foreach ($time as $key) {
                $time[] = strtotime($key);
                array_shift($time);
            }
            if (!empty($search)) {
                $time = ' and rukuform_xq.product_time BETWEEN ' . $time['0'] . ' and ' . $time['1'];
            } else {
                $time = ' rukuform_xq.product_time BETWEEN ' . $time['0'] . ' and ' . $time['1'];
            }
            $search .= $time;
        }
        // 产品名称
        if (!empty($s_material_name)) {
            $material_name = $s_material_name;
            if (!empty($search)) {
                $material_name = ' and rukuform_xq.product_name like ' . "'%" . $material_name . '%' . "'";
            } else {
                $material_name = ' rukuform_xq.product_name like' . "'%" . $material_name . '%' . "'";
            }
            $search .= $material_name;
        }
        //状态s_material_zt
        if ($s_material_zt!=0) {
            $material_zt = $s_material_zt;
            if (!empty($search)) {
                $material_zt = " and rukuform_xq.state = $material_zt";
            } else {
                $material_zt = " rukuform_xq.state = $material_zt";
            }
            $search .= $material_zt;
        }
        //产品属性
        if ($status_id!=0) {
            $stat = $status_id;
            if (!empty($search)) {
                $material_zt = " and rukuform_xq.rk_status_id = $stat";
            } else {
                $material_zt = " rukuform_xq.rk_status_id = $stat";
            }
            $search .= $material_zt;
        }
        $row = db('rukuform_xq')
            ->where('rukuform_xq.state!=0')
            ->where('rukuform_xq.is_del',0)
            ->join('kc_status','rukuform_xq.rk_status_id=kc_status.id and kc_status.is_del=0',"left")
            ->join('cabinet','rukuform_xq.rk_huowei_id=cabinet.id and cabinet.is_del=1','left')
            ->join('warehouse','cabinet.warehouse_id=warehouse.id','left')
            ->order('rukuform_xq.create_time desc')
            ->field('rukuform_xq.*,kc_status.title,cabinet.name,kc_status.title')
            ->select();
        $sums=0;
        foreach($row as $v){
            $sums += $v['rk_nums'];
        }
        $order = db('rukuform_xq')
            ->where('rukuform_xq.state!=0')
            ->where('rukuform_xq.is_del',0)
            ->join('kc_status','rukuform_xq.rk_status_id=kc_status.id and kc_status.is_del=0',"left")
            ->join('cabinet','rukuform_xq.rk_huowei_id=cabinet.id and cabinet.is_del=1','left')
            ->join('warehouse','cabinet.warehouse_id=warehouse.id','left')
            ->where('warehouse.id','in',$warehouse)
            ->where("$search")
            ->order('rukuform_xq.create_time desc')
            ->field('rukuform_xq.*,kc_status.title,cabinet.name,kc_status.title')
            ->paginate(100,false,['query'=>['s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name,'s_material_zt'=>$s_material_zt,'status'=>$status_id]]);
        $orders=$order->all();
        foreach($orders as &$v) {
            $v['ckname'] = db('rukuform') 
            -> field('a.id,a.ck_id,b.name')
            -> alias('a')
            -> join('warehouse b', 'a.ck_id = b.id')
            -> where('a.is_del', 0) -> where('b.is_del', 1)
            -> where('a.id', $v['rukuid'])->find()['name'];
            if($v['rukuid']==null){
                $v['ckname']=db('cabinet')->where('cabinet.id',$v['rk_huowei_id'])->join('warehouse','warehouse.id=cabinet.warehouse_id')->field('warehouse.name')->find()['name'];
            }
        }

        //仓库
        $ware=db('warehouse')->where('is_del',1)->where('id','in',$warehouse)->select();
        foreach ($orders as $o) {
            foreach ($zq as $z) {
                if($o['id']==$z['id']){
                    $md[]=$o;
                }
            }
        }

        $sum=0;
        foreach($orders as $v){
            $sum += $v['rk_nums'];
        }
        //产品属性
        $status=db('kc_status')->where('is_del',0)->select();
        return view('index2',['orders'=>$orders,'status'=>$status,'order'=>$order,'ware'=>$ware,'s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name,'md'=>$md,'zt'=>$zt,'s_material_zt'=>$s_material_zt,'status_id'=>$status_id,'sum'=>$sum,'sums'=>$sums]);
    }

    public function zt() {
        $zt=input('zt');
        Session::set('zt',$zt);
    }
    //查看入库产品
    public function show($id){
        $s_transfers_id=input('s_transfers_id');//仓库名称
        $s_delivery_time=input('s_delivery_time');//生产日期
        $s_material_name=input('s_material_name');//产品名称
        $s_material_zt=input('s_material_zt');//状态
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
//        $time=db('record')->where('huowei',$id)->where('task','结存')->where('is_del',1)->field('time')->order('time desc')->limit(1)->select();
//        if(empty($time)){
            $rows=db('record')->where('huowei',$id)->where('is_del',1)->select();
//        }else{
//            $state_time=$state_time[0]['state_time'];
//            $rows=db('record')->where('huowei',$id)->where("state_time >= '$state_time'")->where('is_del',1)->select();
//            echo db('record')->getlastSql();
//        }
        return view('show',['rows'=>$rows,'s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name,'s_material_zt'=>$s_material_zt]);
    }
    /**
     * 货位选择
     */
    public function huowei($id)
    {
        $rows=db('cabinet')->where('is_del',1)->where('warehouse_id',$id)->select();
        foreach ($rows as $k=>$row) {
            $fs=db('rukuform_xq')->where('is_del',0)->where('state!=0')->select();
            foreach ($fs as $f) {
                if($row['id']==$f['rk_huowei_id'] and $f['state']==2){
                    unset($rows[$k]);
                }
            }
        }
        $rows=array_values($rows);
        return $rows;
    }
    /**
     * 货位选择(返回产品信息)
     */
    public function huowei_w($id) {
        $r=db('rukuform_xq')->where('is_del',0)->where('state',1)->where('rk_huowei_id',$id)->find();
        if($r){
            $r['product_time']=date('Y-m-d',$r['product_time']);
            return $r;
        }else{
            return false;
        }
    }
    /**
     * 绑定产品名称
     */
    public function bang($id)
    {
        $num=input('num');
        $row=db('goods_name')->where('name',$id)->find();
        $m = $row['mao'];
        $j = $row['jing'];
        if($num != ''){
            $mao=sprintf("%.3f",$m*$num/1000);
            $jing=sprintf("%.3f",$j*$num/1000);
            return ['mao'=>$mao,'jing'=>$jing];
        }
        else{
            return $row;
        }
    }
    //毛净重计算
    public function mj()
    {
        $mao=input('mao');
        $jing=input('jing');
        $num=input('num');
        $mao=sprintf("%.3f",$mao*$num/1000);
        $jing=sprintf("%.3f",$jing*$num/1000);
        return ['mao'=>$mao,'jing'=>$jing];
    }
    // 结存
    public function jiecun(){
        $s_transfers_id=input('s_transfers_id');//仓库名称
        $s_delivery_time=input('s_delivery_time');//生产日期
        $s_material_name=input('s_material_name');//产品名称
        $s_material_zt=input('s_material_zt');//状态
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $id=input('id');
        $row=db('rukuform_xq')->where('id',$id)->find();
        $r=db('record')->where('is_del',1)->where('huowei',$row['rk_huowei_id'])->where('task','结存')->count();
        if($r==1){
            $this->error('该货位暂无可结存产品');
        }
        $time=time();
        try{
            //获取货位产品详细信息
            $row=db('rukuform_xq')->where('id',$id)->find();
            $a=db('record')->insertGetId(['time'=>$time,'odd_number'=>'结存','task'=>'结存','customer'=>'结存','early_stage'=>$row['rk_nums'],'huowei'=>$row['rk_huowei_id'],'balance'=>$row['rk_nums']]);
            //获取产品信息的仓库名和货位名
            $ck=db('cabinet')->where('cabinet.id',$row['rk_huowei_id'])->join('warehouse','cabinet.warehouse_id=warehouse.id')->field('warehouse.name as w_name,cabinet.name as c_name')->find();
            $id=db('jc')->insertGetId(['warehouse_name'=>$ck['w_name'],'huowei_name'=>$ck['c_name'],'product_name'=>$row['product_name'],'product_time'=>$row['product_time'],'num'=>$row['rk_nums'],'ctenter'=>$row['content']]);
            $rows=db('record')->where('is_del',1)->where('huowei',$row['rk_huowei_id'])->where("id!=$a")->update(['is_del'=>0,'cj_id'=>$id]);
        if($a && $id && $rows) {
                // 提交事务
                Db::commit();
            }
        } catch (\Exception $e) {
            $this->error('结存失败,请联系管理员');
            // 回滚事务
            Db::rollback();
        }
        return redirect('index',['s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name,'s_material_zt'=>$s_material_zt]);
    }

    public function jiec2() {
        Db::startTrans();
        $data=input();
        $time=time();
        $da=$data['alls'];
        $d=implode(",",$da);
        $row=db('rukuform_xq')->where('id','in',$d)->select();
        for ($i=0;$i<count($row);$i++){
            if($row[$i]['state']!=2){
                try{
                $a=db('record')->insertGetId(['time'=>$time,'odd_number'=>'结存','task'=>'结存','customer'=>'结存','early_stage'=>$row[$i]['rk_nums'],'huowei'=>$row[$i]['rk_huowei_id'],'balance'=>$row[$i]['rk_nums']]);
                $ck=db('cabinet')->where('cabinet.id',$row[$i]['rk_huowei_id'])->join('warehouse','cabinet.warehouse_id=warehouse.id')->field('warehouse.name as w_name,cabinet.name as c_name')->find();
                $id=db('jc')->insertGetId(['warehouse_name'=>$ck['w_name'],'huowei_name'=>$ck['c_name'],'product_name'=>$row[$i]['product_name'],'product_time'=>$row[$i]['product_time'],'num'=>$row[$i]['rk_nums'],'ctenter'=>$row[$i]['content']]);
                $rows=db('record')->where('is_del',1)->where('huowei',$row[$i]['rk_huowei_id'])->where("id!=$a")->update(['is_del'=>0,'cj_id'=>$id]);
                    if($a && $id && $rows) {
                        // 提交事务
                        Db::commit();
                        $msg=['error'=>0,'msg'=>'结存成功，请勿反复结存'];
                    }
                } catch (\Exception $e) {
                    $msg=['error'=>100,'msg'=>'结存失败，请联系管理员'];
                    // 回滚事务
                    Db::rollback();
                }

            }
        }
        return $msg;
    }
    // 月度统计
    public function show_month(){
        $rows=array();
        if(!empty(input('time'))){
            $time=input('time');
        }else{
            $time='';
        }
        if(!empty($time)){
            $times=explode('-',$time);
            $y=$times[0];
            $m=$times[1];
            $rows=db('jc')
                ->where("date_format(state_time,'%m')=$m and date_format(state_time,'%Y')=$y")
                ->select();
            }
        foreach ($rows as $k=>$row) {
            $rows[$k]['product_time']=date('Y-m-d',$row['product_time']);
        }
        return view('show_month',['rows'=>$rows,'time'=>$time]);
    }
    /**
     * 月度统计详细
     */
    public function show_month_xx(){
        $id=input('id');
        $time=input('time');
        $rows=db('record')->where('is_del',0)->where('cj_id',$id)->select();
        foreach ($rows as $k=>$row) {
//            $rows[$k]['time']=date('Y-m-d',$row['time']);
            if(!empty($row['time'])){
                $rows[$k]['time']=date('Y-m-d',$row['time']);
            }
        }
        return view('show_month_xx',['rows'=>$rows,'time'=>$time]);
    }
    // 库存盘点
    public function pandian(){
        static $md;
        $warehouse=self::$stafss['warehouse'];
        $cks = db('warehouse')->where('is_del',1)->where('id','in',$warehouse)->select();
        $list = db('pandian')
            ->group('create_time')
            ->order('create_time desc')
            ->where('is_del',0)
            ->paginate(100);
        $li=$list->all();
        foreach ($li as $k=>$l) {
            foreach ($cks as $ck) {
                if($l['w_name']==$ck['name']){
                    $md=$li;
                }
            }
        }
        return view('pandian',['cks'=>$cks,'list'=>$list,'li'=>$md]);
    }
    // 添加盘点页
    public function add_pandian(){
        $id=input('id');
        $list=db('rukuform_xq')
        ->join('cabinet','cabinet.id=rukuform_xq.rk_huowei_id','left')
        ->join('warehouse','cabinet.warehouse_id=warehouse.id','left')
        ->where('warehouse.id',$id)
        ->where('rukuform_xq.rk_nums != 0')
        ->field('cabinet.name c_name,warehouse.name w_name,rukuform_xq.*')
        ->group('rukuform_xq.id')
        ->select();
        return view('add_pandian',['list'=>$list]);
    }
    // 执行添加
    public function do_add(){
        $data=input();
        if(!empty($data['w_name']) && !empty($data['pd_num'])){
            for ($i=0;$i<count($data['w_name']);$i++){
                if(empty($data['pd_num'][$i])){
                    $data['pd_num'][$i]=0;
                }
                $chayi='';
                $chayi=$data['rk_nums'][$i]-$data['pd_num'][$i];
                dump($chayi);
                db('pandian')
                ->insert([
                    'w_name'=>$data['w_name'][$i],
                    'c_name'=>$data['c_name'][$i],
                    'product_name'=>$data['product_name'][$i],
                    'product_time'=>$data['product_time'][$i],
                    'pd_num'=>$data['pd_num'][$i],
                    'rk_nums'=>$data['rk_nums'][$i],
                    'chayi'=>$data['rk_nums'][$i]-$data['pd_num'][$i],
                    'create_time'=>time(),
                    'count1'=>$data['count1'][$i],
                    'count2'=>$data['count2'][$i],
                    'count3'=>$data['count3'][$i],
                    'count'=>$data['count'],
                ]);
            }
            return redirect('Instor/pandian');
        }else{
            $this -> error('数据为空');
        }
    }
    /**
     * 盘点详细
     */
    public function xiangxi() {
        $time = input('time');
        $list = db('pandian')
            -> where('create_time', $time)
            -> where('is_del', 0)
            -> select();
        return view('xiangxi', ['list' => $list]);
    }
    /**
     * 盘点删除
     */
    public function del_pandian() {
        $time = input('time');
        $list = db('pandian')
            -> where('create_time', $time)
            -> update(['is_del' => 1]);
        if($list){
            return redirect('Instor/pandian');
        }else{
            $this -> error('删除失败');
        }

    }

    /**
     * 其他入库记录列表
     */
    public function other_rk_list() {
        static $md;
        $warehouse=self::$stafss['warehouse'];
        $s_transfers_id=input('s_transfers_id');//产品名称
        $s_delivery_time=input('s_delivery_time');//产品日期
        $s_material_name=input('s_material_name');//仓库
        $search = '';
        //产品名称
        if (!empty($s_transfers_id)) {
            $search = 'product_name like ' . "'%" . $s_transfers_id . '%' . "'";
        }
        // 产品日期
        if (!empty($s_delivery_time)) {
            $time = explode('~', $s_delivery_time);
            foreach ($time as $key) {
                $time[] = strtotime($key);
                array_shift($time);
            }
            if (!empty($search)) {
                $time = " and product_time=$time[0]";
            } else {
                $time = "product_time=$time[0]";
            }
            $search .= $time;
        }
        // 仓库
        if (!empty($s_material_name)) {
            $material_name = $s_material_name;
            if (!empty($search)) {
                $material_name = " and ck_name='$material_name'";
            } else {
                $material_name = "ck_name='$material_name'";
            }
            $search .= $material_name;
        }
        $cabinet=db('warehouse')->where('is_del',1)->where('id','in',$warehouse)->select();
        $rows=db('other_rk')->where("$search")->paginate(100,false,['query'=>['s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name]]);
        $li=$rows->all();
        foreach ($li as $k=>$l) {
            foreach ($cabinet as $ck) {
                if($l['ck_name']==$ck['name']){
                    $md=$li;
                }
            }
        }
        return view('other_rk_list',['rows'=>$rows,'cabinet'=>$cabinet,'s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name,'md'=>$md]);
    }
    /**
     * 其他入库添加界面
     */
    public function other_rk()
    {
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告:越权操作');
        }
        //仓库
        $cks = db('warehouse')->where('is_del',1)->select();
        //产品名称
        $goods_name=db('goods_name')->where('is_del',0)->select();
        //产品属性
        $kc=db('kc_status')->where('is_del',0)->select();
        return view('other_rk',['cks'=>$cks,'goods_name'=>$goods_name,'kc'=>$kc]);
    }
    /**
     * 添加其他入库
     */
    public function tj_rk()
    {
        Db::startTrans();
        $data = input();
        $warehouse=db('warehouse')->where('id',$data['ck_id'])->find();
        $product_time = strtotime($data['product_time']);//产品日期
        $userintime = strtotime($data['userintime']);//入库日期
        array_shift($data);
        if($data['customer']=='' || $data['rk_status_id']==0 || $data['rk_huowei_id']=='' || $data['rk_nums']=='' || $data['product_time']==''){
            $this->error('请完善其他入库订单');
        }
        $r = db('record')->where('is_del', 1)->where('huowei', $data['rk_huowei_id'])->count();
        if ($r) {
            try {
                $c = db('rukuform_xq')->where('is_del', 0)->where('rk_huowei_id', $data['rk_huowei_id'])->find();
                $num = $c['rk_nums'] + $data['rk_nums'];
                //存在则添加记录
                $s = db('record')->insert(['time' => $userintime, 'odd_number' => $data['odd_number'], 'task' => '其他入库', 'customer' => $data['customer'], 'early_stage' => $c['rk_nums'], 'qt_ruku' => $data['rk_nums'], 'balance' => $num, 'huowei' => $data['rk_huowei_id'], 'count' => $data['content']]);
                //修改实时数量
                $a = db('rukuform_xq')->where('is_del', 0)->where('rk_huowei_id', $data['rk_huowei_id'])->update(['rk_nums' => $num]);
                $rk=db('cabinet')->where('id',$data['rk_huowei_id'])->find();
                $f=db('other_rk')->insert(['product_name'=>$data['customer'],'product_time'=>$product_time,'huowei'=>$rk['name'],'count'=>$data['rk_nums'],'rk_time'=>$userintime,'conter'=>$data['content'],'ck_name'=>$warehouse['name']]);
                if ($s && $a && $f) {
                    // 提交事务
                    Db::commit();
                }
            } catch (\Exception $e) {
                $this->error('其他入库失败，请联系管理员');
                // 回滚事务
                Db::rollback();
            }
            return redirect('other_rk_list');
        } else {
            try {
                //不存在则添加库存
                $id = db('rukuform')->insertGetId(['shipmentnum' => $data['shipmentnum'], 'transport' => $data['transport'], 'carid' => $data['carid'], 'stevedore' => $data['stevedore'], 'ck_id' => $data['ck_id'], 'userintime' => $userintime, 'intime' => $userintime]);
                $f = db('rukuform_xq')->insert( ['transfers_id' => $data['odd_number'], 'product_name' => $data['customer'], 'rk_status_id' => $data['rk_status_id'], 'rk_huowei_id' => $data['rk_huowei_id'], 'rk_nums' => $data['rk_nums'], 'product_time' => $product_time, 'netweight' => $data['mao'], 'Grossweight' => $data['jin'], 'state' => 1, 'rukuid' => $id,'qt_rk'=>1]);
                $p = db('record')->insert(['time' => $userintime, 'odd_number' => $data['odd_number'], 'task' => '其他入库', 'early_stage' => 0, 'qt_ruku' => $data['rk_nums'], 'balance' => $data['rk_nums'], 'huowei' => $data['rk_huowei_id'], 'count' => $data['content']]);
                $rk=db('cabinet')->where('id',$data['rk_huowei_id'])->find();
                $s=db('other_rk')->insert(['product_name'=>$data['customer'],'product_time'=>$product_time,'huowei'=>$rk['name'],'count'=>$data['rk_nums'],'rk_time'=>$userintime,'conter'=>$data['content']]);
                if ($id && $f && $p && $s) {
                    // 提交事务
                    Db::commit();
                }
            } catch (\Exception $e) {
                $this->error('其他入库失败，请联系管理员');
                // 回滚事务
                Db::rollback();
            }
            return redirect('other_rk_list');
        }
    }
    /**
     * 删除其他入库记录
     */
    public function other_del($id) {
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $r=db('other_rk')->where('id',$id)->delete();
        if($r){
            return redirect('other_rk_list');
        }else{
            $this->error('删除失败,请联系管理员');
        }
    }

    /**
     * 其他出库记录列表
     */
    public function other_ck_list() {
        static $md;
        $warehouse=self::$stafss['warehouse'];
        $s_transfers_id=input('s_transfers_id');//产品名称
        $s_delivery_time=input('s_delivery_time');//产品日期
        $s_material_name=input('s_material_name');//仓库
        $search = '';
        //产品名称
        if (!empty($s_transfers_id)) {
            $search = 'product_name like ' . "'%" . $s_transfers_id . '%' . "'";
        }
        // 产品日期
        if (!empty($s_delivery_time)) {
            $time = explode('~', $s_delivery_time);
            foreach ($time as $key) {
                $time[] = strtotime($key);
                array_shift($time);
            }
            if (!empty($search)) {
                $time = " and time=$time[0]";
            } else {
                $time = "time=$time[0]";
            }
            $search .= $time;
        }
        // 仓库
        if (!empty($s_material_name)) {
            $material_name = $s_material_name;
            if (!empty($search)) {
                $material_name = " and warehouse='$material_name'";
            } else {
                $material_name = "warehouse='$material_name'";
            }
            $search .= $material_name;
        }
        $cabinet=db('warehouse')->where('is_del',1)->where('id','in',$warehouse)->select();
        $rows=db('other_ck')->where("$search")->paginate(100,false,['query'=>['s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name]]);
        $li=$rows->all();
        foreach ($li as $k=>$l) {
            foreach ($cabinet as $ck) {
                if($l['warehouse']==$ck['name']){
                    $md=$li;
                }
            }
        }
        return view('other_ck_list',['rows'=>$rows,'cabinet'=>$cabinet,'s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name,'md'=>$md]);
    }
    //其他出库页面
    public function other_ck() {
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $f=db('rukuform_xq')->where('state',1)->select();
        for($i=0;$i<count($f);$i++){
            db('rukuform_xq')->where('id',$f[$i]['id'])->update(['sy_count'=>$f[$i]['rk_nums']]);
        }
        $cks = db('warehouse')->where('is_del',1)->select();
        //产品名称
        $goods_name=db('goods_name')->where('is_del',0)->select();
        return view('other_ck',['cks'=>$cks,'goods_name'=>$goods_name]);
    }
    //添加其他出库
    public function tj_ck() {
        Db::startTrans();
        $data=input();
        array_shift($data);
        $delivery_time=strtotime($data['delivery_time']);
        try{
            $id = db('outbound_from')->insertGetId(['transport_id'=>$data['transport_id'],'reachout_name'=>$data['reachout_name'],'delivery_time'=>$delivery_time,'transport'=>$data['transport'],'carid'=>$data['carid'],'driver'=>$data['driver'],'driverphone'=>$data['driverphone'],'workers'=>$data['workers'],'transport_unit'=>$data['transport_unit'],'ck_id'=>$data['ck_id'],'state'=>1]);
            for ($i=0; $i<count($data['delivery_num']); $i++) {
                $time  = time();
                $a = db('outbound_xq_from') -> insert([
                    'chukuid'      => $id,
                    'product_name' => $data['material_name'][$i],
                    'ck_huowei_id' => $data['huowei_name'][$i],
                    'ck_nums'      => $data['huowei_out'][$i],
                    'netweight'    => $data['jin'][$i],
                    'product_time' => strtotime($data['product_time'][$i]),
                    'content'      => $data['detailed'][$i],
                    'create_time'  => $time,
                    'state'        => 0,
                    'qt_ck'=>1
                ]);
                $r = db('rukuform_xq') -> where('is_del', 0) -> where('rk_huowei_id', $data['huowei_name'][$i]) -> find();
                $count = (int)$r['rk_nums'] - (int)$data['huowei_out'][$i];
                $q = db('record') -> insert(['time' => $delivery_time, 'odd_number' => $data['transport_id'], 'task' => '其他出库', 'customer' => $data['reachout_name'], 'early_stage' => $r['rk_nums'], 'qt_chuku' => $data['huowei_out'][$i], 'balance' => $count, 'huowei' => $data['huowei_name'][$i], 'count' => $data['detailed'][$i]]);
                $x = db('rukuform_xq') -> where('is_del', 0) -> where('rk_huowei_id', $data['huowei_name'][$i]) -> update(['rk_nums' => $count]);
                $wa=db('warehouse')->where('id',$data['ck_id'])->find();
                $huowei=db('cabinet')->where('id',$data['huowei_name'][$i])->find();
                $p=db('other_ck')->insert(['time'=>$delivery_time,'warehouse'=>$wa['name'],'product_name'=>$data['material_name'][$i],'product_time'=>strtotime($data['product_time'][$i]),'huowei'=>$huowei['name'],'count'=>$data['huowei_out'][$i],'conter'=>$data['detailed'][$i]]);

            }
            if ($id && $a && $q && $x && $p) {
                // 提交事务
                Db::commit();
            }
        } catch (\Exception $e) {
            $this->error('其他出库失败，请联系管理员');
            // 回滚事务
            Db::rollback();
        }
        return redirect('other_ck_list');
    }
    /**
     * 删除其他出库记录
     */
    public function other_ck_del($id) {
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $r=db('other_ck')->where('id',$id)->delete();
        if($r){
            return redirect('other_ck_list');
        }else{
            $this->error('删除失败,请联系管理员');
        }
    }

    //调拨列表
    public function db()
    {
        static $md;
        $warehouse=self::$stafss['warehouse'];
        $s_transfers_id=input('s_transfers_id');//产品名称
        $s_delivery_time=input('s_delivery_time');//产品日期
        $s_material_name=input('s_material_name');//仓库
        $search = '';
        //产品名称
        if (!empty($s_transfers_id)) {
            $search = 'cp_name like ' . "'%" . $s_transfers_id . '%' . "'";
        }
        // 产品日期
        if (!empty($s_delivery_time)) {
            $time = explode('~', $s_delivery_time);
            foreach ($time as $key) {
                $time[] = strtotime($key);
                array_shift($time);
            }
            if (!empty($search)) {
                $time = " and db_time=$time[0]";
            } else {
                $time = "db_time=$time[0]";
            }
            $search .= $time;
        }
        // 仓库
        if (!empty($s_material_name)) {
            $material_name = $s_material_name;
            if (!empty($search)) {
                $material_name = " and ck_name='$material_name'";
            } else {
                $material_name = "ck_name='$material_name'";
            }
            $search .= $material_name;
        }
        $cabinet=db('warehouse')->where('is_del',1)->where('id','in',$warehouse)->select();
        $rows=db('db_list')
            ->where("$search")
            ->paginate(100,false,['query'=>['s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name]]);
        $li=$rows->all();
        foreach ($li as $k=>$l) {
            foreach ($cabinet as $ck) {
                if($l['ck_name']==$ck['name']){
                    $md=$li;
                }
            }
        }
        return view('db_list',['rows'=>$rows,'cabinet'=>$cabinet,'s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name,'md'=>$md]);
    }
    //调拨出入库
    public function other_db() {
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $f=db('rukuform_xq')->where('state',1)->select();
        for($i=0;$i<count($f);$i++){
            db('rukuform_xq')->where('id',$f[$i]['id'])->update(['sy_count'=>$f[$i]['rk_nums']]);
        }
        $cks = db('warehouse')->where('is_del',1)->select();
        $goods=db('goods_name')->where('is_del',0)->select();
        return view('other_db',['cks'=>$cks,'goods'=>$goods]);
    }
    //调出货位选择
    public function houwei_ck() {
        $sy=isset($_POST['sy']) ? $_POST['sy'] : 0;
        $id=input('id');
        $name=input('name');
        static $md=[];
        $ck=db('cabinet')
            ->where('warehouse_id',$id)
            ->where('is_del',1)
            ->select();
        foreach ($ck as $c) {
            $rukuform=db('rukuform_xq')
                ->where('rukuform_xq.state',1)
                ->where('rukuform_xq.is_del',0)
                ->where('rukuform_xq.sy_count>=1')
                ->where('rukuform_xq.product_name',$name)
                ->join('cabinet','cabinet.id=rukuform_xq.rk_huowei_id')
                ->field('rukuform_xq.*,cabinet.name,cabinet.id as c_id')
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
            $md[$k]['c_id']=$row['c_id'];
            $md[$k]['product_time']=date('Y-m-d',$row['product_time']);
        }
        return $md;
    }
    //调入货位选择
    public function huowei_ck_r() {
        $id=input('id');
        $cabinet=db('cabinet')
            ->join('rukuform_xq','cabinet.id=rukuform_xq.rk_huowei_id and rukuform_xq.is_del=0 and rukuform_xq.state=1','left')
            ->where('cabinet.is_del',1)
            ->where('cabinet.warehouse_id',$id)
            ->field('cabinet.*,rukuform_xq.product_name,rukuform_xq.sy_count,rukuform_xq.product_time')
            ->select();
        for ($i=0;$i<count($cabinet);$i++){
            foreach ($cabinet[$i] as $c=>$k){
                if($cabinet[$i][$c]==null || $cabinet[$i][$c]==''){
                    $cabinet[$i][$c]='无';
                }
            }
        }
        foreach ($cabinet as $k=>$item) {
            $product_time=(int)$item['product_time'];
            if($cabinet[$k]['product_time']!=0){
                $cabinet[$k]['product_time']=date('Y-m-d',$product_time);
            }else{
                $cabinet[$k]['product_time']==0;
            }
        }
        return $cabinet;
    }
    //调出
    public function houwei_cd() {
        $id=input('id');
        $r=db('rukuform_xq')->where('rukuform_xq.is_del',0)->where('rukuform_xq.state',1)->where('rukuform_xq.rk_huowei_id',$id)->join('cabinet','rukuform_xq.rk_huowei_id=cabinet.id')->field('rukuform_xq.*,cabinet.name')->find();
        if(!$r){
            $r=db('cabinet')->where('is_del',1)->where('id',$id)->find();
        }
        return $r;
    }
    /**
     *删除调拨单
     */
    public function db_del($id) {
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $r=db('db_list')->where('id',$id)->delete();
        if($r){
            return redirect('db');
        }else{
            $this->error('删除失败,请联系管理员');
        }
    }

    //tj_db
    public function tj_db() {
        $data=input();
        $warehouse=db('warehouse')->where('id',$data['ck_id'])->find();//仓库名称
        array_shift($data);
        if($data['ck_id']==0 || $data['goods']=='' || $data['d_time']=='' || $data['c_time']=='' || $data['huowei_out']==0 || $data['shu']=='' || empty($data['huowei_name'])){
            $this->error('请完善信息');
        }
        //调出
        $r=db('rukuform_xq')->where('id',$data['rk_id'])->find();
        //调入
        $c=db('rukuform_xq')->where('is_del',0)->where('id',$data['huowei_name'])->find();
        $s=(int)$r['rk_nums']-(int)$data['shu'];
        $c_time=strtotime($data['c_time']);
        $d_time=strtotime($data['d_time']);
        db('record')->insert(['time'=>$d_time,'odd_number'=>$r['transfers_id'],'task'=>'调拨出库','early_stage'=>$r['rk_nums'],'db_chuku'=>$data['shu'],'balance'=>$s,'customer'=>'调拨出库','huowei'=>$r['rk_huowei_id']]);
        db('rukuform_xq')->where('id',$data['rk_id'])->update(['rk_nums'=>$s]);
        //调拨入库
        if($c){
            //修改
            $p=(int)$data['shu']+$c['rk_nums'];
            db('record')->insert(['time'=>$d_time,'odd_number'=>$r['transfers_id'],'task'=>'调拨入库','customer'=>'调拨入库','early_stage'=>$c['rk_nums'],'db_ruku'=>$data['shu'],'balance'=>$p,'huowei'=>$c['rk_huowei_id']]);
            db('rukuform_xq')->where('id',$c['id'])->update(['rk_nums'=>$p]);
        }else{
            //添加
            db('rukuform_xq')->insert(['product_name'=>$data['goods'],'rk_huowei_id'=>$data['huowei_name'],'rk_nums'=>$data['shu'],'product_time'=>$c_time,'state'=>1]);
            db('record')->insert(['time'=>$d_time,'task'=>'调拨入库','customer'=>'调拨入库','early_stage'=>0,'db_ruku'=>$data['shu'],'balance'=>$data['shu'],'huowei'=>$data['huowei_name']]);
        }

        $dc_name=db('cabinet')->where('id',$r['rk_huowei_id'])->find();
        $dr_name=db('cabinet')->where('id',$c['rk_huowei_id'])->find();
        if(!$c){
            $c=db('cabinet')->where('id',$data['huowei_name'])->find();
            db('db_list')->insert(['db_time'=>$d_time,'cp_time'=>$c_time,'cp_name'=>$data['goods'],'dc_name'=>$dc_name['name'],'dr_name'=>$c['name'],'db_num'=>$data['shu'],'ck_name'=>$warehouse['name']]);
        }else{
            db('db_list')->insert(['db_time'=>$d_time,'cp_time'=>$c_time,'cp_name'=>$data['goods'],'dc_name'=>$dc_name['name'],'dr_name'=>$dr_name['name'],'db_num'=>$data['shu'],'ck_name'=>$warehouse['name']]);
        }
        $rows=db('rukuform_xq')->where('is_del',0)->select();
        for($i=0;$i<count($rows);$i++){
            if($rows[$i]['rk_nums']<1){
                db('rukuform_xq')->where('id',$rows[$i]['id'])->update(['is_del'=>1]);
                db('record')->where('huowei',$rows[$i]['rk_huowei_id'])->update(['is_del'=>0]);
            }
        }
        return redirect('db');
    }


    /**
     * 产品冻结
     */
    public function frozen() {
        $id=input('id');
        $s_transfers_id=input('s_transfers_id');//仓库名称
        $s_delivery_time=input('s_delivery_time');//生产日期
        $s_material_name=input('s_material_name');//产品名称
        $s_material_zt=input('s_material_zt');//状态
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告:越权操作');
        }
        $r=db('rukuform_xq')->where('id',$id)->update(['state'=>2]);
        if($r){
            return redirect('index',['s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name,'s_material_zt'=>$s_material_zt]);
        }else{
            $this->error('冻结失败,请联系管理员');
        }
    }
    /**
     * 解除产品冻结
     */
    public function frozen_j() {
        $s_transfers_id=input('s_transfers_id');//仓库名称
        $s_delivery_time=input('s_delivery_time');//生产日期
        $s_material_name=input('s_material_name');//产品名称
        $s_material_zt=input('s_material_zt');//状态
        $id=input('id');
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告:越权操作');
        }
        $r=db('rukuform_xq')->where('id',$id)->update(['state'=>1]);
        if($r){
            return redirect('index',['s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name,'s_material_zt'=>$s_material_zt]);
        }else{
            $this->error('冻结失败,请联系管理员');
        }
    }



    /**
     * 损耗单
     */
    public function wastage_list() {
        static $md;
        $warehouse=self::$stafss['warehouse'];
        $s_transfers_id=input('s_transfers_id');//产品名称
        $s_delivery_time=input('s_delivery_time');//产品日期
        $s_material_name=input('s_material_name');//仓库
        $search = '';
        //产品名称
        if (!empty($s_transfers_id)) {
            $search = 'name like ' . "'%" . $s_transfers_id . '%' . "'";
        }
        // 产品日期
        if (!empty($s_delivery_time)) {
            $time = explode('~', $s_delivery_time);
            foreach ($time as $key) {
                $time[] = strtotime($key);
                array_shift($time);
            }
            if (!empty($search)) {
                $time = " and time=$time[0]";
            } else {
                $time = "time=$time[0]";
            }
            $search .= $time;
        }
        // 仓库
        if (!empty($s_material_name)) {
            $material_name = $s_material_name;
            if (!empty($search)) {
                $material_name = " and ck_name='$material_name'";
            } else {
                $material_name = "ck_name='$material_name'";
            }
            $search .= $material_name;
        }
        $cabinet=db('warehouse')->where('is_del',1)->where('id','in',$warehouse)->select();
        $rows=db('wastage_list')->where("$search")->paginate(100,false,['query'=>['s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name]]);
        $li=$rows->all();
        foreach ($li as $k=>$l) {
            foreach ($cabinet as $ck) {
                if($l['ck_name']==$ck['name']){
                    $md=$li;
                }
            }
        }
        return view('wastage_list',['rows'=>$rows,'cabinet'=>$cabinet,'s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name,'md'=>$md]);
    }
    /**
     * 生成损耗单界面
     */
    public function wastage_add() {
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $f=db('rukuform_xq')->where('state',1)->select();
        for($i=0;$i<count($f);$i++){
            db('rukuform_xq')->where('id',$f[$i]['id'])->update(['sy_count'=>$f[$i]['rk_nums']]);
        }
        $cks = db('warehouse')->where('is_del',1)->select();
        $goods=db('goods_name')->where('is_del',0)->select();
        return view('wastage_add',['goods'=>$goods,'cks'=>$cks]);
    }
    /**
     * 添加损耗单
     */
    public function wastage_inset() {
        Db ::startTrans();
        $data = input();
        $warehouse=db('warehouse')->where('id',$data['ck_id'])->find();
        $time = strtotime($data['d_time']);//损耗日期
        array_shift($data);
        if($data['ck_id']==0 || $data['goods']=='' || $data['d_time']=='' || $data['c_time']=='' || $data['huowei_out']==0 || $data['count']==''){
            $this->error('请完善信息');
        }
        $row   = db('rukuform_xq') -> where('is_del', 0) -> where('id', $data['rk_id']) -> find();
        $count = (int)$row['rk_nums'] - (int)$data['count'];
        try {
            $a    = db('record') -> insert(['time' => $time, 'odd_number' => $row['transfers_id'], 'task' => '损耗单', 'customer' => '损耗单', 'early_stage' => $row['rk_nums'], 'qt_chuku' => $data['count'], 'balance' => $count, 'huowei' => $row['rk_huowei_id'], 'count' => $data['center']]);
            $b    = db('rukuform_xq') -> where('is_del', 0) -> where('id', $data['rk_id']) -> update(['rk_nums' => $count]);
            $rows = db('rukuform_xq') -> where('is_del', 0) -> select();
            for ($i = 0; $i < count($rows); $i++) {
                if ($rows[$i]['rk_nums'] < 1) {
                    db('rukuform_xq') -> where('id', $rows[$i]['id']) -> update(['is_del' => 1]);
                    db('record') -> where('huowei', $rows[$i]['rk_huowei_id']) -> update(['is_del' => 0]);
                }
            }
            $h = db('cabinet') -> where('id', $row['rk_huowei_id']) -> find();
            $r = db('wastage_list') -> insert(['name' => $data['goods'], 'huowei' => $h['name'], 'count' => $data['count'], 'time' => $time, 'center' => $data['center'],'ck_name'=>$warehouse['name']]);
            if ($a && $b && $r) {
                Db ::commit();
            }
        } catch (\Exception $e) {
            $this -> error('添加失败,请重试');
            // 回滚事务
            Db ::rollback();
        }
        return redirect('wastage_list');
    }
    /**
     * 删除损耗单
     */
    public function wastage_del($id) {
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $r=db('wastage_list')->where('id',$id)->delete();
        if($r){
            return redirect('wastage_list');
        }else{
            $this->error('删除失败,请联系管理员');
        }
    }
}
