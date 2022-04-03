# zhamao-ZBot
炸毛框架的一个小模块，BILIBILI推送QQ机器人

推送动态(投稿等)和直播提醒
写了完善的api和sql封装，可以方便的二次开发

## 安装

### Linux 环境

#### 如果你已经安装好了docker, docker compose, 可以直接跳到第三步

1. 安装必要的依赖

    ```bash
    sudo apt-get update
    pip3 install --upgrade docker-compose
    ```

2. 安装 Docker 

    国内用户使用脚本一键安装: `sudo curl -sSL https://get.daocloud.io/docker | sh`  
    国外用户使用脚本一键安装: `sudo curl -sSL get.docker.com | sh`

3. 下载运行docker-compose

```
wget https://api.fishze.com/zbot_bilipush/docker-compose.yml && docker-compose up
```

### Windows 环境


Windows 下的安装仅供体验，勿在生产环境使用。如有必要，请使用虚拟机安装 Linux 并安装在其中。

以下教程仅适用于 Win10/11 x64 下的 `PowerShell`

1.docker/docker-compose 安装方法自行搜索。
2.下载`https://api.fishze.com/zbot_bilipush/docker-compose.yml`并部署

#### 源码部署: [安装文档](https://www.fishze.com/?p=237)

### 使用

以下以linux下运行为例

1. 当你输入`docker-compose up`命令后，各种程序会自动执行安装运行任务
2. 请看好控制台，当出现 二维码 时，请使用需要的QQ号扫描二维码并确认登录
3. 请使用另个一账号作为管理员账号(也可以有好几个管理员账号), 向机器人账号发送`getSuper`获取权限
4. 此时，控制台上出现该账号的验证码，复制并发送给机器人账号，获得授权
5. 订阅时，有多种格式的命令任你挑选
```
格式  $push  {who} {which}
1. $push 鱼小泽泽 转发;图片;文字;视频;小视频;戏剧;专栏;音频;番剧;其它;直播;直播弹幕
   各项以英文分号隔开，你可以自行修改订阅内容
2. $push 鱼小泽泽 -b 102311
    1023为二进制压缩方法 11 表示订阅直播和直播弹幕
    该命令含义为订阅所有
3. $push --uid 141543557 转发;图片;文字;视频;小视频;戏剧;专栏;音频;番剧;其它;直播;直播弹幕
    当名字难以输入或发送时， 可以输入`--uid ` + `用户id`来代替昵称
4. 关于二进制压缩的计算方法，TODO(待填坑)
```
6. 取消订阅时，有两种方式，含义同上
```
1. $cancelPush 鱼小泽泽
2. $cancelPush --uid 141543557
```

