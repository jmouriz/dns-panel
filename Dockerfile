FROM alpine

RUN apk add --no-cache \
	lighttpd \
	php85 \
	php85-cgi \
	php85-curl \
	php85-session \
	php85-sqlite3 \
	php85-pdo_sqlite \
	fcgi \
	sqlite \
	bind-tools \
	bash

RUN ln -sf /usr/bin/php85 /usr/bin/php && \
    mkdir -p /var/www/localhost/htdocs /run/lighttpd /var/log/lighttpd

COPY lighttpd.conf /etc/lighttpd/lighttpd.conf
COPY entrypoint.sh /entrypoint.sh
COPY www/ /var/www/localhost/htdocs/

RUN chmod +x /entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]
