





# Sites not working on HTTPS

> ```
> plesk repair web examplesite.microweber.net -y
> ```

If you have the same issue on other sites, please, just run the same command by changing [examplesite.microweber.net](http://examplesite.microweber.net/) for the desired site, or to run this at server-wide just execute Without specify any particular domain:

> ```
> plesk repair web -y
> ```





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

