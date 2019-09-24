<?php


namespace app\index\controller;


use think\Controller;

class Other extends Controller {
    //视图
    public function transport()
    {
        $s_transfers_id=input('s_transfers_id');//目的地
        $s_delivery_time=input('s_delivery_time');//发运日期
        $s_material_name=input('s_material_name');//车牌号码
        $zydh=input('zydh');//装运单号
        $search = '';
        if (!empty($s_transfers_id)) {
            $search = 'destination like ' . "'%" . $s_transfers_id . '%' . "'";
        }
        // 时间转换
        if (!empty($s_delivery_time)) {
            $time = explode('~', $s_delivery_time);
            foreach ($time as $key) {
                $time[] = strtotime($key);
                array_shift($time);
            }
            if (!empty($search)) {
                $time = ' and delivery_time BETWEEN ' . $time['0'] . ' and ' . $time['1'];
            } else {
                $time = 'delivery_time BETWEEN ' . $time['0'] . ' and ' . $time['1'];
            }
            $search .= $time;
        }
        // 车牌号码
        if (!empty($s_material_name)) {
            $material_name = $s_material_name;
            if (!empty($search)) {
                $material_name = ' and license_plate like ' . "'%" . $material_name . '%' . "'";
            } else {
                $material_name = ' license_plate like ' . "'%" . $material_name . '%' . "'";
            }
            $search .= $material_name;
        }
        //装运单号
        if (!empty($zydh)) {
            $zyd = $zydh;
            if (!empty($search)) {
                $zyd = ' and driver_name like ' . "'%" . $zyd . '%' . "'";
            } else {
                $zyd = ' driver_name like ' . "'%" . $zyd . '%' . "'";
            }
            $search .= $zyd;
        }
        $rows=db('fayunbb')->where("$search")->paginate(100,false,['query'=>['s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name,'zydh'=>$zydh]]);
        return view('transport',['rows'=>$rows,'s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name,'zydh'=>$zydh]);
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
        $data['obj']['delivery_time']=strtotime($data['obj']['delivery_time']);
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
        $data['obj']['delivery_time']=strtotime($data['obj']['delivery_time']);
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
        $s_transfers_id=input('s_transfers_id');//目的地
        $s_delivery_time=input('s_delivery_time');//发运日期
        $s_material_name=input('s_material_name');//车牌号码
        $zydh=input('zydh');//装运单号
        $search = '';
        if (!empty($s_transfers_id)) {
            $search = 'destination like ' . "'%" . $s_transfers_id . '%' . "'";
        }
        // 时间转换
        if (!empty($s_delivery_time)) {
            $time = explode('~', $s_delivery_time);
            foreach ($time as $key) {
                $time[] = strtotime($key);
                array_shift($time);
            }
            if (!empty($search)) {
                $time = ' and delivery_time BETWEEN ' . $time['0'] . ' and ' . $time['1'];
            } else {
                $time = 'delivery_time BETWEEN ' . $time['0'] . ' and ' . $time['1'];
            }
            $search .= $time;
        }
        // 车牌号码
        if (!empty($s_material_name)) {
            $material_name = $s_material_name;
            if (!empty($search)) {
                $material_name = ' and license_plate like ' . "'%" . $material_name . '%' . "'";
            } else {
                $material_name = ' license_plate like ' . "'%" . $material_name . '%' . "'";
            }
            $search .= $material_name;
        }
        //装运单号
        if (!empty($zydh)) {
            $zyd = $zydh;
            if (!empty($search)) {
                $zyd = ' and driver_name like ' . "'%" . $zyd . '%' . "'";
            } else {
                $zyd = ' driver_name like ' . "'%" . $zyd . '%' . "'";
            }
            $search .= $zyd;
        }
        $rows=db('fayunbb')->where('state',0)->where("$search")->paginate(100,false,['query'=>['s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name,'zydh'=>$zydh]]);
        return view('transport_ss',['rows'=>$rows,'s_transfers_id'=>$s_transfers_id,'s_delivery_time'=>$s_delivery_time,'s_material_name'=>$s_material_name,'zydh'=>$zydh]);
    }
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



    //导出
    public function outExcel(){
        $data = input();
        array_shift($data);
        $id=$data['id'];
        $da=str_replace('"', '', $id);
        $da=trim($da,'[');
        $da=trim($da,']');
        $data=db('fayunbb')->where("id in ($da)")->select();
        if(!empty($data)){
            Vendor('PHPExcel.PHPExcel');
            Vendor('PHPExcel.PHPExcel.IOFactory');
            $phpExcel = new \PHPExcel();
            $phpExcel->setActiveSheetIndex(0)
                ->setCellValue('A1', '发运日期')
                ->setCellValue('B1', '始发地')
                ->setCellValue('C1', '目的地')
                ->setCellValue('D1', '车牌号码')
                ->setCellValue('E1', '司机姓名')
                ->setCellValue('F1', '司机电话')
                ->setCellValue('G1', '蒙牛订单日期')
                ->setCellValue('H1', '蒙牛装运单号')
                ->setCellValue('I1', '蒙牛订单吨位')
                ->setCellValue('J1', '蒙牛订单数量')
                ->setCellValue('K1', '实发吨位(毛重)')
                ->setCellValue('L1', '实装数量')
                ->setCellValue('M1', '合同结算吨位(净重)')
                ->setCellValue('N1', '毛净重节约吨位')
                ->setCellValue('O1', '毛净重节约费用')
                ->setCellValue('P1', '公司标准运价')
                ->setCellValue('Q1', '合同单吨运价')
                ->setCellValue('R1', '单吨运价节约金额')
                ->setCellValue('S1', '单吨运价差额节约金额')
                ->setCellValue('T1', '总运费')
                ->setCellValue('U1', '油卡')
                ->setCellValue('V1', '满运宝支付')
                ->setCellValue('W1', '银行支付')
                ->setCellValue('X1', '余额')
                ->setCellValue('Y1', '承运人')
                ->setCellValue('Z1', '运输发票')
                ->setCellValue('AA1', '备注');
            $len = count($data);
            for($i = 0 ; $i < $len ; $i++){
                $v = $data[$i];
                $rownum = $i+2;
                $phpExcel->getActiveSheet()->setCellValue('A' . $rownum, date('Y-m-d',$v['delivery_time']));
                $phpExcel->getActiveSheet()->setCellValue('B' . $rownum, $v['origin']);
                $phpExcel->getActiveSheet()->setCellValue('C' . $rownum, $v['destination']);
                $phpExcel->getActiveSheet()->setCellValue('D' . $rownum, $v['license_plate']);
                $phpExcel->getActiveSheet()->setCellValue('E' . $rownum, $v['name']);
                $phpExcel->getActiveSheet()->setCellValue('F' . $rownum, $v['name_tel']);
                $phpExcel->getActiveSheet()->setCellValue('G' . $rownum, $v['ddrq']);
                $phpExcel->getActiveSheet()->setCellValue('H' . $rownum, $v['driver_name']);
                $phpExcel->getActiveSheet()->setCellValue('I' . $rownum, $v['driver_tel']);
                $phpExcel->getActiveSheet()->setCellValue('J' . $rownum, $v['gross_weight']);
                $phpExcel->getActiveSheet()->setCellValue('K' . $rownum, $v['net_weight']);
                $phpExcel->getActiveSheet()->setCellValue('L' . $rownum, $v['real_clothes']);
                $phpExcel->getActiveSheet()->setCellValue('M' . $rownum, $v['oil_card']);
                $phpExcel->getActiveSheet()->setCellValue('N' . $rownum, $v['gross_weight_freight']);
                $phpExcel->getActiveSheet()->setCellValue('O' . $rownum, $v['net_weight_freight']);
                $phpExcel->getActiveSheet()->setCellValue('P' . $rownum, $v['save_freight']);
                $phpExcel->getActiveSheet()->setCellValue('Q' . $rownum, $v['receipt']);
                $phpExcel->getActiveSheet()->setCellValue('R' . $rownum, $v['mengniu_yunjia']);
                $phpExcel->getActiveSheet()->setCellValue('S' . $rownum, $v['mengniu_zongjia']);
                $phpExcel->getActiveSheet()->setCellValue('T' . $rownum, $v['freight']);
                $phpExcel->getActiveSheet()->setCellValue('U' . $rownum, $v['youka']);
                $phpExcel->getActiveSheet()->setCellValue('V' . $rownum, $v['manyunbao']);
                $phpExcel->getActiveSheet()->setCellValue('W' . $rownum, $v['yinhang']);
                $phpExcel->getActiveSheet()->setCellValue('X' . $rownum, $v['yue']);
                $phpExcel->getActiveSheet()->setCellValue('Y' . $rownum, $v['cehgnyunren']);
                $phpExcel->getActiveSheet()->setCellValue('Z' . $rownum, $v['yunshu']);
                $phpExcel->getActiveSheet()->setCellValue('AA' . $rownum, $v['content']);
            }
            $phpExcel->setActiveSheetIndex(0);
            $filename=date('Y-m-d',time()).'.xlsx';
            $objWriter=\PHPExcel_IOFactory::createWriter($phpExcel,'Excel2007');
            $filePath =$filename;
            $objWriter->save($filePath);
            if(!file_exists($filePath)){
                $response = array(
                    'status' => 'false',
                    'url' => '',
                    'token'=>''
                );
            }else{
                $response = array(
                    'status' => true,
                    'url' => $filename,
                    'token'=>$this->getDownLoadToken($filename)
                );
            }
        }else{
            $response = array(
                'status' => 'false',
                'url' => '',
                'token'=>''
            );
        }
        exit(json_encode($response));
    }
    private function getDownLoadToken($filename,$length = 10){
        $str = null;
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($strPol)-1;

        for($i=0;$i<$length;$i++){
            $str.=$strPol[rand(0,$max)];//rand($min,$max)生成介于min和max两个数之间的一个随机整数
        }
        $res = md5($str.time());
        return $res;
    }
    public function download(){
        $fileName = date('Y-m-d',time()).'.xlsx';
        $path = ROOT_PATH."\public/".$fileName;
        if(!file_exists($path)){
            header("HTTP/1.0 404 Not Found");
            exit;
        }else{
            $file = @fopen($path,"r");
            if(!$file){
                header("HTTP/1.0 505 Internal server error");
                exit;
            }
            header("Content-type: application/octet-stream");
            header("Accept-Ranges: bytes");
            header("Accept-Length: ".filesize($path));
            header("Content-Disposition: attachment; filename=" . $fileName);
            while(!feof($file)){
                echo fread($file,2048);
            }
            fclose($file);
//            unlink($path);
            exit();
        }
    }
}
