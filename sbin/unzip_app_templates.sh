#!/bin/bash

downloadUrl=$(echo "$1" | base64 -d)

latestFolder=$2
if [ ! -d "$latestFolder" ]; then
	mkdir -p "$latestFolder"
fi

cd "$latestFolder"

zipDownloadedFile="microweber-templates.zip";

echo 'Download from url...'
wget "$downloadUrl" -O "$zipDownloadedFile"

# Unzip selected version
echo 'Unzip file...'
unzip $zipDownloadedFile -d $2 > unziping-microweber-templates.log

find $latestFolder -type d -exec chmod 0755 {} \;
find $latestFolder -type f -exec chmod 0644 {} \;

chcon --user system_u --type httpd_sys_content_t -R $latestFolder

rm -f $zipDownloadedFile
rm -f "unziping-microweber-templates.log"

echo "Done!"