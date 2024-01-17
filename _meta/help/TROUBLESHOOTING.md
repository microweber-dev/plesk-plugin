

# Enable Plesk Power user view

`plesk bin poweruser --off -simple false`



# Sites not working on HTTPS

```
plesk repair web examplesite.microweber.net -y
```

# "New configuration files for the Apache web server were not created"
https://cloudblue.freshdesk.com/support/solutions/articles/44001881502--new-configuration-files-for-the-apache-web-server-were-not-created-
 ```
/usr/local/psa/bin/repair --update-vhosts-structure
mysql -uadmin -p`cat /etc/psa/.psa.shadow` psa -e" select * from Configurations where status='error' \G"
```

# Disabling proxy mode on a subdomain over shell
https://talk.plesk.com/threads/turn-off-nginx-proxy-mode.357525/

```
plesk bin subscription --update-web-server-settings SUB.DOMAIN.TDL -nginx-proxy-mode false
```
# Disabling proxy mode on all subdomains over shell

```
for i in `mysql -uadmin -p\`cat /etc/psa/.psa.shadow\` psa -Ns -e "select name from domains"`; do /usr/local/psa/bin/subscription --update-web-server-settings $i -nginx-proxy-mode false; done
```

# Repairing web server configuration

```
If you have the same issue on other sites, please, just run the same command by changing [examplesite.microweber.net](http://examplesite.microweber.net/) for the desired site, or to run this at server-wide just execute Without specify any particular domain:

```
plesk repair web -y
```

# Nginx does not start after IP change


```
/usr/local/psa/admin/sbin/httpdmng --reconfigure-all
/etc/init.d/nginx restart
```

# Another command to reconfigure all domains


```
/usr/local/psa/admin/sbin/httpdmng --reconfigure-all
```
# Nginx fails to start/reload on a Plesk server: Too many open files

https://support.plesk.com/hc/en-us/articles/213938485-nginx-fails-to-start-reload-on-a-Plesk-server-Too-many-open-files

See the following error at Nginx log:

Nginx's virtual host opens 4 log files for each virtual host with physical hosting:

- proxy_access_log
- proxy_access_ssl_log
- webmail_access_log
- webmail_access_ssl_log



```
2022/11/02 15:43:47 [emerg] 1483091#0: open() "/var/www/vhosts/system/examplesite.microweber.net/logs/proxy_error_log" failed (24: Too many open files)
2022/11/02 15:44:33 [emerg] 1483091#0: open() "/var/www/vhosts/system/examplesite.microweber.net/logs/proxy_access_log" failed (24: Too many open files)
2022/11/02 15:46:02 [emerg] 1483091#0: open() "/var/www/vhosts/system/examplesite.microweber.net/logs/proxy_access_log" failed (24: Too many open files)
2022/11/02 15:46:03 [emerg] 1483091#0: open() "/var/www/vhosts/system/examplesite.microweber.net/logs/proxy_access_log" failed (24: Too many open files)
2022/11/02 15:47:51 [emerg] 1483091#0: open() "/var/www/vhosts/system/examplesite.microweber.net/logs/proxy_access_log" failed (24: Too many open files)
2022/11/02 15:51:24 [emerg] 1483091#0: open() "/var/www/vhosts/system/examplesite.microweber.net/logs/proxy_access_log" failed (24: Too many open files)
```



# 	apache stuck process



- Process status:

> ```
> # root @ plesk in ~: ps faxuw | grep httpd
> root     3934959  0.0  0.0   9208  1160 pts/1    S+   16:00   0:00                      \_ grep --color=auto httpd
> root      630265  0.0  0.6 1604188 458628 ?      Ss   Sep07   2:37 /usr/sbin/httpd -DFOREGROUND
> apache   3932748  0.0  0.6 1619360 448580 ?      S    15:51   0:00  \_ /usr/sbin/httpd -DFOREGROUND
> apache   3932749  0.0  0.6 1620536 447956 ?      S    15:51   0:00  \_ /usr/sbin/httpd -DFOREGROUND
> apache   3932750  0.0  0.7 4119564 462952 ?      Sl   15:51   0:00  \_ /usr/sbin/httpd -DFOREGROUND
> apache   3932832  0.0  0.7 3922892 460920 ?      Sl   15:51   0:00  \_ /usr/sbin/httpd -DFOREGROUND
> apache   3932898  0.0  0.7 3922892 460944 ?      Sl   15:51   0:00  \_ /usr/sbin/httpd -DFOREGROUND
> apache   3933040  0.0  0.7 3922892 460232 ?      Sl   15:55   0:00  \_ /usr/sbin/httpd -DFOREGROUND
> ```

To solve this error, please restart Apache process. as is explained on the following article:

https://support.plesk.com/hc/en-us/articles/360015533959-A-website-or-webmail-hosted-in-Plesk-periodically-shows-the-Plesk-web-server-default-page-or-old-website-content-?source=search


# ModSecurity: Output filter: Response body too large

Navigate to Tools & settings > Web Application Firewall (ModSecurity) > Settings > Custom directives section:

Add the following line:

`SecResponseBodyLimit 536870912`

https://www.plesk.com/kb/support/%E2%80%89site-on-plesk-is-not-available-modsecurity-response-body-too-large/



# PHP Memory limit problem

https://docs.plesk.com/en-US/obsidian/administrator-guide/web-hosting/php-management/customizing-php-parameters.79190/



# Too many files open problem
```
nano /etc/sysctl.conf

fs.file-max = 564000
net.ipv4.tcp_tw_reuse=1
net.ipv4.tcp_tw_recycle=1
net.ipv4.tcp_fin_timeout=10


Edit /etc/security/limits.conf and add:
nginx soft nofile 64000
nginx hard nofile 64000

echo 'NGINX_ULIMIT="-n 64000"' >> /etc/sysconfig/nginx

Edit /usr/lib/systemd/system/nginx.service and add a line in the [Service] section:
LimitNOFILE=64000

In /etc/sysconfig/nginx.systemd add:
LimitNOFILE=64000

Add the line ulimit -n 64000 at the beginning of the /usr/local/psa/admin/sbin/nginx-config script:
#!/usr/bin/env bash
ulimit -n 3164000

Reload system daemon:
# systemctl --system daemon-reload
# sysctl -p

Restart sw-cp-server and nginx:
# /etc/init.d/sw-cp-server restart
# /etc/init.d/nginx restart
```



# 503 Error

https://www.plesk.com/kb/support/increased-memory-usage-by-php-cgi-processes-on-server-after-plesk-update/

Connect to the server via SSH
Open /etc/httpd/conf.d/fcgid.conf in any text editor
Decrease timeouts to lower values to finish
php-cgi
processes earlier, and execute
service httpd reload
command to reload Apache configuration. 

```
FcgidIdleTimeout 40
FcgidProcessLifeTime 30
FcgidMaxProcesses 20
FcgidConnectTimeout 30
FcgidIOTimeout 45
FcgidIdleScanInterval 10
```


# Migration assistance

 - https://docs.plesk.com/en-US/obsidian/migration-guide/migrating-from-supported-hosting-platfoms/migrating-via-the-command-line.75722/
 - https://talk.plesk.com/threads/migration-transfer-manager-missing.335372/
```
/usr/local/psa/admin/sbin/modules/panel-migrator/plesk-migrator transfer-accounts --skip-services-checks --skip-services-checks --skip-infrastructure-checks
```


# High CPU usage by Nginx is shown in Health Monitor
https://support.plesk.com/hc/en-us/articles/12377240830999-High-CPU-usage-by-Nginx-is-shown-in-Health-Monitor

```
/etc/nginx/nginx.conf
worker_connections 4096;

service nginx restart


```


# Nginx upstream sent too big header while reading response header from upstream

https://www.cyberciti.biz/faq/nginx-upstream-sent-too-big-header-while-reading-response-header-from-upstream/



# Nginx too many open file
https://talk.plesk.com/threads/nginx-24-too-many-open-files.347297/
```
nginx -t 

nginx: the configuration file /etc/nginx/nginx.conf syntax is ok
nginx: [emerg] open() "/var/www/vhosts/system/example.com/logs/proxy_access_ssl_log" failed (24: Too many open files)
nginx: configuration file /etc/nginx/nginx.conf test failed
```

Run
```
ulimit -Sn 200000
```

Also 
```
/usr/local/psa/admin/sbin/websrv_ulimits --set 5242881 --no-restart
```


# Disable Selinux 

https://support.plesk.com/hc/en-us/articles/12377675193879

# Apache Too many open files

https://support.plesk.com/hc/en-us/articles/12377715513111-Apache-failed-to-start-24-Too-many-open-files-Init-Can-t-open-server-certificate-file

# Cannot send email from plesk server

https://support.plesk.com/hc/en-us/articles/12377115352599-Emails-sent-from-Plesk-server-using-PHP-mail-function-are-rejected-on-some-recipient-mail-servers

# Cannot send email to gmail

https://www.plesk.com/kb/support/unable-to-send-an-email-to-gmail-from-a-plesk-server-our-system-has-detected-that-this-message-does-not-meet-ipv6-sending-guidelines-regarding-ptr-records/