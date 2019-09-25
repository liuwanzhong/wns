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
            ->join('kc_status','rukuform_xq.rk_status_id=kc_status.id',"left")
            ->join('cabinet','rukuform_xq.rk_huowei_id=cabinet.id','left')
            ->join('warehouse','cabinet.warehouse_id=warehouse.id','left')
            ->where('kc_status.is_del',0)
            ->where('cabinet.is_del',1)
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
        $row=db('goods_name')->where('id',$id)->find();
        return $row;
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

    // 库存转结
    public function show_month(){
        $id=db('record')
        ->field('huowei')
        ->group('huowei')
        ->select();
        $month =  date('m',time());
        $year = date('Y',time());
        $xx=array();
        foreach($id as $rid){
            //初期及结存
            $goods_name=db('rukuform_xq')
            ->where('rk_huowei_id',$rid['huowei'])
            ->field('product_name')
            ->select();
            $jiecun=db('record')
            ->where('YEAR(from_unixtime(time))='."'$year'")
            ->where('MONTH(from_unixtime(time))='."'$month'")
            ->where('huowei='.$rid['huowei'])
            ->field('record.balance jiecun')
            ->order('time desc')
            ->limit(1)
            ->select();
            $chuqi=db('record')
            ->join('cabinet','cabinet.id=record.huowei','left')
            ->where('YEAR(from_unixtime(time))='."'$year'")
            ->where('MONTH(from_unixtime(time))='."'$month'")
            ->where('huowei='.$rid['huowei'])
            ->field('cabinet.name,record.early_stage chuqi')
            ->order('time asc')
            ->limit(1)
            ->select();
            //出入库统计
            $cr=db('record')
            ->where('YEAR(from_unixtime(time))='."'$year'")
            ->where('MONTH(from_unixtime(time))='."'$month'")
            ->where('huowei='.$rid['huowei'])
            ->field('SUM(dh_ruku) rdh,SUM(db_ruku) rdb,SUM(qt_ruku) rqt,SUM(xx_chuku) cxx,SUM(db_chuku) cdb,SUM(qt_chuku) cqt')
            ->select();
            if(!empty($chuqi)){
                $cr['chuqi']=$chuqi[0]['chuqi'];
                $cr['name']=$chuqi[0]['name'];
                $cr['jiecun']=$jiecun[0]['jiecun'];
            }
            $cr['goods_name']=$goods_name[0]['product_name'];
            $cr['ru']=$cr[0]['rdh']+$cr[0]['rdb']+$cr[0]['rqt'];
            $cr['chu']=$cr[0]['cxx']+$cr[0]['cdb']+$cr[0]['cqt'];
            $xx[]=$cr;
        }
        return view('show_month',['xx'=>$xx]);
    }

    // 库存盘点
    public function pandian(){
        $cks = db('warehouse')->where('is_del',1)->select();
        $list = db('pandian')
        ->group('create_time')
        ->where('is_del',0)
        ->select();
        return view('pandian',['cks'=>$cks,'list'=>$list]);
    }
    // 添加盘点页
    public function add_pandian(){
        $id=input('id');
        $list=db('rukuform_xq')
        ->join('cabinet','cabinet.id=rukuform_xq.rk_huowei_id','left')
        ->join('warehouse','cabinet.warehouse_id=warehouse.id','left')
        ->where('warehouse.id',$id)
        ->field('cabinet.name c_name,warehouse.name w_name,rukuform_xq.*')
        ->group('rukuform_xq.id')
        ->select();
        return view('add_pandian',['list'=>$list]);
    }
    // 执行添加
    public function do_add(){
        $data=input();
        // dump($data);exit;
        for ($i=0;$i<count($data['w_name']);$i++){
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
            ]);
        }
    }
    public function xiangxi(){
        $time=input('time');
        $list=db('pandian')
        ->where('create_time',$time)
        ->select();
        return view('xiangxi',['list'=>$list]);
    }
}
