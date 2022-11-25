#!/bin/bash

downloadUrl=$(echo "$1" | base64 -d)

latestFolderPlugin=$2
if [ ! -d "$latestFolderPlugin" ]; then
	mkdir -p "$latestFolderPlugin"
fi

cd "$latestFolderPlugin"

zipDownloadedFile="plugin-master.zip";

echo 'Download from url...'
wget "$downloadUrl" -O "$zipDownloadedFile"

# Unzip selected version
echo 'Unzip file...'
unzip -o $zipDownloadedFile -d $latestFolderPlugin > unzipping-plugin-master.log

find $latestFolderPlugin -type d -exec chmod 0755 {} \;
find $latestFolderPlugin -type f -exec chmod 0644 {} \;

chcon --user system_u --type httpd_sys_content_t -R $latestFolderPlugin

rm -f $zipDownloadedFile
rm -f "unzipping-plugin-master.log"

mv $latestFolderPlugin/plesk-plugin-master/* $latestFolderPlugin

echo "Done!"