<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
use think\Request;
class Instor extends Controller
{
    //货物列表
    public function index(){
        $orders = db('rukuform_xq')->field('a.*,b.title,c.name')
            ->alias('a')
            ->join('kc_status b','b.id = a.rk_status_id','left')
            ->join('cabinet c','c.id = a.rk_huowei_id','left')
            ->where('a.is_del',0)->where('b.is_del',0)->where('c.is_del',1)->where('state',1)->select();
        foreach($orders as &$v){
            $v['ckname'] = db('rukuform')->field('a.id,a.ck_id,b.name')
                ->alias('a')
                ->join('warehouse b','a.ck_id = b.id')
                ->where('a.is_del',0)->where('b.is_del',1)
                ->where('a.id',$v['rukuid'])->find()['name'];
//            dump($v);
        }
        echo db('rukuform_xq')->getlastSql();
       dump($orders);exit;
        return view('index2',['orders'=>$orders]);
    }
    public function index2(){
        $list = db('rukuform')->field('a.*,b.name')->alias('a')
            ->join('warehouse b','b.id = a.ck_id','left')
            ->where('a.is_del',0)->where('a.state',1)->where('b.is_del',1)->paginate(20);
        return view('index',['list'=>$list]);
    }
    //查看入库产品
    public function show($id){
        $main = db('rukuform')->where('is_del',0)->where('id',$id)->find();
        $main['ck_id'] = db('warehouse')->where('is_del',1)->where('id',$main['ck_id'])->find()['name'];
//        dump($main['ck_id']);exit();
        $orders = db('rukuform_xq')->field('a.*,b.title,c.name')
            ->alias('a')
            ->join('kc_status b','b.id = a.rk_status_id','left')
            ->join('cabinet c','c.id = a.rk_huowei_id','left')
            ->where('a.rukuid',$id)->where('a.is_del',0)->where('b.is_del',0)->where('c.is_del',1)->select();
//        dump($orders);exit;
        return view('show',['main'=>$main,'orders'=>$orders]);
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


    public function search(){
        return view('search');
    }
}
