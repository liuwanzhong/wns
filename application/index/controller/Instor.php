<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
use think\Request;
class Instor extends Controller
{
    //货物列表
    public function index(){
        $s_transfers_id=input('s_transfers_id');//仓库名称
        $s_delivery_time=input('s_delivery_time');//生产日期
        $s_material_name=input('s_material_name');//产品名称
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
        $orders = db('rukuform_xq')
            ->where('rukuform_xq.state',1)
            ->where('rukuform_xq.is_del',0)
            ->join('kc_status','rukuform_xq.rk_status_id=kc_status.id and kc_status.is_del=0',"left")
            ->join('cabinet','rukuform_xq.rk_huowei_id=cabinet.id and cabinet.is_del=1','left')
            ->join('warehouse','cabinet.warehouse_id=warehouse.id','left')
            ->where("$search")
            ->order('rukuform_xq.create_time desc')
            ->field('rukuform_xq.*,kc_status.title,cabinet.name')
            ->select();
        foreach($orders as &$v) {
            $v['ckname'] = db('rukuform') -> field('a.id,a.ck_id,b.name')
                               -> alias('a')
                               -> join('warehouse b', 'a.ck_id = b.id')
                               -> where('a.is_del', 0) -> where('b.is_del', 1)
                               -> where('a.id', $v['rukuid'])->find()['name'];
            if($v['rukuid']==null){
                $v['ckname']=db('cabinet')->where('cabinet.id',$v['rk_huowei_id'])->join('warehouse','warehouse.id=cabinet.warehouse_id')->field('warehouse.name')->find()['name'];
            }
        }

        //仓库
        $ware=db('warehouse')->where('is_del',1)->select();
        return view('index2',['orders'=>$orders,'ware'=>$ware,'s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name]);
    }
    //查看入库产品
    public function show($id){
        $rows=db('record')->where('huowei',$id)->where('is_del',1)->select();
        return view('show',['rows'=>$rows]);
    }

    //添加数据
    public function insert(Request $request){
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $data = $request->param();
        unset($data['/index/instor/insert_html']);
        $rs = db('goods')->insert($data);
        if($rs){
           back_location("添加成功",url('instor/index'));
        }else{
            $this->error("添加失败");
        }
    }
    //冻结模态框接口
    public function frozen($id){
        $list = db('goods')->where('is_del',1)->where('id',$id)->find();
        return json_encode($list);
    }
    //修改冻结货物
    public function editfrozen(Request $request){
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $data = $request->param();
//        var_dump($data);
        unset($data['/index/instor/editfrozen_html']);
        $id = $data['editfrozenid'];
        $row = db('goods')->where('is_del',1)->where('id',$id)->find();
        if($row['goods_feel'] < $data['goods_frozen']){
            back_location("冻结数量超出",url('instor/index'));
        }
        unset($data['editfrozenid']);
        $data['update_time'] = time();
        $data['goods_feel'] = $row['goods_feel'] - $data['goods_frozen'];
        $data['goods_frozen'] = $row['goods_frozen'] + $data['goods_frozen'];
        $rs = db('goods')->where('is_del',1)->where('id',$id)->update($data);
        if($rs){
            back_location("冻结成功",url('instor/index'));
        }else{
            $this->error("冻结失败");
        }
    }
    //解冻库存
    public function editfeel(Request $request){
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $data = $request->param();
        unset($data['/index/instor/editfeel_html']);
        $id = $data['editfrozenid'];
        $row = db('goods')->where('is_del',1)->where('id',$id)->find();
        if($row['goods_frozen'] < $data['goods_feel']){
            back_location("解冻数量超出",url('instor/index'));
        }
        unset($data['editfrozenid']);
        $data['update_time'] = time();
        $data['goods_frozen'] = $row['goods_frozen'] - $data['goods_feel'];
        $data['goods_feel'] = $row['goods_feel'] + $data['goods_feel'];
        $rs = db('goods')->where('is_del',1)->where('id',$id)->update($data);
        if($rs){
            back_location("解冻成功",url('instor/index'));
        }else{
            $this->error("解冻失败");
        }
    }
    //删除
    public function delete($id){
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $row = db('goods')->where('is_del',1)->where('id',$id)->find();
        if($row['count'] > 0 ){
            back_location("该商品还有库存，无法删除",url('instor/index'));
        }
        $rs = db('goods')->where('id',$id)->update(['is_del'=>0,'delete_time'=>time()]);
        if($rs){
            back_location("删除成功",url('instor/index'));
        }else{
            $this->error("删除失败");
        }
    }




    //其他入库
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
    //货位选择
    public function huowei($id)
    {
        $rows=db('cabinet')->where('is_del',1)->where('warehouse_id',$id)->select();
        return $rows;
    }
    //绑定产品名称
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
    //添加其他入库
    public function tj_rk() {
        $data=input();
        $time=time();
        array_shift($data);
        $r=db('record')->where('is_del',1)->where('huowei',$data['rk_huowei_id'])->count();
        if($r){
            $c=db('rukuform_xq')->where('is_del',0)->where('rk_huowei_id',$data['rk_huowei_id'])->find();
            $num=$c['rk_nums']+$data['rk_nums'];
            //存在则添加记录
            $s=db('record')->insert(['time'=>$time,'odd_number'=>$data['odd_number'],'task'=>'其他入库','customer'=>$data['customer'],'early_stage'=>$c['rk_nums'],'qt_ruku'=>$data['rk_nums'],'balance'=>$num,'huowei'=>$data['rk_huowei_id']]);
            if(!$s){
                $this->error('其他入库失败');
            }else{
                db('rukuform_xq')->where('is_del',0)->where('rk_huowei_id',$data['rk_huowei_id'])->update(['rk_nums'=>$num]);
            }
        }else{
            //不存在则添加库存
            $userintime=strtotime($data['userintime']);
            $id=db('rukuform')->insertGetId(['shipmentnum'=>$data['shipmentnum'],'transport'=>$data['transport'],'carid'=>$data['carid'],'stevedore'=>$data['stevedore'],'ck_id'=>$data['ck_id'],'userintime'=>$userintime]);
            db('rukuform_xq')->insert(['factory'=>$data['factory'],'transfers_id'=>$data['odd_number'],'product_name'=>$data['customer'],'rk_status_id'=>$data['rk_status_id'],'rk_huowei_id'=>$data['rk_huowei_id'],'rk_nums'=>$data['rk_nums'],'product_time'=>$userintime,'netweight'=>$data['mao'],'Grossweight'=>$data['jing'],'state'=>1,'rukuid'=>$id]);
            db('record')->insert(['time'=>$time,'odd_number'=>$data['odd_number'],'task'=>'其他入库','customer'=>$data['factory'],'early_stage'=>0,'qt_ruku'=>$data['rk_nums'],'balance'=>$data['rk_nums'],'huowei'=>$data['rk_huowei_id']]);
        }
    }


    //其他出库
    public function other_ck() {
        $f=db('rukuform_xq')->where('state',1)->select();
        for($i=0;$i<count($f);$i++){
            db('rukuform_xq')->where('id',$f[$i]['id'])->update(['sy_count'=>$f[$i]['rk_nums']]);
        }
        $cks = db('warehouse')->where('is_del',1)->select();
        //产品名称
        $goods_name=db('goods_name')->where('is_del',0)->select();
        return view('other_ck',['cks'=>$cks,'goods_name'=>$goods_name]);
    }
    //提交出库
    public function tj_ck() {

        $data=input();

        array_shift($data);
        $id = db('outbound_from')->insertGetId(['transport_id'=>$data['transport_id'],'reachout_name'=>$data['reachout_name'],'delivery_time'=>strtotime($data['delivery_time']),'transport'=>$data['transport'],'carid'=>$data['carid'],'driver'=>$data['driver'],'driverphone'=>$data['driverphone'],'workers'=>$data['workers'],'transport_unit'=>$data['transport_unit'],'ck_id'=>$data['ck_id'],'total_shu'=>$data['all_count'],'total_zhong'=>$data['all_weight'],'state'=>1]);
        for ($i=0;$i<count($data['delivery_num']);$i++){
            $time=time();
            db('outbound_xq_from')->insert([
                'chukuid'=>$id,
                'product_name'=>$data['material_name'][$i],
                'ck_huowei_id'=>$data['huowei_name'][$i],
                'ck_nums'=>$data['huowei_out'][$i],
                'netweight'=>$data['jin'][$i],
                'product_time'=>strtotime($data['product_time'][$i]),
                'content'=>$data['detailed'][$i],
                'create_time'=>$time,
                'state'=>0
            ]);
            $r=db('rukuform_xq')->where('is_del',0)->where('rk_huowei_id',$data['huowei_name'][$i])->find();
            $count=(int)$r['rk_nums']-(int)$data['huowei_out'][$i];
            db('record')->insert(['time'=>$time,'odd_number'=>$data['transport_id'],'task'=>'其他出库','customer'=>$data['reachout_name'],'early_stage'=>$r['rk_nums'],'qt_chuku'=>$data['huowei_out'][$i],'balance'=>$count,'huowei'=>$data['huowei_name'][$i]]);
            db('rukuform_xq')->where('is_del',0)->where('rk_huowei_id',$data['huowei_name'][$i])->update(['rk_nums'=>$count]);
        }

    }


    //调拨出入库
    public function other_db() {
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

    //tj_db
    public function tj_db() {
        $data=input();
        array_shift($data);
//        dump($data);exit;
        //调拨出库
        $r=db('rukuform_xq')->where('id',$data['rk_id'])->find();
        $s=(int)$r['rk_nums']-(int)$data['shu'];
        $c_time=strtotime($data['c_time']);
        $d_time=strtotime($data['d_time']);
        db('record')->insert(['time'=>$d_time,'odd_number'=>$r['transfers_id'],'task'=>'调拨出库','early_stage'=>$r['rk_nums'],'db_chuku'=>$data['shu'],'balance'=>$s,'customer'=>'调拨出库','huowei'=>$r['rk_huowei_id']]);
        db('rukuform_xq')->where('id',$data['rk_id'])->update(['rk_nums'=>$s]);
        //调拨入库
        $c=db('rukuform_xq')->where('is_del',0)->where('id',$data['huowei_name'])->find();
        if($c){
            //修改
            $p=(int)$data['shu']+$c['rk_nums'];
            db('record')->insert(['time'=>$d_time,'odd_number'=>$r['transfers_id'],'task'=>'调拨入库','customer'=>'调拨入库','early_stage'=>$c['rk_nums'],'db_ruku'=>$data['shu'],'balance'=>$p,'huowei'=>$c['rk_huowei_id']]);
            db('rukuform_xq')->where('id',$c['id'])->update(['rk_nums'=>$p]);
        }else{
            db('rukuform_xq')->insert(['product_name'=>$data['goods'],'rk_huowei_id'=>$data['huowei_name'],'rk_nums'=>$data['shu'],'product_time'=>$c_time,'state'=>1]);
            db('record')->insert(['time'=>$d_time,'task'=>'调拨入库','customer'=>'调拨入库','early_stage'=>0,'db_ruku'=>$data['shu'],'balance'=>$data['shu'],'huowei'=>$data['huowei_name']]);
        }
        return redirect('index');
    }
}
