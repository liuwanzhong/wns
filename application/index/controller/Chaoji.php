<?php
//
//
//namespace app\index\controller;
//
//
//use think\Controller;
//use think\Db;
//use think\Session;
//
//class chaoji extends Controller {
//    public function index() {
//        return view('index');
//    }
//
//    public function pwd() {
//        $pwd=input('pwd');
//        if($pwd!='QF20190930'){
//            $this->error('密码错误');
//        }else{
//            Session::set('pwd',1);
//            return view('chaoji');
//        }
//    }
//    //清除在途台账
//    public function a1() {
//        $r=Db::execute('TRUNCATE cw_management');
//        if($r!==false){
//            $this->error('清除成功');
//        }else{
//            $this->error('清除失败');
//        }
//    }
//    //清除入库计划
//    public function a2() {
//        $r=Db::execute('TRUNCATE rukuform');
//        if($r!==false){
//            $this->error('清除成功');
//        }else{
//            $this->error('清除失败');
//        }
//    }
//    //清除入库明细
//    public function a4() {
//        $r=Db::execute('TRUNCATE rukuform_xq');
//        if($r!==false){
//            $this->error('清除成功');
//        }else{
//            $this->error('清除失败');
//        }
//    }
//    //清除货物列表
//    public function a5() {
//        $r=Db::execute('TRUNCATE rukuform_xq');
//        if($r!==false){
//            $this->error('清除成功');
//        }else{
//            $this->error('清除失败');
//        }
//    }
//    //清除流水详细
//    public function a6() {
//        $r=Db::execute('TRUNCATE cj');
//        $r=Db::execute('TRUNCATE record');
//        if($r!==false){
//            $this->error('清除成功');
//        }else{
//            $this->error('清除失败');
//        }
//    }
//    //清除库存盘点
//    public function a7() {
//        $r=Db::execute('TRUNCATE pandian');
//        if($r!==false){
//            $this->error('清除成功');
//        }else{
//            $this->error('清除失败');
//        }
//    }
//    //清除调拨列表
//    public function a8() {
//        $r=Db::execute('TRUNCATE db_list');
//        if($r!==false){
//            $this->error('清除成功');
//        }else{
//            $this->error('清除失败');
//        }
//    }
//    //清除损耗单
//    public function a9() {
//        $r=Db::execute('TRUNCATE wastage_list');
//        if($r!==false){
//            $this->error('清除成功');
//        }else{
//            $this->error('清除失败');
//        }
//    }
//    //清除其他入库
//    public function a10() {
//        $r=Db::execute('TRUNCATE other_rk');
//        if($r!==false){
//            $this->error('清除成功');
//        }else{
//            $this->error('清除失败');
//        }
//    }
//    //清除其他出库
//    public function a11() {
//        $r=Db::execute('TRUNCATE other_ck');
//        if($r!==false){
//            $this->error('清除成功');
//        }else{
//            $this->error('清除失败');
//        }
//    }
//    //清除系统订单
//    public function a13() {
//        $r=Db::execute('TRUNCATE system_order');
//        if($r!==false){
//            $this->error('清除成功');
//        }else{
//            $this->error('清除失败');
//        }
//    }
//    //清除出库计划
//    public function a14() {
//        $r=Db::execute('TRUNCATE outbound_from');
//        if($r!==false){
//            $this->error('清除成功');
//        }else{
//            $this->error('清除失败');
//        }
//    }
//    //清除出库明细
//    public function a16() {
//        $r=Db::execute('TRUNCATE outbound_xq_from');
//        if($r!==false){
//            $this->error('清除成功');
//        }else{
//            $this->error('清除失败');
//        }
//    }
//}
