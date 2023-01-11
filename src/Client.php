<?php

declare(strict_types=1);

namespace Yurun\TDEngine;

use Swoole\Coroutine;
use Yurun\TDEngine\Action\Sql\SqlResult;
use Yurun\TDEngine\Constants\TimeStampFormat;
use Yurun\TDEngine\Exception\NetworkException;
use Yurun\TDEngine\Exception\OperationException;
use Yurun\Util\HttpRequest;
use Yurun\Util\YurunHttp\Http\Psr7\Uri;

class Client
{
    /**
     * @var ClientConfig
     */
    protected $config;

    /**
     * @var HttpRequest|null
     */
    protected $httpClient;

    /**
     * @var bool
     */
    protected $haveSwoole;

    public function __construct(ClientConfig $config)
    {
        $this->config = $config;
        $this->haveSwoole = \defined('SWOOLE_VERSION');
    }

    public function getConfig(): ClientConfig
    {
        return $this->config;
    }

    public function buildUrl(string $path): string
    {
        $config = $this->getConfig();
        $query = http_build_query(['tz' => $config->getTz()]);

        return Uri::makeUri($config->getHost(), $path, $query, $config->getPort(), $config->getSsl() ? 'https' : 'http', '', $config->getUser() . ':' . $config->getPassword())->__toString();
    }

    private function getHttpClient(): HttpRequest
    {
        if ($this->getConfig()->getKeepAlive() && $this->haveSwoole && Coroutine::getCid() > 0)
        {
            $context = Coroutine::getContext();
            $objectHash = spl_object_hash($this);
            if (isset($context[self::class][$objectHash]))
            {
                return $context[self::class][$objectHash];
            }
            else
            {
                return $context[self::class][$objectHash] = new HttpRequest();
            }
        }
        elseif (isset($this->httpClient))
        {
            return $this->httpClient;
        }
        else
        {
            return new HttpRequest();
        }
    }

    public function request(string $path, string $postBody): array
    {
        $config = $this->getConfig();
        $httpClient = $this->getHttpClient();
        if ('' !== ($hostName = $config->getHostName()))
        {
            $httpClient->header('Host', $hostName);
        }
        $response = $httpClient->post($this->buildUrl($path), $postBody);
        $result = $response->json(true);
        if (null === $result || false === $result)
        {
            throw new NetworkException(sprintf('Http request failed! statusCode:%s, errno:%s, error:%s', $response->getStatusCode(), $response->getErrno(), $response->getError()));
        }
        if ('succ' !== ($result['status'] ?? '') && 0 !== $result['code'])
        {
            throw new OperationException($result['desc'], $result['code']);
        }

        return $result;
    }

    public function sql(string $sql): SqlResult
    {
        $config = $this->getConfig();
        if (version_compare($config->getVersion(), '3', '<'))
        {
            switch ($config->getTimestampFormat())
            {
                case TimeStampFormat::LOCAL_STRING:
                    $path = '/rest/sql';
                    break;
                case TimeStampFormat::TIMESTAMP:
                    $path = '/rest/sqlt';
                    break;
                case TimeStampFormat::UTC_STRING:
                    $path = '/rest/sqlutc';
                    break;
                default:
                    throw new \InvalidArgumentException(sprintf('Invalid timestampFormat %s', $config->getTimestampFormat()));
            }
        }
        else
        {
            $path = '/rest/sql';
        }
        $db = $config->getDb();
        if ('' !== $db)
        {
            $path .= '/' . $db;
        }
        $result = $this->request($path, $sql);

        return new SqlResult($result);
    }
}
