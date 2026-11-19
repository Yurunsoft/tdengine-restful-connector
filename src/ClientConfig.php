<?php

declare(strict_types=1);

namespace Yurun\TDEngine;

use Yurun\TDEngine\Constants\TimeStampFormat;

class ClientConfig
{
    public const TYPE_TDENGINE = 'TDENGINE';
    public const TYPE_IOTDB = 'IOTDB';
    public const IOTDB_MODEL_TREE = 'TREE';
    public const IOTDB_MODEL_TABLE = 'TABLE';

    /**
     * @var string
     */
    protected $host = '127.0.0.1';

    /**
     * @var string
     */
    protected $hostName = '';

    /**
     * @var int
     */
    protected $port = 6041;

    /**
     * @var bool
     */
    private $portConfigured = false;

    /**
     * @var string
     */
    protected $dbType = self::TYPE_TDENGINE;

    /**
     * IoTDB REST API version.
     *
     * @var string
     */
    protected $iotdbApiVersion = 'v1';

    /**
     * IoTDB data model.
     *
     * @var string
     */
    protected $iotdbModel = self::IOTDB_MODEL_TREE;

    /**
     * @var string
     */
    protected $user = 'root';

    /**
     * @var string
     */
    protected $password = 'taosdata';

    /**
     * @var bool
     */
    private $passwordConfigured = false;

    /**
     * @var string
     */
    protected $db = '';

    /**
     * @var bool
     */
    protected $ssl = false;

    /**
     * @var int
     */
    protected $timestampFormat = TimeStampFormat::LOCAL_STRING;

    /**
     * @var bool
     */
    protected $keepAlive = true;

    /**
     * TDengine 版本.
     *
     * @var string
     */
    protected $version = '3';

    /**
     * @var string
     */
    protected $timezone = 'UTC';

    public function __construct(array $config = [])
    {
        if ($config)
        {
            if (isset($config['dbType']))
            {
                $this->setDbType($config['dbType']);
            }
            foreach ($config as $key => $value)
            {
                $this->$key = $value;
            }
            $this->portConfigured = array_key_exists('port', $config);
            $this->passwordConfigured = array_key_exists('password', $config);
        }
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function setHost(string $host): self
    {
        $this->host = $host;

        return $this;
    }

    public function getHostName(): string
    {
        return $this->hostName;
    }

    public function setHostName(string $hostName): self
    {
        $this->hostName = $hostName;

        return $this;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function setPort(int $port): self
    {
        $this->port = $port;
        $this->portConfigured = true;

        return $this;
    }

    public function getDbType(): string
    {
        return $this->dbType;
    }

    public function setDbType(string $dbType): self
    {
        $this->dbType = strtoupper($dbType);
        if (!$this->portConfigured)
        {
            $this->port = $this->isIoTDB() ? 18080 : 6041;
        }
        if (!$this->passwordConfigured)
        {
            $this->password = $this->isIoTDB() ? 'root' : 'taosdata';
        }

        return $this;
    }

    public function isIoTDB(): bool
    {
        return self::TYPE_IOTDB === strtoupper($this->dbType);
    }

    public function getIoTDBApiVersion(): string
    {
        return $this->iotdbApiVersion;
    }

    public function setIoTDBApiVersion(string $iotdbApiVersion): self
    {
        $this->iotdbApiVersion = strtolower($iotdbApiVersion);

        return $this;
    }

    public function getIoTDBModel(): string
    {
        return $this->iotdbModel;
    }

    public function setIoTDBModel(string $iotdbModel): self
    {
        $this->iotdbModel = strtoupper($iotdbModel);

        return $this;
    }

    public function getUser(): string
    {
        return $this->user;
    }

    public function setUser(string $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        $this->passwordConfigured = true;

        return $this;
    }

    public function getDb(): string
    {
        return $this->db;
    }

    public function setDb(string $db): self
    {
        $this->db = $db;

        return $this;
    }

    public function getSsl(): bool
    {
        return $this->ssl;
    }

    public function setSsl(bool $ssl): self
    {
        $this->ssl = $ssl;

        return $this;
    }

    public function getTimestampFormat(): int
    {
        return $this->timestampFormat;
    }

    public function setTimestampFormat(int $timestampFormat): self
    {
        $this->timestampFormat = $timestampFormat;

        return $this;
    }

    public function getKeepAlive(): bool
    {
        return $this->keepAlive;
    }

    public function setKeepAlive(bool $keepAlive): self
    {
        $this->keepAlive = $keepAlive;

        return $this;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): self
    {
        $this->timezone = $timezone;

        return $this;
    }
}
