<?php

namespace HyanCat\NeteaseMusic;

/**
 * Class NeteaseException
 * @namespace NeteaseMusic
 */
class NeteaseException extends \Exception
{
    public function __construct($code, $message)
    {
        parent::__construct($message, $code);
    }
}

/**
 * Class NeteaseVipException
 * @namespace NeteaseMusic
 */
class NeteaseVipException extends NeteaseException
{

}
