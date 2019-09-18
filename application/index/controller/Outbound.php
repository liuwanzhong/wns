<?php
// 出库管理
namespace app\index\controller;


use think\Controller;
use think\Db;
use think\Request;
use think\Loader;
class Outbound extends Controller {
    // 显示页
    public function index(){
        $list = db('system_order')
            // -> where("$search")
            -> order('id desc')
            -> paginate(100);
        return view("index", ['list' => $list]);
    }
    // 系统订单
    public function system_order(){

    }
    // 详细信息回显
    public function record_edit($id){
        $ms = $this -> qx();
        if ($ms == 0) {
            $this -> error('警告：越权操作');
        }
        $row = db('system_order') -> where('id', $id) -> find();
        if ($row['delivery_time'] != 0) {
            $row['delivery_time'] = date("Y/m/d", $row['delivery_time']);
        } else {
            $arr['delivery_time'] = '暂无时间';
        }
        return $row;
    }
    // 详细信息修改
    public function detailed_edit($id){
        $ms = $this -> qx();
        if ($ms == 0) {
            $this -> error('警告：越权操作');
        }
        $data = input();
        $data['delivery_time'] = strtotime($data['delivery_time']);
        $data['updata_time'] = time();
        unset($data['/index/outbound/detailed_edit_html']);
        $r = db('system_order') -> where('id', $id) -> update($data);
        if ($r) {
            return redirect('Outbound/index');
        } else {
            $this -> error('修改失败,请联系管理员');
        }
    }
    // 导入excel
    public function upload_excel(){
        $ms = $this -> qx();
        if ($ms == 0) {
            $this -> error('警告：越权操作');
        }
        //设置文件上传的最大限制
        ini_set('memory_limit', '1024M');
        //加载第三方类文件
        Loader ::import("PHPExcel.PHPExcel");
        //防止乱码
        header("Content-type:application/vnd.ms-excel");
        //实例化主文件
        //$model = new \PHPExcel();
        //接收前台传过来的execl文件
        $file = $_FILES['file'];
        //截取文件的后缀名，转化成小写
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($extension == "xlsx") {
            //2007(相当于是打开接收的这个excel)
            $objReader = \PHPExcel_IOFactory ::createReader('Excel2007');
        } else {
            //2003(相当于是打开接收的这个excel)
            $objReader = \PHPExcel_IOFactory ::createReader('Excel5');
        }

        $objContent   = $objReader -> load($file['tmp_name']);
        $sheetContent = $objContent -> getSheet(0) -> toArray();
        unset($sheetContent[0]);
        // unset($sheetContent[1]);
        // var_dump($sheetContent);exit;
        foreach ($sheetContent as $k => $v) {
            $arr['delivery_time']                   = strtotime($v[0]);//发货日期
            $arr['factory_id']               = $v[1];//工厂编号
            $arr['factory_name']          = $v[2];//工厂名
            $arr['transport_id']        = $v[3];//装运单号
            $arr['Delivery_id']                = $v[4];//交货单号
            $arr['reachout_id']               =$v[5];//售达方代码
            $arr['reachout_name']              = $v[6];//售达方名称
            $arr['reachby_id']                = $v[7];//送达方代码
            $arr['reachby_name']               = $v[8];//送达方名称
            $arr['material_id']                       = $v[9];//物料代码
            $arr['material_name']             = $v[10];//物料名
            $arr['Delivery_num']                = $v[11];//交货数量
            $arr['detailed']              =$v[12];//详细批次
            $arr['is_del']                      = 0;//软删除
            $arr['create_time']                  = time();//创建时间
            if (!empty($v[0]) && $v[0]!=0) {//无发货日期数据不写入
                $res[] = $arr;
            }
        }
        set_time_limit(0);
        $res = Db ::name('system_order') -> insertAll($res);
        if ($res) {
            return redirect('outbound/index');
        } else {
            $this -> error('导入失败');
        }
    }
    // 生成出库单
    public function make_outbound_order(){
        $cd=input('id');
        $id=str_replace(array("[","]","\""),"",$cd);
        $id=explode(',',$id);
        // 发货日期
        $fh=$rows = db('system_order')
        ->field('delivery_time')
        ->where('is_del',0)
        ->where('id','in',$id)
        ->group('delivery_time')
        ->select();
        $cfh=count($fh);
        if($cfh!=1){
            $this -> error('发货日期不一致');
        }
        // 装运单号
        $zy=$rows = db('system_order')
        ->field('transport_id')
        ->where('is_del',0)
        ->where('id','in',$id)
        ->group('transport_id')
        ->select();
        $czy=count($zy);
        // var_dump($zy);exit;
        if($czy!=1){
            $this -> error('装运单号不一致');
        }
        // 售达方
        $sd=$rows = db('system_order')
        ->field('reachby_name')
        ->where('is_del',0)
        ->where('id','in',$id)
        ->group('reachby_name')
        ->select();
        // var_dump($sd);exit;
        $rows = db('system_order')
            ->field('')
            ->where('is_del',0)
            ->where('id','in',$id)
            ->group('material_name')
            ->select();
        return view('make_outbound_order',['rows'=>$rows,'fh'=>$fh,'zy'=>$zy,'sd'=>$sd]);
    }
    // 出库订单
    public function insert(){
        $cd=input();
        var_dump($cd);
    }
}