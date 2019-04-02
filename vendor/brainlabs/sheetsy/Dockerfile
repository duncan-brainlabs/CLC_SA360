FROM ubuntu:16.04

LABEL maintainer ryutaro@brainlabsdigital.com

USER root

RUN ln -snf /bin/bash /bin/sh && \
	apt-get -y update && \
	apt-get -y install \
	php7.0 \
	php7.0-dom \
	php7.0-mbstring \
	php7.0-soap \
	php7.0-zip \
	php-ast \
	composer

# ENV SHEETSY_CREDS=/root/credentials/google.json
# ENV SHEETSY_SPREADSHEET_ID=1VjeRzvPNceyGQuomA8l1IADwafEZQrCbRH3a0ZPVHo8

# Work-around for
# https://bugs.launchpad.net/ubuntu/+source/composer/+bug/1595289
RUN ln -s /usr/share/php/data /usr/share/data

WORKDIR /root

COPY composer.json .

RUN composer install

COPY . .

CMD ["bash", "test.sh"]
