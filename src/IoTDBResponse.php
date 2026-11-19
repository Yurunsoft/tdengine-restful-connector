<?php

declare(strict_types=1);

namespace Yurun\TDEngine;

class IoTDBResponse
{
    public static function normalize(array $response, bool $tableModel = false): array
    {
        if ($tableModel)
        {
            $columns = self::firstArray($response, ['column_names', 'columnNames']);
            $types = self::firstArray($response, ['data_types', 'dataTypes', 'column_types', 'columnTypes']);
            $rows = self::rows(self::firstArray($response, ['values', 'data', 'rows']));
        }
        else
        {
            $columns = self::firstArray($response, ['expressions', 'columnNames', 'column_names']);
            if (!$columns)
            {
                $columns = self::firstArray($response, ['columnNames', 'column_names']);
            }
            $types = self::firstArray($response, ['columnTypes', 'dataTypeList', 'data_types', 'dataTypes', 'column_types']);
            $timestamps = self::firstArray($response, ['timestamps']);
            $columnValues = self::rows(self::firstArray($response, ['values', 'data']));
            $rowCount = count($timestamps);
            foreach ($columnValues as $column)
            {
                $rowCount = max($rowCount, count($column));
            }
            $rows = self::transpose($columnValues, $rowCount);

            $hasTimeColumn = isset($columns[0]) && 'time' === strtolower((string) $columns[0]);
            if ($timestamps && !$hasTimeColumn)
            {
                array_unshift($columns, 'Time');
                array_unshift($types, 'TIMESTAMP');
                foreach ($rows as $index => $row)
                {
                    array_unshift($rows[$index], $timestamps[$index] ?? null);
                }
            }
            elseif ($timestamps && $hasTimeColumn)
            {
                foreach ($rows as $index => $row)
                {
                    if (count($row) < count($columns))
                    {
                        array_unshift($rows[$index], $timestamps[$index] ?? null);
                    }
                }
            }
        }

        $columnMeta = [];
        foreach ($columns as $index => $column)
        {
            $type = $types[$index] ?? '';
            $columnMeta[] = [(string) $column, (string) $type, 0];
        }

        foreach ($rows as $index => $row)
        {
            if (count($row) < count($columns))
            {
                $rows[$index] = array_pad($row, count($columns), null);
            }
            elseif (count($row) > count($columns))
            {
                $rows[$index] = array_slice($row, 0, count($columns));
            }
        }

        return array_merge($response, [
            'status'      => 'succ',
            'code'        => isset($response['code']) ? (int) $response['code'] : 0,
            'desc'        => $response['message'] ?? 'success',
            'head'        => $columns,
            'column_meta' => $columnMeta,
            'data'        => $rows,
            'rows'        => count($rows),
        ]);
    }

    private static function firstArray(array $response, array $keys): array
    {
        foreach ($keys as $key)
        {
            if (isset($response[$key]) && is_array($response[$key]))
            {
                return array_values($response[$key]);
            }
        }

        return [];
    }

    private static function rows(array $values): array
    {
        $rows = [];
        foreach ($values as $value)
        {
            $rows[] = is_array($value) ? array_values($value) : [$value];
        }

        return $rows;
    }

    private static function transpose(array $columns, int $rowCount): array
    {
        $rows = [];
        for ($rowIndex = 0; $rowIndex < $rowCount; ++$rowIndex)
        {
            $row = [];
            foreach ($columns as $column)
            {
                $row[] = $column[$rowIndex] ?? null;
            }
            $rows[] = $row;
        }

        return $rows;
    }
}
