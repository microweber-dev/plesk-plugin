## How to install the plugin

### Automatic installation
1. Open Plesk Panel
2. Go to Extensions Catalog and install the "Microweber" extension


### Command line Install

plesk bin extension --install-url https://github.com/microweber-dev/plesk-plugin/archive/refs/heads/master.zip




### Manual installation
1. Open Plesk Panel
2. Go to Extensions Catalog and install "Panel.ini Editor"
3. Open the Panel.ini Editor
4. Add these lines & save



```
[license]
fileUpload=true

[ext-catalog] 
extensionUpload = true

[php] 
settings.general.open_basedir.default="none"

```


### Uploading

After activation of `extensionUpload` go to Server Management > Extensions

Download the latest plugin version from the [Microweber Plesk Plugin](https://github.com/microweber-dev/plesk-plugin) repository.

Then upload the zip file from the upload button

![upload_extension.png](https://microweber.com/cdn/partners/plesk/upload_extension.png "")



### Folders where the plugin are installed

Folders where the plugin will be installed:

```
/opt/psa/admin/sbin/modules/microweber
/usr/local/psa/admin/plib/modules/microweber
/usr/local/psa/admin/share/modules/microweber
/usr/local/psa/var/modules/microweber
```
