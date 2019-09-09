<?php
/**
 * Created by PhpStorm.
 * User: 金戋
 * Date: 2019/9/6
 * Time: 17:00
 */

namespace app\api\controller;


use think\Controller;

class Em extends Controller
{
    public function index()
    {
        header('content-type:text/html;charset=utf-8');
//配置APPID、APPSECRET
        $APPID = "wx07a8c01e7ff6142e";
        $APPSECRET =  "302cb48b68a76cd831759f8d363481a1";
//获取access_token
        $access_token = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$APPID&secret=$APPSECRET";
//缓存access_token
        session_start();
        $_SESSION['access_token'] = "";
        $_SESSION['expires_in'] = 0;
        $ACCESS_TOKEN = "";
        if(!isset($_SESSION['access_token']) || (isset($_SESSION['expires_in']) && time() > $_SESSION['expires_in']))
        {
            $json = $this->httpRequest( $access_token );
            $json = json_decode($json,true);
            // var_dump($json);
            $_SESSION['access_token'] = $json['access_token'];
            $_SESSION['expires_in'] = time()+7200;
            $ACCESS_TOKEN = $json["access_token"];
        }
        else{
            $ACCESS_TOKEN =  $_SESSION["access_token"];
        }
        $qcode ="https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token=$ACCESS_TOKEN";
        $param = json_encode(array("path"=>"pages/index/index?id=123","width"=> 150));
        $result = $this->httpRequest( $qcode, $param,"POST");
        file_put_contents("qrcode.png", $result);
        $base64_image ="data:image/jpeg;base64,".base64_encode( $result );
//把请求发送到微信服务器换取二维码
    }
    public function httpRequest($url, $data='', $method='GET'){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        if($method=='POST')
        {
            curl_setopt($curl, CURLOPT_POST, 1);
            if ($data != '')
            {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
        }
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }
}