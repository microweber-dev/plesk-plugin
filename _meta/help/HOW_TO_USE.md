
# How to use the Microweber Plesk Plugin?


**For automatic website creation**: In order to make automatic install when the user creates new domain, you must go to *Home->Service Plans->Hosting Plans* and then click on *Additional Services* and select *Install Microweber* from the dropdown box.

![plan.png](https://microweber.com/cdn/partners/plesk/plan.png "")



**For manual website creation**: Click the Microweber icon in the sidebar under *Server management->Microweber* and then click on *Install* and select *Domain* from the dropdown box.

![plan.png](https://microweber.com/cdn/partners/plesk/install.png "")


## Settings

**For plugin setup**: Go to *Server management->Microweber->Settings* and you will be able to set various options of the plugin and also connect it to WHMCS.

![plan.png](https://microweber.com/cdn/partners/plesk/settings.png "")


## Templates download and Updates


**For templates setup**: Go to *Server management->Microweber->Versions* and you will be able to update the plugin and download templates

![plan.png](https://microweber.com/cdn/partners/plesk/versions.png "")


#  Web server setting


If your server is slow you can improve the speed by editing some server setting

### Nginx setting
-  open the created /etc/nginx/conf.d/directives.conf file in a text editor


```
nano /etc/nginx/conf.d/directives.conf
```

-  Add required directives. For example:
```
proxy_buffer_size          128k;
proxy_buffers              4 256k;
proxy_busy_buffers_size    256k;
```

