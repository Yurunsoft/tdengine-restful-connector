<?php

declare(strict_types=1);

namespace Yurun\TDEngine\Test;

use PHPUnit\Framework\TestCase;
use Yurun\TDEngine\ClientConfig;
use Yurun\TDEngine\TDEngineManager;

class ManagerTest extends TestCase
{
    public function testClientConfig(): void
    {
        $this->assertNull(TDEngineManager::getClientConfig());

        TDEngineManager::setClientConfig('test', TestUtil::getClientConfig());
        TDEngineManager::setClientConfig('test2', TestUtil::getClientConfig());

        $this->assertInstanceOf(ClientConfig::class, TDEngineManager::getClientConfig('test'));
    }

    public function testRemoveClientConfig(): void
    {
        TDEngineManager::setClientConfig('testx', new ClientConfig());
        $this->assertNotNull(TDEngineManager::getClientConfig('testx'));
        TDEngineManager::removeClientConfig('testx');
        $this->assertNull(TDEngineManager::getClientConfig('testx'));
    }

    public function testDefaultClientName(): void
    {
        $this->assertNull(TDEngineManager::getDefaultClientName());
        TDEngineManager::setDefaultClientName('test');
        $this->assertEquals('test', TDEngineManager::getDefaultClientName());
    }

    public function testGetClient(): void
    {
        $client = TDEngineManager::getClient();
        $this->assertNotNull($client);
    }
}
