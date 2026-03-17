#!/bin/sh
set -e
mkdir -p /tmp /var/log/lighttpd
exec lighttpd -D -f /etc/lighttpd/lighttpd.conf
