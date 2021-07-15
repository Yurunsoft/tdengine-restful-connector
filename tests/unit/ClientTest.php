<?php

declare(strict_types=1);

namespace Yurun\TDEngine\Test;

use PHPUnit\Framework\TestCase;
use Yurun\TDEngine\Client;
use Yurun\TDEngine\ClientConfig;
use Yurun\TDEngine\Constants\TimeStampFormat;

class ClientTest extends TestCase
{
    private function getClient(): Client
    {
        return new Client(new ClientConfig([
            'host'            => getenv('TDENGINE_HOST') ?: '127.0.0.1',
            'hostName'        => getenv('TDENGINE_HOST_NAME') ?: '',
            'port'            => getenv('TDENGINE_PORT') ?: 6041,
            'user'            => getenv('TDENGINE_USER') ?: 'root',
            'password'        => getenv('TDENGINE_PASSWORD') ?: 'taosdata',
            'ssl'             => getenv('TDENGINE_SSL') ?: false,
            'timestampFormat' => getenv('TDENGINE_TIMESTAMP_FORMAT') ?: TimeStampFormat::LOCAL_STRING,
        ]));
    }

    private function getDbName(): string
    {
        return getenv('TDENGINE_DB_NAME') ?: 'db_test';
    }

    public function testCreateDatabase(): void
    {
        $client = $this->getClient();
        $result = $client->sql('create database if not exists ' . $this->getDbName());
        $this->assertTrue(true);
    }

    public function testCreateTable(): void
    {
        $client = $this->getClient();
        $result = $client->sql('create table if not exists ' . $this->getDbName() . '.tb (ts timestamp, temperature int, humidity float) ');
        $this->assertTrue(true);
    }

    public function testInsert(): array
    {
        $client = $this->getClient();
        $time = time() * 1000;
        $result = $client->sql(sprintf('insert into db_test.tb values(%s,%s,%s)', $time, 36, 44.0));
        $this->assertEquals(1, $result->getRows());

        return ['time' => $time];
    }

    /**
     * @depends testInsert
     */
    public function testSelect(array $data): void
    {
        $client = $this->getClient();
        $result = $client->sql('select * from db_test.tb order by ts desc limit 1');
        $this->assertEquals([
            [
                'ts'          => gmdate('Y-m-d H:i:s', $data['time'] / 1000) . '.000',
                'temperature' => 36,
                'humidity'    => 44.0,
            ],
        ], $result->getData());
    }
}
