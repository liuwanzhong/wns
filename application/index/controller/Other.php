<?php


namespace app\index\controller;


use think\Controller;

class Other extends Controller {
    //视图
    public function transport()
    {
        $rows=db('fayunbb')->paginate(100);
        return view('transport',['rows'=>$rows]);
    }
    //添加
    public function insert()
    {
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $data=input();
        array_shift($data);
        $r=db('fayunbb')->insert($data['obj']);
        if($r){
            $data=['error'=>0,'msg'=>'添加成功'];
        }else{
            $data=['error'=>1,'msg'=>'添加失败'];
        }
        return $data;
    }
    //删除
    public function del($id)
    {
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $del=db('fayunbb')->where('id',$id)->delete();
        if($del){
            return redirect('transport');
        }else{
            $this->error('删除失败，请联系管理员');
        }
    }
    //运输报表修改审核
    public function transport_ss()
    {
        $rows=db('fayunbb')->where('state',0)->paginate(100);
        return view('transport_ss',['rows'=>$rows]);
    }
    //修改
    public function edit()
    {
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $data=input();
        array_shift($data);
        $r=db('fayunbb')->update($data);
        if($r){
            return redirect('transport_ss');
        }else{
            $this->error('修改失败，请联系管理员');
        }
    }
    //审核
    public function shenhe($id)
    {
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $r=db('fayunbb')->where('id',$id)->update(['state'=>1]);
        if($r){
            return redirect('transport_ss');
        }else{
            $this->error('审核失败，请联系管理员');
        }
    }
}
