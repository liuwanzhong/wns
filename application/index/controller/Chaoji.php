<?php


namespace app\index\controller;


use think\Controller;
use think\Db;
use think\Session;

class chaoji extends Controller {
    public function index() {
        return view('index');
    }
    /**
     * 进销存详细
     */
    public function jxc_xx(){
        $jxc_xx=array();
        // 结存数
        $jc_num=db('rukuform_xq')
        ->field('product_name,SUM(rk_nums) rk_nums')
        ->group('product_name')
        ->where('state',1)
        ->select();
        echo db('rukuform_xq')->getlastSql();
        dump(count($jc_num));
        // 初期数
        $cq_num=db('record')
        ->field('hw_name')
        ->where("task = '到货入库' or task = '其他入库' or task = '调拨入库'")
        ->where("task <> '结存'")
        ->where("hw_name <> ''")
        ->group('hw_name')
        ->select();
        echo db('record')->getlastSql();
        dump(count($jc_num));
        // 出入库详细
        $xx=db('record')
        ->field('hw_name,SUM(dh_ruku) dh_ruku,SUM(db_ruku) db_ruku,SUM(qt_ruku) qt_ruku,SUM(xx_chuku) xx_chuku,SUM(db_chuku) db_chuku,SUM(qt_chuku) qt_chuku')
        ->where("hw_name <> ''")
        ->where("task <> '结存'")
        ->group('hw_name')
        ->select();
        echo db('record')->getlastSql();
        dump(count($xx));exit;
        foreach($cq_num as $k=>$v){
            $jxc_xx[$k]['early_stage']=0;
        }
        foreach($jc_num as $k=>$v){
            $jxc_xx[$k]['rk_nums']=$v['rk_nums'];
        }
        foreach($xx as $k=>$v){
            $jxc_xx[$k]['hw_name']=$v['hw_name'];
            $jxc_xx[$k]['dh_ruku']=$v['dh_ruku'];
            $jxc_xx[$k]['db_ruku']=$v['db_ruku'];
            $jxc_xx[$k]['qt_ruku']=$v['qt_ruku'];
            $jxc_xx[$k]['xx_chuku']=$v['xx_chuku'];
            $jxc_xx[$k]['db_chuku']=$v['db_chuku'];
            $jxc_xx[$k]['qt_chuku']=$v['qt_chuku'];
        }
        $early_stage=0;
        $rk_nums=0;
        $dh_ruku=0;
        $db_ruku=0;
        $qt_ruku=0;
        $xx_chuku=0;
        $db_chuku=0;
        $qt_chuku=0;
        foreach($jxc_xx as $v){
            $early_stage+=$v['early_stage'];
            $rk_nums+=$v['rk_nums'];
            $dh_ruku+=$v['dh_ruku'];
            $db_ruku+=$v['db_ruku'];
            $qt_ruku+=$v['qt_ruku'];
            $xx_chuku+=$v['xx_chuku'];
            $db_chuku+=$v['db_chuku'];
            $qt_chuku+=$v['qt_chuku'];
        }
        dump($jxc_xx);exit;
        return view('jxc_xx',['jxc_xx'=>$jxc_xx,'early_stage'=>$early_stage,'rk_nums'=>$rk_nums,'dh_ruku'=>$dh_ruku,'db_ruku'=>$db_ruku,'qt_ruku'=>$qt_ruku,'xx_chuku'=>$xx_chuku,'db_chuku'=>$db_chuku,'qt_chuku'=>$qt_chuku]);
    }
    /**
     * 进销存goods详细
     */
    public function jxc_goods_xx($name){
        $res=db('record')
        ->where('hw_name',$name)
        ->select();
        return view('jxc_goods_xx',['res'=>$res]);
    }
    /**
     * 密码判断
     */
    public function pwd() {
        $pwd=input('pwd');
        if($pwd!='QF20190930'){
            $this->error('密码错误');
        }else{
            Session::set('pwd',1);
            return view('chaoji');
        }
    }
    //清除在途台账
    public function a1() {
        $r=Db::execute('TRUNCATE cw_management');
        if($r!==false){
            $this->error('清除成功');
        }else{
            $this->error('清除失败');
        }
    }
    //清除入库计划
    public function a2() {
        $r=Db::execute('TRUNCATE rukuform');
        if($r!==false){
            $this->error('清除成功');
        }else{
            $this->error('清除失败');
        }
    }
    //清除入库明细
    public function a4() {
        $r=Db::execute('TRUNCATE rukuform_xq');
        if($r!==false){
            $this->error('清除成功');
        }else{
            $this->error('清除失败');
        }
    }
    //清除货物列表
    public function a5() {
        $r=Db::execute('TRUNCATE rukuform_xq');
        if($r!==false){
            $this->error('清除成功');
        }else{
            $this->error('清除失败');
        }
    }
    //清除流水详细
    public function a6() {
        $r=Db::execute('TRUNCATE cj');
        $r=Db::execute('TRUNCATE record');
        if($r!==false){
            $this->error('清除成功');
        }else{
            $this->error('清除失败');
        }
    }
    //清除库存盘点
    public function a7() {
        $r=Db::execute('TRUNCATE pandian');
        if($r!==false){
            $this->error('清除成功');
        }else{
            $this->error('清除失败');
        }
    }
    //清除调拨列表
    public function a8() {
        $r=Db::execute('TRUNCATE db_list');
        if($r!==false){
            $this->error('清除成功');
        }else{
            $this->error('清除失败');
        }
    }
    //清除损耗单
    public function a9() {
        $r=Db::execute('TRUNCATE wastage_list');
        if($r!==false){
            $this->error('清除成功');
        }else{
            $this->error('清除失败');
        }
    }
    //清除其他入库
    public function a10() {
        $r=Db::execute('TRUNCATE other_rk');
        if($r!==false){
            $this->error('清除成功');
        }else{
            $this->error('清除失败');
        }
    }
    //清除其他出库
    public function a11() {
        $r=Db::execute('TRUNCATE other_ck');
        if($r!==false){
            $this->error('清除成功');
        }else{
            $this->error('清除失败');
        }
    }
    //清除系统订单
    public function a13() {
        $r=Db::execute('TRUNCATE system_order');
        if($r!==false){
            $this->error('清除成功');
        }else{
            $this->error('清除失败');
        }
    }
    //清除出库计划
    public function a14() {
        $r=Db::execute('TRUNCATE outbound_from');
        if($r!==false){
            $this->error('清除成功');
        }else{
            $this->error('清除失败');
        }
    }
    //清除出库明细
    public function a16() {
        $r=Db::execute('TRUNCATE outbound_xq_from');
        if($r!==false){
            $this->error('清除成功');
        }else{
            $this->error('清除失败');
        }
    }





    
    /**
     * 进销存导出
     */
    public function outExcel(){
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $jxc_xx=array();
        // 结存数
        $jc_num=db('rukuform_xq')
            ->field('product_name,SUM(rk_nums) rk_nums')
            ->group('product_name')
            ->where('state',1)
            ->select();
        // 初期数
        $cq_num=db('record')
            ->field('hw_name,SUM(early_stage) early_stage')
            ->where("task = '到货入库' or task = '其他入库' or task = '调拨入库'")
            ->where("hw_name <> ''")
            ->group('hw_name')
            ->select();
        // 出入库详细
        $xx=db('record')
            ->field('hw_name,SUM(dh_ruku) dh_ruku,SUM(db_ruku) db_ruku,SUM(qt_ruku) qt_ruku,SUM(xx_chuku) xx_chuku,SUM(db_chuku) db_chuku,SUM(qt_chuku) qt_chuku')
            ->where("hw_name <> ''")
            ->group('hw_name')
            ->select();
        foreach($cq_num as $k=>$v){
            $jxc_xx[$k]['early_stage']=$v['early_stage'];
        }
        foreach($jc_num as $k=>$v){
            $jxc_xx[$k]['rk_nums']=$v['rk_nums'];
        }
        foreach($xx as $k=>$v){
            $jxc_xx[$k]['hw_name']=$v['hw_name'];
            $jxc_xx[$k]['dh_ruku']=$v['dh_ruku'];
            $jxc_xx[$k]['db_ruku']=$v['db_ruku'];
            $jxc_xx[$k]['qt_ruku']=$v['qt_ruku'];
            $jxc_xx[$k]['xx_chuku']=$v['xx_chuku'];
            $jxc_xx[$k]['db_chuku']=$v['db_chuku'];
            $jxc_xx[$k]['qt_chuku']=$v['qt_chuku'];
        }
        if(!empty($jxc_xx)){
            Vendor('PHPExcel.PHPExcel');
            Vendor('PHPExcel.PHPExcel.IOFactory');
            $phpExcel = new \PHPExcel();
            $phpExcel->setActiveSheetIndex(0)
                ->setCellValue('A1', '产品')
                ->setCellValue('B1', '初期数量')
                ->setCellValue('C1', '到货入库')
                ->setCellValue('D1', '调拨入库')
                ->setCellValue('E1', '其他入库')
                ->setCellValue('F1', '销售出库')
                ->setCellValue('G1', '调拨出库')
                ->setCellValue('H1', '其他出库')
                ->setCellValue('I1', '结存');
            $len = count($jxc_xx);
            for($i = 0 ; $i < $len ; $i++){
                $v = $jxc_xx[$i];
                $rownum = $i+2;
                $phpExcel->getActiveSheet()->setCellValue('A' . $rownum, $v['hw_name']);
                $phpExcel->getActiveSheet()->setCellValue('B' . $rownum, $v['early_stage']);
                $phpExcel->getActiveSheet()->setCellValue('C' . $rownum, $v['dh_ruku']);
                $phpExcel->getActiveSheet()->setCellValue('D' . $rownum, $v['db_ruku']);
                $phpExcel->getActiveSheet()->setCellValue('E' . $rownum, $v['qt_ruku']);
                $phpExcel->getActiveSheet()->setCellValue('F' . $rownum, $v['xx_chuku']);
                $phpExcel->getActiveSheet()->setCellValue('G' . $rownum, $v['db_chuku']);
                $phpExcel->getActiveSheet()->setCellValue('H' . $rownum, $v['qt_chuku']);
                $phpExcel->getActiveSheet()->setCellValue('I' . $rownum, $v['rk_nums']);
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
    /**
     * 下载进销存表格
     */
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
            unlink($path);
            exit();
        }
    }
}
