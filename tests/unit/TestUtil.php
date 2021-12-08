<?php

declare(strict_types=1);

namespace Yurun\TDEngine\Test;

use Yurun\TDEngine\ClientConfig;
use Yurun\TDEngine\Constants\TimeStampFormat;

class TestUtil
{
    private function __construct()
    {
        
    }

    public static function getClientConfig():ClientConfig
    {
        return new ClientConfig([
            'host'            => getenv('TDENGINE_HOST') ?: '127.0.0.1',
            'hostName'        => getenv('TDENGINE_HOST_NAME') ?: '',
            'port'            => getenv('TDENGINE_PORT') ?: 6041,
            'user'            => getenv('TDENGINE_USER') ?: 'root',
            'password'        => getenv('TDENGINE_PASSWORD') ?: 'taosdata',
            'ssl'             => getenv('TDENGINE_SSL') ?: false,
            'timestampFormat' => getenv('TDENGINE_TIMESTAMP_FORMAT') ?: TimeStampFormat::LOCAL_STRING,
        ]);
    }
}