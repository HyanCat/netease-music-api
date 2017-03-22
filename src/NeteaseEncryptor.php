<?php

namespace HyanCat\NeteaseMusic;

use phpseclib\Math\BigInteger;

/**
 * NetEase Music Encryptor.
 */
class NeteaseEncryptor
{
    // Constraint
    const MODULUS    = '00e0b509f6259df8642dbc35662901477df22677ec152b5ff68ace615bb7b725152b3ab17a876aea8a5aa76d2e417629ec4ee341f56135fccf695280104e0312ecbda92557c93870114af6c9d05c4f7f0c3685b7a46bee255932575cce10b424d813cfe4875d3e82047b97ddef52741d546b8e289dc6935b3ece0462db0a22b8e7';
    const NONCE      = '0CoJUm6Qyw8W8jud';
    const PUBLIC_KEY = '010001';
    const VI         = '0102030405060708';

    /**
     * 私钥，随机十六位字符串.
     *
     * @var string
     */
    private $_secretKey = '';

    public function __construct()
    {
        $this->_secretKey = $this->randomString(16);
    }

    /**
     * 加密请求参数.
     *
     * @param array $value 加密前的参数数组
     *
     * @return array 加密后的参数数组
     */
    public function encryptParams($parameters)
    {
        $data['params']    = $this->aesEncrypt(json_encode($parameters), self::NONCE);
        $data['params']    = $this->aesEncrypt($data['params'], $this->_secretKey);
        $data['encSecKey'] = $this->rsaEncrypt($this->_secretKey);

        return $data;
    }

    /**
     * AES 加密.
     *
     * @param string $rawData   [description]
     * @param string $secretKey [description]
     * @param string $vi        [description]
     *
     * @return string [description]
     */
    public function aesEncrypt($rawData, $secretKey, $vi = self::VI)
    {
        return openssl_encrypt($rawData, 'aes-128-cbc', $secretKey, false, $vi);
    }

    /**
     * RSA 加密.
     *
     * @param string $rawData   [description]
     * @param string $publicKey [description]
     * @param string $modulus   [description]
     *
     * @return string [description]
     */
    public function rsaEncrypt($rawData, $publicKey = self::PUBLIC_KEY, $modulus = self::MODULUS)
    {
        $reverseData    = strrev(utf8_encode($rawData));
        $hexReverseData = $this->strToHex($reverseData);
        $keyText        = $this->bchexdec($hexReverseData);

        $a   = new BigInteger($keyText);
        $b   = new BigInteger($this->bchexdec($publicKey));
        $c   = new BigInteger($this->bchexdec($modulus));
        $key = $a->modPow($b, $c)->toHex();

        return str_pad($key, 256, '0', STR_PAD_LEFT);
    }

    /**
     * 将任意精度的十六进制字符串转成十进制字符串.
     *
     * @param string $hex [description]
     *
     * @return string [description]
     */
    private function bchexdec($hex)
    {
        $dec = '0';
        $len = strlen($hex);
        for ($i = 0; $i < $len; ++$i) {
            $dec = bcadd($dec, bcmul(strval(hexdec($hex[$i])), bcpow('16', strval($len - $i - 1))));
        }

        return $dec;
    }

    /**
     * 将普通字符串按照 ASCII 码转成十六进制字符串.
     *
     * @param string $str [description]
     *
     * @return string [description]
     */
    public function strToHex($str)
    {
        $hex = '';
        for ($i = 0; $i < strlen($str); ++$i) {
            $hex .= dechex(ord($str[$i]));
        }

        return $hex;
    }

    /**
     * 随机生成数字和字母的字符串.
     *
     * @param int $length [description]
     *
     * @return [type] [description]
     */
    private function randomString($length = 16)
    {
        $str          = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; ++$i) {
            $randomString .= $str[rand(0, strlen($str) - 1)];
        }

        return $randomString;
    }
}
