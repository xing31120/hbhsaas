<?php

namespace app\common\tools;

use app\common\tools\Random;

/**
 * Aes 加密 解密类库
 * Class Aes
 * @package app\common\lib
 */
class Aes
{

    public static $apiKey = '';
    public static $apiIv = '';

    private $hex_iv = null;
    private $key = null;

    public function __construct()
    {
        $this->hex_iv = '00000000000000000000000000000000';
        $this->key = hash('sha256', 'lPwIYiE*ZO^V%1%x', true);
    }

    /**
     * 随机生成向量
     */
    public static function getAesArray(){
        $data['key'] = Random::alnum(32);
        $data['iv'] = Random::alnum(16);
        return $data;
    }

    /**
     * 加密算法
     * @param $str
     * @return string
     */
    public static function apiEncrypt($input, $array = array())
    {
        self::$apiKey = $array['key'];
        self::$apiIv = $array['iv'];
        $data = openssl_encrypt($input, 'AES-256-CBC', self::$apiKey, OPENSSL_RAW_DATA, self::$apiIv);
        $data = base64_encode($data);
        return $data;
    }

    /**
     * 加密算法
     * @param $str
     * @return string
     */
    public static function apiDecrypt($input, $array = array())
    {
        self::$apiKey = $array['key'];
        self::$apiIv = $array['iv'];
        $decrypted = openssl_decrypt(base64_decode($input), 'AES-256-CBC', self::$apiKey, OPENSSL_RAW_DATA, self::$apiIv);
        return $decrypted;
    }


    /**
     * 加密算法
     * @param $str
     * @return string
     */
    function encrypt($input)
    {
        $data = openssl_encrypt($input, 'AES-256-CBC', $this->key, OPENSSL_RAW_DATA, $this->hexToStr($this->hex_iv));
        $data = base64_encode($data);
        return $data;
    }

    /**
     * 解密算法
     * @param $code
     * @return mixed
     */
    function decrypt($input)
    {
        $input = str_replace(' ','+',$input);
        $decrypted = openssl_decrypt(base64_decode($input), 'AES-256-CBC', $this->key, OPENSSL_RAW_DATA, $this->hexToStr($this->hex_iv));
        return $decrypted;
    }

    /**
     * @param $hex
     * @return string
     */
    function hexToStr($hex)
    {
        $string = '';
        for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
            $string .= chr(hexdec($hex[$i] . $hex[$i + 1]));
        }
        return $string;
    }
}