# gen-db-dict

#### 介绍
数据库字典生成工具，目前支持生成markdown文档，支持依表名前缀归类。

#### 要求
php >= 7.3
enable pdo extension

### 生成预览
> 使用MarkerText工具打开后的效果
![](https://s3.bmp.ovh/imgs/2022/06/10/2215302f1812c2d2.png)

### 使用
1. `git clone https://github.com/dxmq/gen-db-dict.git`
2. 修改index.php中的数据库配置
3. `cd gen-db-dict`
4. `php index.php`

> 就会在当前目录生成数据字典markdown文档
