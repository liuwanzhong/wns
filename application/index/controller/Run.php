<?php


namespace app\index\controller;


use think\Controller;
use think\Db;
use think\Loader;

class Run extends Controller {
    //地区库房列表
    public function warehouse() {
        $rows=db('warehouse')->where('is_del',1)->paginate(100);
        return view('warehouse',['rows'=>$rows]);
    }
    //添加库房
    public function warehouse_add() {
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $data=$_POST['name'];
        $content=$_POST['content'];
        $count=db('warehouse')->where('name',$data)->where('is_del',1)->count();
        if($count>0){
            $this->error('库房名称已存在');
        }
        $r=db('warehouse')->insert(['name'=>$data,'content'=>$content]);
        if($r){
            return redirect('warehouse');
        }else{
            $this->error('添加失败,请联系管理员');
        }
    }
    //删除库房
    public function warehouse_del($id) {
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        if($id==0){
            $this->eror('缺少必要参数,请重试');
        }
        $count=db('cabinet')->where('is_del',1)->where('warehouse_id',$id)->find();
        if($count){
            $this->error('该库房下还有货位,不能删除');
        }
        $r=db('warehouse')->where('id',$id)->update(['is_del'=>0]);
        if($r){
            return redirect('warehouse');
        }else{
            $this->error('删除库房失败,请联系管理员');
        }
    }
    //查看库房
    public function warehouse_select() {
        $ms=$this->qx();
        if($ms==0){
            $msg=['error'=>0,'msg'=>'警告:越权操作'];
            return $msg;
        }
        $id=$_POST['id'];
        $row=db('warehouse')->where('id',$id)->find();
        return $row;
    }
    //修改库房
    public function warehouse_edit() {
        $data=input();
        array_shift($data);
        $count=db('warehouse')->where('name',$data['name'])->where("id!=$data[id]")->where('is_del',1)->count();
        if($count>0){
            $this->error('库房名称已存在');
        }
        $r=db('warehouse')->update($data);
        if($r!==false){
            return redirect('warehouse');
        }else{
            $this->error('修改库房失败,请联系管理员');
        }
    }


    //货位列表
    public function cabinet() {
        $warehouse=self::$stafss['warehouse'];
        $rows=db('cabinet')
            ->where('cabinet.is_del',1)
            ->join('warehouse','cabinet.warehouse_id=warehouse.id')
            ->where('warehouse.id','in',$warehouse)
            ->field('cabinet.*,warehouse.name as w_name')
            ->paginate(100);
        $ware=db('warehouse')->where('is_del',1)->where('id','in',$warehouse)->select();
        return view('cabinet',['rows'=>$rows,'ware'=>$ware]);
    }
    //添加货位
    public function cabinet_add() {
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $data=input();
        array_shift($data);
        if($data['warehouse_id']==0){
            $this->error('请选择货位所在仓库');
        }
        $count=db('cabinet')->where('name',$data['name'])->where('warehouse_id',$data['warehouse_id'])->count();
        if($count>0){
            $this->error("$data[name]货位已存在");
        }
        $r=db('cabinet')->insert($data);
        if($r){
            return redirect('cabinet');
        }else{
            $this->error('添加失败,请联系管理员');
        }
    }
    //删除货位
    public function cabinetdel($id) {
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        if($id==0){
            $this->eror('缺少必要参数,请重试');
        }
        $r=db('cabinet')->where('id',$id)->update(['is_del'=>0]);
        if($r){
            return redirect('cabinet');
        }else{
            $this->error('删除货位失败,请联系管理员');
        }
    }
    //查看货位
    public function cabinet_select() {
        $ms=$this->qx();
        if($ms==0){
            $msg=['error'=>0,'msg'=>'警告:越权操作'];
            return $msg;
        }
        $id=$_POST['id'];
        $row=db('cabinet')->where('id',$id)->find();
        return $row;
    }
    //修改货位
    public function cabinet_edit() {
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }

        $data=input();
        array_shift($data);
        if($data['warehouse_id']==0){
            $this->error('请选择货位所在仓库');
        }
        $count=db('cabinet')->where('name',$data['name'])->where("id!=$data[id]")->where("warehouse_id",$data['warehouse_id'])->where('is_del',1)->count();
        if($count>0){
            $this->error("$data[name]货位已存在");
        }
        $r=db('cabinet')->update($data);
        if($r!==false){
            return redirect('cabinet');
        }else{
            $this->error('修改库房失败,请联系管理员');
        }
    }


    //库存列表
    public function index(){
        $list = db('kc_status')->where('is_del',0)->paginate(100);
        return view('index',['list'=>$list]);
    }
    //添加状态
    public function insert(){
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $data = input();
        array_shift($data);
        $rs = db('kc_status')->insert($data);
        if($rs){
            return redirect('index');
        }else{
            $this->error("添加失败");
        }
    }
    //修改查看接口
    public function show($id){
        $ms=$this->qx();
        if($ms==0){
            $msg=['error'=>0,'msg'=>'警告:越权操作'];
            return $msg;
        }
        $list = db('kc_status')->where('is_del',0)->where('id',$id)->find();
        return $list;
    }
    //修改
    public function edit(){
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $data = input();
        array_shift($data);
        $id = $data['myid'];
        unset($data['myid']);
        $data['update_time'] = time();
        $rs = db('kc_status')->where('id',$id)->update($data);
        if($rs){
            return redirect('index');
        }else{
            $this->error("修改失败");
        }
    }
    //删除
    public function delete($id){
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $rs = db('kc_status')->where('id',$id)->update(['is_del'=>1,'delete_time'=>time()]);
        if($rs){
            return redirect('index');
        }else{
            $this->error("删除失败");
        }
    }


    //工人列表
    public function warker(){
        $list = db('warker')->where('is_del',1)->paginate(100);
        return view('warker',['list'=>$list]);
    }
    //添加工人
    public function warker_add(){
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $data = input();
        array_shift($data);
        $rs = db('warker')->insert($data);
        if($rs){
            return redirect('warker');
        }else{
            $this->error("添加失败");
        }
    }
    //修改查看接口
    public function warker_show($id){
        $ms=$this->qx();
        if($ms==0){
            $msg=['error'=>0,'msg'=>'警告:越权操作'];
            return $msg;
        }
        $list = db('warker')->where('is_del',1)->where('id',$id)->find();
        return $list;
    }
    //修改
    public function warker_edit(){
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $data = input();
        array_shift($data);
        $rs = db('warker')->update($data);
        if($rs){
            return redirect('warker');
        }else{
            $this->error("修改失败");
        }
    }
    //删除
    public function warker_delete($id){
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $rs = db('warker')->where('id',$id)->update(['is_del'=>0]);
        if($rs){
            return redirect('warker');
        }else{
            $this->error("删除失败");
        }
    }
    //工人搬运详情
    public function warker_list_show() {
        $num=0;//总数量
        $weight=0;//总重量
        $money=0;//总金额
        $s_delivery_time=input('s_delivery_time');//作业时间
        $name=input('name');//工人名称
        $state=input('state');//作业类型
        $search = '';
        // 时间转换
        if (!empty($s_delivery_time)) {
            $time = explode('~', $s_delivery_time);
            foreach ($time as $key) {
                $time[] = strtotime($key);
                array_shift($time);
            }
            $time = ' stevedore.time BETWEEN ' . $time['0'] . ' and ' . $time['1'];
            $search .= $time;
        }
        if (!empty($name)) {
            $material_name = $name;
            if (!empty($search)) {
                $material_name = " and stevedore.warker_id = $material_name";
            } else {
                $material_name = " stevedore.warker_id = $material_name";
            }
            $search .= $material_name;
        }
        if (!empty($state)) {
            $material_name = $state;
            if (!empty($search)) {
                $material_name = ' and stevedore.task like ' . "'%" . $material_name . '%' . "'";
            } else {
                $material_name =' stevedore.task like ' . "'%" . $material_name . '%' . "'";
            }
            $search .= $material_name;
        }
        $rows=db('stevedore')
            ->join('warker','warker.id=stevedore.warker_id')
            ->where("$search")
            ->field('stevedore.*,warker.name as w_name')
            ->order('stevedore.warker_id asc')
            ->paginate(100,false,['query'=>['s_delivery_time'=>$s_delivery_time]]);
        foreach ($rows as $row) {
            $num+=$row['num'];
            $weight+=$row['weight'];
            $money+=$row['money'];
        }
        $warker=db('warker')->where('is_del',1)->select();
        return view('warker_list_show',['rows'=>$rows,'num'=>$num,'weight'=>$weight,'money'=>$money,'s_delivery_time'=>$s_delivery_time,'warker'=>$warker]);
    }
    //价钱结算
    public function warker_money() {
        $id=input('id');
        $weight=input('weight');
        $money=input('money');
        $m=$weight*$money;
        db('stevedore')->where('id',$id)->update(['money'=>$m]);
        return $m;
    }

    /**
     * 工人导出
     */
    public function outExcel(){
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $s_delivery_time=input('s_delivery_time');//操作时间
        $search = '';
        if (!empty($s_delivery_time)) {
            $time = explode('~', $s_delivery_time);
            foreach ($time as $key) {
                $time[] = strtotime($key);
                array_shift($time);
            }
            $time = ' stevedore.time BETWEEN ' . $time['0'] . ' and ' . $time['1'];
            $search .= $time;
        }
        $row=db('stevedore')
            ->join('warker','warker.id=stevedore.warker_id')
            ->where("$search")
            ->field('stevedore.*,warker.name as w_name')
            ->select();
        if(!empty($row)){
            Vendor('PHPExcel.PHPExcel');
            Vendor('PHPExcel.PHPExcel.IOFactory');
            $phpExcel = new \PHPExcel();
            $phpExcel->setActiveSheetIndex(0)
                ->setCellValue('A1', '作业时间')
                ->setCellValue('B1', '工人姓名')
                ->setCellValue('C1', '装运单号')
                ->setCellValue('D1', '客户名称')
                ->setCellValue('E1', '总数量')
                ->setCellValue('F1', '总重量')
                ->setCellValue('G1', '装卸费')
                ->setCellValue('H1', '作业类型');
            $len = count($row);
            for($i = 0 ; $i < $len ; $i++){
                $v = $row[$i];
                $v['time']=date('Y-m-d H:i:s',$v['time']);
                $rownum = $i+2;
                $phpExcel->getActiveSheet()->setCellValue('A' . $rownum, $v['time']);
                $phpExcel->getActiveSheet()->setCellValue('B' . $rownum, $v['w_name']);
                $phpExcel->getActiveSheet()->setCellValue('C' . $rownum, $v['numbers']);
                $phpExcel->getActiveSheet()->setCellValue('D' . $rownum, $v['name']);
                $phpExcel->getActiveSheet()->setCellValue('E' . $rownum, $v['num']);
                $phpExcel->getActiveSheet()->setCellValue('F' . $rownum, $v['weight']);
                $phpExcel->getActiveSheet()->setCellValue('G' . $rownum, $v['money']);
                $phpExcel->getActiveSheet()->setCellValue('H' . $rownum, $v['task']);
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
     * 下载工人
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




    //库存列表
    public function goods_name(){
        $list = db('goods_name')->where('is_del',0)->paginate(100);
        return view('goods_name',['list'=>$list]);
    }
    //添加状态
    public function goods_name_add(){
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $data = input();
        array_shift($data);
        $rs = db('goods_name')->insert($data);
        if($rs){
            return redirect('goods_name');
        }else{
            $this->error("添加失败");
        }
    }
    //修改查看接口
    public function goods_name_show($id){
        $ms=$this->qx();
        if($ms==0){
            $msg=['error'=>0,'msg'=>'警告:越权操作'];
            return $msg;
        }
        $list = db('goods_name')->where('is_del',0)->where('id',$id)->find();
        return $list;
    }
    //修改
    public function goods_name_edit(){
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $data = input();
        array_shift($data);
        $id = $data['myid'];
        unset($data['myid']);
        $data['update_time'] = time();
        $rs = db('goods_name')->where('id',$id)->update($data);
        if($rs){
            return redirect('goods_name');
        }else{
            $this->error("修改失败");
        }
    }
    //删除
    public function goods_name_del($id){
        $ms=$this->qx();
        if($ms==0){
            $this->error('警告：越权操作');
        }
        $rs = db('goods_name')->where('id',$id)->update(['is_del'=>1]);
        if($rs){
            return redirect('goods_name');
        }else{
            $this->error("删除失败");
        }
    }
    //导入
    public function upload_excel() {
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
        unset($sheetContent[0],$sheetContent[1]);
        foreach ($sheetContent as $k => $v) {
            $arr['name']                = $v[0];//调拨订单号
            $arr['mao']                 = $v[1];//交付单号
            $arr['jing']               = $v[2];//交货单实际出库日期
            $arr['gongchang']           = $v[3];//调拨出库工厂
            $res[] = $arr;
        }
        set_time_limit(0);
        $res = Db ::name('goods_name') -> insertAll($res);
        if ($res) {
            return redirect('goods_name');
        } else {
            $this -> error('添加失败');
        }
    }





    /**
     * 托盘管理
     */
    public function tray(){
        static $md;
        $warehouse=self::$stafss['warehouse'];
        $search = '';
        $data=input();
        if(!empty($data['name'])){
            $name=$data['name'];
            $search = 'name like ' . "'%" . $name . '%' . "'";
        }else{
            $name='';
        }
        if(!empty($data['tp_num'])){
            $tp_num=$data['tp_num'];
            if (!empty($search)) {
                $search .= ' and tp_num like ' . "'%" . $tp_num . '%' . "'";
            }else{
                $search = 'tp_num like ' . "'%" . $tp_num . '%' . "'";
            }
        }else{
            $tp_num='';
        }
        $rows=db('tray')
            ->join('warehouse','warehouse.id=tray.warehouse_id','left')
            ->where('warehouse.id','in',$warehouse)
            ->where('tray.is_del',0)
            ->where($search)
            ->field('tray.*,warehouse.name')
            ->paginate(100,false,['query'=>['name'=>$name,'tp_num'=>$tp_num]]);
        $cks = db('warehouse')->where('is_del',1)->where('id','in',$warehouse)->select();
        return view('tray',['cks'=>$cks,'rows'=>$rows,'tp_num'=>$tp_num,'name'=>$name]);
    }
    /**
     * 托盘详细
     */
    public function tray_xx(){
        $id=$_POST['id'];
        $row=db('tray')
        ->join('warehouse','warehouse.id=tray.warehouse_id','left')
        ->where('tray.warehouse_id = warehouse.id')
        ->where('tray.id',$id)
        ->where('tray.is_del',0)
        ->field('tray.*,warehouse.name')
        ->select();
        return $row[0];
    }
    /**
     * 清空托盘
     */
    public function clean(){
        $id=$_POST['id'];
        $res=db('tray')
        ->where('id',$id)
        ->update([
            'batch'=>'',
            'state'=>0,
            'num'=>'',
            'goods_name'=>''
        ]);
        if($res){
            return 1;
        }
    }
    /**
     * 新增托盘 二维码
     */
    public function tray_add(){
        $data=input();

        if(!empty($data['tp_num'])&&!empty($data['warehouse_id'])){
            $res=db('tray')
            ->where('tp_num',$data['tp_num'])
            ->select();
            if($res){
                $this -> error('编号重复');
                exit;
            }
            $text='/index/Batch/batch?tp_num='.$data['tp_num'];      // 生成的二维码 内容
            $outfile = ROOT_PATH.'public\\trayimg\\'.$data['tp_num'].'.png';     // 生成的二维码 文件名，false为 不保存
            $level = 'QR_ECLEVEL_L';         //级别,也是容错率。下面会有介绍
            $size = 10;                      //大小
            $margin = 4;                     //外边距
            $saveandprint=true;              //是否 保存和打印。true为保存并打印
            //不带LOGO
            Vendor('phpqrcode.phpqrcode');
            //生成二维码图片
            $object = new \QRcode();
            $object->png($text, $outfile, $level, $size,$margin, $saveandprint);
            $path='../../trayimg\\'.$data['tp_num'].'.png';
            $res=db('tray')
            ->insert([
                'tp_num'=>$data['tp_num'],
                'warehouse_id'=>$data['warehouse_id'],
                'create_time'=>time(),
                'pic'=>$outfile,
                'path'=>$path
            ]);
            return redirect('tray');
        }else{
            $this -> error('请填写完整数据');
        }
    }
    public function tray_del(){
        $id=$_POST['id'];
        dump($id);
        $row=db('tray')
        ->where('tray.id',$id)
        ->where('tray.is_del',0)
        ->update([
            'is_del'=>1,
            'del_time'=>time()
        ]);
        if($row){
            return 1;
        }else{
            return 0;
        }
    }
}
