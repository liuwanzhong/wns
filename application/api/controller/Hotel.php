<?php
/**
 * Created by PhpStorm.
 * User: 金戋
 * Date: 2019/9/6
 * Time: 16:45
 */

namespace app\api\controller;


use think\Controller;

class Hotel extends Controller
{
    //餐厅接口
    public function index()
    {
        $row=db('restaurant')->where('is_delete',0)->where('state',0)->select();
        return $row;
    }

    public function home()
    {
        $id=input('restaurant');//餐厅
        $table_number=input('table_number');//桌号
        $row=db('restaurant')->where('id',$id)->find();
       if($row){
           if($row['state']==1){
               $message=['error'=>101,'msg'=>'餐厅已停止营业'];
               return $message;
           }
           $food_sort=db('food_sort')->where('is_delete',0)->where('state',0)->field('name,id')->select();
           $food_id=explode(',',$row['foot_id']);
           $food=db('food')->where('state',0)->where('is_delete',0)->field('name,money,img,id,content')->select();
           $md=[];
           foreach ($food as $f) {
               foreach ($food_id as $f_id) {
                   if($f['id']==$f_id){
                       $md[]=$f;
                   }
               }
           }
           foreach ($md as $k=>$item) {
               $md[$k]['img']='http://crm.cdqifa.cn:66'.$md[$k]['img'];
           }
           return ['restaurant'=>$row['name'],'food_sort'=>$food_sort,'md'=>$md];
       }else{
           return ['error'=>102,'msg'=>'非法访问'];
       }
    }
}