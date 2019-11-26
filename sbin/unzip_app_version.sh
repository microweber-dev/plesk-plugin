#!/bin/bash

downloadUrl=$(echo "$1" | base64 -d)

latestFolder=$2
if [ ! -d "$latestFolder" ]; then
	mkdir -p "$latestFolder"
fi

cd "$latestFolder"

zipDownloadedFile="microweber-app.zip";

echo 'Download from url...'
wget "$downloadUrl" -O "$zipDownloadedFile"

# Unzip selected version
echo 'Unzip file...'
unzip $zipDownloadedFile -d $latestFolder > unziping-microweber-app.log

find $latestFolder -type d -exec chmod 0755 {} \;
find $latestFolder -type f -exec chmod 0644 {} \;

rm -f $zipDownloadedFile
rm -f "unziping-microweber-app.log"

echo "Done!"