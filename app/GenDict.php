<?php

namespace App;

use PDO;

class GenDict
{
    public function connect_config(): array {
        return [
            'HOST_NAME' => '',
            'DB_NAME' => '',
            'USER' => 'root',
            'PASSWORD' => '',
            'CHARSET' => 'utf8mb4'
        ];
    }

    public function pdo(): PDO
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


    public function column_info_column(): string {
        $columns = [
            'COLUMN_NAME',
            'COLUMN_TYPE',
            'IS_NULLABLE',
            'EXTRA',
            'COLUMN_DEFAULT',
            'COLUMN_COMMENT'
        ];
        return implode(',', $columns);
    }

    public function table_info_column(): string {
        $columns = [
            'TABLE_NAME',
            'ENGINE',
            'TABLE_ROWS',
            'DATA_LENGTH',
            'AUTO_INCREMENT',
            'CREATE_TIME',
            'TABLE_COLLATION',
            'TABLE_COMMENT',
        ];
        return implode(',', $columns);
    }

    public function query_column_info(PDO $pdo, string $table_name): array {
        $statement = 'select ' . $this->column_info_column();
        $statement .= ' from INFORMATION_SCHEMA.COLUMNS';
        $statement .= ' where table_schema = ? and table_name = ?';
        $stmt = $pdo->prepare($statement);
        $param = [
            $this->connect_config()['DB_NAME'],
            $table_name
        ];
        $stmt->execute($param);
        return $stmt->fetchAll();
    }

    public function query_table_info(PDO $pdo, string $table_name) {
        $statement = 'select ' . $this->table_info_column();
        $statement .= ' from INFORMATION_SCHEMA.TABLES';
        $statement .= ' where table_schema = ? and table_name = ?';
        $stmt = $pdo->prepare($statement);
        $param = [
            $this->connect_config()['DB_NAME'],
            $table_name
        ];
        $stmt->execute($param);
        return $stmt->fetchAll();
    }


    public function generate() {
        $pdo = $this->pdo();
        $stmt = $pdo->query('show tables');
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {

            var_export($table);
        }
    }

}
