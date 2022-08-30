# tdengine-restful-connector

[![Latest Version](https://poser.pugx.org/yurunsoft/tdengine-restful-connector/v/stable)](https://packagist.org/packages/yurunsoft/tdengine-restful-connector)
![GitHub Workflow Status (branch)](https://img.shields.io/github/workflow/status/Yurunsoft/tdengine-restful-connector/ci/master)
[![Php Version](https://img.shields.io/badge/php-%3E=7.1-brightgreen.svg)](https://secure.php.net/)
[![License](https://img.shields.io/github/license/Yurunsoft/tdengine-restful-connector.svg)](https://github.com/Yurunsoft/tdengine-restful-connector/blob/master/LICENSE)

## 简介

封装了 [TDEngine](https://github.com/taosdata/TDengine) 的 RESTful 接口，可以使用 PHP 轻松地操作 TDEngine 的数据插入和查询了。

此项目支持在 PHP >= 7.1 的项目中使用。

支持在 ThinkPHP、Laravel、[Swoole](https://github.com/swoole/swoole-src)、[imi](https://github.com/imiphp/imi) 等项目中使用

在 Swoole 环境中支持协程化，不会阻塞！

技术支持 QQ 群: 17916227[![点击加群](https://pub.idqqimg.com/wpa/images/group.png "点击加群")](https://jq.qq.com/?_wv=1027&k=5wXf4Zq)，如有问题可以及时解答和修复。

## 安装

`composer require yurunsoft/tdengine-restful-connector`

## 使用

**使用连接管理器：**

```php
// 增加名称为 test 的连接配置
\Yurun\TDEngine\TDEngineManager::setClientConfig('test', new \Yurun\TDEngine\ClientConfig([
    // 'host'            => '127.0.0.1',
    // 'hostName'        => '',
    // 'port'            => 6041,
    // 'user'            => 'root',
    // 'password'        => 'taosdata',
    // 'db'              => 'database'
    // 'ssl'             => false,
    // 'timestampFormat' => \Yurun\TDEngine\Constants\TimeStampFormat::LOCAL_STRING,
    // 'keepAlive'       => true,
]));
// 设置默认数据库为test
\Yurun\TDEngine\TDEngineManager::setDefaultClientName('test');
// 获取客户端对象（\Yurun\TDEngine\Client）
$client = \Yurun\TDEngine\TDEngineManager::getClient();
```

**直接 new 客户端：**

```php
$client = new \Yurun\TDEngine\Client(new \Yurun\TDEngine\ClientConfig([
    // 'host'            => '127.0.0.1',
    // 'hostName'        => '',
    // 'port'            => 6041,
    // 'user'            => 'root',
    // 'password'        => 'taosdata',
    // 'db'              => 'database'
    // 'ssl'             => false,
    // 'timestampFormat' => \Yurun\TDEngine\Constants\TimeStampFormat::LOCAL_STRING,
    // 'keepAlive'       => true,
]));

// 通过 sql 方法执行 sql 语句
var_dump($client->sql('create database if not exists db_test'));
var_dump($client->sql('show databases'));
var_dump($client->sql('create table if not exists db_test.tb (ts timestamp, temperature int, humidity float)'));
var_dump($client->sql(sprintf('insert into db_test.tb values(%s,%s,%s)', time() * 1000, mt_rand(), mt_rand() / mt_rand())));

$result = $client->sql('select * from db_test.tb');

$result->getResponse(); // 获取接口原始返回数据

// 获取列数据
foreach ($result->getColumns() as $column)
{
    $column->getName(); // 列名
    $column->getType(); // 列类型值
    $column->getTypeName(); // 列类型名称
    $column->getLength(); // 类型长度
}

// 获取数据
foreach ($result->getData() as $row)
{
    echo $row['列名']; // 经过处理，可以直接使用列名获取指定列数据
}

$result->getStatus(); // 告知操作结果是成功还是失败；同接口返回格式

$result->getHead(); // 表的定义，如果不返回结果集，则仅有一列“affected_rows”。（从 2.0.17 版本开始，建议不要依赖 head 返回值来判断数据列类型，而推荐使用 column_meta。在未来版本中，有可能会从返回值中去掉 head 这一项。）；同接口返回格式

$result->getRow(); // 表明总共多少行数据；同接口返回格式
```
