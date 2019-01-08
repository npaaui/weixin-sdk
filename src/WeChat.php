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
    /**
     * 开发者配置相关
     */
    private $appID;     // 开发者ID(AppID)

    private $token;     // 令牌(Token)

    private $encodingAESKey;    // 消息加解密密钥(EncodingAESKey)


    /**
     * 消息参数相关
     */
    protected $signature;   // 消息的加密签名

    protected $timestamp;   // 消息的时间戳

    protected $nonce;   // 消息的随机数

    protected $echostr; // 消息的随机字符串

    protected $encrypt_type;    // 消息的加密类型

    protected $msg_signature;   // 消息体的签名
    
    protected $template;    // 消息返回模版
    
    protected $msgXml;     // 原消息(xml)
    protected $msgObj;     // 消息对象(obj)


    public function __construct($config)
    {
        $this->appID = isset($config['appId']) && !empty($config['appId']) ? $config['appId'] : false;
        $this->token = isset($config['token']) && !empty($config['token']) ? $config['token'] : false;
        $this->encodingAESKey = isset($config['encodingAESKey']) && !empty($config['encodingAESKey']) ? $config['encodingAESKey'] : false;
        $this->template = require dirname(__DIR__) . '/src/Template.php';
    }

    //微信消息处理入口
    public function access()
    {
        //获取参数
        $this->checkParams();

        //处理配置服务器URL验证
        if ($this->echostr !== false && $this->checkSignature()) {
            exit($this->echostr);
        }

        //处理微信推送的post数据
        $this->receiveMsg();
    }

    //获取请求参数
    private function checkParams()
    {
        $this->signature = isset($_GET['signature']) && !empty($_GET['signature']) ? $_GET['signature'] : false;
        $this->timestamp = isset($_GET['timestamp']) && !empty($_GET['timestamp']) ? $_GET['timestamp'] : false;
        $this->nonce = isset($_GET['nonce']) && !empty($_GET['nonce']) ? $_GET['nonce'] : false;
        $this->echostr = isset($_GET['echostr']) && !empty($_GET['echostr']) ? $_GET['echostr'] : false;
        $this->encrypt_type = isset($_GET['encrypt_type']) && !empty($_GET['encrypt_type']) ? $_GET['encrypt_type'] : false;
        $this->msg_signature = isset($_GET['msg_signature']) && !empty($_GET['msg_signature']) ? $_GET['msg_signature'] : false;
    }

    /**
     * 验证签名
     * @return bool
     */
    private function checkSignature()
    {
        if ($this->signature !== false && $this->timestamp !== false && $this->nonce !== false) {
            $tmp_signature = $this->getSignature($this->token, $this->timestamp, $this->nonce);
            if ($tmp_signature === $this->signature) {
                return true;
            }
        }
        return false;
    }

    /**
     * 获取微信消息的签名
     * @param string $token 票据
     * @param string $timestamp 时间戳
     * @param string $nonce 随机字符串
     * @return bool|string
     */
    static function getSignature($token, $timestamp, $nonce)
    {
        //排序
        try {
            $array = array($nonce, $timestamp, $token);
            sort($array, SORT_STRING);
            $str = sha1( implode( $array ) );
            return $str;
        } catch (\Exception $e) {
            @error_log('getSignature Error: ' . $e->getMessage(), 0);
            return FALSE;
        }
    }

    /**
     * 处理消息
     */
    public function receiveMsg()
    {
        $this->msgXml = file_get_contents('php://input');
        $this->msgObj = simplexml_load_string($this->msgXml,'SimpleXMLElement', LIBXML_NOCDATA);
    }

    /**
     * 获取完整消息
     * @param string $type
     * @return array|string|object
     */
    public function _getMsg($type = 'obj')
    {
        switch ($type) {
            case 'xml':
                return $this->msgXml;
                break;
            case 'obj':
                return $this->msgObj;
                break;
            case 'array':
                return (array)$this->msgObj;
                break;
            case 'json':
            default:
                return json_encode($this->msgObj);
                break;
        }
    }

    /**
     * 获取消息类型
     * @return mixed
     */
    public function _getMsgType()
    {
        return $this->msgObj->MsgType;
    }

    /**
     * 获取消息内容
     * @return mixed
     */
    public function _getContent()
    {
        return $this->msgObj->Content;
    }

    /**
     * 构建返回消息
     * @param $msg
     * @return string
     */
    public function getReplyMsg($msg)
    {
        // 获取消息类型
        if ( ! (isset($msg['type'])) ){
            $msg['type'] = 'text';
            $msg['content'] = '暂无法识别您的消息，请见谅。';
        }

        switch ($msg['type']) {
            case 'text':
                $xml = sprintf(
                    $this->template['text'],
                    $this->msgObj->FromUserName,
                    $this->msgObj->ToUserName,
                    time(),
                    $msg['type'],
                    $msg['content']);
                break;
            default:
                return "";
        }
        return $xml;
    }
}