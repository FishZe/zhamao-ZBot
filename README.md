# zhamao-ZBot
炸毛框架的一个小模块，BILIBILI推送QQ机器人

推送动态(投稿等)和直播提醒
写了完善的api和sql封装，可以方便的二次开发

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

#### 源码部署: [安装文档](https://www.fishze.com/?p=237)
