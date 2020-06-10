
## Microweber Plesk Plugin Installation

1. Open Plesk Panel
1. Go to Extensions Catalog and install "Panel.ini Editor"
1. Open the Panel.ini Editor
1. Add these lines & save

```
[license]
fileUpload=true

[ext-catalog] 
extensionUpload = true

[php] 
settings.general.open_basedir.default="none"
```
