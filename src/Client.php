<?php

declare(strict_types=1);

namespace Yurun\TDEngine;

use InvalidArgumentException;
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

    public function __construct(ClientConfig $config)
    {
        $this->config = $config;
        if ($config->getKeepAlive())
        {
            $this->httpClient = new HttpRequest();
        }
    }

    public function getConfig(): ClientConfig
    {
        return $this->config;
    }

    public function buildUrl(string $path): string
    {
        $config = $this->getConfig();

        return Uri::makeUri($config->getHost(), $path, '', $config->getPort(), $config->getSsl() ? 'https' : 'http', '', $config->getUser() . ':' . $config->getPassword())->__toString();
    }

    public function request(string $path, string $postBody): array
    {
        $config = $this->getConfig();
        if ($config->getKeepAlive())
        {
            $httpClient = $this->httpClient;
        }
        else
        {
            $httpClient = new HttpRequest();
        }
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
                throw new InvalidArgumentException(sprintf('Invalid timestampFormat %s', $config->getTimestampFormat()));
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
