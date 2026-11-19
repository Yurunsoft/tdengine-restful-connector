<?php

declare(strict_types=1);

namespace Yurun\TDEngine\Test;

use PHPUnit\Framework\TestCase;
use Yurun\TDEngine\Client;
use Yurun\TDEngine\ClientConfig;
use Yurun\TDEngine\Exception\OperationException;

class IoTDBClientTest extends TestCase
{
    public function testIoTDBDefaultsUseRestPortAndDefaultPassword(): void
    {
        $config = new ClientConfig(['dbType' => ClientConfig::TYPE_IOTDB]);
        $fluentConfig = (new ClientConfig())->setDbType(ClientConfig::TYPE_IOTDB);
        $customConfig = (new ClientConfig())->setPort(6041)->setPassword('custom')->setDbType(ClientConfig::TYPE_IOTDB);

        $this->assertSame(18080, $config->getPort());
        $this->assertSame('root', $config->getPassword());
        $this->assertSame(18080, $fluentConfig->getPort());
        $this->assertSame('root', $fluentConfig->getPassword());
        $this->assertSame(6041, $customConfig->getPort());
        $this->assertSame('custom', $customConfig->getPassword());
    }

    public function testTreeV1QueryUsesBasicAuthAndNormalizesColumnarValues(): void
    {
        $response = [
            'expressions' => ['root.sg.d1.s1', 'root.sg.d1.s2'],
            'columnNames' => null,
            'timestamps' => [1000, 2000],
            'values' => [[21.5, 22.0], [48.0, 49.5]],
        ];
        $client = new IoTDBTestClient($this->config(), $response);
        $result = $client->sql('select s1, s2 from root.sg.d1');

        $this->assertSame('/rest/v1/query', $client->lastPath);
        $this->assertSame(['sql' => 'select s1, s2 from root.sg.d1'], $client->lastPayload);
        $this->assertSame('Basic cm9vdDpyb290', $client->lastHeaders['Authorization']);
        $this->assertSame('application/json', $client->lastHeaders['Content-Type']);
        $this->assertSame(['Time', 'root.sg.d1.s1', 'root.sg.d1.s2'], $result->getHead());
        $this->assertSame([
            ['Time' => 1000, 'root.sg.d1.s1' => 21.5, 'root.sg.d1.s2' => 48.0],
            ['Time' => 2000, 'root.sg.d1.s1' => 22.0, 'root.sg.d1.s2' => 49.5],
        ], $result->getData());
    }

    public function testTreeV2RoutesNonQueryAndNormalizesSuccess(): void
    {
        $client = new IoTDBTestClient($this->config([
            'iotdbApiVersion' => 'v2',
        ]), ['code' => 200, 'message' => 'SUCCESS_STATUS']);
        $result = $client->sql('CREATE DATABASE root.sg');

        $this->assertSame('/rest/v2/nonQuery', $client->lastPath);
        $this->assertSame('succ', $result->getStatus());
        $this->assertSame(200, $result->getCode());
        $this->assertSame([], $result->getData());
    }

    public function testTableV1UsesDatabasePayloadAndRowOrientedResults(): void
    {
        $client = new IoTDBTestClient($this->config([
            'dbType'       => ClientConfig::TYPE_IOTDB,
            'iotdbModel'   => ClientConfig::IOTDB_MODEL_TABLE,
            'iotdbApiVersion' => 'v1',
            'db'           => 'telemetry',
        ]), [
            'column_names' => ['temperature', 'status'],
            'data_types'   => ['FLOAT', 'BOOLEAN'],
            'values'       => [[21.5, true], [22.0, false]],
        ]);
        $result = $client->sql('select temperature, status from device_metrics');

        $this->assertSame('/rest/table/v1/query', $client->lastPath);
        $this->assertSame([
            'sql'      => 'select temperature, status from device_metrics',
            'database' => 'telemetry',
        ], $client->lastPayload);
        $this->assertSame([
            ['temperature' => 21.5, 'status' => true],
            ['temperature' => 22.0, 'status' => false],
        ], $result->getData());
        $this->assertSame('FLOAT', $result->getColumns()['temperature']->getTypeName());
        $this->assertSame(\Yurun\TDEngine\Struct\ColumnMeta::TYPE_BOOL, $result->getColumns()['status']->getType());
    }

    public function testIoTDBOperationErrorsKeepServerCodeAndMessage(): void
    {
        $client = new IoTDBTestClient($this->config(), ['code' => 407, 'message' => 'SQL is not supported']);

        try
        {
            $client->sql('select * from root.sg align by device');
            $this->fail('Expected IoTDB operation error');
        }
        catch (OperationException $exception)
        {
            $this->assertSame(407, $exception->getCode());
            $this->assertTrue(false !== strpos($exception->getMessage(), 'SQL is not supported'));
        }
        $this->assertSame('/rest/v1/query', $client->lastPath);
    }

    public function testTableRestV2IsRejectedBeforeMakingRequest(): void
    {
        $client = new IoTDBTestClient($this->config([
            'dbType'          => ClientConfig::TYPE_IOTDB,
            'iotdbModel'      => ClientConfig::IOTDB_MODEL_TABLE,
            'iotdbApiVersion' => 'v2',
        ]), ['code' => 200]);

        $this->expectException(\InvalidArgumentException::class);
        $client->sql('select * from device_metrics');
    }

    public function testTableQueryRequiresConfiguredDatabase(): void
    {
        $client = new IoTDBTestClient($this->config([
            'iotdbModel' => ClientConfig::IOTDB_MODEL_TABLE,
        ]), ['column_names' => [], 'data_types' => [], 'values' => []]);

        $this->expectException(\InvalidArgumentException::class);
        $client->sql('select * from device_metrics');
    }

    private function config(array $overrides = []): ClientConfig
    {
        return new ClientConfig(array_merge([
            'dbType'   => ClientConfig::TYPE_IOTDB,
            'host'     => '127.0.0.1',
            'port'     => 18080,
            'user'     => 'root',
            'password' => 'root',
        ], $overrides));
    }
}

class IoTDBTestClient extends Client
{
    public $lastPath;
    public $lastPayload;
    public $lastHeaders;

    private $response;

    public function __construct(ClientConfig $config, array $response)
    {
        parent::__construct($config);
        $this->response = $response;
    }

    protected function sendIoTDBRequest(string $path, array $payload): array
    {
        $this->lastPath = $path;
        $this->lastPayload = $payload;
        $this->lastHeaders = $this->getIoTDBHeaders();

        return [200, $this->response];
    }
}
