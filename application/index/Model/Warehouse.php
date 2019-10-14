<?php


namespace app\index\Model;


use think\Model;
use think\Request;

class Warehouse extends Model {
    protected $resultSetType = 'collection';
    //关联货位
    public function cabinets() {
        return $this->hasMany('Cabinet','warehouse_id','id');
    }

    //关联管理员
    public function staffs() {
        return $this->hasMany('Staffs','warehouse','id');
    }

    public function index($id) {
        $pageSize=10;
        $page=0;
        $ware=Warehouse::all(function ($query) use ($id){
           $query->where('id', $id)->with(['cabinets' => function($query) use ($id) {
               $query -> where('is_del',1);},'staffs' => function($query){
               $query -> where('staffs_name','龙春兰');}]);
        });
        $status=[-1=>'删除',0=>'禁用',1=>'正常',2=>'待审核'];
        foreach ($ware as $k=>$item) {
            $item['is_del']=$status[$item['is_del']];
            foreach ($item['cabinets'] as $c=>$i) {
                $i['is_del']=$status[$i['is_del']];
            }
            $ware[$k]['cabinets']=array_slice($ware[$k]['cabinets'],$page,$pageSize);
        }
        return $ware;
    }
}
