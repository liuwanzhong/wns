<?php

namespace app\index\controller;


use think\Controller;
use think\Request;

class Instor extends Controller
{
    //货物列表
    public function index(){
        $list = db('goods')->where('is_del',1)->select();
        return view('index',['list'=>$list]);
    }
    //添加数据
    public function insert(Request $request){
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
    //分配库存
}
