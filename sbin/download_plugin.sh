#!/bin/bash

downloadUrl=$(echo "$1" | base64 -d)

latestFolderPlugin=$2
if [ -d "$latestFolderPlugin" ]; then
  rm -rf $latestFolderPlugin
fi

mkdir -p "$latestFolderPlugin"
cd "$latestFolderPlugin"

zipDownloadedFile="plugin-master.zip";

echo 'Download from url...'
wget "$downloadUrl" -O "$zipDownloadedFile"

find $latestFolderPlugin -type d -exec chmod 0755 {} \;
find $latestFolderPlugin -type f -exec chmod 0644 {} \;

chcon --user system_u --type httpd_sys_content_t -R $latestFolderPlugin

plesk bin extension -i $zipDownloadedFile

echo "Done!"