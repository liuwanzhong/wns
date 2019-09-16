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
        foreach ($sheetContent as $k => $v) {
            $arr['delivery_time']                   = strtotime($v[0]);//发货日期
            $arr['factory_id']               = $v[1];//工厂编号
            $arr['factory_name']          = $v[2];//工厂名
            $arr['transport_id']        = $v[3];//装运单号
            $arr['Delivery_id']                = $v[4];//交货单号
            $arr['reachout_id']               =$v[5];//售达方代码
            $arr['reachout_name']              = $v[6];//售达方名称
            $arr['reachby_id|']                = $v[7];//送达方代码
            $arr['reachby_name']               = $v[8];//送达方名称
            $arr['material_id']                       = $v[9];//物料代码
            $arr['material_name']             = $v[10];//物料名
            $arr['Delivery_num']                = $v[11];//交货数量
            $arr['detailed']              =$v[12];//详细批次
            $arr['is_del']                      = 0;//软删除
            $arr['create_time']                  = time();//创建时间
            if (!empty($v[0])) {//无订单编号数据不写入
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
    // 出库订单
    public function outbound_order(){

    }
}