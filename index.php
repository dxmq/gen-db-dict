<?php

require_once __DIR__ . "/vendor/autoload.php";

use App\GenDict;


$db_config = [
    'HOST_NAME' => 'localhost', // 例127.0.0.1
    'DB_NAME' => 'code-generator', // 数据库名
    'USER' => 'root', // 账号
    'PASSWORD' => 'root', // 密码
    'CHARSET' => 'utf8mb4'
];

$dict = new GenDict($db_config);
try {
    // 生成数据源md文档
    $dict->generate_md();
} catch (JsonException $e) {
}