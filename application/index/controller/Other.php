<?php


namespace app\index\controller;


use think\Controller;

class Other extends Controller {
    //视图
//    public function transport_tab(){
//        $rows=db('fayunbb')->select();
//        return ['code'=> 0, "data" => $rows];
//    }
    //添加
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
    //修改
    public function edit()
    {
        $ms=$this->qx();
        if($ms==0){
        $this->error('警告：越权操作');
        }
        $data=input();
        array_shift($data);
        $r=db('fayunbb')->update($data['obj']);
        if($r!==false){
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
        $rows=db('fayunbb')->where('state',0)->paginate(50);
        return view('transport_ss',['rows'=>$rows]);
    }

    public function transport_ss_tab(){
        $page = input('get.limit');
        $list=db('fayunbb')->where('state',0)->paginate($page);
        $arr = $list -> all();
        $rows=db('fayunbb')->where('state',0)->select();
        $rows_length = count($rows);

        return ['code'=> 0, "count" => $rows_length, 'data' => $arr, 'page'=> $page];
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
