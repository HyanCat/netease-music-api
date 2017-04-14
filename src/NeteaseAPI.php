<?php

namespace HyanCat\NeteaseMusic;

use Curl\Curl;

/**
 * NetEase Music API.
 */
class NeteaseAPI
{
    // General
    private $_userAgent = 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/35.0.1916.157 Safari/537.36';
    private $_cookie    = 'os=pc; osver=Microsoft-Windows-10-Professional-build-10586-64bit; appver=2.0.3.131777; channel=netease; __remember_me=true';
    private $_referrer  = 'http://music.163.com';

    /**
     * encryptor.
     *
     * @var NeteaseEncryptor
     */
    protected $encryptor;

    /**
     * Curl instance.
     *
     * @var \Curl\Curl
     */
    protected $curl;

    const URL_SEARCH          = 'http://music.163.com/weapi/cloudsearch/get/web?csrf_token=';
    const URL_ARTIST          = 'http://music.163.com/weapi/v1/artist/%d?csrf_token=';
    const URL_ALBUM           = 'http://music.163.com/weapi/v1/album/%d?csrf_token=';
    const URL_PLAYLIST_DETAIL = 'http://music.163.com/weapi/v3/playlist/detail?csrf_token=';
    const URL_SONG_DETAIL     = 'http://music.163.com/weapi/v3/song/detail?csrf_token=';
    const URL_SONG_URL        = 'http://music.163.com/weapi/song/enhance/player/url?csrf_token=';
    const URL_SONG_LYRIC      = 'http://music.163.com/weapi/song/lyric?csrf_token=';
    const URL_MV              = 'http://music.163.com/weapi/mv/detail?csrf_token=';

    const SEARCH_TYPE_SONG     = 1;
    const SEARCH_TYPE_ALBUM    = 10;
    const SEARCH_TYPE_SINGER   = 100;
    const SEARCH_TYPE_PLAYLIST = 1000;
    const SEARCH_TYPE_USER     = 1002;
    const SEARCH_TYPE_RADIO    = 1009;

    public function __construct()
    {
        $this->encryptor = new NeteaseEncryptor();
        $this->curl      = new Curl();
        $this->_prepare();
    }

    /**
     * 根据关键字搜索
     *
     * @param  string      $keywork 搜索关键字
     * @param  int|integer $type    类型
     * @param  int|integer $limit   限制单次结果数量
     * @param  int|integer $offset  结果偏移量
     *
     * @return array|null           搜索结果
     */
    public function search(string $keywork, int $type = self::SEARCH_TYPE_SONG, int $limit = 20, int $offset = 0)
    {
        $params   = [
            's'          => $keywork,
            'type'       => $type,
            'limit'      => $limit,
            'total'      => 'true',
            'offset'     => $offset,
            'csrf_token' => '',
        ];
        $response = $this->_request(self::URL_SEARCH, $params);

        return $response;
    }

    /**
     * 歌单详情.
     *
     * @param int $playlistID 歌单 ID
     *
     * @return array|null     歌单信息
     */
    public function playlistDetail($playlistID)
    {
        $params   = [
            'id'         => $playlistID,
            'n'          => 1000,
            'csrf_token' => '',
        ];
        $response = $this->_request(self::URL_PLAYLIST_DETAIL, $params);

        return $response;
    }

    /**
     * 歌曲详情.
     *
     * @param int $songID 歌曲 ID
     *
     * @return array|null 歌曲信息
     */
    public function songDetail($songID)
    {
        // 注意：这是个批量接口
        $params   = [
            'c'          => json_encode([['id' => $songID]]),
            'csrf_token' => '',
        ];
        $response = $this->_request(self::URL_SONG_DETAIL, $params);

        return $response;
    }

    /**
     * 歌曲资源.
     * @param  int $songID  歌曲 ID
     * @param int  $quality 歌曲比特率
     * @return array|null
     */
    public function songResource($songID, $quality = 320)
    {
        // 注意：这是个批量接口
        $params   = [
            'ids'        => [$songID],
            'br'         => $quality,
            'csrf_token' => '',
        ];
        $response = $this->_request(self::URL_SONG_URL, $params);

        return $response;
    }

    /**
     * 发送网络请求
     *
     * @param string $url    请求接口的 url
     * @param array  $params 请求参数
     *
     * @throws NeteaseException
     *
     * @return array|null 请求响应数据
     */
    private function _request($url, $params)
    {
        $encryptedParams = $this->encryptor->encryptParams($params);
        $this->curl->post($url, $encryptedParams);
        if ($this->curl->error) {
            throw new NeteaseException($this->curl->errorCode, $this->curl->errorMessage);
        } else {
            $response = $this->curl->response;
            if (! is_string($response) && $response->code === 400) {
                throw new NeteaseVipException($response->code, $response->msg);
            }
            $responseArray = json_decode($response, true);
            if (! array_has($responseArray, 'code') || $responseArray['code'] != 200) {
                throw new NeteaseException(-1, 'Response code error.');
            }

            return $responseArray;
        }

        return;
    }

    /**
     * 准备请求的一些相关属性.
     */
    private function _prepare()
    {
        $this->curl->setUserAgent($this->_userAgent);
        $this->curl->setReferrer($this->_referrer);
        $this->curl->setCookieString($this->_cookie);
    }
}
