<?php


namespace app\index\controller;

use think\Controller;

class Staffs extends Controller {
    public static $md=[];
    //管理员列表
    public function staffs() {
        $rows=db('staffs')
            ->where('staffs.is_del',1)
            ->join('department','department.id=staffs.department_id','left')
            ->field('staffs.*,department.name')
            ->select();
        return view('staffs/staffs',['rows'=>$rows]);
    }
    //添加管理员视图
    public function staffs_add() {
        $rows=db('department')->where('is_del',1)->select();
        $this->read(0);
        $md=self::$md;
        return view('staffs_add',['rows'=>$rows,'md'=>$md]);
    }
    //添加管理员
    public function staffs_insert() {
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $data=input();
        if($data['department_id']==0){
            $msg=["error"=>103,'ts'=>"请选择部门"];
            return $msg;
        }
        array_shift($data);
        $count=db('staffs')->where('staffs_number',$data['staffs_number'])->where('is_del',1)->count();
        if ($count>0){
            $msg=["error"=>102,'ts'=>"编号已存在"];
            return $msg;
        }
        if($data['password']==''){
            $data['password']=md5(md5(123456));
        }
        $data['password']=md5(md5($data['password']));
        if(!empty($data['obj'])){
            $data['power']=implode(",",$data['obj']);
            unset($data['obj']);
        }
        $r=db('staffs')->insert($data);
        if ($r){
            $msg=["error"=>1,'ts'=>"添加成功"];
        }else{
            $msg=["error"=>101,'ts'=>'添加失败,请联系管理员'];
        }
        return $msg;
    }
    //删除管理员
    public function staffs_del($id) {
        if($id==1){
            $this->error('警告--越权操作,超级管理员不能删除');
        }
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $r=db('staffs')->where('id',$id)->update(['is_del'=>0]);
        if($r){
            return redirect('staffs');
        }else{
            $this->error('删除失败,请联系管理员');
        }
    }
    //修改管理员视图
    public function staffs_edit($id) {
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        //部门
        $rows=db('department')->where('is_del',1)->select();
        //权限
        $this->read(0);
        $md=self::$md;
        //个人资料
        $find=db('staffs')->where('id',$id)->find();
        //权限数组
        $sz=explode(",",$find['power']);
        return view('staffs_edit',['rows'=>$rows,'md'=>$md,'find'=>$find,'sz'=>$sz,'id'=>$id]);
    }
    //修改管理员
    public function staffs_update() {
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $data=input();
        array_shift($data);
        $count=db('staffs')->where('staffs_number',$data['staffs_number'])->where('is_del',1)->where("id!=$data[id]")->count();
        if ($count>0){
            $msg=["error"=>102,'ts'=>"编号已存在"];
            return $msg;
        }
        if(!empty($data['password'])){
            $data['password']=md5(md5($data['password']));
        }else{
            unset($data['password']);
        }
        if(!empty($data['obj'])){
            $data['power']=implode(",",$data['obj']);
            unset($data['obj']);
        }
        $r=db('staffs')->update($data);
        if ($r){
            $msg=["error"=>1,'ts'=>"修改成功"];
        }else{
            $msg=["error"=>101,'ts'=>'修改失败,请联系管理员'];
        }
        return $msg;
    }


    //找子级
    public function read($pid)
    {
        $res = db('pow')->where('is_del',1)->where('parent_id',$pid)->field('id,pow_name')->select();
        $data = array();
        foreach($res as $k => $v){
            $res2 = db('pow')->where('is_del',1)->where('parent_id',$v['id'])->field('id,pow_name')->select();
            $data2 = array();
            foreach($res2 as $k2 => $v2){
                $res3 = db('pow')->where('is_del',1)->where('parent_id',$v2['id'])->field('id,pow_name')->select();
                $data3 = array();
                foreach($res3 as $k3 => $v3){
                    $data3[$k3] = $v3;
                }
                $data2[$k2] = $v2;
                $data2[$k2]['child'] = $data3;
            }
            $data[$k] = $v;
            $data[$k]['child'] = $data2;
        }
        $this->assign('menu',$data);
        self::$md=$data;
    }




    //部门列表
    public function department() {
        $rows=db('department')->where('is_del',1)->select();
        return view('department',['rows'=>$rows]);
    }
    //添加部门
    public function department_add() {
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $data=input();
        array_shift($data);
        $r=db('department')->insert($data);
        if($r){
            return redirect('department');
        }else{
            $this->error('添加部门失败,请联系管理员');
        }
    }
    //查看部门
    public function department_select() {
        $ms=$this->qx();
        if($ms==0){
            $msg=['error'=>0,'msg'=>'警告:越权操作'];
            return $msg;
        }
        $id=$_POST['id'];
        $row=db('department')->where('id',$id)->find();
        return $row;
    }
    //修改部门视图
    public function department_xs() {
        $ms=$this->qx();
        if($ms==0){
            $msg=['error'=>0,'msg'=>'警告:越权操作'];
            return $msg;
        }
        $id=$_POST['id'];
        $row=db('department')->where('id',$id)->find();
        return $row;
    }
    //修改部门
    public function department_edit() {
        $data=input();
        array_shift($data);
        $r=db('department')->update($data);
        if($r){
            return redirect('department');
        }else{
            $this->error('修改部门失败,请联系管理员');
        }
    }
    //删除部门
    public function department_del($id=0) {
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        if($id==0){
            $this->error('缺少必要参数,请重试');
        }
        $r=db('department')->where('id',$id)->update(['is_del'=>0]);
        if($r){
            return redirect('department');
        }else{
            $this->error('删除部门失败,请联系管理员');
        }
    }


    //权限列表
    public function pow() {
        $rows=db('pow')->where('is_del',1)->select();
        $rows=$this->order($rows);
        $ca=db('pow')->where('is_del',1)->where('cj!=3')->select();
        $c=$this->order_two($ca);

        return view('pow',['rows'=>$rows,'ca'=>$c]);
    }
    //添加权限
    public function pow_add() {
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $data=input();
        array_shift($data);
        $count=db('pow')->where('pow_name',$data['pow_name'])->where('is_del',1)->count();
        if($count>0){
            $this->error('权限名已被使用');
        }
        if($data['parent_id']==0){
            $data['cj']=1;
        }else{
            $row=db('pow')->where('id',$data['parent_id'])->find();
            $data['cj']=$row['cj']+1;
        }
        if($data['cj']>=3){
            $data['state']='隐藏';
        }
        $r=db('pow')->insert($data);
        if($r){
            return redirect('pow');
        }else{
            $this->error('添加权限失败,请联系管理员');
        }
    }
    //删除权限
    public function pow_del()
    {
        $id=input('id');
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $count=db('pow')->where('parent_id',$id)->where('is_del',1)->count();
        if($count>0){
            $msg=['error'=>0,'msg'=>'该规则内还有子级,不能删除'];
            return $msg;
        }
        $r=db('pow')->where('id',$id)->update(['is_del'=>0]);
        if($r!==false){
            $msg=['error'=>1,'msg'=>'删除成功'];
        }else{
            $msg=['error'=>101,'msg'=>'删除失败,请联系管理员'];
        }
        return $msg;
    }
    //修改权限状态
    public function pow_state($id)
    {
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $state=db('pow')->where('id',$id)->find();
        if($state['cj']==3){
            $this->error('该规则不能显示');
        }
        if($state['state']=='正常'){
            db('pow')->where('id',$id)->update(['state'=>'隐藏']);
        }else{
            db('pow')->where('id',$id)->update(['state'=>'正常']);
        }
        return redirect('Staffs/pow');
    }
    //数据回显
    public function pow_up_edit()
    {
        $id=$_POST['id'];
        $row=db('pow')->where('id',$id)->find();
        return $row;
    }
    //修改权限
    public function pow_edit() {
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $data=input();
        $count=db('pow')->where('pow_name',$data['pow_name'])->where("id!=$data[id]")->where('is_del',1)->count();
        if($count>0){
            $this->error('规则名已存在');
        }
        $row=db('pow')->where('id',$data['parent_id'])->find();
        $data['cj']=$row['cj']+1;
        if($data['cj']==2){
            $data['state']=='隐藏';
        }
        array_shift($data);
        $r=db('pow')->update($data);
        if($r){
            return redirect('Staffs/pow');
        }else{
            $this->error('修改失败');
        }
    }


    //权限列表
    public function powText() {
        $rows=db('pow')->where('is_del',1)->select();
        $rows=$this->order($rows);
        $ca=db('pow')->where('is_del',1)->where('cj!=3')->select();
        $c=$this->order_two($ca);

        return json(['data' => $rows]);
//        return view('pow',['rows'=>$rows,'ca'=>$c]);
    }
}
