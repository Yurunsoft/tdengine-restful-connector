<?php

declare(strict_types=1);

namespace Yurun\TDEngine\Struct;

/**
 * @method string getTypeName()
 * @method static string|null getTypeName(int $value)
 */
class ColumnMeta
{
    public const TYPE_BOOL = 1;
    public const TYPE_TINYINT = 2;
    public const TYPE_SMALLINT = 3;
    public const TYPE_INT = 4;
    public const TYPE_BIGINT = 5;
    public const TYPE_FLOAT = 6;
    public const TYPE_DOUBLE = 7;
    public const TYPE_BINARY = 8;
    public const TYPE_TIMESTAMP = 9;
    public const TYPE_NCHAR = 10;

    public const TYPE_MAP = [
        self::TYPE_BOOL      => 'BOOL',
        self::TYPE_TINYINT   => 'TINYINT',
        self::TYPE_SMALLINT  => 'SMALLINT',
        self::TYPE_INT       => 'INT',
        self::TYPE_BIGINT    => 'BIGINT',
        self::TYPE_FLOAT     => 'FLOAT',
        self::TYPE_DOUBLE    => 'DOUBLE',
        self::TYPE_BINARY    => 'BINARY',
        self::TYPE_TIMESTAMP => 'TIMESTAMP',
        self::TYPE_NCHAR     => 'NCHAR',
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
     * @var string
     */
    protected $typeName;

    /**
     * 类型长度.
     *
     * @var int
     */
    protected $length;

    public function __construct(string $name, int $type, int $length)
    {
        $this->setName($name);
        $this->setType($type);
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
