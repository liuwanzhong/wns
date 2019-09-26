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
        $rows=db('cabinet')
            ->where('cabinet.is_del',1)
            ->join('warehouse','cabinet.warehouse_id=warehouse.id')
            ->field('cabinet.*,warehouse.name as w_name')
            ->paginate(100);
        $ware=db('warehouse')->where('is_del',1)->select();
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
        $rows=db('tray')
        ->where('tray.warehouse_id',7)  
        ->join('warehouse','warehouse.id=tray.warehouse_id','left')
        ->where('tray.is_del',0)
        ->select();
        $cks = db('warehouse')->where('is_del',1)->select();
        // dump($cks);exit;
        return view('tray',['cks'=>$cks,'rows'=>$rows]);
    }
    /**
     * 托盘详细
     */
    public function tray_xx(){
        return view('tray_xx');
    }
    /**
     * 新增托盘
     */
    public function tray_add(){
        dump(input());
        $data=input();
        
        exit;
        $text='123456';                  // 生成的二维码 内容
        $outfile = 'trayimg/'.$text.'.png';     // 生成的二维码 文件名，false为 不保存
        $level = 'QR_ECLEVEL_L';         //级别,也是容错率。下面会有介绍
        $size = 10;                      //大小
        $margin = 4;                     //外边距
        $saveandprint=true;              //是否 保存和打印。true为保存并打印
        //不带LOGO
        Vendor('phpqrcode.phpqrcode');
        //生成二维码图片
        $object = new \QRcode();
        $object->png($text, $outfile, $level, $size,$margin, $saveandprint); 
        echo ROOT_PATH.'public\trayimg/'.$data['tp_num'].'.png';
        $res=db('tray')
        ->insert([
            'tp_num'=>$data['tp_num'],
            'warehouse_id'=>$data['warehouse_id'],
            'create_time'=>time(),
            'pic'=>''
        ]);
        return view('tray_add');  
    }
}
