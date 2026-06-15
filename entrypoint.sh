#!/bin/sh
set -e
mkdir -p /tmp /var/log/lighttpd
lighttpd -D -f /etc/lighttpd/lighttpd.conf &
exec haproxy -f /etc/haproxy/haproxy.cfg -db
