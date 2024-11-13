FROM mysql:8.0
ARG timezone
ENV TIMEZONE=${timezone:-"Asia/Shanghai"}
COPY ./mysql/config/my.cnf /etc/mysql/my.cnf