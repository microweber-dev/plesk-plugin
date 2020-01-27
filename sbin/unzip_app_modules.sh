#!/bin/bash

downloadUrl=$(echo "$1" | base64 -d)

latestFolder=$2
if [ ! -d "$latestFolder" ]; then
	mkdir -p "$latestFolder"
fi

downloadCacheFolder=$(mktemp -d)

cd "$downloadCacheFolder"

zipDownloadedFile='microweber-module.zip';

echo 'Download modules from url...'
wget "$downloadUrl" -O "$zipDownloadedFile"

# Unzip selected version
echo 'Unzip file...'
unzip -o "$zipDownloadedFile" > microweber-module-unzip.log
rm -rf "$zipDownloadedFile"
rm -rf "microweber-module-unzip.log"
getFirstDirectory=$(ls)

find $latestFolder -type d -exec chmod 0755 {} \;
find $latestFolder -type f -exec chmod 0644 {} \;

chcon --user system_u --type httpd_sys_content_t -R $latestFolder

rm -rf "$downloadCacheFolder"

cd *

if [ ! -d "$latestFolder" ]; then
	mkdir -p "$latestFolder"
fi

echo 'Rsync files with' "$latestFolder"
rsync -a "$getFirstDirectory/userfiles" "$latestFolder"

echo "Done!"