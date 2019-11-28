#!/bin/bash

downloadUrl=$(echo "$1" | base64 -d)

latestFolder=$2
if [ ! -d "$latestFolder" ]; then
	mkdir -p "$latestFolder"
fi

downloadCacheFolder='/tmp/microweber-module-cache'

rm -rf "$downloadCacheFolder"
mkdir "$downloadCacheFolder"

cd "$downloadCacheFolder"

zipDownloadedFile='microweber-module.zip';

echo 'Download modules from url...'
wget "$downloadUrl" -O "$zipDownloadedFile"

# Unzip selected version
echo 'Unzip file...'
unzip "$zipDownloadedFile" > microweber-module-unzip.log

find $latestFolder -type d -exec chmod 0755 {} \;
find $latestFolder -type f -exec chmod 0644 {} \;

chcon --user system_u --type httpd_sys_content_t -R $latestFolder

rm -rf "$zipDownloadedFile"
rm -rf "microweber-module-unzip.log"

cd *

if [ ! -d "$latestFolder" ]; then
	mkdir -p "$latestFolder"
fi

echo 'Rsync files with' "$latestFolder"
rsync -a "userfiles" "$latestFolder"

echo "Done!"