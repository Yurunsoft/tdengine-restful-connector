<?php

declare(strict_types=1);

namespace Yurun\TDEngine\Constants;

class TimeStampFormat
{
    /**
     * 本地时间格式.
     */
    public const LOCAL_STRING = 0;

    /**
     * Unix时间戳.
     */
    public const TIMESTAMP = 1;

    /**
     * UTC时间字符串.
     */
    public const UTC_STRING = 2;

    private function __construct()
    {
    }
}
