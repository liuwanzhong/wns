<?php


namespace app\index\controller;

use think\Controller;
use think\Db;

class Table extends Controller {
    //建表
    public function _initialize()
    {
        $tablename = "rukuform_".date("Ym",time());
        $table = db()->query("show tables like '$tablename'");
        if(!$table){
            $sql = "CREATE TABLE `$tablename` (
                  `id` int(10) unsigned NOT NULL COMMENT '入库表垂直分表关联主键id字段',
                  `factory` varchar(255) DEFAULT NULL COMMENT '入库工厂',
                  `product_name` varchar(255) DEFAULT NULL COMMENT '产品名称',
                  `rk_status_id` int(10) unsigned DEFAULT NULL COMMENT '入库状态id',
                  `rk_huowei_id` int(10) unsigned DEFAULT NULL COMMENT '入库货位id',
                  `rk_nums` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '入库实际接收数量',
                  `product_time` varchar(255) NOT NULL COMMENT '产品日期',
                  `product_batch` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '产品入库批次---默认0',
                  `content` text COMMENT '产品备注说明',
                  `is_del` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除：0-未删除；1-删除',
                  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '添加时间',
                  `update_time` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
                  `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                ";
            Db::execute($sql);
        }
    }

    public function index() {
        //产品名称列表
        $list = db('cw_management')->field('material_name,transfers_factory')
            ->group('material_name ')->select();
        //入库状态
        $status = db('kc_status')->where('is_del',0)->select();
        //仓库列表
        $cks = db('warehouse')->where('is_del',1)->select();
//        var_dump($list);exit;
        return view('index',['list'=>$list,'status'=>$status,'cks'=>$cks]);
    }
    public function show($id){
        $list = db('cabinet')->where('is_del',1)->where('warehouse_id',$id)->select();
        return json_encode($list);
    }
}
