<?php

namespace app\index\controller;


use think\Controller;
use think\Db;
use think\Loader;

class Cwmanagement extends Controller
{
    // 仓储信息显示
    public function index(){
        $data = input();
        // 调拨订单号
        $search='';
        if(!empty($data['transfers_id'])){
            $search ='transfers_id = '.$data['transfers_id'];
        }
        // 时间转换
        if(!empty($data['delivery_time'])){
            $time=explode('~',$data['delivery_time']);
            foreach($time as $key){
                $time[]=strtotime($key);
                array_shift($time);
            }
            if(!empty($search)){
                $time = ' and delivery_time BETWEEN '.$time['0'].' and '.$time['1'];
            }else{
                $time = 'delivery_time BETWEEN '.$time['0'].' and '.$time['1'];
            }
            $search .= $time;
        }
        // 物料名
        if(!empty($data['material_name'])){
            $material_name=$data['material_name'];
            if(!empty($search)){
                $material_name = ' and material_name like '."'%".$material_name.'%'."'";
            }else{
                $material_name = ' material_name like '."'%".$material_name.'%'."'";
            }
            $search .= $material_name;
        }
        
        $list = db('cw_management')
                ->where("$search")
                ->order('id desc')
                ->paginate(100);
        return view("index",['list'=>$list,'data'=>$data]);
    }
    // 导入excel页面
    public function upload(){
        return view();
    }
    //excel导入
    public function upload_excel(){
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        //设置文件上传的最大限制
        ini_set('memory_limit','1024M');
        //加载第三方类文件
        Loader::import("PHPExcel.PHPExcel");
        //防止乱码
        header("Content-type:text/html;charset=utf-8");
        //实例化主文件
        //$model = new \PHPExcel();
        //接收前台传过来的execl文件
        $file = $_FILES['file'];
        //截取文件的后缀名，转化成小写
        $extension = strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
        if($extension == "xlsx"){
            //2007(相当于是打开接收的这个excel)
            $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
        }else{
            //2003(相当于是打开接收的这个excel)
            $objReader = \PHPExcel_IOFactory::createReader('Excel5');
        }

        $objContent = $objReader -> load($file['tmp_name']);
        $sheetContent = $objContent -> getSheet(0) -> toArray();
        unset($sheetContent[0]);
        foreach ($sheetContent as $k => $v){
            // $arr['id'] = $v[0];
            $arr['transfers_id'] = $v[0];//调拨订单号
            $arr['delivery_id'] = $v[1];//交付单号
            $arr['delivery_time'] = strtotime($v[2]);//交货单实际出库日期
            $arr['transfers_factory'] = $v[3];//调拨出库工厂
            $arr['material_name'] = $v[4];//物料名称
            $arr['production_time'] = strtotime($v[5]);//生产日期
            $arr['transport_type'] = $v[6];//装运类型
            $arr['container_id'] = $v[7];//集装箱号/车皮号
            $arr['zt_net_weight'] = $v[8];//在途净重
            $arr['zt_Gross_weight'] = $v[9];//在途毛重
            $arr['zt_num'] = $v[10];//在途数量
            $arr['Bring_up_num'] = $v[11];//调出数量
            $arr['transfers_into_time'] = strtotime($v[12]);//调拨入库时间
            $arr['transfers_into_addres'] = $v[13];//调拨入库地点
            $arr['transfers_out'] = $v[14];//调拨出库地点
            $arr['Bring_up_Gross_weight'] = $v[15];//调出毛重
            $arr['Bring_up_net_weight'] = $v[16];//调出净重
            $arr['transfers_into_factory'] = $v[17];//调拨入库工厂
            $arr['transfers_into_num'] = $v[18];//调拨入库数量
            $arr['transfers_into_Gross_weight'] = $v[19];//调拨入库毛重
            $arr['transfers_into_net_weight'] = $v[20];//调拨入库净重
            $arr['note'] = $v[21];//备注
            $arr['is_del'] = 0;//软珊瑚
            $arr['state_time'] = time();//软珊瑚
            if(!empty($v[4])){//无物料名数据不写入
                $res[] = $arr;
            }
        }
        set_time_limit(0);
        $res = Db::name('cw_management') -> insertAll($res);
        if($res){
            echo "成功";
        }else{
            echo "失败";
        }
    }
    
    // 详细信息回显
    public function record_edit($id){
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $id=input('id');
        $row=db('cw_management')->where('id',$id)->find();
        if($row['delivery_time']!=0){
            $row['delivery_time'] = date("Y/m/d",$row['delivery_time']);
        }else{
            $arr['delivery_time'] = '暂无时间';
        }
        if($row['transfers_into_time']!=0){
            $row['transfers_into_time'] = date("Y/m/d",$row['transfers_into_time']);
        }else{
            $arr['transfers_into_time'] = '暂无时间';
        }
        if($row['production_time']!=0){
            $row['production_time'] = date("Y/m/d",$row['production_time']);
        }else{
            $arr['production_time'] = '暂无时间';
        }
        return $row;
    }
    // 修改该信息
    public function record_update($id){
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $data = input();
        unset($data['/index/cwmanagement/record_update_html']);
        $r=db('cw_management')->where('id',$id)->update($data);
        if($r){
            return redirect('cwmanagement/index');
        }else{
            $this->error('修改失败,请联系管理员');
        }

    }
    // 删除选中
    public function check_record_del(){
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $id=input('id');
        if(!empty($id)){
            $r=db('cw_management')->where('id',$id)->delete();
            if($r){
                return redirect('cwmanagement/index');
            }else{
                $this->error('删除失败');
            }
        }else{
            $data=input();
            $da=implode(",",$data['check_all']);
            $key='on,';

            if(strpos($da,$key)!==false){
                $da=mb_substr($da,3);
            }
            

            $r=db('cw_management')->where("id in($da)")->delete();
            if ($r){
                $msg=["error"=>1,'ts'=>"删除成功"];
            }else{
                $msg=["error"=>101,'ts'=>'删除失败'];
            }
            return $msg;
        }
        
    }
    // 清除6
    public function clink(){
        $time=time();
        $res=db('cw_management')
            ->where("state_time <= ($time-15552000) and is_del = 1")
            ->delete();
        if($res){
            return redirect('cwmanagement/index');
        }else{
            $this->error('删除失败');
        }
    }
}
