<?php
namespace app\api\controller;
use think\session\driver\Redis;

include_once "wxBizDataCrypt.php";
class Users
{
    //获取用户信息接口
    public function users($code,$iv,$encryptedData)
    {

        $appid = 'wx07a8c01e7ff6142e';
        $appsecret = '302cb48b68a76cd831759f8d363481a1';
        //获取access_token
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$appsecret";
        $ac_t = file_get_contents($url);
        $ac_t = json_decode($ac_t, true);
        $access_token = $ac_t['access_token'];
        function httpGet($url)
        {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, 500);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_URL, $url);
            $res = curl_exec($curl);
            curl_close($curl);
            return $res;
        }
        $grant_type = "authorization_code"; //授权（必填）
        $params = "appid=" . $appid . "&secret=" . $appsecret . "&js_code=" . $code . "&grant_type=" . $grant_type;
        $url = "https://api.weixin.qq.com/sns/jscode2session?" . $params;
        $res = json_decode(httpGet($url), true);
        $sessionKey = $res['session_key'];//取出json里对应的值
        $pc = new \WXBizDataCrypt($appid, $sessionKey);
        $errCode = $pc->decryptData($encryptedData, $iv, $data);
        if ($errCode == 0) {
            $da = json_decode($data, true);
            $msg=['error'=>0,'da'=>$da];
        } else {
            $msg=['error'=>1,'msg'=>'获取信息失败'];
        }
        return $msg;
    }
    //用户授权
    public function index()
    {
        //redis
        $r = new \Redis();
        $r->connect('127.0.0.1');
        $code = input('code');
        $iv = input('iv');
        $encryptedData = input('encryptedData');
        $da=$this->users($code,$iv,$encryptedData);
        if($da['error']==0){
                $da = $da['da'];
                //redis键值对
                $key = uniqid() . mt_rand(1000, 9999);
                //uniqid为键,openid为值
                $r->set($key, $da['openId']);
                //有效期
                $r->expire($key, 3600 * 8);
                //用户基本信息
                $count = db('consumer')->where('openid', $da['openId'])->count();
                if ($count == 0) {
                    $r = db('consumer')->insert(['openid' => $da['openId'], 'username' => $da['nickName'], 'header' => $da['avatarUrl']]);
                    if ($r) {
                        $msg = ['error' => 0, 'message' => '登录成功', 'key' => $key];
                    } else {
                        $msg = ['error' => 1002, 'message' => '注册失败'];
                    }
                } else {
                    $msg = ['error' => 0, 'message' => '登录成功', 'key' => $key];
                }
            } else {
                $msg = ['error' => 1001, 'message' => '授权失败'];
            }
            return $msg;
    }
    //用户登录
    public function login()
    {
        //redis
        $r=new \Redis();
        $r->connect('127.0.0.1');
        $openid=input('openid');
        $row=db('consumer')->where('is_del',0)->where('openid',$openid)->find();
        if($row){
            if($row['status']==2){
                $data=['error'=>1004,'msg'=>'黑名单'];
            }else{
                $key=uniqid().mt_rand(1000,9999);
                $r->set($key,$row['openid']);
                $r->expire($key,3600*8);
                $data=['error'=>0,'msg'=>'登录成功','value'=>$row,'key'=>$key];
            }
        }else{
            $data=['error'=>1005,'msg'=>'请授权'];
        }
        return $data;
    }
}

















