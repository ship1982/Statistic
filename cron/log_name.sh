#!/bin/bash

DATE=$(date +%Y-%m-%d-%H-%M)
mv /var/log/nginx/pixel_access.log /var/www/log/pixel_access_$DATE.log
mv /var/log/nginx/pixel_error.log /var/www/log/pixel_error_$DATE.log
kill -USR1 `cat /var/run/nginx.pid`
sleep 1