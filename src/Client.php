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
        if (version_compare($config->getVersion(), '3', '<'))
        {
            $query = '';
        }
        else
        {
            $query = http_build_query(['tz' => $config->getTimezone()]);
        }

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

    protected function requestIoTDB(string $path, array $payload): array
    {
        [$httpStatus, $result] = $this->sendIoTDBRequest($path, $payload);
        $code = isset($result['code']) ? (int) $result['code'] : 200;
        if ($code !== 200)
        {
            $message = $result['message'] ?? $result['error'] ?? 'IoTDB operation failed';
            throw new OperationException(sprintf('IoTDB REST error %d: %s', $code, $message), $code);
        }
        if ($httpStatus < 200 || $httpStatus >= 300)
        {
            throw new NetworkException(sprintf('IoTDB HTTP request failed! statusCode:%s, message:%s', $httpStatus, $result['message'] ?? 'HTTP error'));
        }

        return $result;
    }

    protected function sendIoTDBRequest(string $path, array $payload): array
    {
        $config = $this->getConfig();
        $httpClient = $this->getHttpClient();
        foreach ($this->getIoTDBHeaders() as $name => $value)
        {
            $httpClient->header($name, $value);
        }
        $body = json_encode($payload);
        if (false === $body)
        {
            throw new NetworkException('Unable to encode IoTDB REST request: ' . json_last_error_msg());
        }
        $response = $httpClient->post($this->buildIoTDBUrl($path), $body);
        $result = $response->json(true);
        if (!is_array($result))
        {
            throw new NetworkException(sprintf('IoTDB HTTP request failed! statusCode:%s, errno:%s, error:%s', $response->getStatusCode(), $response->getErrno(), $response->getError()));
        }

        return [(int) $response->getStatusCode(), $result];
    }

    protected function getIoTDBHeaders(): array
    {
        $config = $this->getConfig();
        $headers = [
            'Authorization' => 'Basic ' . base64_encode($config->getUser() . ':' . $config->getPassword()),
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ];
        if ('' !== ($hostName = $config->getHostName()))
        {
            $headers['Host'] = $hostName;
        }

        return $headers;
    }

    protected function buildIoTDBUrl(string $path): string
    {
        $config = $this->getConfig();

        return Uri::makeUri($config->getHost(), $path, '', $config->getPort(), $config->getSsl() ? 'https' : 'http', '', '')->__toString();
    }

    public function sql(string $sql): SqlResult
    {
        $config = $this->getConfig();
        if ($config->isIoTDB())
        {
            return $this->iotdbSql($sql);
        }
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

    private function iotdbSql(string $sql): SqlResult
    {
        $sql = trim($sql);
        if ('' === $sql)
        {
            throw new \InvalidArgumentException('IoTDB SQL must not be empty');
        }

        $config = $this->getConfig();
        $version = strtolower($config->getIoTDBApiVersion());
        if ('1' === $version)
        {
            $version = 'v1';
        }
        elseif ('2' === $version)
        {
            $version = 'v2';
        }
        if (!in_array($version, ['v1', 'v2'], true))
        {
            throw new \InvalidArgumentException(sprintf('Unsupported IoTDB REST API version %s', $config->getIoTDBApiVersion()));
        }

        $model = strtoupper($config->getIoTDBModel());
        if (!in_array($model, [ClientConfig::IOTDB_MODEL_TREE, ClientConfig::IOTDB_MODEL_TABLE], true))
        {
            throw new \InvalidArgumentException(sprintf('Unsupported IoTDB model %s', $config->getIoTDBModel()));
        }
        if (ClientConfig::IOTDB_MODEL_TABLE === $model && 'v1' !== $version)
        {
            throw new \InvalidArgumentException('IoTDB Table REST currently supports API v1 only');
        }

        $isQuery = $this->isIoTDBQuery($sql);
        if (ClientConfig::IOTDB_MODEL_TABLE === $model && $isQuery && '' === $config->getDb())
        {
            throw new \InvalidArgumentException('IoTDB Table REST queries require the db configuration option');
        }
        $operation = $isQuery ? 'query' : 'nonQuery';
        $path = ClientConfig::IOTDB_MODEL_TABLE === $model
            ? '/rest/table/v1/' . $operation
            : '/rest/' . $version . '/' . $operation;
        $payload = ['sql' => $sql];
        if (ClientConfig::IOTDB_MODEL_TABLE === $model && '' !== $config->getDb())
        {
            $payload['database'] = $config->getDb();
        }

        $result = $this->requestIoTDB($path, $payload);

        return new SqlResult(IoTDBResponse::normalize($result, ClientConfig::IOTDB_MODEL_TABLE === $model));
    }

    private function isIoTDBQuery(string $sql): bool
    {
        while ('' !== $sql)
        {
            $sql = ltrim($sql);
            if (0 === strpos($sql, '--'))
            {
                $newline = strpos($sql, "\n");
                $sql = false === $newline ? '' : substr($sql, $newline + 1);
            }
            elseif (0 === strpos($sql, '/*'))
            {
                $end = strpos($sql, '*/');
                $sql = false === $end ? '' : substr($sql, $end + 2);
            }
            else
            {
                break;
            }
        }

        return 1 === preg_match('/^(SELECT|SHOW|LIST|COUNT|DESCRIBE|DESC|EXPLAIN)\b/i', $sql);
    }
}
