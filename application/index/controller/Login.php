<?php
/**
 * Created by PhpStorm.
 * User: 金戋
 * Date: 2019/8/27
 * Time: 16:18
 */

namespace app\index\controller;


use think\Controller;
use think\Session;

class Login extends Controller
{
    public static $md=[];
    //登录界面
    public function login()
    {
        return view('login');
    }
    //登录验证
    public function proving()
    {
        $data=input();
        $pwd=md5(md5($data['password']));
        $row=db('staffs')
            ->where('is_del',1)
            ->where('staffs_number',$data['username'])
            ->where('password',$pwd)
            ->find();
        if($row){
            Session::set('users',$row);
            return redirect('home');
        }else{
            $this->error('登录失败,用户名或密码错误');
        }
        return redirect('home');
    }
    //首页数据
    public function home() {
        $a=[];
        $user=Session::get('users');
        //导航容器
        if($user['id']==1){
            $rows=db('pow')->where('is_del',1)->where('state','正常')->where('cj',1)->select();
            $rows_one=db('pow')->where('is_del',1)->where('state','正常')->select();
            foreach ($rows as $r=>$o) {
                foreach ($rows_one as $a=>$b) {
                    if($o['id']==$b['parent_id']){
                        $rows[$r]['er'][$b['pow_url']]=$b['pow_name'];
                    }

                }
                $a=$rows;
                foreach ($a as $k=>$item) {
                    if(!isset($item['er'])){
                        $a[$k]['er']=[];
                    }
                }
            }
        }else{
            $u=db('staffs')->where('id',$user['id'])->find();
            $code=explode(",",$u['power']);
            $this->read(0,$code);
            $md = self::$md;
            $rows=$md;
            //找到所有层级为1的
            foreach ($rows as $row) {
                if($row['cj']==1){
                    $a[]=$row;
                }
            }
            //利用层级为1的找到子级，并附给er数组
            foreach ($a as $f=>$b) {
                foreach ($rows as $k=>$row) {
                    if ($b['id']==$row['parent_id']){
                        $a[$f]['er'][$row['pow_url']]=$row['pow_name'];
                    }
                }
            }
            //没有数据时，er为空
            foreach ($a as $p=>$item) {
                if (empty($item['er'])){
                    $a[$p]['er']=[];
                }
            }
        }
        $menu=Session::get('users');
        return view('index',['rows'=>$a,'menu'=>$menu]);
    }

    public function read($pid,$code)
    {
        foreach ($code as $item) {
            $rows=db('pow')->where('is_del',1)->where('state','正常')->where('parent_id',$pid)->where('id',$item)->order('sort desc')->select();
            foreach ($rows as $row) {
                self::$md[]=$row;
                $this->read($row['id'],$code);
            }
       }
    }

    //修改密码
    public function users_edit($id) {
        $pwd1=md5(md5(input('pwd1')));
        dump($id);
        $pwd2=input('pwd2');
        $pwd3=input('pwd3');
        $count=db('staffs')->where('id',$id)->where('password',$pwd1)->count();
        if($count==0){
            $this->error('原密码错误');
        }
        if($pwd2!=$pwd3){
            $this->error('两次密码不重复');
        }
        $password=md5(md5(input('pwd2')));
        $r=db('staffs')->where('id',$id)->update(['password'=>$password]);
        if($r){
            $this->error('修改成功,请重新登录','out');
        }else{
            $this->error('修改失败');
        }
    }

    //退出登录
    public function out() {
        Session::delete('users');
        Session::delete('zt');
        return redirect('login');
    }
}
