<?php

namespace App;

use JsonException;
use PDO;

class GenDict
{
    private array $connect_config;

    private string $db_name;

    private PDO $pdo;

    private array $column_info;

    private array $table_info;

    private string $table_header_md;

    private string $column_header_md;

    private int $total_table_count;

    public function __construct($connect_config)
    {
        $this->connect_config = $connect_config;
        $this->db_name = $this->connect_config['DB_NAME'];
        $this->pdo = $this->build_pdo();
        $this->column_info = $this->column_info();
        $this->table_info = $this->table_info();
        $this->table_header_md = $this->generate_table_info_table_header_md();
        $this->column_header_md = $this->generate_column_info_table_header_md();
    }

    /**
     * 生成表信息markdown表头
     * @return string
     */
    private function generate_column_info_table_header_md(): string
    {
        $column_info = $this->column_info();
        $comment = array_values($column_info);
        $md = PHP_EOL . '| ' . implode(' | ', $comment) . ' |';
        $md .= PHP_EOL;
        foreach ($column_info as $key => $value) {
            if ($key !== 'is_nullable') {
                $md .= '| --- ';
            } else {
                // 居中
                $md .= '|:---:';
            }
        }
        $md .= '|';
        return $md;
    }

    /**
     * 生成表字段markdown表头
     * @return string
     */
    private function generate_table_info_table_header_md(): string
    {
        $table_info = $this->table_info();
        $comment = array_values($table_info);
        $md = PHP_EOL . '| ' . implode(' | ', $comment) . ' |';
        $md .= PHP_EOL . str_repeat('| --- ', count($table_info)) . '|';
        return $md;
    }

    private function table_info(): array
    {
        return [
            'table_name' => '表名',
            'table_comment' => '注释',
            'table_collation' => '编码',
            'engine' => '数据库引擎',
            'create_time' => '创建时间',
        ];
    }

    private function column_info(): array
    {
        return [
            'column_name' => '字段名',
            'column_type' => '字段类型',
            'column_comment' => '注释',
            'column_default' => '默认值',
            'extra' => '附加值',
            'is_nullable' => '是否允许空值',
        ];
    }

    private function build_pdo(): PDO
    {
        $config = $this->connect_config;
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
    private function column_info_column(): string
    {
        $columns = array_keys($this->column_info);
        return implode(',', $columns);
    }

    /**
     * 表信息field
     * @return string
     */
    private function table_info_column(): string
    {
        $table_columns = array_keys($this->table_info);
        return implode(',', $table_columns);
    }

    /**
     * 查询表字段信息
     * @param string $table_name
     * @return array
     */
    private function fetch_column_info(string $table_name): array
    {
        $statement = 'select ' . $this->column_info_column();
        $statement .= ' from INFORMATION_SCHEMA.COLUMNS';
        $statement .= ' where table_schema = ? and table_name = ?';
        $stmt = $this->pdo->prepare($statement);
        $param = [
            $this->db_name,
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
    private function fetch_table_info(string $table_name): array
    {
        $statement = 'select ' . $this->table_info_column();
        $statement .= ' from INFORMATION_SCHEMA.TABLES';
        $statement .= ' where table_schema = ? and table_name = ?';
        $stmt = $this->pdo->prepare($statement);
        $param = [
            $this->db_name,
            $table_name
        ];
        $stmt->execute($param);
        return $stmt->fetchAll();
    }

    /**
     * 查询数据所有的表
     * @return array
     */
    private function fetch_tables(): array
    {
        return $this->pdo->query('show tables')->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * 合并表信息与表字段信息
     * @param $table_name
     * @return array
     */
    private function merge_info($table_name): array
    {
        $table_info = $this->fetch_table_info($table_name);
        $column_info = $this->fetch_column_info($table_name);
        return array_merge($table_info[0], ['column_infos' => $column_info]);
    }

    private function table_infos(): array
    {
        $tables = $this->fetch_tables();
        $this->total_table_count = count($tables);
        $table_infos = [];
        foreach ($tables as $table_name) {
            $table_infos[] = $this->merge_info($table_name);
        }

        $res = [];
        foreach ($table_infos as $table_info) {
            if (strpos($table_info['table_name'], '_') !== false) {
                $explode = explode("_", $table_info['table_name']);
                $res[$explode[0]][] = $table_info;
            } else {
                $res['no_prefix'][] = $table_info;
            }
        }
        return $res;
    }

    /**
     * 将生成数据转为JSON写入文件
     * @throws JsonException
     */
    public function write_table_infos_to_file(): void
    {
        $filename = $this->db_name . '_schema.json';
        $table_infos = $this->table_infos();
        $json = json_encode($table_infos, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        file_put_contents($filename, $json);
    }

    /**
     * 生成数据源markdown文档
     * @return void
     * @throws JsonException
     */
    public function generate_md(): void
    {
        $filename = $this->db_name . '_schema.json';
        if (file_exists($filename)) {
            $json = file_get_contents($filename);
            $table_infos = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
        } else {
            $table_infos = $this->table_infos();
        }
        $md = '## ' . $this->db_name . '数据字典';
        $md .= PHP_EOL . '------';
        $md .= PHP_EOL . '生成时间：' . date('Y年m月d日 H:i:s');
        $md .= PHP_EOL . '总的表数量：' . $this->total_table_count;
        foreach ($table_infos as $group => $infos) {
            $md .= PHP_EOL . '### ' . $group . '_';

            foreach ($infos as $table_info) {
                $md .= PHP_EOL . '#### ' . $table_info['table_name'];
                $md .= PHP_EOL . $this->table_header_md;
                $table_row = sprintf('| %s | %s | %s | %s | %s |', $table_info['table_name'], $table_info['table_comment'], $table_info['table_collation'], $table_info['engine'], $table_info['create_time']);
                $md .= PHP_EOL . $table_row . PHP_EOL;
                $md .= $this->column_header_md;
                foreach ($table_info['column_infos'] as $column_info) {
                    $str = sprintf('| %s | %s | %s | %s | %s | %s |', $column_info['column_name'], $column_info['column_type'], $column_info['column_comment']
                        , $column_info['column_default'], $column_info['extra'], $column_info['is_nullable']);
                    $md .= PHP_EOL . $str;
                };
                $md .= PHP_EOL . '*****';
            }

        }
        $md_file_name = $this->db_name . '.md';
        file_put_contents($md_file_name, $md);
    }

}
