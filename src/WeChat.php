<?php
/**
 * Created by PhpStorm.
 * User: alice
 * Date: 2018/12/18
 * Time: 17:42
 */

namespace Npaaui\WeChat;


class WeChat
{
    private $TEST = "test";

    public static function index()
    {
        return "this is weChat index";
    }

    public function access()
    {
        //获得参数 signature nonce token timestamp echostr
        $nonce = $_GET['nonce'];
        $token = 'alice';
        $timestamp = $_GET['timestamp'];
        $signature = $_GET['signature'];
        //形成数组，然后按字典序排序
        $array = array($nonce, $timestamp, $token);
        sort($array);
        //拼接成字符串,sha1加密 ，然后与signature进行校验
        $str = sha1(implode($array));
        if ($str == $signature && isset($_GET['echostr'])) {
            //第一次接入weixin api接口的时候
            echo $_GET['echostr'];
            exit;
        }
    }
}