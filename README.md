# CMS支持扩展

wulacms的支持库，提供配置加载、命令行安装，命令行管理模板等功能.

## 命令行安装wulacms

### 1. 直接运行`php artisan wulacms:install`
根据提示输入完成安装即可.

### 2. 基于配置安装 `php artisan wulacms:install conf`

`conf`使用HTTP GET请求参数格式，参数解释如下:

1. env:  wulacms运行环境,可选[dev,pro,test,int]
2. dbhost: 数据库服务器地址
3. dbport: 数据库端口
4. dbname: 数据库名
5. dbuser: 数据库用户名
6. dbpwd: 数据库密码
7. charset: 编码  
8. username: 登录用户名

> 管理员密码将自动生成.

## 缓存支持
1. 在`bootstrap.php`文件中将`APP_MODE`设为`pro`。
2. 修改`conf/cache_config.php`配置缓存服务器。
3. 在输出页面内容前定义`EXPIRE`常量值为缓存时间即可(单位秒)。

### 防雪崩机制
在`bootstrap.php`文件中将`ANTI_AVALANCHE`设为`true`即可开启（需要redis支持）。

## 防CC支持

1. 在`bootstrap.php`文件中将`ANTI_CC`设为单位时间内同一IP可访问次数开启防CC机制。
   * `ANTI_CC` 格式有两种：
      1. 直接配置访问次数，格式为:`100`。表示60秒内最多访问100次。
      2. 同时配置访问次数与单位时间,格式为:`60/120`。表示120秒内最多访问100次。
   * 通过定义`ANTI_CC_WHITE`常量设置白名单，以逗号分隔.
      
2. 在`conf/ccredis_config.php`配置供防CC机制工作的redis。
   ```php
   return ['host'=>'localhost','port'=>6379,'db'=>0,'auth'=>'','timeout'=>5];
   ```