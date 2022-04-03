FROM python:3.9

MAINTAINER fishze zbot@fishze.com

ENV DEBIAN_FRONTEND=noninteractive

ENV TZ=Asia/Shanghai

USER root

RUN sed -E -i -e 's/(archive|ports).ubuntu.com/mirrors.tencentyun.com/g' -e '/security.ubuntu.com/d' /etc/apt/sources.list && \
    sed -E -i -e 's/deb.debian.org/mirrors.tencentyun.com/g' -e '/security.debian.org/d' /etc/apt/sources.list

RUN apt upgrade && apt update && \
    apt-get install -y sudo && \
    sudo apt-get install -y wget unzip libgl1-mesa-glx ttf-wqy-microhei

RUN cd / && mkdir data && cd /data && mkdir toolkit && cd toolkit && \ 
    sudo wget https://ghproxy.com/https://github.com/FishZe/zhamao-ZBot/releases/download/0.0.2/toolkit.zip --no-check-certificate && \
    unzip toolkit.zip

RUN sudo pip config set global.index-url http://mirrors.tencentyun.com/pypi/simple && \ 
    sudo python -m pip install --upgrade pip && \
    cd /data/toolkit && sudo pip3 install -r requirements.txt --trusted-host mirrors.tencentyun.com

CMD cd /data/toolkit/danmu &&  uvicorn main:app --host 0.0.0.0 --port 20002 --reload 