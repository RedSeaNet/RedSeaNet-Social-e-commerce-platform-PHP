FROM nginx:latest
ARG timezone
ENV TIMEZONE=${timezone:-"Asia/Shanghai"}
RUN apt-get update -y && apt-get install -y git 
RUN apt-get update -y && apt-get install -y curl 
RUN apt-get update -y && apt-get install -y cron
RUN apt-get update -y && apt-get install -y socat