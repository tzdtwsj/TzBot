# TzBot - 基于PHP的QQ机器人  
---
## 介绍  
TzBot是一个基于PHP的QQ机器人框架，对接go-cqhttp，支持插件加载  
## 运行时环境  
需要以下php扩展：parallel，Phar，json，mbstring，sockets  
可选扩展：readline  
---
## 安装TzBot
### 方式一
克隆本仓库，然后直接运行  
```shell
git clone https://github.com/tzdtwsj/TzBot
cd TzBot
php main.php
```
### 方式二
从releases下载最新的phar文件，下载到一个新建的目录，运行php phar文件即可运行  
---
## 配置go-cqhttp
请前往[go-cqhttp的Releases](https://github.com/Mrs4s/go-cqhttp/releases)进行下载go-cqhttp，然后启动go-cqhttp，选择正向WebSocket，生成config.yml后编辑此文件，填写登录信息，其他无需改动  
## 配置TzBot
第一次启动会生成config.json，一般不需要编辑   
配置文件说明：  
```json
{
    "server_host": "127.0.0.1",//连接地址
    "server_port": 8080,//连接端口
    "access-token": "",//访问密钥
    "superadmin_qq": [//超管QQ号，支持多个
        123456
    ],
    "enable-core" : true,//启用核心（非常重要，不建议关闭）
    "enable-cmd": true //启用控制台，在systemd监管进程可以关闭此选项
}
```
强烈建议启用访问密钥，可以保证安全  
启用方法：编辑go-cqhttp的配置文件config.yml，找到default-middlewares下的access-token，填写值，然后TzBot的配置文件config.json里的access-token也要填写相同值  
go-cqhttp配置文件里access-token的位置：
```yml
# 默认中间件锚点
default-middlewares: &default
  # 访问密钥, 强烈推荐在公网的服务器设置
  access-token: ''
  # 事件过滤器文件目录
  filter: ''
  # API限速设置
  # 该设置为全局生效
  # 原 cqhttp 虽然启用了 rate_limit 后缀, 但是基本没插件适配
  # 目前该限速设置为令牌桶算法, 请参考:
  # https://baike.baidu.com/item/%E4%BB%A4%E7%89%8C%E6%A1%B6%E7%AE%97%E6%B3%95/6597000?fr=aladdin
  rate-limit:
    enabled: false # 是否启用限速
    frequency: 1  # 令牌回复频率, 单位秒
    bucket: 1     # 令牌桶大小
```
