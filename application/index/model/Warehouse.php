<?php
namespace app\index\model;
use think\Model;

class Warehouse extends Model{
//     public function warehouse(){
////        return db('warehouse')->where('is_del',0)->paginate(2);
//         return  $this->hasOne('warehouse')->slect();
//     }
    protected $table = 'warehouse';
    public static function index(){

        $list = '1';
        $re = warehouse::all(1);

//        $re = warehouse::all(function($query) use ($list) {
//
//            $query->where('id','=',$list);
//
//        });

//        return db('warehouse')->getlastSql();
        return $re[0]->data;

        }


}

?>