<?php

declare(strict_types=1);

namespace Yurun\TDEngine\Action\Sql;

use Yurun\TDEngine\Struct\ColumnMeta;

class SqlResult
{
    /**
     * 接口原始返回结果数组.
     *
     * @var array
     */
    protected $response;

    /**
     * @var ColumnMeta[]
     */
    protected $columns;

    /**
     * @var array
     */
    protected $data;

    public function __construct(array $response)
    {
        $this->response = $response;

        $columns = $columnNames = [];
        foreach ($response['column_meta'] as $row)
        {
            $columns[$row[0]] = new ColumnMeta(...$row);
            $columnNames[] = $row[0];
        }
        $this->columns = $columns;

        $data = [];
        foreach ($response['data'] as $row)
        {
            $data[] = array_combine($columnNames, $row);
        }
        $this->data = $data;
    }

    public function getResponse(): array
    {
        return $this->response;
    }

    /**
     * @return ColumnMeta[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getStatus(): string
    {
        return $this->response['status'];
    }

    public function getHead(): array
    {
        return $this->response['head'] ?? [];
    }

    public function getRows(): int
    {
        return $this->response['rows'];
    }
}
