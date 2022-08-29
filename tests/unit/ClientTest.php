<?php

declare(strict_types=1);

namespace Yurun\TDEngine\Test;

use PHPUnit\Framework\TestCase;
use Yurun\TDEngine\Client;

class ClientTest extends TestCase
{
    private function getClient(): Client
    {
        return new Client(TestUtil::getClientConfig());
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
        $result = $client->sql(sprintf('insert into db_test.tb values(%s,%s,%s)', $time, 36, 44.5));
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
        if([
            [
                'ts'          => gmdate('Y-m-d H:i:s', $data['time'] / 1000) . '.000',
                'temperature' => 36,
                'humidity'    => 44.5,
            ],
        ] !== $result->getData() && [
            [
                'ts'          => gmdate('Y-m-d H:i:s', $data['time'] / 1000) . 'Z',
                'temperature' => 36,
                'humidity'    => 44.5,
            ],
        ] !== $result->getData()){
            var_dump($result->getData());
            $this->assertTrue(false);
        }
    }
}
