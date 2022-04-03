FROM ubuntu

MAINTAINER fishze zbot@fishze.com

ENV DEBIAN_FRONTEND=noninteractive

ENV TZ=Asia/Shanghai

RUN sed -E -i -e 's/(archive|ports).ubuntu.com/mirrors.tencentyun.com/g' -e '/security.ubuntu.com/d' /etc/apt/sources.list && \
    sed -E -i -e 's/deb.debian.org/mirrors.tencentyun.com/g' -e '/security.debian.org/d' /etc/apt/sources.list

RUN apt upgrade && apt update && \
    apt-get install -y wget sed
    
RUN cd / && mkdir data && cd data && \
    wget https://ghproxy.com/https://github.com/Mrs4s/go-cqhttp/releases/download/v1.0.0-rc1/go-cqhttp_linux_amd64.tar.gz
 
RUN cd /data && \
    tar -xzf go-cqhttp_linux_amd64.tar.gz && \
    echo "1234" > in && \
    ./go-cqhttp < in

COPY ./device.json /data/device.json

CMD cd /data && \
    sed -E -i -e 's/your_websocket_universal.server/zhamao:20001/g' /data/config.yml && \
    sed -E -i -e "s/uin: 1233456/uin: "$QQ_ID"/g" /data/config.yml && \
    sed -E -i -e "s/password: ''/password: '"$QQ_PASSWD"'/g" /data/config.yml && \
    sed -E -i -e "s/\"protocol\":5/\"protocol\":"$PROTOCOL"/g" /data/device.json && \
    cd /data && sleep 25 && ./go-cqhttp -faststart