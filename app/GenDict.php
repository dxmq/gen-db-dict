<?php

namespace App;

use PDO;

class GenDict
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = $this->build_pdo();
    }

    public function connect_config(): array
    {
        return [
            'HOST_NAME' => '',
            'DB_NAME' => '',
            'USER' => 'root',
            'PASSWORD' => '',
            'CHARSET' => 'utf8mb4'
        ];
    }

    public function build_pdo(): PDO
    {
        $config = $this->connect_config();
        $host = $config['HOST_NAME'];
        $db = $config['DB_NAME'];
        $user = $config['USER'];
        $pass = $config['PASSWORD'];
        $charset = $config['CHARSET'];
        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        return new PDO($dsn, $user, $pass, $options);
    }


    /**
     * 表字段信息field
     * @return string
     */
    public function column_info_column(): string
    {
        $columns = [
            'column_name',
            'column_type',
            'is_nullable',
            'extra',
            'column_default',
            'column_default'
        ];
        return implode(',', $columns);
    }

    /**
     * 表信息field
     * @return string
     */
    public function table_info_column(): string
    {
        $columns = [
            'table_name',
            'engine',
            'table_rows',
            'data_length',
            'auto_increment',
            'create_time',
            'table_collation',
            'table_comment',
        ];
        return implode(',', $columns);
    }

    /**
     * 查询表字段信息
     * @param string $table_name
     * @return array
     */
    public function fetch_column_info(string $table_name): array
    {
        $statement = 'select ' . $this->column_info_column();
        $statement .= ' from INFORMATION_SCHEMA.COLUMNS';
        $statement .= ' where table_schema = ? and table_name = ?';
        $stmt = $this->pdo->prepare($statement);
        $param = [
            $this->connect_config()['DB_NAME'],
            $table_name
        ];
        $stmt->execute($param);
        return $stmt->fetchAll();
    }

    /**
     * 查询表信息
     * @param string $table_name
     * @return array
     */
    public function fetch_table_info(string $table_name): array
    {
        $statement = 'select ' . $this->table_info_column();
        $statement .= ' from INFORMATION_SCHEMA.TABLES';
        $statement .= ' where table_schema = ? and table_name = ?';
        $stmt = $this->pdo->prepare($statement);
        $param = [
            $this->connect_config()['DB_NAME'],
            $table_name
        ];
        $stmt->execute($param);
        return $stmt->fetchAll();
    }

    /**
     * 查询数据所有的表
     * @return array
     */
    public function fetch_tables(): array
    {
        $stmt = $this->pdo->query('show tables');
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * 合并表信息与表字段信息
     * @param $table_name
     * @return array
     */
    public function merge_info($table_name): array
    {
        $table_info = $this->fetch_table_info($table_name);
        $column_info = $this->fetch_column_info($table_name);
        return array_merge($table_info[0], ['column_infos' => $column_info]);
    }


    public function generate()
    {
        $tables = $this->fetch_tables();
        $table_infos = [];
        foreach ($tables as $table_name) {
            $table_infos[] = $this->merge_info($table_name);
        }
        var_export($table_infos);
    }

}
