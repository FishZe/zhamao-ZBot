FROM ubuntu

ENV DEBIAN_FRONTEND=noninteractive

ENV TZ=Asia/Shanghai

USER root

RUN sed -E -i -e 's/(archive|ports).ubuntu.com/mirrors.tencentyun.com/g' -e '/security.ubuntu.com/d' /etc/apt/sources.list && \
    sed -E -i -e 's/deb.debian.org/mirrors.tencentyun.com/g' -e '/security.debian.org/d' /etc/apt/sources.list

RUN apt update -y && apt upgrade -y && \
    apt install -y curl tar wget zip unzip git libssl-dev openssl

RUN cd / && mkdir data && cd /data && mkdir zhamao-app/ && cd zhamao-app/ && bash -c "$(curl -fsSL https://api.zhamao.xin/go.sh)"

RUN cd /data && wget https://ghproxy.com/https://github.com/FishZe/zhamao-ZBot/releases/download/0.0.2/ZBot-BiliPush_0.0.2.phar --no-check-certificate && \
    cd /data/zhamao-app && mkdir zm_data && cd zm_data && mkdir modules && \
    mv /data/ZBot-BiliPush_0.0.2.phar /data/zhamao-app/zm_data/modules/ && \
    cd /data/zhamao-app && yes n | ./zhamao module:unpack ZBot-BiliPush

COPY ./global.php /data/zhamao-app/config/global.php

COPY ./Config.php /data/zhamao-app/src/Module/Bili/Config.php

CMD cd /data/zhamao-app/ && sleep 20 && vendor/bin/start server

