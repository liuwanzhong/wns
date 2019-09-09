<?php

namespace app\index\controller;


use think\Controller;
use think\Db;
use think\Loader;

class ceshi extends Controller
{
    
    public function upload(){
        return view();
    }
    //excel导入
    public function upload_excel(){
        // var_dump($_FILES);exit;
        //设置文件上传的最大限制
        ini_set('memory_limit','1024M');
        //加载第三方类文件
        Loader::import("PHPExcel.PHPExcel");
        //防止乱码
        header("Content-type:text/html;charset=utf-8");
        //实例化主文件
//        $model = new \PHPExcel();
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
            $arr['id'] = $v[0];
            $arr['transfers_id'] = $v[1];//调拨订单号
            $arr['delivery_id'] = $v[2];//交付单号
            $arr['delivery_time'] = $v[3];//交货单实际出库日期
            $arr['transfers_factory'] = $v[4];//调拨出库工厂
            $arr['material_name'] = $v[5];//物料名称
            $arr['production_time'] = $v[6];//生产日期
            $arr['transport_type'] = $v[7];//装运类型
            $arr['container_id'] = $v[8];//集装箱号/车皮号
            $arr['zt_net_weight'] = $v[9];//在途净重
            $arr['zt_Gross_weight'] = $v[10];//在途毛重
            $arr['zt_num'] = $v[11];//在途数量
            $arr['Bring_up_num'] = $v[12];//调出数量
            $arr['transfers_into_time'] = $v[13];//调拨入库时间
            $arr['transfers_into_addres'] = $v[14];//调拨入库地点
            $arr['transfers_out'] = $v[15];//调拨出库地点
            $arr['Bring_up_Gross_weight'] = $v[16];//调出毛重
            $arr['Bring_up_net_weight'] = $v[17];//调出净重
            $arr['transfers_into_factory'] = $v[18];//调拨入库工厂
            $arr['transfers_into_num'] = $v[19];//调拨入库数量
            $arr['transfers_into_Gross_weight'] = $v[20];//调拨入库毛重
            $arr['transfers_into_net_weight'] = $v[21];//调拨入库净重
            $arr['note'] = $v[22];//备注
            $res[] = $arr;
        }
        // var_dump($res);exit;
        $res = Db::name('ceshi') -> insertAll($res);
        if($res){
            echo "成功";
        }else{
            echo "失败";
        }
    }

    public function out_excel(){
        $xlsData = Db('ceshi')->select();
        Vendor('PHPExcel.PHPExcel');//调用类库,路径是基于vendor文件夹的
        Vendor('PHPExcel.PHPExcel.Worksheet.Drawing');
        Vendor('PHPExcel.PHPExcel.Writer.Excel2007');
        $objExcel = new \PHPExcel();
        //set document Property
        $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel2007');
 
        $objActSheet = $objExcel->getActiveSheet();
        $key = ord("A");
        $letter =explode(',',"A,B");
        $arrHeader = array('id','姓名');
        //填充表头信息
        $lenth =  count($arrHeader);
        for($i = 0;$i < $lenth;$i++) {
            $objActSheet->setCellValue("$letter[$i]1","$arrHeader[$i]");
        };
        //填充表格信息
        foreach($xlsData as $k=>$v){
            $k +=2;
            $objActSheet->setCellValue('A'.$k,$v['id']);
            $objActSheet->setCellValue('B'.$k, $v['name']);
            // 表格高度
            $objActSheet->getRowDimension($k)->setRowHeight(20);
        }
 
        $width = array(10,15,20,25,30);
        //设置表格的宽度
        $objActSheet->getColumnDimension('A')->setWidth($width[1]);
        $objActSheet->getColumnDimension('B')->setWidth($width[2]);
 
 
        $outfile = "信息列表.xlsx";
        ob_end_clean();
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition:inline;filename="'.$outfile.'"');
        header("Content-Transfer-Encoding: binary");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        $objWriter->save('php://output');
    }


    // //导出
    // public function out_excel(){
    //     ini_set('memory_limit','1024M');
    //     Loader::import('PHPExcel.PHPExcel');
    //     header("content-type:text/html;charset=utf8");
    //     $objExcel = new \PHPExcel();
    //     $objSheet = $objExcel -> getActiveSheet();
    //     $objSheet -> setTitle("PHPExcel导出测试");
    //     $data = Db::name('user') -> select();
    //     $objSheet -> setCellValue('A1','ID')
    //             -> setCellValue('B1','姓名')
    //     $j = 2;
    //     foreach ($data as $k => $v){
    //         $objSheet -> setCellValue('A'.$j,$v['id'])
    //             -> setCellValue('B'.$j,$v['name'])
    //         $j++;
    //     }
    //     $file_name = '文件名'.xlsx';
    //     header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    //     header("Content-Disposition: attachment;filename=$file_name");
    //     header('Cache-Control: max-age=0');
    //     header('Cache-Control: max-age=1');
    //     header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    //     header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
    //     header ('Cache-Control: cache, must-revalidate');
    //     header ('Pragma: public'); 

    //     $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel2007');
    //     $objWriter->save('php://output');
    //     exit;
    // }



}
