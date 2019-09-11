<?php


namespace app\index\controller;


use think\Controller;

class Run extends Controller {
    //地区库房列表
    public function warehouse() {
        $rows=db('warehouse')->where('is_del',1)->select();
        return view('warehouse',['rows'=>$rows]);
    }
    //添加库房
    public function warehouse_add() {
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $data=$_POST['name'];
        $content=$_POST['content'];
        $count=db('warehouse')->where('name',$data)->where('is_del',1)->count();
        if($count>0){
            $this->error('库房名称已存在');
        }
        $r=db('warehouse')->insert(['name'=>$data,'content'=>$content]);
        if($r){
            return redirect('warehouse');
        }else{
            $this->error('添加失败,请联系管理员');
        }
    }
    //删除库房
    public function warehouse_del($id) {
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        if($id==0){
            $this->eror('缺少必要参数,请重试');
        }
        $r=db('warehouse')->where('id',$id)->update(['is_del'=>0]);
        if($r){
            return redirect('warehouse');
        }else{
            $this->error('删除库房失败,请联系管理员');
        }
    }
    //查看库房
    public function warehouse_select() {
        $id=$_POST['id'];
        $row=db('warehouse')->where('id',$id)->find();
        return $row;
    }
    //修改库房
    public function warehouse_edit() {
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $data=input();
        array_shift($data);
        $count=db('warehouse')->where('name',$data['name'])->where("id!=$data[id]")->where('is_del',1)->count();
        if($count>0){
            $this->error('库房名称已存在');
        }
        $r=db('warehouse')->update($data);
        if($r!==false){
            return redirect('warehouse');
        }else{
            $this->error('修改库房失败,请联系管理员');
        }
    }


    //货位列表
    public function cabinet() {
        $rows=db('cabinet')
            ->where('cabinet.is_del',1)
            ->join('warehouse','cabinet.warehouse_id=warehouse.id')
            ->field('cabinet.*,warehouse.name as w_name')
            ->select();
        $ware=db('warehouse')->where('is_del',1)->select();
        return view('cabinet',['rows'=>$rows,'ware'=>$ware]);
    }
    //添加货位
    public function cabinet_add() {
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $data=input();
        array_shift($data);
        if($data['warehouse_id']==0){
            $this->error('请选择货位所在仓库');
        }
        $count=db('cabinet')->where('name',$data['name'])->where('warehouse_id',$data['warehouse_id'])->count();
        if($count>0){
            $this->error("$data[name]货位已存在");
        }
        $r=db('cabinet')->insert($data);
        if($r){
            return redirect('cabinet');
        }else{
            $this->error('添加失败,请联系管理员');
        }
    }
    //删除货位
    public function cabinetdel($id) {
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        if($id==0){
            $this->eror('缺少必要参数,请重试');
        }
        $r=db('cabinet')->where('id',$id)->update(['is_del'=>0]);
        if($r){
            return redirect('cabinet');
        }else{
            $this->error('删除货位失败,请联系管理员');
        }
    }
    //查看货位
    public function cabinet_select() {
        $id=$_POST['id'];
        $row=db('cabinet')->where('id',$id)->find();
        return $row;
    }
    //修改货位
    public function cabinet_edit() {
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }

        $data=input();
        array_shift($data);
        if($data['warehouse_id']==0){
            $this->error('请选择货位所在仓库');
        }
        $count=db('cabinet')->where('name',$data['name'])->where("id!=$data[id]")->where("warehouse_id",$data['warehouse_id'])->where('is_del',1)->count();
        if($count>0){
            $this->error("$data[name]货位已存在");
        }
        $r=db('cabinet')->update($data);
        if($r!==false){
            return redirect('cabinet');
        }else{
            $this->error('修改库房失败,请联系管理员');
        }
    }


    //库存列表
    public function index(){
        $list = db('kc_status')->where('is_del',0)->paginate(20);
        return view('index',['list'=>$list]);
    }
    //添加状态
    public function insert(){
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $data = input();
        array_shift($data);
        $rs = db('kc_status')->insert($data);
        if($rs){
            return redirect('index');
        }else{
            $this->error("添加失败");
        }
    }
    //修改查看接口
    public function show($id){
        $list = db('kc_status')->where('is_del',0)->where('id',$id)->find();
        return json_encode($list);
    }
    //修改
    public function edit(){
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $data = input();
        array_shift($data);
        $id = $data['myid'];
        unset($data['myid']);
        $data['update_time'] = time();
        $rs = db('kc_status')->where('id',$id)->update($data);
        if($rs){
            return redirect('index');
        }else{
            $this->error("修改失败");
        }
    }
    //删除
    public function delete($id){
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $rs = db('kc_status')->where('id',$id)->update(['is_del'=>1,'delete_time'=>time()]);
        if($rs){
            return redirect('index');
        }else{
            $this->error("删除失败");
        }
    }
}
