<?php

declare(strict_types=1);

date_default_timezone_set('Asia/Shanghai');

spl_autoload_register(function (string $class): void {
    $mappings = [
        'Yurun\\TDEngine\\Test\\' => __DIR__ . '/unit/',
        'Yurun\\TDEngine\\'      => __DIR__ . '/../src/',
    ];
    foreach ($mappings as $prefix => $directory)
    {
        if (0 === strpos($class, $prefix))
        {
            $file = $directory . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (is_file($file))
            {
                require $file;
            }
            return;
        }
    }
});
