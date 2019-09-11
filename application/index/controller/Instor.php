<?php

namespace app\index\controller;


use think\Controller;
use think\Db;
use think\Request;

class Instor extends Controller
{
    //构造函数，更新货物库存
    public function _initialize() {
//        //查询订单表
//        $orders = db('cw_management')->field('material_name,count(material_name) as nums,transfers_into_net_weight,production_time')->group('material_name ')->select();
//        //查询库存表
//        $goods = db('goods')->where('is_del',1)->select();
//        //开事物
//        Db::startTrans();
//        try{
//            //遍历订单表
//            foreach($orders as $order){
//                //遍历库存表
//                foreach($goods as $good){
//                    //如果相同，就更新
//                    if($good['title'] == $order['material_name']){
//                        $data['title'] = $order['material_name'];
//                        $data['count'] = $order['nums'];
//                        $data['goods_feel'] = $data['count'] - $good['goods_frozen'];
//                        $data['weight'] = $order['transfers_into_net_weight'];
//                        $data['production_time'] = $order['production_time'];
//                        db('goods')->where('id',$good['id'])->update($data);
//                        break;
//                    }else{//不同就新增
//                        $data['title'] = $order['material_name'];
//                        $data['count'] = $order['nums'];
//                        $data['goods_feel'] = $order['nums'];
//                        $data['weight'] = $order['transfers_into_net_weight'];
//                        $data['production_time'] = $order['production_time'];
//                        db('goods')->insert($data);
//                        break;
//                    }
//                }
//            }
//            Db::commit();
//        } catch (\Exception $e) {
//            dump($e->getMessage());
//            Db::rollback();
//        }
    }
    //货物列表
    public function index(){
        $list = db('cw_management')->field('transfers_id as id,material_name as title,sum(transfers_into_num) as count,transfers_into_net_weight as weight,production_time')
            ->group('material_name ')->paginate(20);
//        $list = db('goods')->where('is_del',1)->select();
        return view('index',['list'=>$list]);
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
    //分配库存
}
