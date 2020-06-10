1.Open Plesk Panel
2.Go to Extensions Catalog and install "Panel.ini Editor"
3.Open the Panel.ini Editor
4.Add these lines & save

[license]
fileUpload=true

[ext-catalog] 
extensionUpload = true

[php] 
settings.general.open_basedir.default="none"
