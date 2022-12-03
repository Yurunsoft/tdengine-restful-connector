<?php

declare(strict_types=1);

namespace Yurun\TDEngine\Struct;

/**
 * @method string|null getTypeName()
 * @method static      string|null getTypeName(int $value)
 * @method int         getTypeValue()
 * @method static      int|null getTypeValue(string $name)
 */
class ColumnMeta
{
    public const TYPE_UNKNOWN = 0;
    public const TYPE_BOOL = 1;
    public const TYPE_TINYINT = 2;
    public const TYPE_SMALLINT = 3;
    public const TYPE_INT = 4;
    public const TYPE_BIGINT = 5;
    public const TYPE_FLOAT = 6;
    public const TYPE_DOUBLE = 7;
    public const TYPE_BINARY = 8;
    public const TYPE_VARCHAR = 8;
    public const TYPE_TIMESTAMP = 9;
    public const TYPE_NCHAR = 10;
    public const TYPE_UTINYINT = 11;
    public const TYPE_USMALLINT = 12;
    public const TYPE_UINT = 13;
    public const TYPE_UBIGINT = 14;
    public const TYPE_JSON = 15;
    public const TYPE_VARBINARY = 16;
    public const TYPE_DECIMAL = 17;
    public const TYPE_BLOB = 18;
    public const TYPE_MEDIUMBLOB = 19;

    public const TYPE_MAP = [
        self::TYPE_BOOL       => 'BOOL',
        self::TYPE_TINYINT    => 'TINYINT',
        self::TYPE_SMALLINT   => 'SMALLINT',
        self::TYPE_INT        => 'INT',
        self::TYPE_BIGINT     => 'BIGINT',
        self::TYPE_FLOAT      => 'FLOAT',
        self::TYPE_DOUBLE     => 'DOUBLE',
        self::TYPE_BINARY     => 'BINARY',
        self::TYPE_TIMESTAMP  => 'TIMESTAMP',
        self::TYPE_NCHAR      => 'NCHAR',
        self::TYPE_UTINYINT   => 'UTINYINT',
        self::TYPE_USMALLINT  => 'USMALLINT',
        self::TYPE_UINT       => 'UINT',
        self::TYPE_UBIGINT    => 'UBIGINT',
        self::TYPE_JSON       => 'JSON',
        self::TYPE_VARBINARY  => 'VARBINARY',
        self::TYPE_DECIMAL    => 'DECIMAL',
        self::TYPE_BLOB       => 'BLOB',
        self::TYPE_MEDIUMBLOB => 'MEDIUMBLOB',
    ];

    /**
     * 列名.
     *
     * @var string
     */
    protected $name;

    /**
     * 列类型值
     *
     * @var int
     */
    protected $type;

    /**
     * 列类型名称.
     *
     * @var string|null
     */
    protected $typeName;

    /**
     * 类型长度.
     *
     * @var int
     */
    protected $length;

    /**
     * @param int|string $type
     */
    public function __construct(string $name, $type, int $length)
    {
        $this->setName($name);
        if (\is_int($type))
        {
            $this->setType($type);
        }
        else
        {
            $this->setTypeName($type);
        }
        $this->setLength($length);
    }

    /**
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        if ('getTypeName' === $name)
        {
            return $this->typeName;
        }
        elseif ('getTypeValue' === $name)
        {
            return $this->type;
        }
    }

    /**
     * @return mixed
     */
    public static function __callStatic(string $name, array $arguments)
    {
        if ('getTypeName' === $name)
        {
            return self::TYPE_MAP[$arguments[0] ?? null] ?? null;
        }
        elseif ('getTypeValue' === $name)
        {
            $value = array_search($arguments[0] ?? null, self::TYPE_MAP);
            if (false === $value)
            {
                return self::TYPE_UNKNOWN;
            }
            else
            {
                return $value;
            }
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;
        $this->typeName = static::getTypeName($type);

        return $this;
    }

    public function setTypeName(string $typeName): self
    {
        $this->typeName = $typeName;
        $this->type = static::getTypeValue($typeName);

        return $this;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function setLength(int $length): self
    {
        $this->length = $length;

        return $this;
    }
}
